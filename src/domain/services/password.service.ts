import bcrypt from 'bcrypt';
import { ValidationError } from '../../shared/errors/validation.error.js';

export class PasswordService {
    private static readonly SALT_ROUNDS = 12;

    static async hash(password: string): Promise<string> {
        return bcrypt.hash(password, this.SALT_ROUNDS);
    }

    static async verify(password: string, hash: string): Promise<boolean> {
        return bcrypt.compare(password, hash);
    }

    static validate(password: string): void {
        const errors: string[] = [];

        if (password.length < 8) {
            errors.push('Password must be at least 8 characters long');
        }
        if (!/[A-Z]/.test(password)) {
            errors.push('Password must contain at least one uppercase letter');
        }
        if (!/[a-z]/.test(password)) {
            errors.push('Password must contain at least one lowercase letter');
        }
        if (!/[0-9]/.test(password)) {
            errors.push('Password must contain at least one number');
        }

        if (errors.length > 0) {
            throw new ValidationError(errors);
        }
    }
}
