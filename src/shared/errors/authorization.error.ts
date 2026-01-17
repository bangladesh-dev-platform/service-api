import { AppError } from './base.error.js';

export class AuthorizationError extends AppError {
    constructor(message = 'Forbidden') {
        super(message, 403);
    }
}
