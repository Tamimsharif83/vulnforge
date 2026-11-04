<?php
require __DIR__ . '/common.php';
// Secure: a 32-byte random secret, generated once and stable, HS256 pinned.
$secretFile = '/tmp/vf-jwt-secret';
if (!is_file($secretFile)) { file_put_contents($secretFile, bin2hex(random_bytes(32))); }
$secret = (string) file_get_contents($secretFile);
$issueAlg = 'HS256';
$claims = $token === '' ? null : jwt_read($token, function ($header, $c, $signing, $sig) use ($secret) {
    if (($header['alg'] ?? '') !== 'HS256') { return false; }
    return hash_equals(b64url(hash_hmac('sha256', $signing, $secret, true)), $sig); // @sink
});
require __DIR__ . '/view.php';
