<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Database connection manager (PDO singleton per connection name).
 */
class Database
{
    private static ?\PDO $pdo = null;

    public static function connection(): \PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $cfg = require CONFIG_PATH . 'database.php';

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $cfg['host'],
            $cfg['port'],
            $cfg['database']
        );

        $options = [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => false,
            \PDO::ATTR_STRINGIFY_FETCHES  => false,
        ];

        try {
            self::$pdo = new \PDO($dsn, $cfg['username'], $cfg['password'], $options);
        } catch (\PDOException $e) {
            if (APP_DEBUG) {
                throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
            }
            throw new \RuntimeException('Database connection failed.');
        }

        return self::$pdo;
    }

    /** Shortcut: run a prepared statement and return the PDOStatement. */
    public static function run(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function lastId(): string
    {
        return self::connection()->lastInsertId();
    }

    public static function beginTransaction(): bool
    {
        return self::connection()->beginTransaction();
    }

    public static function commit(): bool
    {
        return self::connection()->commit();
    }

    public static function rollBack(): bool
    {
        return self::connection()->rollBack();
    }

    public static function inTransaction(): bool
    {
        return self::connection()->inTransaction();
    }
}
