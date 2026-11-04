<?php
// Medium: strips ../ once, in a single non-recursive pass.
$doc = str_replace('../', '', $_GET['doc'] ?? 'welcome.txt');
$resolved = __DIR__ . '/docs/' . $doc; // @sink
Trace::step('Resolved file path', $resolved, $doc);
$contents = (string) @file_get_contents($resolved);
require __DIR__ . '/view.php';
