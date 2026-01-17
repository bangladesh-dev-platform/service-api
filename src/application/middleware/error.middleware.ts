import { Request, Response, NextFunction } from 'express';
import { AppError } from '../../shared/errors/base.error.js';

export const errorMiddleware = (
    err: any,
    req: Request,
    res: Response,
    next: NextFunction
) => {
    console.error(`[Error] ${req.method} ${req.url}`, err);

    const statusCode = err instanceof AppError ? err.statusCode : 500;
    const message = err.message || 'Internal Server Error';
    const details = err.details || null;

    res.status(statusCode).json({
        success: false,
        error: {
            message,
            details,
            code: err.name
        }
    });
};
