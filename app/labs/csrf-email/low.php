<?php
$email = 'alice@forgeshop.local';
$changed = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    // Low: no CSRF token checked at all.
    $email = $_POST['email']; // @sink
    $changed = true;
    Lab::markSolved('csrf-email', current_level());
    Trace::note('Email changed with no anti-CSRF token present: any cross-site POST would work.');
}
require __DIR__ . '/view.php';
