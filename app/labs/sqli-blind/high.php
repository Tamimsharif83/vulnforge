<?php
// High: case-insensitive removal, but a single pass. Nesting defeats it.
$u = str_ireplace('sleep', '', $_GET['username'] ?? '');
$sql = "SELECT id FROM users WHERE username='$u'"; // @sink
$start = microtime(true);
try { $taken = count(Db::raw($sql, $u)) > 0; }
catch (PDOException $e) { $taken = false; Trace::note('Query error: ' . $e->getMessage()); }
$elapsedMs = (int) round((microtime(true) - $start) * 1000);
require __DIR__ . '/view.php';
