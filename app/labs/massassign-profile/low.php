<?php
require __DIR__ . '/profile.php';
$p = load_profile();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Attribute names map to case-insensitive columns, so keys are normalized.
    foreach ($_POST as $key => $value) { $p[strtolower($key)] = $value; } // @sink  binds every field
    save_profile($p);
}
require __DIR__ . '/view.php';
