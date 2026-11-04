<?php
require __DIR__ . '/Preferences.php';
$cookie = $_COOKIE['prefs'] ?? '';
$prefs = null;
if ($cookie !== '') {
    $data = base64_decode($cookie, true) ?: $cookie;
    if (str_starts_with($data, 'O:')) {
        Trace::note('Rejected: the data is a top-level serialized object.');
    } else {
        Trace::step('Data passed to unserialize', $data, '');
        // High: only Preferences may deserialize, but it is itself the gadget.
        $prefs = unserialize($data, ['allowed_classes' => ['Preferences']]); // @sink
    }
}
$prefs = is_object($prefs) ? $prefs : (is_array($prefs) ? ($prefs[0] ?? null) : null);
require __DIR__ . '/view.php';
