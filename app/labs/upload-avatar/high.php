<?php
// High: blacklist dangerous extensions, but the list is incomplete.
$accept = function (string $tmp, string $name, string $type): ?string {
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $blocked = ['php', 'php3', 'php4', 'php5', 'pht']; // @sink
    if (in_array($ext, $blocked, true)) {
        return null;
    }
    return basename($name);
};
require __DIR__ . '/handler.php';
