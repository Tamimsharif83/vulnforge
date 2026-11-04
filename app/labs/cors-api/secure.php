<?php
require __DIR__ . '/shared.php';
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
// Secure: exact-match allowlist; only a known origin is echoed.
$allow = ['https://forgeshop.local', 'http://localhost:8080'];
$ok = in_array($origin, $allow, true); // @sink
cors_emit($ok ? $origin : null, $ok ? 'Origin exactly matches the allowlist.' : 'Origin not on the allowlist.');
