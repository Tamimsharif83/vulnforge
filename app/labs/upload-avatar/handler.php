<?php
// Shared across levels. $accept(string $tmp, string $name): ?string returns the
// stored filename to keep, or null to reject. Each level supplies its own $accept.
$dir = __DIR__ . '/../../public/uploads';
$result = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $name = $_FILES['avatar']['name'];
    $tmp = $_FILES['avatar']['tmp_name'];
    $clientType = $_FILES['avatar']['type'];
    $stored = $accept($tmp, $name, $clientType);
    if ($stored === null) {
        http_response_code(400);
        $result = 'Rejected: that file was not accepted at this level.';
    } else {
        move_uploaded_file($tmp, "$dir/$stored");
        $url = '/uploads/' . $stored;
        Trace::step('Stored file', "$dir/$stored", '', 'Served at ' . $url);
        $ext = strtolower(pathinfo($stored, PATHINFO_EXTENSION));
        if (in_array($ext, ['php', 'phtml', 'php3', 'php4', 'php5', 'pht', 'phar'], true)) {
            Lab::markSolved('upload-avatar', current_level());
            Trace::note("An executable PHP file was stored. Request $url?c=cat+/opt/forge/flag.txt to run commands.");
        }
        $result = "Stored at $url";
    }
} else {
    $result = 'Send a multipart POST with an avatar file.';
}
Trace::note($result);
?>
<!DOCTYPE html>
<title>ForgeShop Profile</title>
<link rel="stylesheet" href="/assets/shop.css">
<h1>Profile avatar</h1>
<p><?= e($result) ?></p>
