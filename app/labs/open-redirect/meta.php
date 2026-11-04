<?php
return [
    'order' => 130,
    'title' => 'Post-Login Redirect: Open Redirect',
    'category' => 'Business Logic',
    'summary' => 'After an action the app redirects to a next= URL.',
    'severity' => 'Medium',
    'cwe' => 'CWE-601',
    'difficulty' => 'Beginner',
    'scenario' => 'The redirect sends the browser to the next parameter. Make it send the victim off-site to https://evil.example. The console shows the Location header without following it.',
    'feature' => '/labs/open-redirect/run?next=/account',
    'request' => ['method' => 'GET', 'path' => '/labs/open-redirect/run?next=/account'],
    'payloads' => ['https://evil.example/', '//evil.example/', '/\\/evil.example/', '/\\evil.example'],
    'hints' => [
        'The Location header echoes next directly. An absolute URL like https://evil.example/ redirects off-site.',
        'At Medium only values starting with / are allowed. A protocol-relative URL //evil.example also starts with / and the browser treats it as absolute.',
        'At High // is blocked, but browsers normalize backslashes to slashes. /\\evil.example becomes //evil.example in the browser.',
    ],
    'debrief' => 'The redirect target came from the request with weak validation, so you aimed it off-site. Attackers use open redirects for convincing phishing and to bypass allowlists in OAuth and SSO flows. The Secure level only redirects to a known set of relative paths.',
    'root_cause' => 'A redirect destination is taken from user input without validating it points within the application.',
    'fix' => 'Redirect only to allowlisted relative paths, or validate that the resolved URL host equals your own. Never place raw user input in a Location header.',
    'hunting' => 'Look for next, url, return, redirect, continue, dest, callback. Try an absolute URL, //host, /\\host, and whitespace tricks. Valuable in OAuth redirect_uri and login flows. Many programs accept open redirect as a chainable finding.',
    'report' => "Title: Open redirect in next parameter\nSeverity: Medium (CVSS 3.1 4.7)\nWeakness: CWE-601\n\nSteps to reproduce:\n1. Request /labs/open-redirect/run?next=https://evil.example/\n2. The response redirects to the external site.\n\nImpact: Phishing, and allowlist bypass in OAuth/SSO redirect flows.\n\nRemediation: Allowlist relative paths or verify the host equals your own.",
];
