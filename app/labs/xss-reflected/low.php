<?php
// Low: the term is written straight into the page body.
$q = $_GET['q'] ?? '';
$context = 'HTML body text. Input is concatenated directly into the document.';
$body = "<p>No results for $q</p>"; // @sink
require __DIR__ . '/render.php';
