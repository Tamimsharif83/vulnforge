<?php
// Marks solved if the Location would send the browser to an external host.
// Mirrors browser normalization: backslashes become slashes.
function redirect_is_external(string $location): bool
{
    $norm = str_replace('\\', '/', $location);
    if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $norm)) {
        $host = parse_url($norm, PHP_URL_HOST);
        return $host !== null && $host !== '' && $host !== ($_SERVER['HTTP_HOST'] ?? '');
    }
    return str_starts_with($norm, '//') && !str_starts_with($norm, '///');
}
function do_redirect(string $location): void
{
    if (redirect_is_external($location)) {
        Lab::markSolved('open-redirect', current_level());
        Trace::note('The Location points to an external host; the browser would leave the site.');
    }
    Trace::step('Location header set', "Location: $location", $location);
    header('Location: ' . $location);
    http_response_code(302);
    echo 'Redirecting to ' . e($location);
}
