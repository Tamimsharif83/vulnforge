<?php
return [
    'order' => 11,
    'title' => 'Login: SQL Injection Auth Bypass',
    'category' => 'SQL Injection',
    'summary' => 'The login query is built from the username and password fields.',
    'severity' => 'Critical',
    'cwe' => 'CWE-89',
    'difficulty' => 'Beginner',
    'scenario' => 'Log in as admin without knowing the password. The login builds one SQL query from both fields. POST username and password in the console.',
    'feature' => '/labs/sqli-login/run',
    'request' => ['method' => 'POST', 'path' => '/labs/sqli-login/run', 'headers' => 'Content-Type: application/x-www-form-urlencoded', 'body' => "username=admin' -- -&password=x"],
    'payloads' => ["admin' -- -", "admin' OR '1'='1", "admin'||'1'='1", "' OR role='admin' -- -"],
    'hints' => [
        'The What Happened tab shows the full query. Your username closes the string and the rest of the query is yours. A comment removes the password check.',
        'If comment markers are filtered, you do not need them: admin\' OR \'1\'=\'1 leaves the trailing quote to close the password string.',
        'At High the keyword OR is stripped. MySQL also spells OR as ||, so admin\'||\'1\'=\'1 works.',
    ],
    'debrief' => 'Both fields were concatenated into the query, so the username field alone rewrote the WHERE clause and the password check never applied. Comment stripping and keyword filtering do not help because SQL has many equivalent forms. The Secure level binds both fields as parameters and verifies the password hash in PHP.',
    'root_cause' => 'Credentials are concatenated into SQL, so input changes the query logic instead of being treated as data.',
    'fix' => 'Use a parameterized query to fetch the user by username only, then verify the password hash in application code (password_verify in real systems).',
    'hunting' => 'Test login and any single-field lookup with a quote, then with \' OR \'1\'=\'1 and comment variants. Watch for logic changes (logged in, different result counts). In source, look for query strings built with the username or password variable.',
    'report' => "Title: SQL injection authentication bypass in login\nSeverity: Critical (CVSS 3.1 9.8)\nWeakness: CWE-89\n\nSteps to reproduce:\n1. POST username=admin' -- - with any password.\n2. The password check is commented out; you are logged in as admin.\n\nImpact: Full authentication bypass, account takeover.\n\nRemediation: Parameterized lookup by username, then verify the password hash in code.",
];
