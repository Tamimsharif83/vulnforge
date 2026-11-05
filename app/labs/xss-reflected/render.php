<?php
// $context describes where input landed; $body is the HTML fragment shown.
if (xss_executes($_GET['q'] ?? '', $body)) {
    Lab::markSolved('xss-reflected', current_level());
}
Trace::step('Reflected sink and context', $body, $raw ?? ($_GET['q'] ?? ''), $context);
?>
<!DOCTYPE html>
<title>ForgeShop Search</title>
<link rel="stylesheet" href="/assets/shop.css">
<?= shop_header('Shop') ?>
<h1>Search</h1>
<form action="/labs/xss-reflected/run"><input name="q" value="<?= e($_GET['q'] ?? '') ?>"><button>Search</button></form>
<?= $body ?>
