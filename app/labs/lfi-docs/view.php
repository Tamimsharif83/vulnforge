<?php
// Shared view. $contents and $resolved come from the level.
if (str_contains($contents, trim((string) @file_get_contents(SECRET_FILE)))
    || str_contains($contents, 'root:x:0:0:')) {
    Lab::markSolved('lfi-docs', current_level());
}
?>
<!DOCTYPE html>
<title>ForgeShop Help</title>
<link rel="stylesheet" href="/assets/shop.css?v=2">
<?= shop_header('Account') ?>
<h1>Help centre</h1>
<nav><a href="/labs/lfi-docs/run?doc=welcome.txt">Welcome</a> | <a href="/labs/lfi-docs/run?doc=returns.txt">Returns</a></nav>
<pre><?= e($contents) ?></pre>
