import app from './app.js';
import { appConfig } from './config/app.config.js';
import db from './infrastructure/database/connection.js';

async function bootstrap() {
    try {
        console.log('🚀 Starting Bangladesh Digital Auth API (Node.js)...');

        // 1. Check database connection
        await db.connectDatabase();
        console.log('✓ PostgreSQL connected');

        // 2. Start Express server
        const server = app.listen(appConfig.port, () => {
            console.log(`✓ Server listening on port ${appConfig.port}`);
            console.log(`✓ Environment: ${appConfig.env}`);
            console.log(`✓ API Base: http://localhost:${appConfig.port}/api/v1`);
        });

        // Handle graceful shutdown
        const shutdown = async () => {
            console.log('Shutting down gracefully...');
            server.close(async () => {
                await db.pool.end();
                console.log('Process terminated');
                process.exit(0);
            });
        };

        process.on('SIGTERM', shutdown);
        process.on('SIGINT', shutdown);

    } catch (error) {
        console.error('❌ Failed to start server:', error);
        process.exit(1);
    }
}

bootstrap();
