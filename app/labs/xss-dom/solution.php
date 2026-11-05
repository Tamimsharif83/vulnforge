<?php
$common = fn($goal, $payload, $why, $note) => [
  'goal' => $goal,
  'steps' => [
    "This sink runs in the browser (see the Source tab), so solve it in the live pane, not the console.",
    $note,
    "Type this into the Theme field and click Apply: $payload",
    "The provided forgeSolve() runs, and the workspace marks the level solved.",
  ],
  'why' => $why,
];
return [
  'low' => $common(
    "Inject an element whose handler calls forgeSolve().",
    '<img src=x onerror=forgeSolve()>',
    "At Low the value is written with innerHTML, which parses it as HTML.",
    "innerHTML parses your string as markup, so an element with an event handler executes immediately in the DOM."
  ),
  'medium' => $common(
    "Use a handler the <script> filter does not remove.",
    '<svg onload=forgeSolve()>',
    "A <script> pattern is stripped before innerHTML, but <svg onload> is not.",
    "Removing a <script> pattern leaves other parser-driven handlers; innerHTML still builds the element and fires onload."
  ),
  'high' => $common(
    "Defeat the on<word>= filter with whitespace.",
    '<img src=x onerror = forgeSolve()>',
    "The filter removes on<word>= only when there is no space around the equals.",
    "HTML allows spaces around an attribute's equals sign, so onerror = still binds a handler while the tight regex misses it."
  ),
  'secure' => [
    'goal' => "Confirm textContent prevents DOM XSS.",
    'steps' => [
      "The value is written with textContent, which never parses markup.",
      "Type <img src=x onerror=forgeSolve()> and Apply; it appears as literal text.",
    ],
    'why' => "textContent assigns the value as text, so the browser never interprets it as HTML and no handler can be created.",
  ],
];
