<?php
require __DIR__ . '/engine.php';
$tpl = $_GET['tpl'] ?? 'Hello {{7*7}}';
$rendered = ssti_render($tpl, []); // no blocklist
require __DIR__ . '/view.php';
