<?php
require __DIR__ . '/common.php';
$secret = 'forge-dev-secret'; $issueAlg = 'HS256';
// Low: if the token says alg:none, trust it unsigned. Classic broken verify.
$claims = $token === '' ? null : jwt_read($token, function ($header, $c, $signing, $sig) use ($secret) {
    if (($header['alg'] ?? '') === 'none') { return true; } // @sink
    return hash_equals(b64url(hash_hmac('sha256', $signing, $secret, true)), $sig);
});
require __DIR__ . '/view.php';
