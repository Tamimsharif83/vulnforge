<?php
// Solve by real elapsed time: if the student made the DB pause, the query took
// far longer than any legitimate lookup. No keyword matching is involved.
if ($elapsedMs > 1500) {
    Lab::markSolved('sqli-blind', current_level());
    Trace::note("The query took {$elapsedMs} ms. A delay this long means your injected SLEEP executed on the server.");
}
?>
<!DOCTYPE html>
<title>ForgeShop Signup</title>
<link rel="stylesheet" href="/assets/shop.css?v=2">
<?= shop_header('Account') ?>
<h1>Choose a username</h1>
<form action="/labs/sqli-blind/run"><input name="username" value="<?= e($_GET['username'] ?? '') ?>"><button>Check</button></form>
<p><?= $taken ? 'That username is taken.' : 'That username is available.' ?></p>
