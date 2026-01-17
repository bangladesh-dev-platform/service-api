import { z } from 'zod';

export const RegisterDTO = z.object({
    body: z.object({
        email: z.string().email('Invalid email address'),
        password: z.string().min(8, 'Password must be at least 8 characters long'),
        first_name: z.string().optional(),
        last_name: z.string().optional(),
        phone: z.string().optional()
    })
});

export const LoginDTO = z.object({
    body: z.object({
        email: z.string().email('Invalid email address'),
        password: z.string()
    })
});

export const RefreshDTO = z.object({
    body: z.object({
        refresh_token: z.string()
    })
});
