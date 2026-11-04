<?php
return [
    'order' => 21,
    'title' => 'Product Reviews: Stored XSS',
    'category' => 'Cross-Site Scripting',
    'summary' => 'Reviews are saved and shown to everyone who opens the product.',
    'severity' => 'High',
    'cwe' => 'CWE-79',
    'difficulty' => 'Intermediate',
    'scenario' => 'Post a review. It is stored and rendered to every viewer of the product. Store a payload that runs script when the page loads. POST the review in the console (body: comment=...), then reload the feature pane.',
    'feature' => '/labs/xss-stored/run',
    'request' => ['method' => 'POST', 'path' => '/labs/xss-stored/run', 'headers' => 'Content-Type: application/x-www-form-urlencoded', 'body' => 'comment=<img src=x onerror=alert(1)>'],
    'payloads' => ['<script>alert(1)</script>', '<img src=x onerror=alert(1)>', '<svg onload=alert(1)>', '<a href="javascript:alert(1)">click</a>'],
    'hints' => [
        'POST a review, then GET the same page: the stored review is rendered back. Where in the HTML does it land?',
        'At Medium <script> is stripped. Event-handler tags such as <img src=x onerror=...> are not.',
        'At High only a few formatting tags are allowed, but an <a> tag keeps its href. A javascript: URL in that href runs on click.',
    ],
    'debrief' => 'Your review was stored and later written into every viewer\'s page without encoding, so it runs in their browser, not just yours. Stored XSS needs no victim interaction with a crafted link; simply viewing the page is enough. The Secure level encodes the stored text on output.',
    'root_cause' => 'Stored user content is rendered into HTML without output encoding, so it executes for every viewer.',
    'fix' => 'Encode on output (htmlspecialchars with ENT_QUOTES), or sanitize with a vetted allowlist library. Never trust stored data to be safe just because it was stored.',
    'hunting' => 'Anywhere user content is shown to others: comments, reviews, profiles, names, support tickets, filenames. Submit a marker, then view as another user. Check that both storage and rendering are considered. In source, trace stored fields to their echo without encoding.',
    'report' => "Title: Stored cross-site scripting in product reviews\nSeverity: High (CVSS 3.1 6.1, AV:N/AC:L/PR:N/UI:R/S:C/C:L/I:L/A:N)\nWeakness: CWE-79\n\nSteps to reproduce:\n1. POST a review containing <img src=x onerror=alert(1)>\n2. Load the product page; the script executes for any viewer.\n\nImpact: Persistent script execution against all viewers: session theft, worming.\n\nRemediation: Output encoding or allowlist sanitization at render time.",
];
