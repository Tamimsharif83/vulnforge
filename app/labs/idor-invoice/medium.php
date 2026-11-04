<?php
const SESSION_UID = 2;
// Medium: the id is base64-encoded (obscurity), still no ownership check.
$lookedUp = base64_decode($_GET['id'] ?? 'Mg==', true) ?: '0';
$row = Db::prepared('SELECT * FROM invoices WHERE id = ?', [$lookedUp])[0] ?? null; // @sink
require __DIR__ . '/view.php';
