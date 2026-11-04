<?php
if (require __DIR__ . '/run_prelude.php') return;
// Secure: textContent never parses the value as markup.
$sink = 'out.textContent = value;'; // @sink
require __DIR__ . '/page.php';
