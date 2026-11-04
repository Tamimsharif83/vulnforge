<?php
// Medium: remove <script> tags, once, case-insensitively.
$raw = $_GET['q'] ?? '';
$q = str_ireplace('<script>', '', $raw);
$context = 'HTML body text, with <script> stripped. Event-handler tags still run.';
$body = "<p>No results for $q</p>"; // @sink
require __DIR__ . '/render.php';
