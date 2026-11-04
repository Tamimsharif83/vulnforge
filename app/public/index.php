<?php
declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

student_id();
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

if ($path === '/') {
    render('dashboard', ['labs' => Lab::all(), 'solved' => Lab::solvedLevels()]);
} elseif ($path === '/learn') {
    render('learn', ['labs' => Lab::all()]);
} elseif (preg_match('#^/labs/([a-z0-9-]+)$#', $path, $m) && ($lab = Lab::find($m[1]))) {
    render('workspace', ['lab' => $lab, 'solved' => Lab::solvedLevels()[$m[1]] ?? []]);
} elseif (preg_match('#^/labs/([a-z0-9-]+)/run$#', $path, $m) && Lab::find($m[1])) {
    Lab::run($m[1]);
} elseif (preg_match('#^/labs/([a-z0-9-]+)/source$#', $path, $m) && Lab::find($m[1])) {
    json_out(Lab::sources($m[1]));
} elseif (preg_match('#^/trace/([a-f0-9]{24})$#', $path, $m)) {
    $trace = Trace::load($m[1]);
    $trace === null ? json_out(['error' => 'Trace not found'], 404) : json_out($trace);
} elseif ($path === '/console/send' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // The relayed request reuses this session; holding the lock here would deadlock it.
    session_write_close();
    json_out(Console::send(json_decode((string) file_get_contents('php://input'), true) ?: []));
} elseif ($path === '/level' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $level = in_array($_POST['level'] ?? '', LEVELS, true) ? $_POST['level'] : 'low';
    setcookie('vf_level', $level, ['path' => '/', 'samesite' => 'Lax']);
    json_out(['level' => $level]);
} else {
    http_response_code(404);
    render('notfound');
}
