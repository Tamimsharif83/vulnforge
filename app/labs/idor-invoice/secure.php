<?php
const SESSION_UID = 2;
$lookedUp = $_GET['id'] ?? '2';
// Secure: scope the lookup to the session user; others' ids return nothing.
$row = Db::prepared('SELECT * FROM invoices WHERE id = ? AND owner_uid = ?', [$lookedUp, SESSION_UID])[0] ?? null; // @sink
require __DIR__ . '/view.php';
