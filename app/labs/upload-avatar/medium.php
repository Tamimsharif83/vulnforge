<?php
// Medium: trust the Content-Type the client sent for the file part.
$accept = function (string $tmp, string $name, string $type): ?string {
    if (!str_starts_with($type, 'image/')) { // @sink
        return null;
    }
    return basename($name);
};
require __DIR__ . '/handler.php';
