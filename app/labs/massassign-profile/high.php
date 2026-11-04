<?php
require __DIR__ . '/profile.php';
$p = load_profile();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $key => $value) {
        if (strtolower($key) === 'role') { continue; } // @sink  guards role, not balance
        $p[strtolower($key)] = is_numeric($value) ? (int) $value : $value;
    }
    save_profile($p);
}
require __DIR__ . '/view.php';
