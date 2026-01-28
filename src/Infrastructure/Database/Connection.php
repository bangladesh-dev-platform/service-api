<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use PDO;
use PDOException;

class Connection
{
    private static ?PDO $instance = null;

    /**
     * Get database connection instance (singleton pattern)
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../../../config/database.php';
            
            $dsn = sprintf(
                '%s:host=%s;port=%d;dbname=%s',
                $config['driver'],
                $config['host'],
                $config['port'],
                $config['database']
            );

            try {
                self::$instance = new PDO(
                    $dsn,
                    $config['username'],
                    $config['password'],
                    $config['options']
                );
            } catch (PDOException $e) {
                throw new \RuntimeException(
                    'Database connection failed: ' . $e->getMessage(),
                    (int)$e->getCode(),
                    $e
                );
            }
        }

        return self::$instance;
    }

    /**
     * Close the database connection
     */
    public static function close(): void
    {
        self::$instance = null;
    }

    /**
     * Begin a transaction
     */
    public static function beginTransaction(): bool
    {
        return self::getInstance()->beginTransaction();
    }

    /**
     * Commit a transaction
     */
    public static function commit(): bool
    {
        return self::getInstance()->commit();
    }

    /**
     * Rollback a transaction
     */
    public static function rollback(): bool
    {
        return self::getInstance()->rollBack();
    }

    /**
     * Prevent cloning
     */
    private function __clone()
    {
    }

    /**
     * Prevent unserialization
     */
    public function __wakeup()
    {
        throw new \RuntimeException('Cannot unserialize singleton');
    }
}
