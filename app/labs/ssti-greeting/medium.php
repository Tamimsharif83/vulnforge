<?php
require __DIR__ . '/engine.php';
$tpl = $_GET['tpl'] ?? 'Hello {{7*7}}';
// Medium: block the obvious command functions.
$rendered = ssti_render($tpl, ['system', 'exec', 'shell_exec', 'passthru', '`']);
require __DIR__ . '/view.php';
