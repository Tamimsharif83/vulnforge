<?php
// High: input lands inside an attribute value; only angle brackets are stripped.
$raw = $_GET['q'] ?? '';
$q = str_replace(['<', '>'], '', $raw);
$context = 'Attribute value: value="INPUT". Break out of the attribute with a quote.';
$body = "<input type=\"text\" value=\"$q\" readonly>"; // @sink
require __DIR__ . '/render.php';
