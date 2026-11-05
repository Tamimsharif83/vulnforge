<?php
$p = '/labs/idor-invoice/run?';
return [
  'low' => [
    'goal' => "Read the admin invoice by changing the id.",
    'steps' => [
      "You are alice (uid 2); your invoices are 2 and 3. The admin invoice is id 1.",
      "Request id=1. Nothing checks the record belongs to you.",
    ],
    'why' => "The id alone decides what you see, with no ownership check, so any id returns that record regardless of who owns it.",
    'run' => [['method' => 'GET', 'path' => $p . 'id=1']],
  ],
  'medium' => [
    'goal' => "Read the admin invoice when the id is base64-encoded.",
    'steps' => [
      "The id is base64 in the URL, which is obscurity, not access control.",
      "Encode the number you want: base64 of 1 is MQ==. Request id=MQ==.",
    ],
    'why' => "Encoding the identifier changes its appearance, not its authorization. Decoding to 1 still fetches the admin record because ownership is never checked.",
    'run' => [['method' => 'GET', 'path' => $p . 'id=MQ==']],
  ],
  'high' => [
    'goal' => "Defeat an ownership check that trusts a request parameter.",
    'steps' => [
      "The server compares the record owner to an as= value taken from the request.",
      "Claim you are the owner: request id=1&as=1.",
    ],
    'why' => "Authorization that trusts a client-supplied identity is no authorization at all. By stating as=1 you assert ownership of the admin record and the check passes.",
    'run' => [['method' => 'GET', 'path' => $p . 'id=1&as=1']],
  ],
  'secure' => [
    'goal' => "Confirm the session-scoped query blocks access.",
    'steps' => [
      "The lookup is scoped to the session user (uid 2) in the query itself.",
      "Request id=1 and get nothing back.",
    ],
    'why' => "By constraining the query to the authenticated user's own records, another user's id simply matches no row, so there is nothing to leak.",
    'run' => [['method' => 'GET', 'path' => $p . 'id=1']],
  ],
];
