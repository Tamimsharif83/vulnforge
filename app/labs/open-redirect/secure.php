<?php
require __DIR__ . '/shared.php';
$next = $_GET['next'] ?? '/account';
// Secure: only allowlisted relative paths are honoured.
$allowed = ['/account', '/', '/labs/open-redirect/run'];
if (!in_array($next, $allowed, true)) {
    Trace::note("Rejected next=$next; redirecting to the default instead.");
    $next = '/account';
}
do_redirect($next); // @sink
