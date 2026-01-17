import fs from 'fs';
import path from 'path';
import db from '../src/infrastructure/database/connection.js';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

async function migrate() {
    const client = await db.getClient();
    try {
        console.log('🚀 Running migrations...');

        // 1. Create migrations table if not exists
        await client.query(`
      CREATE TABLE IF NOT EXISTS migrations (
        id SERIAL PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);

        // 2. Read migration files
        const migrationsDir = path.join(__dirname, '../src/infrastructure/database/migrations');
        const files = fs.readdirSync(migrationsDir)
            .filter(f => f.endsWith('.sql'))
            .sort();

        // 3. Get executed migrations
        const executed = await client.query('SELECT name FROM migrations');
        const executedNames = executed.rows.map(r => r.name);

        // 4. Run pending migrations
        for (const file of files) {
            if (!executedNames.includes(file)) {
                console.log(`  → Executing ${file}...`);
                const sql = fs.readFileSync(path.join(migrationsDir, file), 'utf8');

                await client.query('BEGIN');
                try {
                    await client.query(sql);
                    await client.query('INSERT INTO migrations (name) VALUES ($1)', [file]);
                    await client.query('COMMIT');
                    console.log(`  ✓ ${file} completed`);
                } catch (error) {
                    await client.query('ROLLBACK');
                    console.error(`  ❌ Error in ${file}:`, error);
                    throw error;
                }
            }
        }

        console.log('✨ All migrations completed successfully!');
    } catch (error) {
        console.error('❌ Migration failed:', error);
        process.exit(1);
    } finally {
        client.release();
        process.exit(0);
    }
}

migrate();
