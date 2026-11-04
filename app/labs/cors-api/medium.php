<?php
require __DIR__ . '/shared.php';
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
// Medium: allow any Origin ending in forgeshop.local.
$ok = $origin !== '' && str_ends_with(parse_url($origin, PHP_URL_HOST) ?? '', 'forgeshop.local'); // @sink
cors_emit($ok ? $origin : null, $ok ? 'Origin host ends with forgeshop.local.' : 'Origin not allowed.');
