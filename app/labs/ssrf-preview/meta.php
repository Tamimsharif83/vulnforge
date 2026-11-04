<?php
return [
    'order' => 60,
    'title' => 'Link Preview: SSRF',
    'category' => 'SSRF',
    'summary' => 'The link preview fetches a URL you supply, from the server.',
    'severity' => 'High',
    'cwe' => 'CWE-918',
    'difficulty' => 'Intermediate',
    'scenario' => 'The preview tool fetches your URL server-side and shows the response. The server sits on an internal network with services the internet cannot see. Reach one of them: the internal admin API (http://internal-api/) or the cloud metadata service (http://169.254.169.254/latest/meta-data/iam/security-credentials/forge-role).',
    'feature' => '/labs/ssrf-preview/run?url=http://internal-api/',
    'request' => ['method' => 'GET', 'path' => '/labs/ssrf-preview/run?url=http://internal-api/'],
    'payloads' => ['http://internal-api/', 'http://169.254.169.254/latest/meta-data/iam/security-credentials/forge-role', 'http://metadata/latest/x', 'http://2852039166/latest/x'],
    'hints' => [
        'The request leaves from the server, not your browser, so it can reach hosts you cannot. Point it at http://internal-api/ and read the response in the preview.',
        'At Medium the loopback address and the literal metadata IP are blocked. The metadata host also answers to the name metadata, and internal-api is not blocked at all.',
        'At High the service names and the 169.254 range are on a denylist, but it matches text, not resolved addresses. 169.254.169.254 as a single decimal integer (2852039166) resolves to the same host.',
    ],
    'debrief' => 'The server fetched a URL you controlled, so you reached internal-only services behind its network boundary, including cloud credentials. Denylists of hostnames and IP strings fail because one address has many spellings (names, decimal, octal, IPv6). The Secure level resolves the host and rejects any private, loopback, or link-local address, and allowlists the scheme.',
    'root_cause' => 'The server makes a request to a user-supplied destination without restricting it to intended, external targets.',
    'fix' => 'Resolve the host and block private/loopback/link-local ranges on the resolved IP, allowlist schemes and ports, disable redirects or re-validate after each, and prefer an allowlist of permitted destinations.',
    'hunting' => 'Look for features that fetch URLs: link previews, webhooks, avatar-from-URL, PDF/HTML renderers, import-from-URL, XML/SVG processors. Point them at 169.254.169.254, 127.0.0.1, and internal names. Try IP encodings and redirects to bypass filters. Blind cases: use an out-of-band listener. In source, grep for file_get_contents, curl_exec, fopen on request-controlled URLs.',
    'report' => "Title: Server-side request forgery in link preview\nSeverity: High (CVSS 3.1 8.6)\nWeakness: CWE-918\n\nSteps to reproduce:\n1. Request /labs/ssrf-preview/run?url=http://169.254.169.254/latest/meta-data/iam/security-credentials/forge-role\n2. The server returns internal cloud credentials.\n\nImpact: Access to internal services and cloud metadata credentials; pivot into the internal network.\n\nRemediation: Resolve-and-validate against private ranges, scheme/port allowlist, no open redirects.",
];
