<?php
return [
    'order' => 131,
    'title' => 'Account API: CORS Misconfiguration',
    'category' => 'Business Logic',
    'summary' => 'A JSON API reflects the Origin and allows credentials.',
    'severity' => 'High',
    'cwe' => 'CWE-942',
    'difficulty' => 'Intermediate',
    'scenario' => 'This API returns account data and sets CORS headers from your Origin. If it reflects an attacker Origin while allowing credentials, any site can read a logged-in victim\'s data. Send an Origin header and inspect the Access-Control-Allow-Origin response.',
    'feature' => '/labs/cors-api/run',
    'request' => ['method' => 'GET', 'path' => '/labs/cors-api/run', 'headers' => 'Origin: https://evil.example'],
    'payloads' => ['Origin: https://evil.example', 'Origin: https://evil-forgeshop.local', 'Origin: https://forgeshop.local.evil.example', 'Origin: https://forgeshop.local'],
    'hints' => [
        'Send Origin: https://evil.example and look at Access-Control-Allow-Origin in the response. If it echoes your Origin and Allow-Credentials is true, the API is readable cross-site.',
        'At Medium only Origins ending in forgeshop.local are allowed. evil-forgeshop.local ends with it too.',
        'At High the check is a substring match. https://forgeshop.local.evil.example contains forgeshop.local.',
    ],
    'debrief' => 'The API reflected an untrusted Origin into Access-Control-Allow-Origin while allowing credentials, so a malicious site could read responses in a victim\'s authenticated context. Suffix and substring checks are bypassable with lookalike domains. The Secure level compares the Origin against an exact allowlist and never reflects an untrusted value with credentials.',
    'root_cause' => 'The Access-Control-Allow-Origin value is derived from the request Origin without a strict allowlist, together with Allow-Credentials: true.',
    'fix' => 'Allowlist exact origins; echo only a matched origin. Never combine a reflected or wildcard origin with Allow-Credentials: true.',
    'hunting' => 'Send Origin: https://evil.example and check ACAO/ACAC. Try null, subdomains, suffix and substring lookalikes. If ACAO reflects your origin with credentials, prove it by reading an authenticated endpoint from an attacker page.',
    'report' => "Title: CORS misconfiguration reflects arbitrary Origin with credentials\nSeverity: High (CVSS 3.1 7.5)\nWeakness: CWE-942\n\nSteps to reproduce:\n1. Send Origin: https://evil.example to the API.\n2. Response returns Access-Control-Allow-Origin: https://evil.example and Allow-Credentials: true.\n\nImpact: Any site can read authenticated API responses for a logged-in victim.\n\nRemediation: Exact-match origin allowlist; never reflect untrusted origins with credentials.",
];
