<?php
return [
    'order' => 30,
    'title' => 'Network Tools: Command Injection',
    'category' => 'Command Injection',
    'summary' => 'The connectivity checker runs ping with your input in the command line.',
    'severity' => 'Critical',
    'cwe' => 'CWE-78',
    'difficulty' => 'Beginner',
    'scenario' => "ForgeShop's status page lets staff ping a host. The host you type is placed into a real shell command. Read the contents of /opt/forge/flag.txt, a file the feature never meant to expose.",
    'feature' => '/labs/cmdi-ping/run?host=127.0.0.1',
    'request' => ['method' => 'GET', 'path' => '/labs/cmdi-ping/run?host=127.0.0.1'],
    'payloads' => ['127.0.0.1; id', '127.0.0.1; cat /opt/forge/flag.txt', '127.0.0.1$(cat /opt/forge/flag.txt)', '127.0.0.1%0acat /opt/forge/flag.txt'],
    'hints' => [
        'The What Happened tab shows the exact command string. Your input is pasted straight into it. What shell metacharacter starts a second command?',
        'At Low a semicolon works. If a character is filtered, the trace shows which bytes survived; find a separator the filter forgot.',
        'Command substitution $(...) and a raw newline (%0a in the URL) are separators too. One of them is not on the blacklist at High.',
    ],
    'debrief' => 'Your input became part of a shell command, so a separator let you run a second program and read a file the ping feature never exposes. Blacklisting metacharacters fails because shells have many separators; the Secure level passes the host as a single escaped argument, so there is no shell for your input to break out of.',
    'root_cause' => 'Untrusted input is concatenated into a string that is handed to a shell, which interprets metacharacters as syntax.',
    'fix' => 'Avoid the shell: pass arguments as an array, or escape each argument with escapeshellarg and validate the host against an allowlist pattern, as the Secure level does.',
    'hunting' => 'Look for features that wrap system tools: ping, nslookup, whois, image conversion, PDF generation, ffmpeg. Probe with 127.0.0.1;id, | id, $(id), %0aid and a backtick variant. Blind cases show up as time delays (;sleep 5) or out-of-band DNS. In PHP source, grep for exec, shell_exec, system, passthru, popen, proc_open and backticks.',
    'report' => "Title: OS command injection in network status host parameter\nSeverity: Critical (CVSS 3.1 9.8, AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H)\nWeakness: CWE-78\n\nSteps to reproduce:\n1. Request /labs/cmdi-ping/run?host=127.0.0.1;id\n2. Observe the output of id in the response, proving command execution.\n3. Read arbitrary files, e.g. host=127.0.0.1;cat /opt/forge/flag.txt\n\nImpact: Arbitrary command execution in the web server's context.\n\nRemediation: Pass arguments without a shell, or escape with escapeshellarg plus input allowlisting.",
];
