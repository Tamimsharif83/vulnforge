<?php
// Shared view. Runs the command the level built and shows the output.
Trace::step('Shell command executed', $cmd, $host ?? '');
$output = (string) shell_exec($cmd . ' 2>&1');
AppEvents::commandExec($cmd . ' 2>&1');
if (str_contains($output, trim((string) @file_get_contents(SECRET_FILE)))) {
    Lab::markSolved('cmdi-ping', current_level());
}
?>
<!DOCTYPE html>
<title>ForgeShop Status</title>
<link rel="stylesheet" href="/assets/shop.css?v=2">
<?= shop_header('Account') ?>
<h1>Connectivity check</h1>
<form action="/labs/cmdi-ping/run">
  <input name="host" value="<?= e($_GET['host'] ?? '') ?>" placeholder="Host or IP">
  <button>Ping</button>
</form>
<pre><?= e($output) ?></pre>
