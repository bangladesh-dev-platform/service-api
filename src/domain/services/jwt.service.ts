import jwt from 'jsonwebtoken';
import { jwtConfig } from '../../config/jwt.config.js';

export interface JwtPayload {
    sub: string;
    email: string;
    roles: string[];
    permissions: string[];
    type: 'access' | 'refresh';
}

export class JwtService {
    static generateAccessToken(user: { id: string, email: string, roles: string[], permissions: string[] }): string {
        const payload: JwtPayload = {
            sub: user.id,
            email: user.email,
            roles: user.roles,
            permissions: user.permissions,
            type: 'access'
        };

        return jwt.sign(payload, jwtConfig.accessSecret, {
            expiresIn: jwtConfig.accessExpiry,
            issuer: jwtConfig.issuer
        });
    }

    static generateRefreshToken(userId: string): string {
        const payload = {
            sub: userId,
            type: 'refresh'
        };

        return jwt.sign(payload, jwtConfig.refreshSecret, {
            expiresIn: jwtConfig.refreshExpiry,
            issuer: jwtConfig.issuer
        });
    }

    static verifyAccessToken(token: string): JwtPayload {
        return jwt.verify(token, jwtConfig.accessSecret) as JwtPayload;
    }

    static verifyRefreshToken(token: string): any {
        return jwt.verify(token, jwtConfig.refreshSecret);
    }

    static decode(token: string): any {
        return jwt.decode(token);
    }
}
