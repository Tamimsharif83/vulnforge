<?php
// $user is the row the query returned (or false). Auth bypass = a row came back
// whose stored hash does not match the submitted password.
$submitted = (string) ($_POST['password'] ?? '');
$ok = is_array($user);
if ($ok && md5($submitted) !== ($user['password_md5'] ?? '')) {
    Lab::markSolved('sqli-login', current_level());
    Trace::note('Auth bypass: a user row was returned although the password did not match its stored hash.');
}
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $attempted = (string) ($_POST['username'] ?? '');
    if ($ok) {
        Logger::setUser((string) ($user['username'] ?? $attempted), isset($user['id']) ? (string) $user['id'] : null);
        AppEvents::auth('user-login', 'success', (string) ($user['username'] ?? $attempted));
    } else {
        AppEvents::auth('user-login', 'failure', $attempted, 'invalid-credentials');
    }
}
?>
<!DOCTYPE html>
<title>ForgeShop Login</title>
<link rel="stylesheet" href="/assets/shop.css?v=2">
<?= shop_header('Account') ?>
<h1>Sign in</h1>
<?php if ($ok): ?>
  <p>Signed in as <strong><?= e($user['username']) ?></strong> (role: <?= e($user['role']) ?>).</p>
<?php else: ?>
  <p>Invalid credentials.</p>
<?php endif; ?>
<form method="post" action="/labs/sqli-login/run">
  <input name="username" placeholder="Username">
  <input name="password" placeholder="Password" type="text">
  <button>Sign in</button>
</form>
