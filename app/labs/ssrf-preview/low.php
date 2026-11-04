<?php
require __DIR__ . '/fetch.php';
// Low: fetch whatever URL is given.
$url = $_GET['url'] ?? 'http://internal-api/'; // @sink
ssrf_show(ssrf_fetch($url));
