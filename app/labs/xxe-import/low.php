<?php
$raw = file_get_contents('php://input');
$name = null;
if (trim($raw) !== '') {
    $doc = new DOMDocument();
    // Low: entities are substituted and DTDs loaded. External entities resolve.
    @$doc->loadXML($raw, LIBXML_NOENT | LIBXML_DTDLOAD); // @sink
    $node = $doc->getElementsByTagName('name')->item(0);
    $name = $node ? $node->textContent : null;
}
require __DIR__ . '/view.php';
