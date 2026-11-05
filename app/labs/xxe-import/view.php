<?php
// $name is the parsed product name; $raw is the submitted XML.
if ($name !== null && (str_contains($name, trim((string) @file_get_contents(SECRET_FILE))) || str_contains($name, 'root:x:0:0:'))) {
    Lab::markSolved('xxe-import', current_level());
}
Trace::step('Parsed product name from XML', $name ?? '(parse failed)', '', 'The parser substituted any external entity referenced here.');
echo '<!DOCTYPE html><title>Product import</title><link rel="stylesheet" href="/assets/shop.css?v=2">' . shop_header('Shop') . '<h1>Bulk import</h1>';
echo '<p>POST an XML document with a &lt;product&gt;&lt;name&gt; element.</p>';
echo '<p>Imported product: <strong>' . e($name ?? '(none)') . '</strong></p>';
