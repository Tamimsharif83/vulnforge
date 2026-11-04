<?php
// Low: the name is appended to the documents folder with no checks.
$doc = $_GET['doc'] ?? 'welcome.txt';
$resolved = __DIR__ . '/docs/' . $doc; // @sink
Trace::step('Resolved file path', $resolved, $doc);
$contents = (string) @file_get_contents($resolved);
require __DIR__ . '/view.php';
