<?php
// Secure: encode stored text for the HTML context on output.
$transform = fn (string $c): string => htmlspecialchars($c, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); // @sink
require __DIR__ . '/render.php';
