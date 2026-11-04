<?php
$raw = file_get_contents('php://input');
$name = null;
if (trim($raw) !== '') {
    // Secure: no entity substitution, no DTD load, no network. DOCTYPE is inert.
    $doc = new DOMDocument();
    $ok = @$doc->loadXML($raw, LIBXML_NONET); // @sink
    if (!$ok || str_contains($raw, '<!DOCTYPE')) {
        http_response_code(400); Trace::note('Rejected: DOCTYPE declarations are not allowed.');
        echo 'Blocked.'; return;
    }
    $node = $doc->getElementsByTagName('name')->item(0);
    $name = $node ? $node->textContent : null;
}
require __DIR__ . '/view.php';
