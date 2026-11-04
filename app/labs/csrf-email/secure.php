<?php
// Secure: unpredictable per-session token, verified; cookie would be SameSite.
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }
$token = $_SESSION['csrf'];
$email = 'alice@forgeshop.local';
$changed = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    if (hash_equals($token, $_POST['csrf_token'] ?? '')) { // @sink
        $email = $_POST['email'];
        $changed = true;
        Trace::note('Accepted only with the correct per-session token (legitimate use, not forgeable).');
    } else {
        Trace::note('Rejected: missing or invalid per-session CSRF token.');
    }
}
require __DIR__ . '/view.php';
