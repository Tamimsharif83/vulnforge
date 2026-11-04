<?php
require __DIR__ . '/profile.php';
$p = load_profile();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $key => $value) {
        if ($key === 'role') { continue; } // @sink  blacklist checks the raw key
        $p[strtolower($key)] = $value; // but the model normalizes names to columns
    }
    save_profile($p);
}
require __DIR__ . '/view.php';
