<?php
// Secure: basename, extension allowlist, and a realpath containment check.
$doc = basename($_GET['doc'] ?? 'welcome.txt');
$base = realpath(__DIR__ . '/docs');
$resolved = realpath($base . '/' . $doc);
Trace::step('Resolved file path', (string) $resolved, '');
if (!str_ends_with($doc, '.txt') || $resolved === false || !str_starts_with($resolved, $base . '/')) {
    http_response_code(404);
    Trace::note('Rejected: the resolved path is outside the documents folder, or not a .txt file.');
    echo 'Document not found.';
    return;
}
$contents = (string) file_get_contents($resolved);
require __DIR__ . '/view.php';
