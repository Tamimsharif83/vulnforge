<?php
if (require __DIR__ . '/run_prelude.php') return;
// Medium: a <script> pattern is removed, then written as HTML.
$sink = "out.innerHTML = value.replace(/<script.*?>/gi, '');"; // @sink
require __DIR__ . '/page.php';
