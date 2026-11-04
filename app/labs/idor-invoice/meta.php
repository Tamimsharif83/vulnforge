<?php
return [
    'order' => 110,
    'title' => 'Invoices: IDOR',
    'category' => 'Broken Access Control',
    'summary' => 'Invoices are fetched by id. You are signed in as alice.',
    'severity' => 'High',
    'cwe' => 'CWE-639',
    'difficulty' => 'Beginner',
    'scenario' => 'You are signed in as alice (user id 2). Your invoices are 2 and 3. The admin invoice is id 1 and holds a sensitive note. Read it by changing the id.',
    'feature' => '/labs/idor-invoice/run?id=2',
    'request' => ['method' => 'GET', 'path' => '/labs/idor-invoice/run?id=2'],
    'payloads' => ['1', '2', 'MQ==', '3'],
    'hints' => [
        'The id in the URL selects the record. Nothing checks the record belongs to you. Try id=1.',
        'At Medium the id is base64-encoded in the URL. Encode the number you want: base64("1") is MQ==.',
        'At High the server checks ownership against an as= parameter it trusts from the request. Set as=1 to claim you are the owner.',
    ],
    'debrief' => 'The record id was the only thing deciding what you saw, and it was yours to change. Obscuring the id (base64) or trusting a request-supplied identity does not help. The Secure level checks the record owner against the server-side session user, so alice can only read alice\'s invoices.',
    'root_cause' => 'Authorization is missing: the server returns a record by id without verifying the caller owns it.',
    'fix' => 'Check ownership on every object access against the authenticated session identity, never against a client-supplied id. Prefer scoping queries to the current user.',
    'hunting' => 'Change any id, uuid, filename, or account number in requests and watch for other users\' data. Compare two accounts. Encoded or hashed ids are still IDOR if unverified. Automate with Burp (Autorize, Intruder). Extremely common in REST APIs: /api/users/{id}, /orders/{id}.',
    'report' => "Title: IDOR allows reading other users' invoices\nSeverity: High (CVSS 3.1 6.5)\nWeakness: CWE-639\n\nSteps to reproduce:\n1. As alice, request /labs/idor-invoice/run?id=1 (admin's invoice).\n2. The record is returned despite not belonging to you.\n\nImpact: Disclosure of other users' records.\n\nRemediation: Enforce per-object ownership checks against the session user.",
];
