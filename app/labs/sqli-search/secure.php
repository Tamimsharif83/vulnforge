<?php
// Secure: the term is bound as a parameter, so it can never become query syntax.
$q = $_GET['q'] ?? '';
$sql = 'SELECT id, name, category, price FROM products WHERE name LIKE ?';

$rows = Db::prepared($sql, ['%' . $q . '%']);
require __DIR__ . '/results.php';
