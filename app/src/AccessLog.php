<?php
declare(strict_types=1);

/**
 * HTTP access logging (Phase 2). Emits one ECS record per request to
 * access.jsonl when the response completes. Loaded as a PHP auto_prepend file
 * so it captures every PHP request, including directly-executed uploaded files
 * (web shells) — not only routes that pass through the front controller.
 *
 * Observational only: it buffers output to measure response size and reads
 * request data without consuming it, but never changes the response or status.
 */
final class AccessLog
{
    private static ?float $start = null;
    /** @var array<int,array<string,mixed>> uploaded-file metadata captured at request start */
    private static array $files = [];

    public static function begin(): void
    {
        try {
            Logger::init();
            if (!Logger::enabled()) {
                return;
            }
            self::$start = (float) hrtime(true);
            Logger::requestId();
            // Hash uploads now: the app may move the temp file before shutdown.
            self::$files = self::captureUploads();
            // Buffer the response so its byte size can be measured at shutdown.
            ob_start();
            register_shutdown_function([self::class, 'finish']);
        } catch (\Throwable $e) {
            // Never let logging setup affect the request.
        }
    }

    public static function finish(): void
    {
        try {
            if (self::$start === null) {
                return;
            }
            $durationNs = (int) ((float) hrtime(true) - self::$start);
            $status = http_response_code();
            $status = is_int($status) ? $status : 200;
            $respBytes = ob_get_length();
            $respBytes = $respBytes === false ? null : (int) $respBytes;

            $client = Logger::clientIp();
            [$bodyContent, $bodyBytes, $bodyTruncated] = self::requestBody();

            $doc = Logger::ecsBase('vulnforge.access') + [
                'event.kind'     => 'event',
                'event.category' => ['web'],
                'event.type'     => ['access'],
                'event.duration' => $durationNs,
                'event.outcome'  => $status >= 500 ? 'failure' : 'success',

                'http.request.method'   => $_SERVER['REQUEST_METHOD'] ?? null,
                'http.version'          => self::httpVersion(),
                'url.original'          => $_SERVER['REQUEST_URI'] ?? null,
                'url.path'              => self::urlPart('path'),
                'url.query'             => self::urlPart('query'),
                'http.request.referrer' => $_SERVER['HTTP_REFERER'] ?? null,
                'http.request.mime_type'    => self::mimeOnly($_SERVER['CONTENT_TYPE'] ?? null),
                'http.request.body.bytes'   => $bodyBytes,
                'http.request.body.content' => $bodyContent,

                'http.response.status_code' => $status,
                'http.response.body.bytes'  => $respBytes,
                'http.response.mime_type'   => self::responseMime(),

                'source.ip'           => $client['ip'],
                'source.port'         => $client['port'],
                'user_agent.original' => $_SERVER['HTTP_USER_AGENT'] ?? null,

                'vulnforge.session_hash' => Logger::sessionHash(),
                'vulnforge.http.request.headers' => self::safeHeaders(),
            ];
            if ($bodyTruncated) {
                $doc['vulnforge.body_truncated'] = true;
            }

            // Authenticated identity, if an app feature established one.
            $user = Logger::user();
            if ($user['name'] !== null) {
                $doc['user.name'] = $user['name'];
            }
            if ($user['id'] !== null) {
                $doc['user.id'] = $user['id'];
            }

            // Upload metadata (first uploaded file), never file contents.
            if (self::$files !== []) {
                $f = self::$files[0];
                $doc += [
                    'file.name'        => $f['name'],
                    'file.extension'   => $f['extension'],
                    'file.size'        => $f['size'],
                    'file.mime_type'   => $f['mime_type'],
                    'file.hash.sha256' => $f['sha256'],
                ];
            }

            Logger::write('access.jsonl', $doc);
        } catch (\Throwable $e) {
            // Swallow: logging must never affect the response.
        }
        // Do not close the buffer; PHP flushes it after shutdown functions run.
    }

    // ----- helpers -------------------------------------------------------

    private static function httpVersion(): ?string
    {
        $proto = $_SERVER['SERVER_PROTOCOL'] ?? '';
        return (is_string($proto) && str_contains($proto, '/')) ? explode('/', $proto, 2)[1] : null;
    }

    private static function urlPart(string $part): ?string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $v = parse_url((string) $uri, $part === 'path' ? PHP_URL_PATH : PHP_URL_QUERY);
        return is_string($v) ? $v : null;
    }

    private static function mimeOnly(?string $contentType): ?string
    {
        if ($contentType === null || $contentType === '') {
            return null;
        }
        return trim(explode(';', $contentType, 2)[0]);
    }

    private static function responseMime(): ?string
    {
        foreach (headers_list() as $header) {
            if (stripos($header, 'content-type:') === 0) {
                return self::mimeOnly(trim(substr($header, strlen('content-type:'))));
            }
        }
        // No explicit header set yet: PHP will send its default_mimetype (the
        // output buffer flushes after this shutdown hook), so report that.
        $default = ini_get('default_mimetype');
        return ($default !== false && $default !== '') ? self::mimeOnly($default) : null;
    }

    /**
     * Build the request body representation with truncation and redaction.
     *
     * @return array{0:?string,1:?int,2:bool} [content, byteLength, truncated]
     */
    private static function requestBody(): array
    {
        $contentLength = isset($_SERVER['CONTENT_LENGTH']) ? (int) $_SERVER['CONTENT_LENGTH'] : null;
        $mime = self::mimeOnly($_SERVER['CONTENT_TYPE'] ?? null);
        $isForm = in_array($mime, ['application/x-www-form-urlencoded', 'multipart/form-data'], true);

        if ($isForm && $_POST !== []) {
            // Field names + non-file values, each redacted per configuration.
            $pairs = [];
            foreach ($_POST as $name => $value) {
                $flat = is_scalar($value) ? (string) $value : json_encode($value);
                $pairs[] = rawurlencode((string) $name) . '=' . rawurlencode(Logger::redactValue((string) $name, (string) $flat));
            }
            $content = implode('&', $pairs);
        } elseif (!$isForm) {
            // JSON / XML / plain / raw bodies: read without consuming (php://input
            // is reusable since PHP 5.6), capped so huge bodies do not blow memory.
            $max = (int) (getenv('VF_LOG_MAX_BODY_BYTES') ?: 8192);
            $fh = @fopen('php://input', 'rb');
            $content = $fh ? (string) stream_get_contents($fh, $max + 1) : '';
            if ($fh) {
                @fclose($fh);
            }
        } else {
            $content = ''; // multipart with no non-file fields
        }

        if ($content === '') {
            return [null, $contentLength ?? 0, false];
        }
        [$value, $truncated] = Logger::truncate($content);
        return [$value, $contentLength ?? strlen($content), $truncated];
    }

    /**
     * Request headers, strictly allowlisted. Full values only for Host,
     * Content-Type, Origin and X-Forwarded-For; cookie names only; the
     * Authorization scheme only (never the credential).
     *
     * @return array<string,mixed>
     */
    private static function safeHeaders(): array
    {
        $out = [];
        $map = [
            'HTTP_HOST'            => 'Host',
            'CONTENT_TYPE'         => 'Content-Type',
            'HTTP_ORIGIN'          => 'Origin',
            'HTTP_X_FORWARDED_FOR' => 'X-Forwarded-For',
        ];
        foreach ($map as $server => $label) {
            if (!empty($_SERVER[$server])) {
                $out[$label] = $_SERVER[$server];
            }
        }
        if ($_COOKIE !== []) {
            $out['cookie_names'] = array_keys($_COOKIE); // names only, never values
        }
        $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if ($auth !== '') {
            $out['authorization_scheme'] = explode(' ', trim($auth), 2)[0]; // scheme only
        }
        return $out;
    }

    /**
     * Metadata (incl. SHA-256) for every uploaded file, captured at request
     * start while the temp files still exist. Never reads into the log body.
     *
     * @return array<int,array<string,mixed>>
     */
    private static function captureUploads(): array
    {
        $out = [];
        foreach ($_FILES as $field) {
            // Normalize single and multi-file fields.
            $names = is_array($field['name'] ?? null) ? $field['name'] : [$field['name'] ?? null];
            $tmps  = is_array($field['tmp_name'] ?? null) ? $field['tmp_name'] : [$field['tmp_name'] ?? null];
            $sizes = is_array($field['size'] ?? null) ? $field['size'] : [$field['size'] ?? null];
            $types = is_array($field['type'] ?? null) ? $field['type'] : [$field['type'] ?? null];
            $errs  = is_array($field['error'] ?? null) ? $field['error'] : [$field['error'] ?? null];
            foreach ($names as $i => $name) {
                if (($errs[$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                    continue;
                }
                $tmp = $tmps[$i] ?? '';
                $out[] = [
                    'name'      => $name,
                    'extension' => $name ? strtolower(pathinfo((string) $name, PATHINFO_EXTENSION)) : null,
                    'size'      => isset($sizes[$i]) ? (int) $sizes[$i] : null,
                    'mime_type' => $types[$i] ?? null, // client-declared
                    'sha256'    => ($tmp && is_uploaded_file($tmp)) ? @hash_file('sha256', $tmp) : null,
                ];
            }
        }
        return $out;
    }
}
