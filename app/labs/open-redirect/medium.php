<?php
require __DIR__ . '/shared.php';
$next = $_GET['next'] ?? '/account';
// Medium: must start with a slash. //evil.example still qualifies.
if (!str_starts_with($next, '/')) { $next = '/account'; } // @sink
do_redirect($next);
