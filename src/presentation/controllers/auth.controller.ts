import { Request, Response, NextFunction } from 'express';
import { AuthService } from '../../domain/services/auth.service.js';
import { UserRepository } from '../../infrastructure/repositories/user.repository.js';
import { ApiResponse } from '../responses/api-response.js';

const userRepository = new UserRepository();
const authService = new AuthService(userRepository);

export class AuthController {
    async register(req: Request, res: Response, next: NextFunction) {
        try {
            const result = await authService.register(req.body);
            return ApiResponse.success(res, result, 'User registered successfully', 201);
        } catch (error) {
            next(error);
        }
    }

    async login(req: Request, res: Response, next: NextFunction) {
        try {
            const { email, password } = req.body;
            const result = await authService.login(email, password);
            return ApiResponse.success(res, result, 'Login successful');
        } catch (error) {
            next(error);
        }
    }

    async refresh(req: Request, res: Response, next: NextFunction) {
        try {
            const { refresh_token } = req.body;
            const result = await authService.refresh(refresh_token);
            return ApiResponse.success(res, result, 'Token refreshed successfully');
        } catch (error) {
            next(error);
        }
    }

    async logout(req: Request, res: Response, next: NextFunction) {
        try {
            const { refresh_token } = req.body;
            await authService.logout(refresh_token);
            return ApiResponse.success(res, null, 'Logged out successfully');
        } catch (error) {
            next(error);
        }
    }
}
