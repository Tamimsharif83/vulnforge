<?php
require __DIR__ . '/shared.php';
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
// High: allow any Origin containing forgeshop.local (substring).
$ok = $origin !== '' && str_contains($origin, 'forgeshop.local'); // @sink
cors_emit($ok ? $origin : null, $ok ? 'Origin contains forgeshop.local.' : 'Origin not allowed.');
