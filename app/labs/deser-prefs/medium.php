<?php
require __DIR__ . '/Preferences.php';
$cookie = $_COOKIE['prefs'] ?? '';
$prefs = null;
if ($cookie !== '') {
    $data = base64_decode($cookie, true) ?: $cookie;
    if (str_starts_with($data, 'O:')) { // @sink  "no top-level objects"
        Trace::note('Rejected: the data is a top-level serialized object.');
    } else {
        Trace::step('Data passed to unserialize', $data, '');
        $prefs = unserialize($data);
    }
}
$prefs = is_object($prefs) ? $prefs : (is_array($prefs) ? ($prefs[0] ?? null) : null);
require __DIR__ . '/view.php';
