<?php
if (require __DIR__ . '/run_prelude.php') return;
// High: strips on<word>= handlers, but only when there is no whitespace.
$sink = "out.innerHTML = value.replace(/on\\w+=/gi, '').replace(/<script/gi, '');"; // @sink
require __DIR__ . '/page.php';
