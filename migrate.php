#!/usr/bin/env php
<?php

declare(strict_types=1);

use App\Infrastructure\Database\Connection;
use App\Infrastructure\Database\MigrationRunner;

require __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$command = $argv[1] ?? 'help';

try {
    $pdo = Connection::getInstance();
    $migrationsPath = __DIR__ . '/src/Infrastructure/Database/Migrations';
    $runner = new MigrationRunner($pdo, $migrationsPath);

    switch ($command) {
        case 'migrate':
            echo "Running migrations...\n";
            $runner->run();
            break;

        case 'rollback':
            echo "Rolling back migrations...\n";
            $runner->rollback();
            break;

        case 'help':
        default:
            echo "Database Migration Tool\n";
            echo "Usage: php migrate.php [command]\n\n";
            echo "Commands:\n";
            echo "  migrate   - Run all pending migrations\n";
            echo "  rollback  - Rollback the last migration\n";
            echo "  help      - Show this help message\n";
            break;
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
