<?php
if (require __DIR__ . '/run_prelude.php') return;
// Low: the value is written as HTML.
$sink = 'out.innerHTML = value;'; // @sink
require __DIR__ . '/page.php';
