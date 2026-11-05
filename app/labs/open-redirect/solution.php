<?php
$p = '/labs/open-redirect/run?next=';
return [
  'low' => [
    'goal' => "Redirect the browser off-site.",
    'steps' => [
      "The Location header echoes next directly.",
      "Set next to an absolute URL: https://evil.example/",
    ],
    'why' => "With no validation, an absolute URL in next becomes the redirect target, sending the victim to an attacker site.",
    'run' => [['method' => 'GET', 'path' => $p . rawurlencode('https://evil.example/')]],
  ],
  'medium' => [
    'goal' => "Redirect off-site when the value must start with a slash.",
    'steps' => [
      "Only values starting with / are allowed.",
      "A protocol-relative URL also starts with /: //evil.example/",
    ],
    'why' => "//evil.example starts with a slash yet the browser treats it as an absolute URL to another host, so the slash check does not keep you on-site.",
    'run' => [['method' => 'GET', 'path' => $p . rawurlencode('//evil.example/')]],
  ],
  'high' => [
    'goal' => "Redirect off-site when // is also blocked.",
    'steps' => [
      "The value must start with / but not //.",
      "Browsers normalize backslashes to slashes: /\\evil.example becomes //evil.example.",
    ],
    'why' => "The server sees /\\evil.example as a path, but the browser rewrites the backslash to a slash and leaves the site, so the check and the browser disagree.",
    'run' => [['method' => 'GET', 'path' => $p . rawurlencode('/\\evil.example')]],
  ],
  'secure' => [
    'goal' => "Confirm an allowlist of relative paths holds.",
    'steps' => [
      "Only known relative paths are honoured; anything else falls back to a default.",
      "Send next=https://evil.example/ and watch it be ignored.",
    ],
    'why' => "By allowing only a fixed set of in-app paths, no attacker-controlled destination can ever reach the Location header.",
    'run' => [['method' => 'GET', 'path' => $p . rawurlencode('https://evil.example/')]],
  ],
];
