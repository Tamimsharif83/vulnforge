<?php
// Secure: encode for the HTML context on output.
$q = $_GET['q'] ?? '';
$context = 'Encoded with htmlspecialchars(ENT_QUOTES): the input renders as text.';
$safe = htmlspecialchars($q, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$body = "<p>No results for $safe</p>"; // @sink
$raw = $q;
require __DIR__ . '/render.php';
