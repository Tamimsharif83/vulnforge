<?php
// Shared fetcher. $url has already passed the level's guard. Redirects are not
// followed, to keep the lesson about the destination the student chose.
function ssrf_fetch(string $url): string
{
    $ctx = stream_context_create(['http' => ['timeout' => 5, 'follow_location' => 0, 'ignore_errors' => true]]);
    $host = parse_url($url, PHP_URL_HOST) ?: '(none)';
    $resolved = $host !== '(none)' ? gethostbyname($host) : '(none)';
    Trace::step('Server-side request', "GET $url  (host $host resolves to $resolved)", $url);
    $body = @file_get_contents($url, false, $ctx);
    $status = null;
    if (isset($http_response_header[0]) && preg_match('#\s(\d{3})\s#', $http_response_header[0], $m)) {
        $status = (int) $m[1];
    }
    AppEvents::outbound($url, $host === '(none)' ? null : $host, $resolved === '(none)' ? null : $resolved, $status);
    return $body === false ? '(request failed)' : $body;
}
function ssrf_show(string $body): void
{
    foreach (['FORGE-INTERNAL{', 'FORGE-METADATA{'] as $marker) {
        if (str_contains($body, $marker)) {
            Lab::markSolved('ssrf-preview', current_level());
        }
    }
    echo '<!DOCTYPE html><title>Link preview</title><link rel="stylesheet" href="/assets/shop.css?v=2">' . shop_header('Account') . '';
    echo '<h1>Link preview</h1><form action="/labs/ssrf-preview/run"><input name="url" value="' . e($_GET['url'] ?? '') . '" style="flex:1"><button>Preview</button></form>';
    echo '<pre>' . e($body) . '</pre>';
}
