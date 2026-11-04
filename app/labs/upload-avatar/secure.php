<?php
// Secure: allowlist by real image content, force the extension, random name.
$accept = function (string $tmp, string $name, string $type): ?string {
    $info = @getimagesize($tmp);
    $map = [IMAGETYPE_PNG => 'png', IMAGETYPE_JPEG => 'jpg', IMAGETYPE_GIF => 'gif'];
    if ($info === false || !isset($map[$info[2]])) { // @sink
        return null;
    }
    return bin2hex(random_bytes(8)) . '.' . $map[$info[2]];
};
require __DIR__ . '/handler.php';
