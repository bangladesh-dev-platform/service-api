import { UserRepositoryInterface } from '../../domain/repositories/user.repository.interface.js';
import { User, UserProps } from '../../domain/entities/user.entity.js';
import db from '../database/connection.js';
import { v4 as uuidv4 } from 'uuid';

export class UserRepository implements UserRepositoryInterface {
    async findById(id: string): Promise<User | null> {
        const userResult = await db.query(
            'SELECT * FROM users WHERE id = $1',
            [id]
        );

        if (userResult.rowCount === 0) return null;

        return this.mapToUser(userResult.rows[0]);
    }

    async findByEmail(email: string): Promise<User | null> {
        const userResult = await db.query(
            'SELECT * FROM users WHERE email = $1',
            [email]
        );

        if (userResult.rowCount === 0) return null;

        return this.mapToUser(userResult.rows[0]);
    }

    async findByEmailWithPassword(email: string): Promise<{ user: User, passwordHash: string } | null> {
        const userResult = await db.query(
            'SELECT * FROM users WHERE email = $1',
            [email]
        );

        if (userResult.rowCount === 0) return null;

        const row = userResult.rows[0];
        const user = await this.mapToUser(row);

        return {
            user,
            passwordHash: row.password_hash
        };
    }

    async create(userData: any): Promise<User> {
        const client = await db.getClient();
        try {
            await client.query('BEGIN');

            const id = uuidv4();
            const userResult = await client.query(
                `INSERT INTO users (id, email, password_hash, first_name, last_name, phone)
         VALUES ($1, $2, $3, $4, $5, $6)
         RETURNING *`,
                [
                    id,
                    userData.email,
                    userData.password_hash,
                    userData.first_name || null,
                    userData.last_name || null,
                    userData.phone || null
                ]
            );

            // Default role: user
            await client.query(
                'INSERT INTO user_roles (user_id, role) VALUES ($1, $2)',
                [id, 'user']
            );

            await client.query('COMMIT');

            return this.mapToUser(userResult.rows[0], ['user'], []);
        } catch (error) {
            await client.query('ROLLBACK');
            throw error;
        } finally {
            client.release();
        }
    }

    async update(id: string, userData: any): Promise<User> {
        const updates: string[] = [];
        const values: any[] = [];
        let i = 1;

        const allowedUpdates = ['first_name', 'last_name', 'phone', 'email_verified'];

        for (const key of allowedUpdates) {
            if (userData[key] !== undefined) {
                updates.push(`${key} = $${i}`);
                values.push(userData[key]);
                i++;
            }
        }

        if (updates.length === 0) {
            const user = await this.findById(id);
            if (!user) throw new Error('User not found');
            return user;
        }

        values.push(id);
        const userResult = await db.query(
            `UPDATE users SET ${updates.join(', ')} WHERE id = $${i} RETURNING *`,
            values
        );

        if (userResult.rowCount === 0) throw new Error('User not found');

        return this.mapToUser(userResult.rows[0]);
    }

    async delete(id: string): Promise<boolean> {
        const result = await db.query('DELETE FROM users WHERE id = $1', [id]);
        return result.rowCount! > 0;
    }

    async list(params: { page: number, perPage: number }): Promise<{ users: User[], total: number }> {
        const offset = (params.page - 1) * params.perPage;

        const countResult = await db.query('SELECT COUNT(*) FROM users');
        const total = parseInt(countResult.rows[0].count);

        const userResult = await db.query(
            'SELECT * FROM users ORDER BY created_at DESC LIMIT $1 OFFSET $2',
            [params.perPage, offset]
        );

        const users = await Promise.all(userResult.rows.map(row => this.mapToUser(row)));

        return { users, total };
    }

    async saveRefreshToken(userId: string, token: string, expiresAt: Date): Promise<void> {
        await db.query(
            'INSERT INTO refresh_tokens (user_id, token, expires_at) VALUES ($1, $2, $3)',
            [userId, token, expiresAt]
        );
    }

    async findRefreshToken(token: string): Promise<{ userId: string, expiresAt: Date } | null> {
        const result = await db.query(
            'SELECT user_id, expires_at FROM refresh_tokens WHERE token = $1',
            [token]
        );

        if (result.rowCount === 0) return null;

        return {
            userId: result.rows[0].user_id,
            expiresAt: result.rows[0].expires_at
        };
    }

    async deleteRefreshToken(token: string): Promise<void> {
        await db.query('DELETE FROM refresh_tokens WHERE token = $1', [token]);
    }

    async deleteUserRefreshTokens(userId: string): Promise<void> {
        await db.query('DELETE FROM refresh_tokens WHERE user_id = $1', [userId]);
    }

    private async mapToUser(row: any, overrideRoles?: string[], overridePermissions?: string[]): Promise<User> {
        const roles = overrideRoles || await this.getUserRoles(row.id);
        const permissions = overridePermissions || await this.getUserPermissions(row.id);

        return new User({
            id: row.id,
            email: row.email,
            first_name: row.first_name,
            last_name: row.last_name,
            phone: row.phone,
            email_verified: row.email_verified,
            roles,
            permissions,
            created_at: row.created_at,
            updated_at: row.updated_at
        });
    }

    private async getUserRoles(userId: string): Promise<string[]> {
        const result = await db.query(
            'SELECT role FROM user_roles WHERE user_id = $1',
            [userId]
        );
        return result.rows.map(row => row.role);
    }

    private async getUserPermissions(userId: string): Promise<string[]> {
        const result = await db.query(
            'SELECT permission FROM user_permissions WHERE user_id = $1',
            [userId]
        );
        return result.rows.map(row => row.permission);
    }
}
