<?php
declare(strict_types=1);

final class Lab
{
    public static function all(): array
    {
        $labs = [];
        foreach (glob(LABS_DIR . '/*/meta.php') as $file) {
            $slug = basename(dirname($file));
            $labs[$slug] = ['slug' => $slug] + require $file;
        }
        uasort($labs, fn ($a, $b) => [$a['order'], $a['title']] <=> [$b['order'], $b['title']]);
        return $labs;
    }

    public static function find(string $slug): ?array
    {
        return self::all()[$slug] ?? null;
    }

    /** Executes the handler for the active level. This is the code the Source tab displays. */
    public static function run(string $slug): void
    {
        $level = current_level();
        header('X-VF-Trace: ' . Trace::begin());
        header('X-VF-Level: ' . $level);
        // Time-based labs hold a request open; other requests must not queue on the session lock.
        session_write_close();
        try {
            require LABS_DIR . "/$slug/$level.php";
        } finally {
            Trace::save($slug, $level);
        }
    }

    /** Per-level walkthrough data for the Solution tab, if the lab ships one. */
    public static function solution(string $slug): ?array
    {
        $file = LABS_DIR . "/$slug/solution.php";
        return is_file($file) ? require $file : null;
    }

    public static function sources(string $slug): array
    {
        $sources = [];
        foreach (LEVELS as $level) {
            $sources[$level] = (string) file_get_contents(LABS_DIR . "/$slug/$level.php");
        }
        return $sources;
    }

    public static function markSolved(string $slug, string $level): void
    {
        $statement = Db::pdo()->prepare('INSERT IGNORE INTO lab_progress (student, slug, level) VALUES (?, ?, ?)');
        $statement->execute([student_id(), $slug, $level]);
        header('X-VF-Solved: 1');
    }

    public static function solvedLevels(): array
    {
        $statement = Db::pdo()->prepare('SELECT slug, level FROM lab_progress WHERE student = ?');
        $statement->execute([student_id()]);
        $solved = [];
        foreach ($statement->fetchAll() as $row) {
            $solved[$row['slug']][] = $row['level'];
        }
        return $solved;
    }
}
