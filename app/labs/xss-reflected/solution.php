<?php
$p = '/labs/xss-reflected/run?q=';
return [
  'low' => [
    'goal' => "Inject a script into the HTML body where your term is echoed.",
    'steps' => [
      "Your term lands directly in <p>No results for INPUT</p>.",
      "Send <script>alert(1)</script>; the browser parses it as a real script element.",
    ],
    'why' => "The server wrote your input into the page without encoding, so the browser cannot tell your markup from the page's own. The script element runs in the victim's session.",
    'run' => [['method' => 'GET', 'path' => $p . rawurlencode('<script>alert(1)</script>')]],
  ],
  'medium' => [
    'goal' => "Run script without a <script> tag, which is stripped.",
    'steps' => [
      "<script> is removed, but event-handler attributes on other tags still run.",
      "Send <img src=x onerror=alert(1)>: the broken image triggers onerror.",
    ],
    'why' => "Script execution does not require a <script> tag. An element with an event handler runs JavaScript when the event fires, so removing only <script> leaves many working vectors.",
    'run' => [['method' => 'GET', 'path' => $p . rawurlencode('<img src=x onerror=alert(1)>')]],
  ],
  'high' => [
    'goal' => "Break out of an attribute value when angle brackets are stripped.",
    'steps' => [
      "Your input sits inside value=\"INPUT\" and < > are removed, so you cannot add a new tag.",
      "Close the attribute with a quote and add your own handler: \" autofocus onfocus=\"alert(1)",
      "autofocus fires onfocus immediately without user interaction.",
    ],
    'why' => "You do not need a new tag when you can add attributes to the existing one. The quote escapes the value, and autofocus+onfocus gives you execution purely from attribute injection.",
    'run' => [['method' => 'GET', 'path' => $p . rawurlencode('" autofocus onfocus="alert(1)')]],
  ],
  'secure' => [
    'goal' => "Confirm output encoding neutralizes the payload.",
    'steps' => [
      "The term is passed through htmlspecialchars with ENT_QUOTES before output.",
      "Send <script>alert(1)</script> and view it rendered as inert text.",
    ],
    'why' => "Encoding converts < > \" ' into entities, so the browser shows them as characters instead of parsing them as markup. The same input is now harmless data.",
    'run' => [['method' => 'GET', 'path' => $p . rawurlencode('<script>alert(1)</script>')]],
  ],
];
