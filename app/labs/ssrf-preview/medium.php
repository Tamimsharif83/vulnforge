<?php
require __DIR__ . '/fetch.php';
// Medium: deny loopback and the literal metadata IP, by substring.
$url = $_GET['url'] ?? 'http://internal-api/';
$denied = ['127.0.0.1', 'localhost', '169.254.169.254']; // @sink
foreach ($denied as $needle) {
    if (stripos($url, $needle) !== false) {
        http_response_code(400);
        Trace::note("Rejected: the URL contains the blocked string \"$needle\".");
        echo 'Blocked host.';
        return;
    }
}
ssrf_show(ssrf_fetch($url));
