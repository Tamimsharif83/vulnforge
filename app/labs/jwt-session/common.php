<?php
require __DIR__ . '/jwt.php';
$auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if ($auth === '' && function_exists('apache_request_headers')) {
    foreach (apache_request_headers() as $name => $value) {
        if (strcasecmp($name, 'Authorization') === 0) { $auth = $value; break; }
    }
}
$token = str_starts_with($auth, 'Bearer ') ? substr($auth, 7) : ($_GET['token'] ?? '');
