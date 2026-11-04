<?php
require __DIR__ . '/shared.php';
$next = $_GET['next'] ?? '/account';
do_redirect($next); // @sink  no validation
