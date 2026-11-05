<?php
$p = '/labs/sqli-login/run';
$h = 'Content-Type: application/x-www-form-urlencoded';
$body = fn($u) => 'username=' . rawurlencode($u) . '&password=' . rawurlencode('wrong');
return [
  'low' => [
    'goal' => "Comment out the password check so only the username condition matters.",
    'steps' => [
      "The query is WHERE username='INPUT' AND password_md5=MD5('INPUT').",
      "Enter admin' -- - as the username: the quote closes the username value and -- - comments out the whole AND password check.",
      "Any password works because that half of the query is gone.",
    ],
    'why' => "Both fields are concatenated into one query, so the username field alone rewrites the WHERE clause. The commented-out password comparison is never evaluated, and the admin row is returned.",
    'run' => [['method' => 'POST', 'path' => $p, 'headers' => $h, 'body' => $body("admin' -- -")]],
  ],
  'medium' => [
    'goal' => "Bypass without any comment, since comment markers are stripped.",
    'steps' => [
      "Comment markers (-- , #, /*) are removed, so you cannot comment out the password check.",
      "Use admin' OR '1'='1 : the trailing quote closes the password string, and OR makes the row match on username alone.",
      "The condition becomes username='admin' OR ('1'='1' AND password=...), which is true for admin.",
    ],
    'why' => "You do not need a comment to bypass a login; a crafted OR plus a quote that balances the remaining string literal keeps the query valid while forcing the WHERE clause true for the admin row.",
    'run' => [['method' => 'POST', 'path' => $p, 'headers' => $h, 'body' => $body("admin' OR '1'='1")]],
  ],
  'high' => [
    'goal' => "Bypass when the OR keyword is also stripped.",
    'steps' => [
      "The OR keyword and comment markers are removed.",
      "MySQL also spells logical OR as ||. Use admin'||'1'='1 .",
      "Same effect as OR, but the filter does not recognise the operator form.",
    ],
    'why' => "SQL offers more than one way to express OR. The || operator is logically identical but survives a filter that only looks for the word OR, so the WHERE clause is still forced true.",
    'run' => [['method' => 'POST', 'path' => $p, 'headers' => $h, 'body' => $body("admin'||'1'='1")]],
  ],
  'secure' => [
    'goal' => "Confirm the parameterized lookup plus hash check cannot be bypassed.",
    'steps' => [
      "The user is fetched by username with a bound parameter, then the password hash is verified in PHP.",
      "Send admin' -- - and watch it be looked up as a literal username that does not exist.",
    ],
    'why' => "The username is data, never query structure, so injection cannot rewrite the lookup. Even a returned row must pass an explicit hash comparison, so a wrong password is rejected.",
    'run' => [['method' => 'POST', 'path' => $p, 'headers' => $h, 'body' => $body("admin' -- -")]],
  ],
];
