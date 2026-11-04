<?php
require __DIR__ . '/common.php';
$secret = 'secret'; $issueAlg = 'HS256';
// Medium: alg:none is rejected, but the HMAC secret is a dictionary word.
$claims = $token === '' ? null : jwt_read($token, function ($header, $c, $signing, $sig) use ($secret) {
    if (($header['alg'] ?? '') !== 'HS256') { return false; }
    return hash_equals(b64url(hash_hmac('sha256', $signing, $secret, true)), $sig); // @sink
});
require __DIR__ . '/view.php';
