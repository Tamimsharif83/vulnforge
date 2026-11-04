<?php
// High: removes ../ until none remain, but still honours stream wrappers.
$raw = $_GET['doc'] ?? 'welcome.txt';
while (str_contains($raw, '../')) {
    $raw = str_replace('../', '', $raw);
}
// "Supports external document sources" via wrappers, used without a prefix.
$resolved = str_contains($raw, '://') ? $raw : __DIR__ . '/docs/' . $raw; // @sink
Trace::step('Resolved file path', $resolved, $raw);
$contents = (string) @file_get_contents($resolved);
require __DIR__ . '/view.php';
