<?php
declare(strict_types=1);

/**
 * VulnForge structured logging engine (Phase 1).
 *
 * Writes Elastic Common Schema (ECS) JSON Lines that any ECS-compatible SIEM
 * (Elastic, Wazuh, Splunk, ...) can ingest. It is intentionally decoupled from
 * any consumer: it only produces standard JSON, one object per line.
 *
 * Design rules:
 *   - Fail-safe: logging must never throw to the caller, change a response, or
 *     meaningfully slow a request. Every public path is wrapped in try/catch.
 *   - Feature-flagged via environment variables; enabled by default.
 *   - Hostile-payload-safe JSON: invalid UTF-8 is substituted, control bytes
 *     (newlines, NUL) are escaped, so log lines cannot be forged by injection.
 *
 * This class is the engine only. The access log (Phase 2) and the application
 * security events (Phase 3) are built on top of it.
 */
final class Logger
{
    public const ECS_VERSION = '8.11.0';

    /** @var array<string,mixed>|null resolved configuration, built once */
    private static ?array $cfg = null;

    /** @var string|null per-request correlation id (http.request.id) */
    private static ?string $requestId = null;

    /** @var array{name:?string,id:?string} identity set by auth labs at runtime */
    private static array $user = ['name' => null, 'id' => null];

    // ----- configuration -------------------------------------------------

    /**
     * Build configuration from the environment. Idempotent: the first call
     * resolves and caches config; later calls are no-ops unless $overrides is
     * passed (used by tests to point the engine at a temp directory).
     *
     * @param array<string,mixed> $overrides
     */
    public static function init(array $overrides = []): void
    {
        if (self::$cfg !== null && $overrides === []) {
            return;
        }
        $defaultDir = dirname(__DIR__) . '/logs'; // /var/www/app/logs in the container
        $cfg = [
            'enabled'         => self::envBool('VF_LOG_ENABLED', true),
            'dir'             => self::envStr('VF_LOG_DIR', $defaultDir),
            'max_body_bytes'  => self::envInt('VF_LOG_MAX_BODY_BYTES', 8192),
            'redact_fields'   => self::envList('VF_LOG_REDACT_FIELDS', []),
            'trusted_proxies' => self::envList('VF_TRUSTED_PROXIES', [
                '127.0.0.1', '::1', '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16',
            ]),
            'stdout'          => self::envBool('VF_LOG_STDOUT', false),
            'rotate_bytes'    => self::envInt('VF_LOG_ROTATE_BYTES', 20 * 1024 * 1024),
            'rotate_keep'     => self::envInt('VF_LOG_ROTATE_KEEP', 5),
            'service_version' => self::envStr('VF_SERVICE_VERSION', '1.0.0'),
        ];
        // Test/override keys mirror the config keys above.
        foreach ($overrides as $key => $value) {
            $cfg[$key] = $value;
        }
        self::$cfg = $cfg;
    }

    /** @return array<string,mixed> */
    private static function cfg(): array
    {
        if (self::$cfg === null) {
            self::init();
        }
        return self::$cfg;
    }

    public static function enabled(): bool
    {
        return (bool) self::cfg()['enabled'];
    }

    /** Reset cached state. Test-only. */
    public static function resetForTests(): void
    {
        self::$cfg = null;
        self::$requestId = null;
        self::$user = ['name' => null, 'id' => null];
    }

    // ----- identity & correlation ---------------------------------------

    /** Stable per-request UUIDv4, generated on first use. */
    public static function requestId(): string
    {
        if (self::$requestId === null) {
            // Honour an upstream id if the proxy supplied one, else mint a UUIDv4.
            $incoming = $_SERVER['HTTP_X_REQUEST_ID'] ?? '';
            self::$requestId = preg_match('/^[A-Za-z0-9._-]{8,128}$/', $incoming)
                ? $incoming
                : self::uuid4();
        }
        return self::$requestId;
    }

    /** Record an authenticated identity so the access log can attribute it. */
    public static function setUser(?string $name, ?string $id = null): void
    {
        if ($name !== null) {
            self::$user['name'] = $name;
        }
        if ($id !== null) {
            self::$user['id'] = $id;
        }
    }

    /** @return array{name:?string,id:?string} */
    public static function user(): array
    {
        return self::$user;
    }

    /** sha256 of the session cookie (never the raw token), or null if none. */
    public static function sessionHash(): ?string
    {
        $raw = $_COOKIE['forge_session'] ?? '';
        return $raw === '' ? null : hash('sha256', $raw);
    }

    // ----- ECS scaffolding ----------------------------------------------

    /**
     * The common envelope every ECS document shares. Callers merge their own
     * fields on top. Field names are dotted ECS keys; the writer nests them.
     *
     * @return array<string,mixed>
     */
    public static function ecsBase(string $dataset): array
    {
        return [
            '@timestamp'       => self::now(),
            'ecs.version'      => self::ECS_VERSION,
            'event.dataset'    => $dataset,
            'service.name'     => 'vulnforge',
            'service.version'  => (string) self::cfg()['service_version'],
            'host.name'        => gethostname() ?: 'vulnforge',
            'http.request.id'  => self::requestId(),
        ];
    }

    /** RFC 3339 timestamp in UTC with millisecond precision. */
    public static function now(): string
    {
        $t = microtime(true);
        $ms = (int) floor(($t - floor($t)) * 1000);
        return gmdate('Y-m-d\TH:i:s', (int) $t) . sprintf('.%03dZ', $ms);
    }

    // ----- writing -------------------------------------------------------

    /**
     * Write one ECS document as a JSON line to $filename inside the log dir.
     * Dotted keys (e.g. "http.request.method") are expanded into nested
     * objects so the output is valid ECS. Never throws.
     *
     * @param array<string,mixed> $doc
     */
    public static function write(string $filename, array $doc): void
    {
        try {
            if (!self::enabled()) {
                return;
            }
            $line = self::encode(self::expandDots($doc)) . "\n";
            self::emit($filename, $line);
        } catch (\Throwable $e) {
            // Logging must never affect the request. Swallow all errors.
        }
    }

    /**
     * Encode a document as a single safe JSON line:
     *   - invalid UTF-8 bytes are replaced (no encoder failure),
     *   - control characters incl. newline and NUL are escaped by json_encode,
     *   - slashes/unicode are kept readable.
     *
     * @param array<string,mixed> $doc
     */
    public static function encode(array $doc): string
    {
        $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR;
        $json = json_encode($doc, $flags);
        if ($json === false) {
            // Last-resort fallback so a line is still emitted.
            $json = json_encode([
                '@timestamp'   => self::now(),
                'ecs.version'  => self::ECS_VERSION,
                'error.message' => 'log encode failed',
            ], $flags);
        }
        // Defend against any stray newline leaking into the line.
        return str_replace(["\n", "\r"], ['\\n', '\\r'], (string) $json);
    }

    private static function emit(string $filename, string $line): void
    {
        $cfg = self::cfg();
        $dir = (string) $cfg['dir'];
        if (!is_dir($dir)) {
            // Group-readable so the host user can read bind-mounted logs.
            @mkdir($dir, 0775, true);
        }
        $path = $dir . '/' . basename($filename);

        // Serialize writers and rotation with a sidecar lock file.
        $lock = @fopen($path . '.lock', 'c');
        if ($lock !== false) {
            @flock($lock, LOCK_EX);
        }
        try {
            if (is_file($path)
                && (filesize($path) + strlen($line)) > (int) $cfg['rotate_bytes']) {
                self::rotate($path, (int) $cfg['rotate_keep']);
            }
            $new = !is_file($path);
            @file_put_contents($path, $line, FILE_APPEND);
            if ($new) {
                @chmod($path, 0664); // readable by the host user's group
            }
            if ($cfg['stdout']) {
                $out = @fopen('php://stdout', 'a');
                if ($out !== false) {
                    @fwrite($out, $line);
                    @fclose($out);
                }
            }
        } finally {
            if ($lock !== false) {
                @flock($lock, LOCK_UN);
                @fclose($lock);
            }
        }
    }

    /**
     * Size-based rotation with gzip, logrotate style:
     * file -> file.1.gz, shifting older backups up to $keep, dropping the oldest.
     */
    private static function rotate(string $path, int $keep): void
    {
        $keep = max(1, $keep);
        $oldest = "$path.$keep.gz";
        if (is_file($oldest)) {
            @unlink($oldest);
        }
        for ($i = $keep - 1; $i >= 1; $i--) {
            $src = "$path.$i.gz";
            if (is_file($src)) {
                @rename($src, "$path." . ($i + 1) . '.gz');
            }
        }
        if (is_file($path)) {
            self::gzipFile($path, "$path.1.gz");
            @unlink($path);
        }
    }

    private static function gzipFile(string $src, string $dst): void
    {
        $in = @fopen($src, 'rb');
        $out = @gzopen($dst, 'wb9');
        if ($in === false || $out === false) {
            if ($in !== false) { @fclose($in); }
            if ($out !== false) { @gzclose($out); }
            return;
        }
        while (!feof($in)) {
            $chunk = fread($in, 262144);
            if ($chunk === false) {
                break;
            }
            gzwrite($out, $chunk);
        }
        @fclose($in);
        @gzclose($out);
        @chmod($dst, 0664);
    }

    // ----- value helpers (used by Phases 2 & 3) -------------------------

    /**
     * Truncate a string to the configured maximum byte length.
     *
     * @return array{0:string,1:bool} [value, wasTruncated]
     */
    public static function truncate(string $value, ?int $max = null): array
    {
        $max = $max ?? (int) self::cfg()['max_body_bytes'];
        if ($max >= 0 && strlen($value) > $max) {
            return [substr($value, 0, $max), true];
        }
        return [$value, false];
    }

    /** @return array<int,string> lowercased field names to redact */
    public static function redactList(): array
    {
        return array_map('strtolower', (array) self::cfg()['redact_fields']);
    }

    /**
     * If $name is a redacted field, return the redaction marker; otherwise the
     * original value. The marker keeps the length so analysts see the shape.
     */
    public static function redactValue(string $name, string $value): string
    {
        if (in_array(strtolower($name), self::redactList(), true)) {
            return '[REDACTED len=' . strlen($value) . ']';
        }
        return $value;
    }

    // ----- client IP (trusted-proxy aware) ------------------------------

    /**
     * Resolve the client address. X-Forwarded-For is honoured only when the
     * immediate peer is a trusted proxy; otherwise the socket peer is used.
     *
     * @return array{ip:?string,port:?int}
     */
    public static function clientIp(): array
    {
        $peer = $_SERVER['REMOTE_ADDR'] ?? null;
        $port = isset($_SERVER['REMOTE_PORT']) ? (int) $_SERVER['REMOTE_PORT'] : null;
        $trusted = (array) self::cfg()['trusted_proxies'];
        $xff = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';

        if ($peer !== null && $xff !== '' && self::ipInList($peer, $trusted)) {
            $chain = array_map('trim', explode(',', $xff));
            // Walk right-to-left; the first address that is not a trusted proxy
            // is the real client.
            for ($i = count($chain) - 1; $i >= 0; $i--) {
                $candidate = $chain[$i];
                if ($candidate !== '' && !self::ipInList($candidate, $trusted)) {
                    return ['ip' => $candidate, 'port' => $port];
                }
            }
            // Whole chain was trusted; fall back to the left-most entry.
            if ($chain !== [] && $chain[0] !== '') {
                return ['ip' => $chain[0], 'port' => $port];
            }
        }
        return ['ip' => $peer, 'port' => $port];
    }

    /** @param array<int,string> $cidrs */
    public static function ipInList(string $ip, array $cidrs): bool
    {
        foreach ($cidrs as $cidr) {
            if (self::ipMatch($ip, $cidr)) {
                return true;
            }
        }
        return false;
    }

    private static function ipMatch(string $ip, string $cidr): bool
    {
        if (strpos($cidr, '/') === false) {
            return $ip === $cidr;
        }
        [$subnet, $bits] = explode('/', $cidr, 2);
        $bits = (int) $bits;
        $ipBin = @inet_pton($ip);
        $subnetBin = @inet_pton($subnet);
        if ($ipBin === false || $subnetBin === false || strlen($ipBin) !== strlen($subnetBin)) {
            return false;
        }
        $bytes = intdiv($bits, 8);
        $rem = $bits % 8;
        if ($bytes > 0 && strncmp($ipBin, $subnetBin, $bytes) !== 0) {
            return false;
        }
        if ($rem === 0) {
            return true;
        }
        $mask = chr((0xff << (8 - $rem)) & 0xff);
        return (ord($ipBin[$bytes]) & ord($mask)) === (ord($subnetBin[$bytes]) & ord($mask));
    }

    // ----- internal helpers ---------------------------------------------

    /**
     * Expand dotted keys ("a.b.c" => value) into nested arrays, so documents
     * authored with flat ECS field names serialize as proper nested JSON.
     *
     * @param array<string,mixed> $doc
     * @return array<string,mixed>
     */
    public static function expandDots(array $doc): array
    {
        $out = [];
        foreach ($doc as $key => $value) {
            if (!is_string($key) || strpos($key, '.') === false) {
                $out[$key] = $value;
                continue;
            }
            $parts = explode('.', $key);
            $ref =& $out;
            foreach ($parts as $i => $part) {
                if ($i === count($parts) - 1) {
                    $ref[$part] = $value;
                } else {
                    if (!isset($ref[$part]) || !is_array($ref[$part])) {
                        $ref[$part] = [];
                    }
                    $ref =& $ref[$part];
                }
            }
            unset($ref);
        }
        return $out;
    }

    private static function uuid4(): string
    {
        $d = random_bytes(16);
        $d[6] = chr((ord($d[6]) & 0x0f) | 0x40);
        $d[8] = chr((ord($d[8]) & 0x3f) | 0x80);
        $hex = bin2hex($d);
        return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4)
            . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20, 12);
    }

    private static function envStr(string $key, string $default): string
    {
        $v = getenv($key);
        return ($v === false || $v === '') ? $default : $v;
    }

    private static function envInt(string $key, int $default): int
    {
        $v = getenv($key);
        return ($v === false || $v === '' || !is_numeric($v)) ? $default : (int) $v;
    }

    private static function envBool(string $key, bool $default): bool
    {
        $v = getenv($key);
        if ($v === false) {
            return $default;
        }
        return !in_array(strtolower(trim($v)), ['0', 'false', 'no', 'off', ''], true);
    }

    /**
     * @param array<int,string> $default
     * @return array<int,string>
     */
    private static function envList(string $key, array $default): array
    {
        $v = getenv($key);
        if ($v === false) {
            return $default;
        }
        $parts = array_values(array_filter(array_map('trim', explode(',', $v)), static fn ($s) => $s !== ''));
        return $parts;
    }
}
