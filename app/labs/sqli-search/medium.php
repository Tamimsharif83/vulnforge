<?php
// Medium: a keyword filter, but str_replace only matches lowercase.
$q = str_replace(['union', 'select'], '', $_GET['q'] ?? '');
$sql = "SELECT id, name, category, price FROM products WHERE name LIKE '%$q%'"; // @sink

try {
    $rows = Db::raw($sql, $q);
} catch (PDOException $error) {
    http_response_code(500);
    echo 'The search could not be completed.';
    return;
}
require __DIR__ . '/results.php';
