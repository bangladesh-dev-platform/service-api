import { Request, Response, NextFunction } from 'express';
import { AnyZodObject, ZodError } from 'zod';
import { ValidationError } from '../../shared/errors/validation.error.js';

export const validate = (schema: AnyZodObject) => {
    return async (req: Request, res: Response, next: NextFunction) => {
        try {
            await schema.parseAsync({
                body: req.body,
                query: req.query,
                params: req.params
            });
            next();
        } catch (error) {
            if (error instanceof ZodError) {
                const details: Record<string, string[]> = {};
                error.errors.forEach(err => {
                    const path = err.path.slice(1).join('.'); // Remove 'body', 'query', etc.
                    if (!details[path]) details[path] = [];
                    details[path].push(err.message);
                });
                next(new ValidationError(details));
            } else {
                next(error);
            }
        }
    };
};
