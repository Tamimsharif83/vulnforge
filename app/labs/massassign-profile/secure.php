<?php
require __DIR__ . '/profile.php';
$p = load_profile();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['email', 'bio'] as $key) { // @sink  allowlist only
        if (isset($_POST[$key])) { $p[$key] = (string) $_POST[$key]; }
    }
    save_profile($p);
}
require __DIR__ . '/view.php';
