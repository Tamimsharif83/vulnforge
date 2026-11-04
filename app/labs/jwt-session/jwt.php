<?php
// Minimal JWT so the lab has no dependencies. Teaching only.
function b64url(string $data): string { return rtrim(strtr(base64_encode($data), '+/', '-_'), '='); }
function b64url_decode(string $data): string { return (string) base64_decode(strtr($data, '-_', '+/')); }
function jwt_make(array $claims, string $alg, string $secret): string
{
    $header = b64url(json_encode(['alg' => $alg, 'typ' => 'JWT']));
    $payload = b64url(json_encode($claims));
    $signing = "$header.$payload";
    $sig = $alg === 'none' ? '' : b64url(hash_hmac('sha256', $signing, $secret, true));
    return "$signing.$sig";
}
/** Returns the decoded claims if the token passes $verify, else null. */
function jwt_read(string $token, callable $verify): ?array
{
    $parts = explode('.', $token);
    if (count($parts) !== 3) { return null; }
    [$h, $p, $s] = $parts;
    $header = json_decode(b64url_decode($h), true) ?: [];
    $claims = json_decode(b64url_decode($p), true) ?: [];
    Trace::step('Decoded JWT', "header: " . json_encode($header) . "\nclaims: " . json_encode($claims) . "\nsignature present: " . ($s !== '' ? 'yes' : 'no'), '');
    return $verify($header, $claims, "$h.$p", $s) ? $claims : null;
}
