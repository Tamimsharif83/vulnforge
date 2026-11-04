<?php
$email = 'alice@forgeshop.local';
$token = 'csrf-static-123'; // same for everyone: predictable
$changed = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    if (($_POST['csrf_token'] ?? '') === $token) { // @sink  constant token
        $email = $_POST['email'];
        $changed = true;
        Lab::markSolved('csrf-email', current_level());
        Trace::note('Accepted a hardcoded, non-secret token; an attacker can embed it.');
    } else {
        Trace::note('Rejected: token missing or wrong.');
    }
}
require __DIR__ . '/view.php';
