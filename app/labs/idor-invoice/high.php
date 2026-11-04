<?php
const SESSION_UID = 2;
$lookedUp = $_GET['id'] ?? '2';
$row = Db::prepared('SELECT * FROM invoices WHERE id = ?', [$lookedUp])[0] ?? null;
// High: checks ownership, but trusts an identity supplied in the request.
$claimedUid = (int) ($_GET['as'] ?? SESSION_UID); // @sink
if ($row && $row['owner_uid'] != $claimedUid) {
    Trace::note("Ownership check used as=$claimedUid from the request, not the session.");
    $row = null;
}
require __DIR__ . '/view.php';
