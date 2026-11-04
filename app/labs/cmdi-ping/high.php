<?php
// High: a longer blacklist, but a newline is a command separator too.
$raw = $_GET['host'] ?? '127.0.0.1';
$host = str_replace([';', '&', '|', '$', '(', ')', '`'], '', $raw);
$cmd = "ping -c 1 $host"; // @sink
require __DIR__ . '/result.php';
