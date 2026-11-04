<?php
require __DIR__ . '/engine.php';
$tpl = $_GET['tpl'] ?? 'Hello {{7*7}}';
// High: a longer denylist, still incomplete (file(), fopen, etc. remain).
$rendered = ssti_render($tpl, ['system', 'exec', 'shell_exec', 'passthru', 'popen', 'proc_open', '`', 'file_get_contents', 'readfile', 'include', 'require']);
require __DIR__ . '/view.php';
