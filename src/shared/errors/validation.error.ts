import { AppError } from './base.error.js';

export class ValidationError extends AppError {
    constructor(details: any) {
        super('Validation failed', 422, details);
    }
}
