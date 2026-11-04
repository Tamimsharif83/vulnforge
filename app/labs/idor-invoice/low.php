<?php
const SESSION_UID = 2;
$lookedUp = $_GET['id'] ?? '2';
// Low: fetch by id with no ownership check.
$row = Db::prepared('SELECT * FROM invoices WHERE id = ?', [$lookedUp])[0] ?? null; // @sink
require __DIR__ . '/view.php';
