<?php
$raw = file_get_contents('php://input');
$name = null;
if (trim($raw) !== '') {
    if (str_contains($raw, 'SYSTEM')) { // @sink
        http_response_code(400); Trace::note('Rejected: the document contains the SYSTEM keyword.');
        echo 'Blocked.'; return;
    }
    $doc = new DOMDocument();
    @$doc->loadXML($raw, LIBXML_NOENT | LIBXML_DTDLOAD);
    $node = $doc->getElementsByTagName('name')->item(0);
    $name = $node ? $node->textContent : null;
}
require __DIR__ . '/view.php';
