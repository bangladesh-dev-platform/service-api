import { Response, NextFunction } from 'express';
import { AuthRequest } from '../../application/middleware/auth.middleware.js';
import { UserRepository } from '../../infrastructure/repositories/user.repository.js';
import { ApiResponse } from '../responses/api-response.js';

const userRepository = new UserRepository();

export class UserController {
    async getProfile(req: AuthRequest, res: Response, next: NextFunction) {
        try {
            const userId = req.user!.id;
            const user = await userRepository.findById(userId);

            if (!user) {
                return ApiResponse.error(res, 'User not found', 404);
            }

            return ApiResponse.success(res, user.toJSON());
        } catch (error) {
            next(error);
        }
    }

    async updateProfile(req: AuthRequest, res: Response, next: NextFunction) {
        try {
            const userId = req.user!.id;
            const user = await userRepository.update(userId, req.body);
            return ApiResponse.success(res, user.toJSON(), 'Profile updated successfully');
        } catch (error) {
            next(error);
        }
    }

    async listUsers(req: AuthRequest, res: Response, next: NextFunction) {
        try {
            // Check for admin role
            if (!req.user!.roles.includes('admin')) {
                return ApiResponse.error(res, 'Unauthorized', 403);
            }

            const page = parseInt(req.query.page as string || '1');
            const perPage = parseInt(req.query.per_page as string || '10');

            const { users, total } = await userRepository.list({ page, perPage });

            return ApiResponse.paginated(
                res,
                users.map(u => u.toJSON()),
                page,
                perPage,
                total
            );
        } catch (error) {
            next(error);
        }
    }
}
