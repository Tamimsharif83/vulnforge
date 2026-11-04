<?php
require __DIR__ . '/fetch.php';
// High: a wider denylist, but it matches text in the host, not resolved IPs.
$url = $_GET['url'] ?? 'http://internal-api/';
$host = (string) parse_url($url, PHP_URL_HOST);
$denied = ['127.', 'localhost', '169.254', 'internal-api', 'metadata', '0.0.0.0', '::1']; // @sink
foreach ($denied as $needle) {
    if (stripos($host, $needle) !== false) {
        http_response_code(400);
        Trace::note("Rejected: the host \"$host\" matches the blocked pattern \"$needle\".");
        echo 'Blocked host.';
        return;
    }
}
ssrf_show(ssrf_fetch($url));
