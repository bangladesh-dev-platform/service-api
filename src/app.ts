import express from 'express';
import { errorMiddleware } from './application/middleware/error.middleware.js';
import { corsMiddleware } from './application/middleware/cors.middleware.js';
import routes from './presentation/routes/index.js';

const app = express();

// Global Middleware
app.use(express.json());
app.use(express.urlencoded({ extended: true }));
app.use(corsMiddleware);

// Routes
app.use('/api/v1', routes);

// Health check
app.get('/health', (req, res) => {
    res.status(200).json({ status: 'OK', timestamp: new Date().toISOString() });
});

// 404 handler
app.use((req, res) => {
    res.status(404).json({
        success: false,
        error: {
            message: `Route ${req.method} ${req.url} not found`,
            code: 'NOT_FOUND'
        }
    });
});

// Error handling
app.use(errorMiddleware);

export default app;
