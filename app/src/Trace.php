<?php
declare(strict_types=1);

/** Records what the server did during a lab request, for the What Happened tab. */
final class Trace
{
    private static ?string $id = null;
    private static array $events = [];

    public static function begin(): string
    {
        self::$id = bin2hex(random_bytes(12));
        return self::$id;
    }

    public static function sql(string $sql, string $input, int $rows, float $seconds, ?string $error = null, ?array $params = null): void
    {
        $start = $input === '' ? false : strpos($sql, $input);
        self::$events[] = [
            'type' => 'sql',
            'text' => $sql,
            'mark' => $start === false ? null : [$start, $start + strlen($input)],
            'rows' => $rows,
            'ms' => (int) round($seconds * 1000),
            'error' => $error,
            'params' => $params,
        ];
    }

    public static function note(string $text): void
    {
        self::$events[] = ['type' => 'note', 'text' => $text];
    }

    /**
     * A labeled step for the What Happened tab: $label names the sink, $text is
     * what the server built or resolved, and $input (if given and present in
     * $text) is highlighted so the student sees exactly which bytes were theirs.
     */
    public static function step(string $label, string $text, string $input = '', string $note = ''): void
    {
        $start = $input === '' ? false : strpos($text, $input);
        self::$events[] = [
            'type' => 'step',
            'label' => $label,
            'text' => $text,
            'mark' => $start === false ? null : [$start, $start + strlen($input)],
            'note' => $note,
        ];
    }

    public static function save(string $slug, string $level): void
    {
        if (self::$id === null) {
            return;
        }
        if (!is_dir(TRACE_DIR)) {
            mkdir(TRACE_DIR, 0700, true);
        }
        file_put_contents(TRACE_DIR . '/' . self::$id . '.json', json_encode([
            'slug' => $slug,
            'level' => $level,
            'events' => self::$events,
        ], JSON_INVALID_UTF8_SUBSTITUTE));
    }

    public static function load(string $id): ?array
    {
        if (!preg_match('/^[a-f0-9]{24}$/', $id)) {
            return null;
        }
        $file = TRACE_DIR . "/$id.json";
        return is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
    }
}
