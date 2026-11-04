<?php
$email = 'alice@forgeshop.local';
$token = '';
$changed = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    // High: Referer/Origin check that allows a missing value.
    $sameSite = $referer === '' || str_contains($referer, $_SERVER['HTTP_HOST'] ?? ''); // @sink
    if ($sameSite) {
        $email = $_POST['email'];
        $changed = true;
        if ($referer === '') {
            Lab::markSolved('csrf-email', current_level());
            Trace::note('Accepted because the Referer was blank; a cross-site request can strip Referer.');
        }
    } else {
        Trace::note('Rejected: Referer did not match the host.');
    }
}
require __DIR__ . '/view.php';
