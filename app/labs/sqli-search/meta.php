<?php
return [
    'order' => 10,
    'title' => 'Product Search: UNION Extraction',
    'category' => 'SQL Injection',
    'summary' => 'Pull data from another table through the storefront search box.',
    'severity' => 'Critical',
    'cwe' => 'CWE-89',
    'difficulty' => 'Beginner',
    'scenario' => 'The storefront search builds its query from the search term. Retrieve the administrator password hash from the users table.',
    'feature' => '/labs/sqli-search/run?q=keyboard',
    'request' => ['method' => 'GET', 'path' => '/labs/sqli-search/run?q=keyboard'],
    'payloads' => ["'", "' -- -", "' ORDER BY 1 -- -", "' UNION SELECT NULL -- -"],
    'hints' => [
        'Send a single quote and read the What Happened tab. Where does your input land in the query?',
        'A UNION needs the same number of columns as the original SELECT. ORDER BY with a rising number reveals that count.',
        'The original query returns four columns. The users table has username and password_md5 columns.',
    ],
    'debrief' => 'You closed the string literal and appended a UNION, so the database returned rows from a table the feature never meant to expose. The root cause is query text built from input. Parameterized queries remove the bug class; the keyword filters at Medium and High only change the spelling you need.',
    'root_cause' => 'User input is concatenated into SQL text, so the database cannot tell data from query structure.',
    'fix' => 'Use a prepared statement and bind the search term as a parameter, as the Secure level does. Filtering keywords is not a fix: the Medium and High levels show why.',
    'hunting' => 'Look at every parameter that feeds a list, filter, or sort. Send a quote and compare the response with a doubled quote. In Burp, send the request to Repeater and change one thing at a time. In WordPress plugins, grep for $wpdb->query and $wpdb->get_results calls that interpolate variables without $wpdb->prepare.',
    'report' => "Title: SQL injection in product search parameter q\nSeverity: Critical (CVSS 3.1 9.8, AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H)\nWeakness: CWE-89\n\nSteps to reproduce:\n1. Request /labs/sqli-search/run?q=' and observe the database error.\n2. Determine the column count with ORDER BY.\n3. Use UNION SELECT to return rows from the users table.\n\nImpact: An unauthenticated attacker can read the full database, including password hashes.\n\nRemediation: Replace string concatenation with parameterized queries.",
];
