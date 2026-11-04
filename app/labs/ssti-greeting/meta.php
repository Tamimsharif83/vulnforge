<?php
return [
    'order' => 70,
    'title' => 'Greeting Template: SSTI to RCE',
    'category' => 'Server-Side Template Injection',
    'summary' => 'Your custom greeting is rendered as a template on the server.',
    'severity' => 'Critical',
    'cwe' => 'CWE-1336',
    'difficulty' => 'Advanced',
    'scenario' => 'ForgeShop lets you set a greeting with {{ }} placeholders, rendered server-side. The template is built from your input. Confirm template injection with {{7*7}}, then read /opt/forge/flag.txt.',
    'feature' => '/labs/ssti-greeting/run?tpl=Hello {{7*7}}',
    'request' => ['method' => 'GET', 'path' => '/labs/ssti-greeting/run?tpl=Hello {{7*7}}'],
    'payloads' => ['{{7*7}}', "{{system('cat /opt/forge/flag.txt')}}", "{{file_get_contents('/opt/forge/flag.txt')}}", "{{implode('',file('/opt/forge/flag.txt'))}}"],
    'hints' => [
        '{{7*7}} rendering as 49 (not the literal text) proves the braces are evaluated, not just substituted. The What Happened tab shows what was evaluated.',
        'Once an expression is evaluated, call a function. At Low, {{system(\'cat /opt/forge/flag.txt\')}} runs a command.',
        'If command functions are blocked, read the file directly: file_get_contents, or file() joined, both reach the filesystem without a shell.',
    ],
    'debrief' => 'Your input was compiled as a template, so your expression ran on the server. {{7*7}} to 49 was the tell; from there any expression, including file reads and commands, executes. Blocklists of function names fail because PHP has many ways to reach the same capability. The Secure level renders only named variables and never evaluates expressions.',
    'root_cause' => 'User input is used as the template source, so the template engine compiles and executes attacker expressions.',
    'fix' => 'Never build a template from user input. Pass user data as variables to a fixed template, and use a sandboxed engine. The Secure level substitutes only allowlisted variable names, with no evaluation.',
    'hunting' => 'Probe inputs that render with {{}} or ${} with {{7*7}}, ${7*7}, #{7*7}. If the product of the numbers appears, identify the engine and use its RCE gadget (Twig _self, Jinja __class__). Common in email templates, custom themes, and report builders.',
    'report' => "Title: Server-side template injection leading to RCE\nSeverity: Critical (CVSS 3.1 9.8)\nWeakness: CWE-1336/CWE-94\n\nSteps to reproduce:\n1. Set greeting to {{7*7}}; it renders as 49.\n2. Set greeting to {{system('id')}}; command output appears.\n\nImpact: Arbitrary code execution on the server.\n\nRemediation: Treat user input as data, not template source; use a sandboxed engine.",
];
