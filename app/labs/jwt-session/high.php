<?php
require __DIR__ . '/common.php';
$secret = 'forge-2026'; $issueAlg = 'HS256';
// High: HS256 pinned, but the secret is still crackable with a wordlist.
$claims = $token === '' ? null : jwt_read($token, function ($header, $c, $signing, $sig) use ($secret) {
    if (($header['alg'] ?? '') !== 'HS256') { return false; }
    return hash_equals(b64url(hash_hmac('sha256', $signing, $secret, true)), $sig); // @sink
});
require __DIR__ . '/view.php';
