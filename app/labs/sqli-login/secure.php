<?php
// Secure: look up by username with a bound parameter, verify the hash in code.
$u = $_POST['username'] ?? '';
$p = $_POST['password'] ?? '';
$rows = Db::prepared('SELECT id, username, role, password_md5 FROM users WHERE username = ?', [$u]);
$candidate = $rows[0] ?? false;
$user = ($candidate && hash_equals($candidate['password_md5'], md5($p))) ? $candidate : false;
require __DIR__ . '/view.php';
