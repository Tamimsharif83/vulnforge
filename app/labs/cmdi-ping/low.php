<?php
// Low: the host is concatenated straight into the command.
$host = $_GET['host'] ?? '127.0.0.1';
$cmd = "ping -c 1 $host"; // @sink
require __DIR__ . '/result.php';
