<?php
// High: allow a few formatting tags, but an <a href> keeps a javascript: URL.
$transform = fn (string $c): string => strip_tags($c, '<b><i><a><em>'); // @sink
require __DIR__ . '/render.php';
