<?php
require __DIR__ . '/shared.php';
$next = $_GET['next'] ?? '/account';
// High: must start with / and not //. Backslash tricks slip past this.
if (!str_starts_with($next, '/') || str_starts_with($next, '//')) { $next = '/account'; } // @sink
do_redirect($next);
