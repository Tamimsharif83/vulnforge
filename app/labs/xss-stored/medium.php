<?php
// Medium: strip <script> tags at render time; event handlers survive.
$transform = fn (string $c): string => str_ireplace('<script>', '', $c); // @sink
require __DIR__ . '/render.php';
