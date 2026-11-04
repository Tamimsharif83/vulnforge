<?php
// Low: reviews are rendered exactly as stored.
$transform = fn (string $c): string => $c; // @sink
require __DIR__ . '/render.php';
