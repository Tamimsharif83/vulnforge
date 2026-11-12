<?php
declare(strict_types=1);

/**
 * Phase 1 engine tests for Logger. Plain PHP, no framework.
 *
 *   php tests/LoggerTest.php
 *
 * Exits non-zero if any check fails.
 */

require __DIR__ . '/../app/src/Logger.php';

$GLOBALS['t_total'] = 0;
$GLOBALS['t_fail'] = 0;

function check(bool $cond, string $msg): void
{
    $GLOBALS['t_total']++;
    if ($cond) {
        return;
    }
    $GLOBALS['t_fail']++;
    fwrite(STDERR, "  FAIL: $msg\n");
}

function tmpdir(): string
{
    $d = sys_get_temp_dir() . '/vf-log-' . bin2hex(random_bytes(6));
    mkdir($d, 0775, true);
    return $d;
}

/** Read all JSON records from a .jsonl file, asserting one record per line. */
function readLines(string $file): array
{
    $raw = file_get_contents($file);
    $lines = $raw === '' ? [] : explode("\n", rtrim($raw, "\n"));
    $out = [];
    foreach ($lines as $line) {
        $out[] = json_decode($line, true);
    }
    return $out;
}

// ---------------------------------------------------------------------------
echo "Logger engine tests\n";

// 1) Basic write + ECS envelope + dotted-key nesting.
(function () {
    $dir = tmpdir();
    Logger::resetForTests();
    Logger::init(['dir' => $dir]);
    $doc = Logger::ecsBase('vulnforge.access') + [
        'event.kind' => 'event',
        'http.request.method' => 'GET',
        'http.response.status_code' => 200,
    ];
    Logger::write('access.jsonl', $doc);

    $file = "$dir/access.jsonl";
    check(is_file($file), 'access.jsonl is created');
    $recs = readLines($file);
    check(count($recs) === 1, 'exactly one record written');
    $r = $recs[0] ?? [];
    check(($r['ecs']['version'] ?? null) === Logger::ECS_VERSION, 'ecs.version nested + correct');
    check(($r['event']['dataset'] ?? null) === 'vulnforge.access', 'event.dataset nested');
    check(($r['service']['name'] ?? null) === 'vulnforge', 'service.name present');
    check(($r['http']['request']['method'] ?? null) === 'GET', 'http.request.method nested');
    check(($r['http']['response']['status_code'] ?? null) === 200, 'status_code stays an int');
    check(!empty($r['http']['request']['id']), 'http.request.id present');
    check(isset($r['@timestamp']) && preg_match('/^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d\.\d{3}Z$/', $r['@timestamp']) === 1,
        '@timestamp is RFC3339 UTC with ms');
})();

// 2) Feature flag: disabled writes nothing.
(function () {
    $dir = tmpdir();
    Logger::resetForTests();
    Logger::init(['dir' => $dir, 'enabled' => false]);
    Logger::write('access.jsonl', ['x' => 1]);
    check(!is_file("$dir/access.jsonl"), 'disabled logging writes no file');
})();

// 3) Hostile payloads stay a single valid JSON line.
(function () {
    $dir = tmpdir();
    Logger::resetForTests();
    Logger::init(['dir' => $dir]);
    $nasty = "line1\nFAKE-FIELD: injected\r\n\"quotes\" and \\slash\\ \t tab"
        . " \x00NUL\x00 unicode: café — 日本語 "
        . "\xC3\x28invalid-utf8 " . str_repeat('A', 5000);
    Logger::write('access.jsonl', Logger::ecsBase('vulnforge.access') + ['url.query' => $nasty]);

    $raw = file_get_contents("$dir/access.jsonl");
    check(substr_count(rtrim($raw, "\n"), "\n") === 0, 'hostile payload stays on one physical line');
    check(strpos($raw, "\x00") === false, 'no raw NUL byte in output');
    $recs = readLines("$dir/access.jsonl");
    check(count($recs) === 1 && is_array($recs[0]), 'hostile line is valid, parseable JSON');
    $val = $recs[0]['url']['query'] ?? '';
    check(strpos($val, 'FAKE-FIELD') !== false, 'injected text is preserved as data, not a new field');
    check(($recs[0]['FAKE-FIELD'] ?? null) === null, 'no forged top-level field from the newline');
    check(strpos($val, "\x00") !== false, 'NUL round-trips as a value character');
})();

// 4) Truncation.
(function () {
    Logger::resetForTests();
    Logger::init(['dir' => tmpdir(), 'max_body_bytes' => 10]);
    [$v, $trunc] = Logger::truncate(str_repeat('x', 50));
    check($v === str_repeat('x', 10) && $trunc === true, 'long value truncated to max with flag');
    [$v2, $trunc2] = Logger::truncate('short');
    check($v2 === 'short' && $trunc2 === false, 'short value is not truncated');
})();

// 5) Redaction.
(function () {
    Logger::resetForTests();
    Logger::init(['dir' => tmpdir(), 'redact_fields' => ['password', 'csrf_token']]);
    check(Logger::redactValue('password', 'hunter2') === '[REDACTED len=7]', 'listed field is redacted with length');
    check(Logger::redactValue('PASSWORD', 'abc') === '[REDACTED len=3]', 'redaction is case-insensitive');
    check(Logger::redactValue('q', "' OR 1=1") === "' OR 1=1", 'non-listed field is kept verbatim');
})();

// 6) Rotation with gzip.
(function () {
    $dir = tmpdir();
    Logger::resetForTests();
    Logger::init(['dir' => $dir, 'rotate_bytes' => 300, 'rotate_keep' => 2]);
    for ($i = 0; $i < 40; $i++) {
        Logger::write('access.jsonl', ['n' => $i, 'pad' => str_repeat('p', 60)]);
    }
    check(is_file("$dir/access.jsonl"), 'current log exists after rotation');
    check(is_file("$dir/access.jsonl.1.gz"), 'rotated .1.gz backup created');
    check(!is_file("$dir/access.jsonl.3.gz"), 'backups capped at rotate_keep (no .3.gz)');
    $gz = (string) gzdecode((string) file_get_contents("$dir/access.jsonl.1.gz"));
    check(strpos($gz, '"pad"') !== false, 'gzip backup decodes to readable JSON lines');
})();

// 7) Fail-safe: an unwritable directory must not throw.
(function () {
    Logger::resetForTests();
    Logger::init(['dir' => '/proc/vf-cannot-create/nope']);
    $threw = false;
    try {
        Logger::write('access.jsonl', ['x' => 1]);
    } catch (\Throwable $e) {
        $threw = true;
    }
    check($threw === false, 'write to an unwritable dir never throws (request unaffected)');
})();

// 8) Trusted-proxy client IP resolution.
(function () {
    Logger::resetForTests();
    Logger::init(['dir' => tmpdir(), 'trusted_proxies' => ['127.0.0.1', '172.16.0.0/12']]);

    $_SERVER['REMOTE_ADDR'] = '172.20.0.5';   // the edge proxy (trusted)
    $_SERVER['REMOTE_PORT'] = '54000';
    $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.9, 172.20.0.5';
    $c = Logger::clientIp();
    check($c['ip'] === '203.0.113.9', 'XFF honoured through a trusted proxy -> real client');

    $_SERVER['REMOTE_ADDR'] = '198.51.100.7'; // direct, untrusted peer
    $_SERVER['HTTP_X_FORWARDED_FOR'] = '1.2.3.4'; // spoofed, must be ignored
    $c2 = Logger::clientIp();
    check($c2['ip'] === '198.51.100.7', 'untrusted peer: XFF ignored, socket peer used');
    unset($_SERVER['HTTP_X_FORWARDED_FOR']);
})();

// 9) CIDR matching.
(function () {
    check(Logger::ipInList('10.1.2.3', ['10.0.0.0/8']) === true, 'IPv4 inside /8 matches');
    check(Logger::ipInList('11.1.2.3', ['10.0.0.0/8']) === false, 'IPv4 outside /8 does not match');
    check(Logger::ipInList('127.0.0.1', ['127.0.0.1']) === true, 'exact IPv4 matches');
    check(Logger::ipInList('::1', ['::1']) === true, 'exact IPv6 matches');
})();

// 10) Request id: stable, and honours a sane upstream id.
(function () {
    Logger::resetForTests();
    unset($_SERVER['HTTP_X_REQUEST_ID']);
    Logger::init(['dir' => tmpdir()]);
    $a = Logger::requestId();
    $b = Logger::requestId();
    check($a === $b && preg_match('/^[0-9a-f-]{36}$/', $a) === 1, 'request id is a stable UUIDv4');

    Logger::resetForTests();
    $_SERVER['HTTP_X_REQUEST_ID'] = 'edge-abc_123';
    Logger::init(['dir' => tmpdir()]);
    check(Logger::requestId() === 'edge-abc_123', 'valid upstream X-Request-Id is honoured');
    unset($_SERVER['HTTP_X_REQUEST_ID']);
})();

// 11) Overhead stays small: many writes complete quickly.
(function () {
    Logger::resetForTests();
    Logger::init(['dir' => tmpdir()]);
    $base = Logger::ecsBase('vulnforge.access') + ['http.request.method' => 'GET', 'url.path' => '/x'];
    $start = microtime(true);
    for ($i = 0; $i < 1000; $i++) {
        Logger::write('access.jsonl', $base + ['n' => $i]);
    }
    $elapsed = microtime(true) - $start;
    // Very generous bound: 1000 line-writes (with locking) should be well under this.
    check($elapsed < 5.0, sprintf('1000 writes complete quickly (%.3fs < 5s)', $elapsed));
})();

// ---------------------------------------------------------------------------
$total = $GLOBALS['t_total'];
$fail = $GLOBALS['t_fail'];
echo sprintf("\n%d checks, %d passed, %d failed\n", $total, $total - $fail, $fail);
exit($fail === 0 ? 0 : 1);
