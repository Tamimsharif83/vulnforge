<?php
if (str_contains($rendered, trim((string) @file_get_contents(SECRET_FILE)))) {
    Lab::markSolved('ssti-greeting', current_level());
}
Trace::step('Template rendered on the server', $tpl, '', 'Expressions inside {{ }} were evaluated.');
echo '<!DOCTYPE html><title>ForgeShop Greeting</title><link rel="stylesheet" href="/assets/shop.css"><h1>Greeting preview</h1>';
echo '<form action="/labs/ssti-greeting/run"><input name="tpl" value="' . e($tpl) . '" style="flex:1"><button>Render</button></form>';
echo '<div style="margin-top:12px;padding:10px;border:1px solid #ddd;border-radius:6px">' . $rendered . '</div>';
