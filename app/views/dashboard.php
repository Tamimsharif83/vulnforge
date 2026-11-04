<?php
$title = 'Labs - VulnForge';
$groups = [];
foreach ($labs as $lab) {
    $groups[$lab['category']][] = $lab;
}
$total = count($labs) * count(LEVELS);
$done = array_sum(array_map('count', $solved));
?>
<section class="intro">
  <h1>Workbench</h1>
  <p>Each lab is a real feature of ForgeShop running against a real server. Attack it, read what the server did, then read the fix.</p>
  <p class="metric">Coverage <strong><?= $done ?></strong> of <?= $total ?> lab levels</p>
</section>
<?php foreach ($groups as $category => $items): ?>
  <section class="group">
    <h2><?= e($category) ?></h2>
    <div class="cards">
      <?php foreach ($items as $lab): $levels = $solved[$lab['slug']] ?? []; ?>
        <a class="card" href="/labs/<?= e($lab['slug']) ?>">
          <h3><?= e($lab['title']) ?></h3>
          <p><?= e($lab['summary']) ?></p>
          <div class="chips">
            <span class="chip sev-<?= e(strtolower($lab['severity'])) ?>"><?= e($lab['severity']) ?></span>
            <span class="chip"><?= e($lab['cwe']) ?></span>
            <span class="chip"><?= count($levels) ?> of <?= count(LEVELS) - 1 ?> solved</span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </section>
<?php endforeach; ?>
