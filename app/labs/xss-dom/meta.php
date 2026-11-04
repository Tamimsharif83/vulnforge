<?php
return [
    'order' => 22,
    'title' => 'Theme Preview: DOM-based XSS',
    'category' => 'Cross-Site Scripting',
    'summary' => 'A client-side script writes your theme text into the page with innerHTML.',
    'severity' => 'Medium',
    'cwe' => 'CWE-79',
    'difficulty' => 'Intermediate',
    'scenario' => 'This preview runs entirely in the browser: JavaScript takes the theme text and writes it into the page. The server never sees your payload. Type a payload into the Theme field in the live pane and click Apply. A helper forgeSolve() is on the page; make your payload call it. Read the Source tab to see the exact sink.',
    'feature' => '/labs/xss-dom/run',
    'request' => ['method' => 'GET', 'path' => '/labs/xss-dom/run'],
    'payloads' => ['<img src=x onerror=forgeSolve()>', '<svg onload=forgeSolve()>', '<img src=x onerror = forgeSolve()>'],
    'hints' => [
        'The sink is element.innerHTML in the page script (see the Source tab). Any tag with an event handler runs when the browser parses it.',
        'At Medium a <script> pattern is removed before writing. Use an image or svg handler instead: <img src=x onerror=forgeSolve()>.',
        'At High the filter strips on<word>= with no spaces. Put whitespace around the equals: onerror = forgeSolve().',
    ],
    'debrief' => 'The vulnerability lives in client-side code: a value flowed into innerHTML, a sink that parses HTML, without sanitization. Because it never reaches the server, server-side filters and WAFs cannot see it. The Secure level uses textContent, which treats the value as text and never parses it as markup.',
    'root_cause' => 'Client-side JavaScript passes untrusted input to a sink that parses HTML (innerHTML), so markup in the value executes.',
    'fix' => 'Write untrusted values with textContent or set them as properties, never innerHTML. If HTML is required, sanitize with a trusted library (DOMPurify). Review sources (location, postMessage, storage) and sinks (innerHTML, document.write, eval).',
    'hunting' => 'Grep client JS for sinks: innerHTML, outerHTML, document.write, eval, setTimeout(string), and element.setAttribute of href/src. Trace back to sources: location.hash/search, document.referrer, postMessage, localStorage. Burp will not show it in responses; use the browser DevTools and DOM Invader.',
    'report' => "Title: DOM-based cross-site scripting in theme preview\nSeverity: Medium (CVSS 3.1 6.1, AV:N/AC:L/PR:N/UI:R/S:C/C:L/I:L/A:N)\nWeakness: CWE-79\n\nSteps to reproduce:\n1. In the theme preview, enter <img src=x onerror=alert(1)>\n2. The script executes in the browser when the preview renders.\n\nImpact: Script execution in the victim's browser context, driven entirely client-side.\n\nRemediation: Use textContent; sanitize with DOMPurify if HTML is required.",
];
