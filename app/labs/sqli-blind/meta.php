<?php
return [
    'order' => 12,
    'title' => 'Username Check: Blind and Time-Based SQLi',
    'category' => 'SQL Injection',
    'summary' => 'The signup username checker returns only "available" or "taken".',
    'severity' => 'High',
    'cwe' => 'CWE-89',
    'difficulty' => 'Advanced',
    'scenario' => 'This endpoint shows no data and no errors, only whether a username is taken. The query is still injectable. Confirm it by making the database pause: inject a condition that calls SLEEP so the response is visibly delayed. The response time is the oracle you would use to extract data one bit at a time.',
    'feature' => '/labs/sqli-blind/run?username=alice',
    'request' => ['method' => 'GET', 'path' => '/labs/sqli-blind/run?username=alice'],
    'payloads' => ["alice' AND SLEEP(2) -- -", "alice' AND sleep(2) -- -", "alice' AND SLsleepEEP(2) -- -", "' OR (SELECT SLEEP(2)) -- -"],
    'hints' => [
        'There is no visible output, so use time. A true condition that calls SLEEP(2) delays the response; the What Happened tab shows the elapsed milliseconds.',
        'At Medium the word SLEEP is removed once and case-sensitively. Lowercase sleep survives.',
        'At High removal is case-insensitive but still a single pass. Nest it: SLsleepEEP becomes SLEEP after one removal of the inner sleep.',
    ],
    'debrief' => 'With no data or errors in the response, the response time became the signal. A condition that calls SLEEP ran on the server, proving injection; the same true/false oracle extracts data character by character (for example comparing SUBSTRING of a hash). Keyword filtering fails against mixed case and nesting. The Secure level parameterizes the lookup, so timing never varies with input.',
    'root_cause' => 'Input is concatenated into SQL; even with no output, injected expressions still execute and affect timing.',
    'fix' => 'Parameterize the query. Do not rely on hiding output or errors: the query still runs attacker-controlled logic.',
    'hunting' => 'For endpoints that reveal only yes/no or nothing, test timing: \' AND SLEEP(5) -- - and compare. Automate boolean extraction with Burp Intruder or sqlmap --technique=BT. Look for any parameter feeding a WHERE clause, even on endpoints that return no rows.',
    'report' => "Title: Blind (time-based) SQL injection in username availability check\nSeverity: High (CVSS 3.1 8.6)\nWeakness: CWE-89\n\nSteps to reproduce:\n1. Request /labs/sqli-blind/run?username=alice' AND SLEEP(5) -- -\n2. The response is delayed ~5s, confirming injection.\n3. Extract data with boolean/time conditions over SUBSTRING.\n\nImpact: Full database disclosure despite no visible output.\n\nRemediation: Parameterized queries.",
];
