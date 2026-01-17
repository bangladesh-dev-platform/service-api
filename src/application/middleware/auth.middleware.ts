import { Request, Response, NextFunction } from 'express';
import { JwtService } from '../../domain/services/jwt.service.js';
import { AuthenticationError } from '../../shared/errors/authentication.error.js';

export interface AuthRequest extends Request {
    user?: {
        id: string;
        email: string;
        roles: string[];
        permissions: string[];
    };
}

export const authMiddleware = (
    req: AuthRequest,
    res: Response,
    next: NextFunction
) => {
    const authHeader = req.headers.authorization;

    if (!authHeader || !authHeader.startsWith('Bearer ')) {
        throw new AuthenticationError('Authorization header missing or invalid');
    }

    const token = authHeader.split(' ')[1];

    try {
        const payload = JwtService.verifyAccessToken(token);

        if (payload.type !== 'access') {
            throw new AuthenticationError('Invalid token type');
        }

        req.user = {
            id: payload.sub,
            email: payload.email,
            roles: payload.roles,
            permissions: payload.permissions
        };

        next();
    } catch (error: any) {
        if (error.name === 'TokenExpiredError') {
            throw new AuthenticationError('Token has expired');
        }
        throw new AuthenticationError('Invalid or expired token');
    }
};
