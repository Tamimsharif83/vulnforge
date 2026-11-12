<?php
declare(strict_types=1);

/**
 * Relay for the Attack Console. Browsers forbid setting Host and hide Set-Cookie
 * from fetch, so the request is written to a socket here instead. The destination
 * is fixed to this container's own web server: the relay is not an SSRF primitive.
 */
final class Console
{
    public static function send(array $request): array
    {
        $method = strtoupper(preg_replace('/[^A-Za-z]/', '', (string) ($request['method'] ?? 'GET')));
        $path = (string) ($request['path'] ?? '/');
        $body = (string) ($request['body'] ?? '');
        if ($method === '' || !str_starts_with($path, '/')) {
            return ['error' => 'A request needs a method and a path starting with /.'];
        }

        $headers = [];
        foreach (preg_split('/\r?\n/', (string) ($request['headers'] ?? '')) as $line) {
            if (str_contains($line, ':')) {
                [$name, $value] = explode(':', $line, 2);
                $headers[strtolower(trim($name))] = [trim($name), trim($value)];
            }
        }
        $headers['host'] ??= ['Host', $_SERVER['HTTP_HOST'] ?? 'localhost'];
        // Carry the lab session forward. If the student set their own Cookie
        // header (e.g. a crafted prefs cookie), merge the session's cookies in
        // rather than dropping them, so the level and student identity persist.
        $session = [];
        foreach (explode(';', $_SERVER['HTTP_COOKIE'] ?? '') as $pair) {
            if (str_contains($pair, '=')) {
                [$name] = explode('=', trim($pair), 2);
                $session[$name] = trim($pair);
            }
        }
        if ($session !== []) {
            $provided = [];
            if (isset($headers['cookie'])) {
                foreach (explode(';', $headers['cookie'][1]) as $pair) {
                    if (str_contains($pair, '=')) {
                        [$name] = explode('=', trim($pair), 2);
                        $provided[$name] = trim($pair);
                    }
                }
            }
            $merged = array_values($provided + $session); // provided wins on conflict
            $headers['cookie'] = ['Cookie', implode('; ', $merged)];
        }
        // Multipart parsing needs CRLF between part headers; a textarea gives LF.
        // Add the CR where it is missing; bodies that already use CRLF are untouched.
        if (isset($headers['content-type']) && str_contains(strtolower($headers['content-type'][1]), 'multipart/form-data')) {
            $body = preg_replace('/(?<!\r)\n/', "\r\n", $body);
        }
        if ($body !== '') {
            $headers['content-length'] = ['Content-Length', (string) strlen($body)];
        }
        // Attribute the relayed request to the real client in the access log,
        // not to this loopback relay. We set X-Forwarded-For ourselves (ignoring
        // any client-supplied one) so a console-driven attack keeps its source
        // IP. The app's labs do not read this header, so behavior is unchanged.
        if (class_exists('Logger')) {
            $client = Logger::clientIp();
            if (!empty($client['ip'])) {
                $headers['x-forwarded-for'] = ['X-Forwarded-For', $client['ip']];
            }
        }
        $headers['connection'] = ['Connection', 'close'];

        $raw = "$method $path HTTP/1.0\r\n";
        foreach ($headers as [$name, $value]) {
            $raw .= "$name: $value\r\n";
        }
        $raw .= "\r\n$body";

        $started = microtime(true);
        $socket = @fsockopen('127.0.0.1', 80, $errno, $errstr, 5);
        if ($socket === false) {
            return ['error' => "Could not reach the app server: $errstr"];
        }
        stream_set_timeout($socket, 55);
        fwrite($socket, $raw);
        $response = (string) stream_get_contents($socket);
        $timedOut = stream_get_meta_data($socket)['timed_out'];
        fclose($socket);
        if ($timedOut) {
            return ['error' => 'No complete response after 55 seconds. If you used SLEEP, note that it runs once per row the query scans.'];
        }

        return [
            'request' => $raw,
            'response' => mb_convert_encoding($response, 'UTF-8', 'UTF-8'),
            'ms' => (int) round((microtime(true) - $started) * 1000),
        ];
    }
}
