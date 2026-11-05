<?php
$form = 'Content-Type: application/x-www-form-urlencoded';
return [
  'low' => [
    'goal' => "Change the email with a request that carries no anti-CSRF token.",
    'steps' => [
      "No token is required, so any cross-site POST would work.",
      "POST email=attacker@evil.example with no token.",
      "The Report tab has a self-submitting PoC page.",
    ],
    'why' => "Because the action needs no proof it came from the app, an attacker page can submit it using the victim's automatically-attached cookies.",
    'run' => [['method' => 'POST', 'path' => '/labs/csrf-email/run', 'headers' => $form, 'body' => 'email=attacker@evil.example']],
  ],
  'medium' => [
    'goal' => "Include the static, non-secret token.",
    'steps' => [
      "A token is required, but it is a fixed constant for everyone: csrf-static-123.",
      "POST the email plus csrf_token=csrf-static-123.",
    ],
    'why' => "A token that is not unpredictable and per-session can simply be hardcoded into the attacker's page, so it adds no protection.",
    'run' => [['method' => 'POST', 'path' => '/labs/csrf-email/run', 'headers' => $form, 'body' => 'email=attacker@evil.example&csrf_token=csrf-static-123']],
  ],
  'high' => [
    'goal' => "Exploit a Referer check that allows a blank Referer.",
    'steps' => [
      "The server only checks the Referer and treats a missing one as same-site.",
      "Send the POST with an empty Referer header and no token.",
    ],
    'why' => "Browsers can be made to omit the Referer (for example from an https page or via Referrer-Policy), so a defense that trusts a blank Referer is bypassable.",
    'run' => [['method' => 'POST', 'path' => '/labs/csrf-email/run', 'headers' => "$form\nReferer:", 'body' => 'email=attacker@evil.example']],
  ],
  'secure' => [
    'goal' => "Confirm a per-session token blocks forgery.",
    'steps' => [
      "A request needs the unpredictable per-session token, which an attacker cannot know.",
      "Send the POST without the right token and watch it be rejected.",
    ],
    'why' => "An unguessable per-session token (plus SameSite cookies) means a cross-site page cannot assemble a valid request, so the state change is refused.",
    'run' => [['method' => 'POST', 'path' => '/labs/csrf-email/run', 'headers' => $form, 'body' => 'email=attacker@evil.example']],
  ],
];
