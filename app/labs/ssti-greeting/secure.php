<?php
$tpl = $_GET['tpl'] ?? 'Hello {{name}}';
// Secure: substitute only allowlisted variable names; never evaluate expressions.
$vars = ['name' => 'guest', 'shop' => 'ForgeShop'];
$rendered = preg_replace_callback('/\{\{\s*(\w+)\s*\}\}/', function ($m) use ($vars) {
    return isset($vars[$m[1]]) ? e($vars[$m[1]]) : '';
}, e($tpl)); // @sink  (input is HTML-encoded; only known vars are filled)
require __DIR__ . '/view.php';
