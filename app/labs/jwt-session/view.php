<?php
// $claims is null (rejected) or the verified claims. Admin claims = forgery,
// because this server only ever issues role=user tokens.
$issued = jwt_make(['user' => 'guest42', 'role' => 'user'], $issueAlg, $secret);
if (is_array($claims) && ($claims['role'] ?? '') === 'admin') {
    Lab::markSolved('jwt-session', current_level());
    Trace::note('Accepted a token asserting role=admin. The server never issues admin tokens, so this is a forgery.');
}
header('Content-Type: text/html');
echo '<!DOCTYPE html><title>ForgeShop API</title><link rel="stylesheet" href="/assets/shop.css?v=2">' . shop_header('Account') . '<h1>Account API</h1>';
if (is_array($claims)) {
    echo '<p>Authenticated as <strong>' . e($claims['user'] ?? '?') . '</strong>, role <strong>' . e($claims['role'] ?? '?') . '</strong>.';
    echo ($claims['role'] ?? '') === 'admin' ? ' Admin panel unlocked.</p>' : '</p>';
} else {
    echo '<p>No valid token. You have been issued a user token below.</p>';
}
echo '<p>Your issued token:</p><pre class="wrap">' . e($issued) . '</pre>';
echo '<p>Send it back as: <code>Authorization: Bearer &lt;token&gt;</code></p>';
