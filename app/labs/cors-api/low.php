<?php
require __DIR__ . '/shared.php';
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
// Low: reflect any Origin.
cors_emit($origin !== '' ? $origin : null, 'Any Origin is reflected.'); // @sink
