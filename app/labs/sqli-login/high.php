<?php
// High: also strip the OR keyword. MySQL still spells OR as ||.
$u = $_POST['username'] ?? '';
$u = str_ireplace([' or ', '-- ', '--', '#', '/*'], ' ', $u);
$u = preg_replace('/\bor\b/i', '', $u);
$p = $_POST['password'] ?? '';
$sql = "SELECT id, username, role, password_md5 FROM users WHERE username='$u' AND password_md5=MD5('$p')"; // @sink
try { $user = Db::raw($sql, $u)[0] ?? false; }
catch (PDOException $e) { $user = false; Trace::note('Query error: ' . $e->getMessage()); }
require __DIR__ . '/view.php';
