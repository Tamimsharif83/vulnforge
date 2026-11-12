<?php
declare(strict_types=1);

/**
 * Application security events (Phase 3). Security-relevant behavior the access
 * log cannot show, written to app-events.jsonl. Every event shares the request's
 * http.request.id (set by Logger) so it joins with the access log, and uses the
 * dataset vulnforge.app.
 *
 * These are hooked at the points where the app already performs the action; they
 * never change the action, the response, or a lab's behavior. All emission is
 * fail-safe (Logger::write swallows errors).
 */
final class AppEvents
{
    /** @param array<string,mixed> $fields */
    public static function emit(array $fields): void
    {
        Logger::write('app-events.jsonl', Logger::ecsBase('vulnforge.app') + ['event.kind' => 'event'] + $fields);
    }

    public static function auth(string $action, string $outcome, ?string $user, ?string $reason = null): void
    {
        $f = [
            'event.category' => ['authentication'],
            'event.type'     => ['start'],
            'event.action'   => $action,
            'event.outcome'  => $outcome,
        ];
        if ($user !== null) {
            $f['user.name'] = $user;
        }
        if ($reason !== null) {
            $f['event.reason'] = $reason;
        }
        self::emit($f);
    }

    /** @param array<string,mixed> $extra */
    public static function iam(string $action, ?string $targetUser = null, array $extra = []): void
    {
        $f = [
            'event.category' => ['iam'],
            'event.type'     => ['change'],
            'event.action'   => $action,
        ] + $extra;
        if ($targetUser !== null) {
            $f['user.target.name'] = $targetUser;
        }
        self::emit($f);
    }

    public static function fileUpload(string $path, string $name, ?int $size, ?string $sha256): void
    {
        self::emit([
            'event.category'   => ['file'],
            'event.type'       => ['creation'],
            'event.action'     => 'file-upload',
            'file.path'        => $path,
            'file.name'        => $name,
            'file.extension'   => strtolower(pathinfo($name, PATHINFO_EXTENSION)),
            'file.size'        => $size,
            'file.hash.sha256' => $sha256,
        ]);
    }

    public static function fileRead(string $requested, ?string $resolved): void
    {
        self::emit([
            'event.category' => ['file'],
            'event.type'     => ['access'],
            'event.action'   => 'file-read',
            'file.path'      => $resolved,                 // as resolved
            'vulnforge.file.requested' => $requested,      // as requested
        ]);
    }

    public static function dbError(string $type, string $message): void
    {
        [$msg] = Logger::truncate($message, 2048);
        self::emit([
            'event.category' => ['database'],
            'event.type'     => ['error'],
            'event.action'   => 'query-error',
            'event.outcome'  => 'failure',
            'error.type'     => $type,
            'error.message'  => $msg,
        ]);
    }

    public static function outbound(string $url, ?string $domain, ?string $ip, ?int $status): void
    {
        self::emit([
            'event.category' => ['network'],
            'event.type'     => ['connection'],
            'event.action'   => 'outbound-request',
            'url.original'   => $url,
            'destination.domain' => $domain,
            'destination.ip'     => $ip,
            'http.response.status_code' => $status,
        ]);
    }

    public static function commandExec(string $commandLine, ?int $exitCode = null): void
    {
        [$cmd] = Logger::truncate($commandLine, 4096);
        $f = [
            'event.category'       => ['process'],
            'event.type'           => ['start'],
            'event.action'         => 'command-exec',
            'process.command_line' => $cmd,
        ];
        if ($exitCode !== null) {
            $f['process.exit_code'] = $exitCode;
        }
        self::emit($f);
    }

    public static function appError(string $type, string $message, ?string $stack = null): void
    {
        [$msg] = Logger::truncate($message, 2048);
        $f = [
            'event.category' => ['web'],
            'event.type'     => ['error'],
            'event.action'   => 'app-error',
            'event.outcome'  => 'failure',
            'error.type'     => $type,
            'error.message'  => $msg,
        ];
        if ($stack !== null) {
            [$f['error.stack_trace']] = Logger::truncate($stack, 4096);
        }
        self::emit($f);
    }

    /**
     * Register a shutdown hook that records a fatal error or any 5xx response as
     * an app-error event. Safe to call once per request from the prepend.
     */
    public static function registerErrorShutdown(): void
    {
        register_shutdown_function(static function (): void {
            try {
                if (!Logger::enabled()) {
                    return;
                }
                $last = error_get_last();
                $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR];
                if ($last !== null && in_array($last['type'], $fatalTypes, true)) {
                    self::appError(
                        'php_fatal_' . $last['type'],
                        $last['message'] . ' in ' . ($last['file'] ?? '?') . ':' . ($last['line'] ?? 0)
                    );
                    return;
                }
                $code = http_response_code();
                if (is_int($code) && $code >= 500) {
                    self::appError('http_' . $code, 'response status ' . $code);
                }
            } catch (\Throwable $e) {
                // never affect the request
            }
        });
    }
}
