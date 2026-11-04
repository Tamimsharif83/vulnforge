<?php
function cors_emit(?string $allowOrigin, string $reason): void
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($allowOrigin !== null) {
        header('Access-Control-Allow-Origin: ' . $allowOrigin);
        header('Access-Control-Allow-Credentials: true');
        // Reflecting an untrusted origin with credentials is the vulnerability.
        if ($origin !== '' && $allowOrigin === $origin && $origin !== ('https://' . ($_SERVER['HTTP_HOST'] ?? ''))
            && !str_starts_with($origin, 'http://' . ($_SERVER['HTTP_HOST'] ?? ''))) {
            Lab::markSolved('cors-api', current_level());
        }
    }
    Trace::step('CORS decision', "Request Origin: " . ($origin ?: '(none)') . "\nAccess-Control-Allow-Origin: " . ($allowOrigin ?? '(not set)') . "\nAccess-Control-Allow-Credentials: " . ($allowOrigin !== null ? 'true' : '(not set)'), $origin, $reason);
    header('Content-Type: application/json');
    echo json_encode(['account' => 'alice', 'email' => 'alice@forgeshop.local', 'balance' => 100]);
}
