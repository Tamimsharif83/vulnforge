<?php
return [
    'order' => 100,
    'title' => 'Theme Cookie: PHP Object Injection',
    'category' => 'Insecure Deserialization',
    'summary' => 'Your theme preferences ride in a serialized cookie the server unpacks.',
    'severity' => 'High',
    'cwe' => 'CWE-502',
    'difficulty' => 'Advanced',
    'scenario' => 'The prefs cookie is a base64 PHP-serialized Preferences object. The server unserializes it on each request. The class has a __wakeup that reads a file path from one of its properties. Craft a cookie that reads /opt/forge/flag.txt. Send it as the prefs cookie.',
    'feature' => '/labs/deser-prefs/run',
    'request' => ['method' => 'GET', 'path' => '/labs/deser-prefs/run', 'headers' => 'Cookie: prefs=PASTE_PAYLOAD; vf_level=low'],
    'payloads' => ['O:11:"Preferences":2:{s:5:"theme";s:4:"dark";s:8:"debugLog";s:22:"/opt/forge/flag.txt";}', 'a:1:{i:0;O:11:"Preferences":...}', 'debugLog => /opt/forge/flag.txt', 'base64 the whole thing'],
    'hints' => [
        'Look at the Preferences class in the Source tab. The debugLog property is read by __wakeup. Set it to /opt/forge/flag.txt, serialize the object, base64 it, and send it as prefs.',
        'At Medium a top-level object (string starting with O:) is rejected. Wrap the object in an array: a:1:{i:0;O:11:"Preferences":...}. unserialize still builds the nested object.',
        'At High only the Preferences class is allowed to deserialize, but Preferences is itself the gadget, so allowlisting it changes nothing; combine the array wrapper with the allowed class.',
    ],
    'debrief' => 'Serialized data from the client was unserialized, so you instantiated an object and controlled its properties, and its __wakeup used one as a file path. Rejecting the O: prefix or allowlisting the class fails when the allowed class is itself a usable gadget. The Secure level passes allowed_classes=false, so no objects are created and the data is treated as inert.',
    'root_cause' => 'Untrusted serialized data is passed to unserialize, instantiating attacker-chosen objects and triggering their magic methods.',
    'fix' => 'Do not unserialize untrusted input. Use a data-only format (JSON) for client state, or call unserialize with allowed_classes=false. Sign server-issued state if it must round-trip.',
    'hunting' => 'Look for serialized blobs in cookies, hidden fields, caches, and queues (PHP: O:, a:, s:; patterns like Tzo in base64). Map magic methods (__wakeup, __destruct, __toString) across the codebase for gadget chains (phpggc catalogs many). In source, grep for unserialize on request data.',
    'report' => "Title: PHP object injection via unserialized prefs cookie\nSeverity: High (CVSS 3.1 8.1)\nWeakness: CWE-502\n\nSteps to reproduce:\n1. Set prefs cookie to a base64 serialized Preferences object with debugLog=/opt/forge/flag.txt.\n2. The server reads and returns the file in __wakeup.\n\nImpact: Arbitrary file read now; RCE with a suitable gadget chain.\n\nRemediation: Avoid unserialize on untrusted input; use JSON or allowed_classes=false.",
];
