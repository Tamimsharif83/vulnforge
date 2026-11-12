<?php
declare(strict_types=1);

/**
 * Phase 3 tests for AppEvents. Plain PHP, no framework.
 *
 *   php tests/AppEventsTest.php
 */

require __DIR__ . '/../app/src/Logger.php';
require __DIR__ . '/../app/src/AppEvents.php';

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

function withLog(callable $fn): array
{
    $dir = sys_get_temp_dir() . '/vf-ev-' . bin2hex(random_bytes(6));
    mkdir($dir, 0775, true);
    Logger::resetForTests();
    Logger::init(['dir' => $dir]);
    $fn();
    $file = "$dir/app-events.jsonl";
    if (!is_file($file)) {
        return [];
    }
    $out = [];
    foreach (explode("\n", rtrim((string) file_get_contents($file), "\n")) as $line) {
        if ($line !== '') {
            $out[] = json_decode($line, true);
        }
    }
    return $out;
}

function g(?array $d, string ...$keys)
{
    foreach ($keys as $k) {
        $d = is_array($d) ? ($d[$k] ?? null) : null;
    }
    return $d;
}

echo "AppEvents tests\n";

// 1) Envelope: dataset, kind, and the join key are always present.
(function () {
    $recs = withLog(fn () => AppEvents::auth('user-login', 'success', 'alice'));
    $r = $recs[0] ?? [];
    check(g($r, 'event', 'dataset') === 'vulnforge.app', 'dataset is vulnforge.app');
    check(g($r, 'event', 'kind') === 'event', 'event.kind is event');
    check(!empty(g($r, 'http', 'request', 'id')), 'http.request.id join key present');
    check(g($r, 'ecs', 'version') === Logger::ECS_VERSION, 'ecs.version present');
})();

// 2) Authentication.
(function () {
    $recs = withLog(function () {
        AppEvents::auth('user-login', 'success', "admin' -- -");
        AppEvents::auth('user-login', 'failure', 'ghost', 'invalid-credentials');
    });
    check(g($recs[0], 'event', 'category') === ['authentication'], 'auth category');
    check(g($recs[0], 'event', 'outcome') === 'success', 'auth success outcome');
    check(g($recs[0], 'user', 'name') === "admin' -- -", 'attacker username captured verbatim');
    check(g($recs[1], 'event', 'outcome') === 'failure', 'auth failure outcome');
    check(g($recs[1], 'event', 'reason') === 'invalid-credentials', 'failure reason recorded');
})();

// 3) IAM account changes.
(function () {
    $recs = withLog(fn () => AppEvents::iam('role-changed', 'alice', ['vulnforge.account.role' => 'admin']));
    check(g($recs[0], 'event', 'category') === ['iam'], 'iam category');
    check(g($recs[0], 'event', 'action') === 'role-changed', 'iam action');
    check(g($recs[0], 'user', 'target', 'name') === 'alice', 'iam target user');
    check(g($recs[0], 'vulnforge', 'account', 'role') === 'admin', 'iam extra field nested');
})();

// 4) File upload — metadata only.
(function () {
    $recs = withLog(fn () => AppEvents::fileUpload('/var/www/app/public/uploads/shell.phtml', 'shell.phtml', 42, 'deadbeef'));
    check(g($recs[0], 'event', 'category') === ['file'], 'upload file category');
    check(g($recs[0], 'event', 'action') === 'file-upload', 'upload action');
    check(g($recs[0], 'file', 'name') === 'shell.phtml', 'file name');
    check(g($recs[0], 'file', 'extension') === 'phtml', 'file extension derived');
    check(g($recs[0], 'file', 'hash', 'sha256') === 'deadbeef', 'file sha256');
})();

// 5) File read — requested and resolved.
(function () {
    $recs = withLog(fn () => AppEvents::fileRead('../../../../etc/passwd', '/etc/passwd'));
    check(g($recs[0], 'event', 'action') === 'file-read', 'file-read action');
    check(g($recs[0], 'file', 'path') === '/etc/passwd', 'resolved path in file.path');
    check(g($recs[0], 'vulnforge', 'file', 'requested') === '../../../../etc/passwd', 'requested path recorded');
})();

// 6) Outbound request.
(function () {
    $recs = withLog(fn () => AppEvents::outbound('http://169.254.169.254/latest/meta-data/', '169.254.169.254', '169.254.169.254', 200));
    check(g($recs[0], 'event', 'category') === ['network'], 'outbound category');
    check(g($recs[0], 'destination', 'ip') === '169.254.169.254', 'destination.ip');
    check(g($recs[0], 'http', 'response', 'status_code') === 200, 'outbound status code');
})();

// 7) Command exec.
(function () {
    $recs = withLog(fn () => AppEvents::commandExec('ping -c 1 127.0.0.1; id', 0));
    check(g($recs[0], 'event', 'category') === ['process'], 'process category');
    check(g($recs[0], 'process', 'command_line') === 'ping -c 1 127.0.0.1; id', 'command line as executed');
    check(g($recs[0], 'process', 'exit_code') === 0, 'exit code when known');
})();

// 8) DB error and message truncation.
(function () {
    $long = str_repeat('x', 5000);
    $recs = withLog(fn () => AppEvents::dbError('SQLSTATE[42000]', $long));
    check(g($recs[0], 'event', 'category') === ['database'], 'database category');
    check(g($recs[0], 'error', 'type') === 'SQLSTATE[42000]', 'error type');
    check(strlen((string) g($recs[0], 'error', 'message')) === 2048, 'long error message truncated to 2048');
})();

// 9) App error.
(function () {
    $recs = withLog(fn () => AppEvents::appError('http_500', 'response status 500'));
    check(g($recs[0], 'event', 'action') === 'app-error', 'app-error action');
    check(g($recs[0], 'event', 'outcome') === 'failure', 'app-error outcome failure');
})();

// 10) Disabled logging writes nothing.
(function () {
    $dir = sys_get_temp_dir() . '/vf-ev-off-' . bin2hex(random_bytes(6));
    mkdir($dir, 0775, true);
    Logger::resetForTests();
    Logger::init(['dir' => $dir, 'enabled' => false]);
    AppEvents::auth('user-login', 'success', 'alice');
    check(!is_file("$dir/app-events.jsonl"), 'disabled logging writes no app-events file');
})();

$total = $GLOBALS['t_total'];
$fail = $GLOBALS['t_fail'];
echo sprintf("\n%d checks, %d passed, %d failed\n", $total, $total - $fail, $fail);
exit($fail === 0 ? 0 : 1);
