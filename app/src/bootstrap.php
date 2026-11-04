<?php
declare(strict_types=1);

const LEVELS = ['low', 'medium', 'high', 'secure'];
const LABS_DIR = __DIR__ . '/../labs';
const TRACE_DIR = '/tmp/vf-traces';
const SECRET_FILE = '/opt/forge/flag.txt';

session_name('forge_session');
session_start();

require __DIR__ . '/Db.php';
require __DIR__ . '/Trace.php';
require __DIR__ . '/Lab.php';
require __DIR__ . '/Console.php';

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function current_level(): string
{
    $level = $_COOKIE['vf_level'] ?? 'low';
    return in_array($level, LEVELS, true) ? $level : 'low';
}

// Progress follows the student, not whichever ForgeShop account a lab logs into.
function student_id(): string
{
    $id = $_COOKIE['vf_student'] ?? '';
    if (!preg_match('/^[a-f0-9]{32}$/', $id)) {
        $id = bin2hex(random_bytes(16));
        setcookie('vf_student', $id, ['expires' => time() + 31536000, 'path' => '/', 'samesite' => 'Lax', 'httponly' => true]);
    }
    return $id;
}

function render(string $view, array $vars = []): void
{
    extract($vars);
    ob_start();
    require __DIR__ . "/../views/$view.php";
    $content = ob_get_clean();
    require __DIR__ . '/../views/layout.php';
}

function json_out(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
}
