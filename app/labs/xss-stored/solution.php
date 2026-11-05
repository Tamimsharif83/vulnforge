<?php
$post = fn($c) => ['method' => 'POST', 'path' => '/labs/xss-stored/run', 'headers' => 'Content-Type: application/x-www-form-urlencoded', 'body' => 'comment=' . rawurlencode($c)];
$get = ['method' => 'GET', 'path' => '/labs/xss-stored/run'];
return [
  'low' => [
    'goal' => "Store a payload that runs for every viewer of the reviews.",
    'steps' => [
      "POST a review containing <script>alert(1)</script>.",
      "Reload the page (GET): the stored review is rendered back verbatim and runs.",
    ],
    'why' => "The review is saved and later written into every viewer's page without encoding, so it executes whenever anyone opens the product, no crafted link required.",
    'run' => [$post('<script>alert(1)</script>'), $get],
  ],
  'medium' => [
    'goal' => "Store a handler-based payload, since <script> is stripped at render.",
    'steps' => [
      "<script> is removed when rendering, but event handlers are not.",
      "Store <img src=x onerror=alert(1)>, then reload to see it fire.",
    ],
    'why' => "Sanitizing only <script> at render time still leaves event-handler vectors, which execute for every viewer exactly like a script tag would.",
    'run' => [$post('<img src=x onerror=alert(1)>'), $get],
  ],
  'high' => [
    'goal' => "Abuse an allowed tag that keeps a dangerous attribute.",
    'steps' => [
      "strip_tags allows a few formatting tags, including <a>, and keeps its href.",
      "Store <a href=\"javascript:alert(1)\">click</a>; the javascript URL runs on click.",
    ],
    'why' => "Allowing a tag is not the same as making it safe. An <a> with a javascript: href is a stored XSS vector that survives a tag allowlist that ignores attribute values.",
    'run' => [$post('<a href="javascript:alert(1)">click</a>'), $get],
  ],
  'secure' => [
    'goal' => "Confirm output encoding makes stored content inert.",
    'steps' => [
      "Stored reviews are passed through htmlspecialchars at render time.",
      "Store <img src=x onerror=alert(1)> and reload: it shows as text.",
    ],
    'why' => "Encoding at output time means stored markup is displayed, not parsed. Data being stored is never trusted to be safe to render.",
    'run' => [$post('<img src=x onerror=alert(1)>'), $get],
  ],
];
