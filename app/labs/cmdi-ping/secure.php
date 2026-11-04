<?php
// Secure: validate the shape, then pass the host as one escaped argument.
$host = $_GET['host'] ?? '127.0.0.1';
if (!preg_match('/^[A-Za-z0-9.\-]+$/', $host)) {
    http_response_code(400);
    Trace::note('Rejected: the host did not match the allowed pattern, so no command ran.');
    echo 'Invalid host.';
    return;
}
$cmd = 'ping -c 1 ' . escapeshellarg($host); // @sink
require __DIR__ . '/result.php';
