import dotenv from 'dotenv';
dotenv.config();

export const appConfig = {
    port: parseInt(process.env.PORT || '3000'),
    env: process.env.NODE_ENV || 'development',
    corsOrigins: process.env.CORS_ORIGINS?.split(',') || ['*']
};
