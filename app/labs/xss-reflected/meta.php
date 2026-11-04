<?php
return [
    'order' => 20,
    'title' => 'Search Box: Reflected XSS',
    'category' => 'Cross-Site Scripting',
    'summary' => 'The search page echoes your term back into the HTML.',
    'severity' => 'High',
    'cwe' => 'CWE-79',
    'difficulty' => 'Beginner',
    'scenario' => 'The "no results" message repeats your search term in the page. Get a script to run. The What Happened tab shows the context your input landed in.',
    'feature' => '/labs/xss-reflected/run?q=laptop',
    'request' => ['method' => 'GET', 'path' => '/labs/xss-reflected/run?q=laptop'],
    'payloads' => ['<script>alert(1)</script>', '<img src=x onerror=alert(1)>', '<svg onload=alert(1)>', '" autofocus onfocus="alert(1)'],
    'hints' => [
        'The What Happened tab shows where your input appears. At Low it lands directly in the page body as HTML.',
        'If <script> is filtered, other tags run script through event handlers: <img src=x onerror=...> and <svg onload=...>.',
        'At High angle brackets are stripped, so you cannot open a new tag. Break out of the quotes and add an event-handler attribute, e.g. a quote then autofocus onfocus=.',
    ],
    'debrief' => 'Your input was placed into the page without output encoding, so the browser parsed it as markup and ran your script. The context matters: body text, an attribute value, and a script block each need different breakouts. The Secure level encodes output for the HTML context with htmlspecialchars and ENT_QUOTES, so the same input renders as inert text.',
    'root_cause' => 'Untrusted input is written into an HTML response without encoding for the context it lands in.',
    'fix' => 'Encode on output for the exact context (htmlspecialchars with ENT_QUOTES for HTML and attributes), and prefer frameworks that auto-escape. A Content-Security-Policy adds defense in depth.',
    'hunting' => 'Reflect a unique marker and see where it appears: body, attribute, script, URL. Then try context-appropriate breakouts. Check search, error messages, and any parameter echoed back. In source, look for echo/print of request data without htmlspecialchars.',
    'report' => "Title: Reflected cross-site scripting in search parameter q\nSeverity: High (CVSS 3.1 6.1, AV:N/AC:L/PR:N/UI:R/S:C/C:L/I:L/A:N)\nWeakness: CWE-79\n\nSteps to reproduce:\n1. Request /labs/xss-reflected/run?q=<script>alert(1)</script>\n2. Observe the script executing in the response.\n\nImpact: Attacker-controlled script runs in a victim's session: session theft, actions on behalf of the user.\n\nRemediation: Context-aware output encoding; Content-Security-Policy.",
];
