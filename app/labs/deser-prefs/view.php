<?php
$leak = $GLOBALS['vf_deser_output'] ?? '';
if ($leak !== '' && str_contains($leak, trim((string) @file_get_contents(SECRET_FILE)))) {
    Lab::markSolved('deser-prefs', current_level());
}
$theme = ($prefs instanceof Preferences) ? $prefs->theme : 'dark';
echo '<!DOCTYPE html><title>ForgeShop Theme</title><link rel="stylesheet" href="/assets/shop.css?v=2">' . shop_header('Account') . '<h1>Preferences</h1>';
echo '<p>Active theme: <strong>' . e($theme) . '</strong></p>';
if ($leak !== '') { echo '<p>Debug log contents:</p><pre>' . e($leak) . '</pre>'; }
echo '<p>The prefs cookie carries a serialized Preferences object.</p>';
