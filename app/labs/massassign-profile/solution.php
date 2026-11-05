<?php
$h = 'Content-Type: application/x-www-form-urlencoded';
$req = fn($body) => ['method' => 'POST', 'path' => '/labs/massassign-profile/run', 'headers' => $h, 'body' => $body];
return [
  'low' => [
    'goal' => "Set a privileged field the form never shows.",
    'steps' => [
      "The handler copies every submitted field onto your record.",
      "Add role=admin alongside the normal fields.",
    ],
    'why' => "Binding all request fields to the model lets you set attributes the UI never exposes, such as role, escalating your own account to admin.",
    'run' => [$req('email=alice@forgeshop.local&bio=hi&role=admin')],
  ],
  'medium' => [
    'goal' => "Bypass a blacklist that drops the exact key role.",
    'steps' => [
      "The key role is removed, but attribute names map to case-insensitive columns.",
      "Send ROLE=admin; the raw-key filter misses it and the model normalizes it to role.",
    ],
    'why' => "The filter checks the literal key while the model treats ROLE and role as the same column, so the uppercase form slips past and still sets the role.",
    'run' => [$req('email=alice@forgeshop.local&ROLE=admin')],
  ],
  'high' => [
    'goal' => "Escalate through a sibling field when role is fully blocked.",
    'steps' => [
      "role is blocked case-insensitively, but balance is still bindable.",
      "Send balance=999999 to tamper with a different privileged field.",
    ],
    'why' => "Guarding one field leaves every other privileged attribute exposed. Mass assignment of balance is still a privilege/entitlement bug even when role is locked down.",
    'run' => [$req('email=alice@forgeshop.local&balance=999999')],
  ],
  'secure' => [
    'goal' => "Confirm an allowlist blocks privileged fields.",
    'steps' => [
      "Only bio and email are bound; everything else is ignored.",
      "Send role=admin&balance=999999 and watch neither change.",
    ],
    'why' => "Binding only an explicit allowlist of user-editable fields means privileged attributes can never be set from a request, whatever their name or case.",
    'run' => [$req('email=alice@forgeshop.local&role=admin&balance=999999')],
  ],
];
