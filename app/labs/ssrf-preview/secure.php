<?php
require __DIR__ . '/fetch.php';
// Secure: allowlist the scheme, resolve the host, reject private/reserved IPs.
$url = $_GET['url'] ?? 'http://internal-api/';
$parts = parse_url($url);
$scheme = strtolower($parts['scheme'] ?? '');
$host = $parts['host'] ?? '';
$ip = $host !== '' ? gethostbyname($host) : '';
$public = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
if (!in_array($scheme, ['http', 'https'], true) || $public === false) {
    http_response_code(400);
    Trace::note("Rejected: \"$host\" resolves to $ip, which is not a permitted public address.");
    echo 'Blocked host.';
    return;
}
ssrf_show(ssrf_fetch($url));
