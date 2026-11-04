<?php
// Medium: strips the separators people reach for first, and nothing else.
$raw = $_GET['host'] ?? '127.0.0.1';
$host = str_replace([';', '&', '|'], '', $raw);
$cmd = "ping -c 1 $host"; // @sink
require __DIR__ . '/result.php';
