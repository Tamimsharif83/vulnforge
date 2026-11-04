<?php
return [
    'order' => 80,
    'title' => 'Session Token: JWT Forgery',
    'category' => 'Authentication',
    'summary' => 'The API authenticates you with a JSON Web Token.',
    'severity' => 'Critical',
    'cwe' => 'CWE-347',
    'difficulty' => 'Advanced',
    'scenario' => 'This endpoint issues you a token for a normal user and trusts the token on later requests. The server never issues admin tokens. Present a token that makes the server treat you as admin. Send it as the Authorization: Bearer header. The issued token and the current level are shown in the response.',
    'feature' => '/labs/jwt-session/run',
    'request' => ['method' => 'GET', 'path' => '/labs/jwt-session/run', 'headers' => 'Authorization: Bearer PASTE_TOKEN_HERE'],
    'payloads' => ['eyJhbGciOiJub25lIiwidHlwIjoiSldUIn0.eyJ1c2VyIjoiYXR0YWNrZXIiLCJyb2xlIjoiYWRtaW4ifQ.', 'alg:none', 'HS256 weak secret', 'Authorization: Bearer <token>'],
    'hints' => [
        'Decode the issued token at jwt.io or by base64url-decoding each part. The role claim decides access. The What Happened tab shows the decoded header and claims.',
        'At Low the server honours alg:none and skips the signature. Build a token with header {"alg":"none","typ":"JWT"} and claims {"role":"admin"}, leaving the signature empty. The first payload is exactly that.',
        'At Medium and High the server requires HS256 but the signing secret is weak. Crack it (hashcat -m 16500, or try secret/forge-2026), then sign your own admin claims with it.',
    ],
    'debrief' => 'The server trusted the token without a sound signature check: it either honoured alg:none or used a guessable secret, so you minted your own admin token. Tokens are bearer credentials; their integrity depends entirely on a verified signature with a strong secret and a pinned algorithm. The Secure level pins HS256 and uses a 32-byte random secret, so forgery fails.',
    'root_cause' => 'Token signatures are not verified correctly: alg:none is accepted, or the signing key is weak enough to guess or crack.',
    'fix' => 'Pin the expected algorithm server-side, reject alg:none, and sign with a high-entropy secret (or asymmetric keys). Verify the signature before trusting any claim.',
    'hunting' => 'Decode every JWT you see. Try alg:none and the empty-signature trick. Crack HS256 secrets with hashcat mode 16500 or jwt_tool. Look for algorithm confusion (RS256 verified as HS256 using the public key). Check that claims like role, user, and exp are actually verified.',
    'report' => "Title: JWT forgery via alg:none and weak secret\nSeverity: Critical (CVSS 3.1 9.8)\nWeakness: CWE-347\n\nSteps to reproduce:\n1. Send Authorization: Bearer <token with alg=none, role=admin, empty signature>.\n2. The server grants admin access.\n\nImpact: Full authentication bypass and privilege escalation.\n\nRemediation: Pin the algorithm, reject none, use a strong secret, verify signatures.",
];
