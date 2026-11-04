<?php
return [
    'order' => 40,
    'title' => 'Document Viewer: Path Traversal and LFI',
    'category' => 'Path Traversal',
    'summary' => 'The help viewer loads a file by the name in the URL.',
    'severity' => 'High',
    'cwe' => 'CWE-22',
    'difficulty' => 'Intermediate',
    'scenario' => 'The help centre reads a document whose name comes from the doc parameter. Escape the documents folder and read /opt/forge/flag.txt.',
    'feature' => '/labs/lfi-docs/run?doc=welcome.txt',
    'request' => ['method' => 'GET', 'path' => '/labs/lfi-docs/run?doc=welcome.txt'],
    'payloads' => ['../../../../../../etc/passwd', '../../../../../../opt/forge/flag.txt', '....//....//....//....//....//....//opt/forge/flag.txt', 'php://filter/resource=/opt/forge/flag.txt'],
    'hints' => [
        'The What Happened tab shows the resolved path the server opened. Add ../ segments and watch the path climb out of the documents folder.',
        'If a single ../ is stripped, nesting helps: ....// becomes ../ after one pass of removal.',
        'At High, ../ is removed completely, but the viewer also honours stream wrappers. php://filter with an absolute resource path never contains ../.',
    ],
    'debrief' => 'The filename was trusted, so ../ segments walked the path to a file outside the documents folder, and at High a php:// wrapper reached an absolute path without any ../ at all. The Secure level reduces the input to its basename and confirms the final real path stays inside the documents folder.',
    'root_cause' => 'A user-supplied name is used to build a filesystem path without constraining the result to an intended directory.',
    'fix' => 'Take basename of the input, allowlist the extension, resolve with realpath, and verify the result is still inside the documents directory. Disable or reject stream wrappers.',
    'hunting' => 'Any parameter naming a file, template, page, language, or report is a candidate: ?page=, ?file=, ?template=, ?lang=. Try ../ sequences, encoded variants (%2e%2e%2f, ....//), and the php://filter wrapper to read source. In PHP, grep for include, require, file_get_contents, fopen, readfile taking request input.',
    'report' => "Title: Path traversal and local file inclusion in document viewer\nSeverity: High (CVSS 3.1 7.5, AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:N/A:N)\nWeakness: CWE-22\n\nSteps to reproduce:\n1. Request /labs/lfi-docs/run?doc=../../../../../../etc/passwd\n2. Observe the file contents returned.\n3. Read application secrets, e.g. doc=../../../../../../opt/forge/flag.txt\n\nImpact: Disclosure of arbitrary readable files, including configuration and source.\n\nRemediation: basename plus extension allowlist plus realpath containment check.",
];
