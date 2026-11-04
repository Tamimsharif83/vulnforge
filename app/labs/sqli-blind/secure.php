<?php
// Secure: the username is a bound parameter, so timing never varies with input.
$u = $_GET['username'] ?? '';
$start = microtime(true);
$taken = count(Db::prepared('SELECT id FROM users WHERE username = ?', [$u])) > 0;
$elapsedMs = (int) round((microtime(true) - $start) * 1000);
require __DIR__ . '/view.php';
