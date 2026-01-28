<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use PDO;

class MigrationRunner
{
    private PDO $pdo;
    private string $migrationsPath;

    public function __construct(PDO $pdo, string $migrationsPath)
    {
        $this->pdo = $pdo;
        $this->migrationsPath = $migrationsPath;
    }

    /**
     * Run all pending migrations
     */
    public function run(): void
    {
        $this->createMigrationsTable();
        
        $migrations = $this->getPendingMigrations();
        
        if (empty($migrations)) {
            echo "No pending migrations.\n";
            return;
        }

        foreach ($migrations as $migration) {
            echo "Running migration: {$migration}\n";
            $this->runMigration($migration);
            echo "Migration {$migration} completed.\n";
        }

        echo "All migrations completed successfully.\n";
    }

    /**
     * Create migrations tracking table
     */
    private function createMigrationsTable(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS migrations (
                id SERIAL PRIMARY KEY,
                migration VARCHAR(255) NOT NULL UNIQUE,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ";
        
        $this->pdo->exec($sql);
    }

    /**
     * Get list of pending migrations
     */
    private function getPendingMigrations(): array
    {
        $files = glob($this->migrationsPath . '/*.sql');
        $migrationFiles = array_map('basename', $files);
        sort($migrationFiles);

        $stmt = $this->pdo->query('SELECT migration FROM migrations');
        $executed = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return array_diff($migrationFiles, $executed);
    }

    /**
     * Run a single migration
     */
    private function runMigration(string $migration): void
    {
        $filePath = $this->migrationsPath . '/' . $migration;
        $sql = file_get_contents($filePath);

        if ($sql === false) {
            throw new \RuntimeException("Could not read migration file: {$migration}");
        }

        try {
            $this->pdo->beginTransaction();
            
            // Execute the migration SQL
            $this->pdo->exec($sql);
            
            // Record the migration
            $stmt = $this->pdo->prepare('INSERT INTO migrations (migration) VALUES (?)');
            $stmt->execute([$migration]);
            
            $this->pdo->commit();
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw new \RuntimeException(
                "Migration {$migration} failed: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Rollback last migration (optional, for future use)
     */
    public function rollback(): void
    {
        $stmt = $this->pdo->query(
            'SELECT migration FROM migrations ORDER BY executed_at DESC LIMIT 1'
        );
        
        $lastMigration = $stmt->fetchColumn();
        
        if (!$lastMigration) {
            echo "No migrations to rollback.\n";
            return;
        }

        echo "Note: Rollback requires manual intervention. Last migration: {$lastMigration}\n";
    }
}
