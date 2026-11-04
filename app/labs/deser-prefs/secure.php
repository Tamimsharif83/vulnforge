<?php
require __DIR__ . '/Preferences.php';
$cookie = $_COOKIE['prefs'] ?? '';
$prefs = null;
if ($cookie !== '') {
    $data = base64_decode($cookie, true) ?: $cookie;
    Trace::step('Data passed to unserialize', $data, '');
    // Secure: no classes may be instantiated, so no magic methods fire.
    $decoded = unserialize($data, ['allowed_classes' => false]); // @sink
    Trace::note('allowed_classes=false: any object becomes __PHP_Incomplete_Class and no code runs.');
}
require __DIR__ . '/view.php';
