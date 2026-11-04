<?php
// Low: keep the client's filename, store it, no checks at all.
$accept = function (string $tmp, string $name, string $type): ?string {
    return basename($name); // @sink
};
require __DIR__ . '/handler.php';
