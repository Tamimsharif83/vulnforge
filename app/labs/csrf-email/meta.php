<?php
return [
    'order' => 120,
    'title' => 'Change Email: CSRF',
    'category' => 'CSRF',
    'summary' => 'The change-email action accepts a simple POST.',
    'severity' => 'Medium',
    'cwe' => 'CWE-352',
    'difficulty' => 'Intermediate',
    'scenario' => 'A state-changing action (change email) should not be triggerable by another site. Issue a request that an attacker page could forge: with no token, with a guessable token, or with a stripped Referer. The Report tab shows a working PoC HTML page.',
    'feature' => '/labs/csrf-email/run',
    'request' => ['method' => 'POST', 'path' => '/labs/csrf-email/run', 'headers' => "Content-Type: application/x-www-form-urlencoded\nReferer:", 'body' => 'email=attacker@evil.example'],
    'payloads' => ['email=attacker@evil.example', 'csrf_token=csrf-static-123', 'Referer:', 'Origin: https://evil.example'],
    'hints' => [
        'At Low there is no anti-CSRF token at all, so any cross-site POST changes the email. Send the POST with just the email field.',
        'At Medium a token is required but it is a fixed constant (csrf-static-123), so an attacker can hardcode it. Include it.',
        'At High only the Referer/Origin is checked, and a missing one is allowed. Browsers can be made to send no Referer; send the request with an empty Referer header and no token.',
    ],
    'debrief' => 'Each level issued a state change that an attacker-controlled page could forge: no token, a predictable token, or a defense that trusts a blank Referer. CSRF works because the browser attaches the victim\'s cookies automatically. The Secure level requires an unpredictable per-session token and sets SameSite on the cookie.',
    'root_cause' => 'A state-changing request is accepted without proof it originated from the application itself.',
    'fix' => 'Require an unpredictable, per-session CSRF token on every state-changing request, verify it server-side, and set SameSite=Lax or Strict on session cookies.',
    'hunting' => 'For each state-changing POST, remove the token and replay; change or blank the Referer/Origin. If it still works, build a self-submitting form PoC. Check that tokens are per-session and unpredictable, and that SameSite is set. GET-based state changes are automatically forgeable.',
    'report' => "Title: Cross-site request forgery on change-email\nSeverity: Medium (CVSS 3.1 6.5)\nWeakness: CWE-352\n\nProof of concept:\n<form action=\"http://localhost:8080/labs/csrf-email/run\" method=\"POST\">\n  <input type=\"hidden\" name=\"email\" value=\"attacker@evil.example\">\n</form>\n<script>document.forms[0].submit()</script>\n\nImpact: An attacker page changes the victim's email, enabling account takeover via password reset.\n\nRemediation: Per-session CSRF token plus SameSite cookies.",
];
