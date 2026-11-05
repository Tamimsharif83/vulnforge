<?php
// $sink is the JavaScript expression that renders the theme value into #out.
// It is shown verbatim in the Source tab, so each level's client code is visible.
?>
<!DOCTYPE html>
<title>ForgeShop Theme</title>
<link rel="stylesheet" href="/assets/shop.css">
<?= shop_header('Account') ?>
<h1>Theme preview</h1>
<p>Enter a theme label; it is rendered live below.</p>
<form onsubmit="apply();return false;">
  <input id="theme" value="Midnight" style="flex:1">
  <button>Apply</button>
</form>
<div id="out" style="margin-top:12px;padding:10px;border:1px solid #ddd;border-radius:6px"></div>
<script>
// A same-origin helper the challenge asks you to trigger from your payload.
window.forgeSolve = function () {
  fetch('/labs/xss-dom/run?forge_solve=1').then(function () {
    try { parent.postMessage('vf-solved', '*'); } catch (e) {}
  });
};
function apply() {
  var value = document.getElementById('theme').value;
  var out = document.getElementById('out');
  <?= $sink ?>
}
apply();
</script>
