<?php
require __DIR__ . '/Preferences.php';
$cookie = $_COOKIE['prefs'] ?? '';
$prefs = null;
if ($cookie !== '') {
    $data = base64_decode($cookie, true) ?: $cookie;
    Trace::step('Data passed to unserialize', $data, '');
    $prefs = unserialize($data); // @sink
}
$prefs = is_object($prefs) ? $prefs : (is_array($prefs) ? ($prefs[0] ?? null) : null);
require __DIR__ . '/view.php';
