<?php
// Shared by every level. Only the query construction differs between them.
$adminHash = Db::pdo()->query("SELECT password_md5 FROM users WHERE username = 'admin'")->fetchColumn();
foreach ($rows as $row) {
    if (in_array($adminHash, array_map('strval', $row), true)) {
        Lab::markSolved('sqli-search', current_level());
        break;
    }
}
// Does a returned row look like a normal product (4 columns: id,name,category,price)?
$looksLikeProduct = static fn (array $r): bool =>
    count($r) === 4 && isset($r['price']) && is_numeric($r['price']);
?>
<!DOCTYPE html>
<title>ForgeShop Search</title>
<link rel="stylesheet" href="/assets/shop.css?v=2">
<?= shop_header('Shop') ?>
  <h1>Browse the catalog</h1>
  <p class="lede">Hot-swappable keyboards, docks, and desk gear, forged for your setup.</p>
  <form action="/labs/sqli-search/run">
    <input name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Search products" autocomplete="off">
    <button>Search</button>
  </form>
  <?php if (!$rows): ?>
    <p class="notice">No products matched that search.</p>
  <?php else: ?>
    <div class="products">
      <?php foreach ($rows as $row):
          $vals = array_values($row); ?>
        <?php if ($looksLikeProduct($row)): ?>
          <article class="product">
            <div class="thumb"><?= e(mb_substr(trim((string) $row['name']), 0, 1)) ?></div>
            <div class="body">
              <span class="name"><?= e((string) $row['name']) ?></span>
              <span class="cat"><span class="badge"><?= e((string) $row['category']) ?></span></span>
              <div class="foot">
                <span class="price">$<?= e(number_format((float) $row['price'], 2)) ?></span>
                <button class="ghost" type="button">Add</button>
              </div>
            </div>
          </article>
        <?php else: ?>
          <article class="product"><div class="body">
            <?php foreach ($vals as $value): ?><div class="name"><?= e((string) $value) ?></div><?php endforeach; ?>
          </div></article>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
