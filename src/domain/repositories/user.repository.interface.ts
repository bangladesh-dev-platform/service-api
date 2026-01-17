import { User } from '../entities/user.entity.js';

export interface UserRepositoryInterface {
    findById(id: string): Promise<User | null>;
    findByEmail(email: string): Promise<User | null>;
    findByEmailWithPassword(email: string): Promise<{ user: User, passwordHash: string } | null>;
    create(userData: any): Promise<User>;
    update(id: string, userData: any): Promise<User>;
    delete(id: string): Promise<boolean>;
    list(params: { page: number, perPage: number }): Promise<{ users: User[], total: number }>;

    // Refresh tokens
    saveRefreshToken(userId: string, token: string, expiresAt: Date): Promise<void>;
    findRefreshToken(token: string): Promise<{ userId: string, expiresAt: Date } | null>;
    deleteRefreshToken(token: string): Promise<void>;
    deleteUserRefreshTokens(userId: string): Promise<void>;
}
