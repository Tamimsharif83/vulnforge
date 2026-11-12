<?php
declare(strict_types=1);

final class Db
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', getenv('DB_HOST'), getenv('DB_NAME'));
            self::$pdo = new PDO($dsn, getenv('DB_USER'), getenv('DB_PASS'), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }
        return self::$pdo;
    }

    /** Runs a string-built query. $input is the student-controlled part, marked in the trace. */
    public static function raw(string $sql, string $input = ''): array
    {
        $started = microtime(true);
        try {
            $rows = self::pdo()->query($sql)->fetchAll();
            Trace::sql($sql, $input, count($rows), microtime(true) - $started);
            return $rows;
        } catch (PDOException $error) {
            Trace::sql($sql, $input, 0, microtime(true) - $started, $error->getMessage());
            AppEvents::dbError($error->getCode() ? 'SQLSTATE[' . $error->getCode() . ']' : get_class($error), $error->getMessage());
            throw $error;
        }
    }

    /** Runs a parameterized query. The trace shows the template and bound values separately. */
    public static function prepared(string $sql, array $params = []): array
    {
        $started = microtime(true);
        try {
            $statement = self::pdo()->prepare($sql);
            $statement->execute($params);
            $rows = $statement->fetchAll();
        } catch (PDOException $error) {
            AppEvents::dbError($error->getCode() ? 'SQLSTATE[' . $error->getCode() . ']' : get_class($error), $error->getMessage());
            throw $error;
        }
        Trace::sql($sql, '', count($rows), microtime(true) - $started, null, $params);
        return $rows;
    }
}
