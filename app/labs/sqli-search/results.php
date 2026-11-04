<?php
// Shared by every level. Only the query construction differs between them.
$adminHash = Db::pdo()->query("SELECT password_md5 FROM users WHERE username = 'admin'")->fetchColumn();
foreach ($rows as $row) {
    if (in_array($adminHash, array_map('strval', $row), true)) {
        Lab::markSolved('sqli-search', current_level());
        break;
    }
}
?>
<!DOCTYPE html>
<title>ForgeShop Search</title>
<link rel="stylesheet" href="/assets/shop.css">
<h1>ForgeShop</h1>
<form action="/labs/sqli-search/run">
  <input name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Search products">
  <button>Search</button>
</form>
<table>
  <tr><th>ID</th><th>Name</th><th>Category</th><th>Price</th></tr>
  <?php foreach ($rows as $row): ?>
    <tr><?php foreach ($row as $value): ?><td><?= e((string) $value) ?></td><?php endforeach; ?></tr>
  <?php endforeach; ?>
</table>
