<?php
$req = fn($origin) => ['method' => 'GET', 'path' => '/labs/cors-api/run', 'headers' => "Origin: $origin"];
return [
  'low' => [
    'goal' => "Get the API to reflect an attacker Origin with credentials.",
    'steps' => [
      "Send Origin: https://evil.example.",
      "Check the response: Access-Control-Allow-Origin echoes your origin and Allow-Credentials is true.",
    ],
    'why' => "Reflecting an arbitrary Origin while allowing credentials lets any site read authenticated responses for a logged-in victim.",
    'run' => [$req('https://evil.example')],
  ],
  'medium' => [
    'goal' => "Bypass a suffix check on the Origin.",
    'steps' => [
      "Only origins ending in forgeshop.local are allowed.",
      "A lookalike ends with it too: https://evil-forgeshop.local",
    ],
    'why' => "A suffix match accepts attacker-registered lookalike domains that merely end with the allowed string, so the reflection is still attacker-controlled.",
    'run' => [$req('https://evil-forgeshop.local')],
  ],
  'high' => [
    'goal' => "Bypass a substring check on the Origin.",
    'steps' => [
      "The check only requires forgeshop.local to appear anywhere in the origin.",
      "Use https://forgeshop.local.evil.example.",
    ],
    'why' => "A substring match is satisfied by any domain that contains the allowed string as a label, including an attacker domain, so credentials are exposed cross-site.",
    'run' => [$req('https://forgeshop.local.evil.example')],
  ],
  'secure' => [
    'goal' => "Confirm an exact-match allowlist refuses untrusted origins.",
    'steps' => [
      "Only exact allowlisted origins are echoed back.",
      "Send Origin: https://evil.example and see no Access-Control-Allow-Origin for it.",
    ],
    'why' => "Comparing the Origin against an exact allowlist, and never reflecting an untrusted value with credentials, means a malicious site is never granted read access.",
    'run' => [$req('https://evil.example')],
  ],
];
