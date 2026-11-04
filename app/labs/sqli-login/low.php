<?php
$u = $_POST['username'] ?? '';
$p = $_POST['password'] ?? '';
$sql = "SELECT id, username, role, password_md5 FROM users WHERE username='$u' AND password_md5=MD5('$p')"; // @sink
try { $user = Db::raw($sql, $u)[0] ?? false; }
catch (PDOException $e) { $user = false; Trace::note('Query error: ' . $e->getMessage()); }
require __DIR__ . '/view.php';
