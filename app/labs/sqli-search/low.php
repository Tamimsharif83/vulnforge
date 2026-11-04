<?php
// Low: the search term goes into the query unchanged.
$q = $_GET['q'] ?? '';
$sql = "SELECT id, name, category, price FROM products WHERE name LIKE '%$q%'"; // @sink

try {
    $rows = Db::raw($sql, $q);
} catch (PDOException $error) {
    http_response_code(500);
    echo 'Database error: ' . e($error->getMessage());
    return;
}
require __DIR__ . '/results.php';
