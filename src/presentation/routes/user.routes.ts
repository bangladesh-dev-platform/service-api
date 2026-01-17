import { Router } from 'express';
import { UserController } from '../controllers/user.controller.js';
import { authMiddleware } from '../../application/middleware/auth.middleware.js';

const router = Router();
const userController = new UserController();

// All user routes are protected
router.use(authMiddleware);

router.get('/me', userController.getProfile);
router.put('/me', userController.updateProfile);
router.get('/', userController.listUsers);

export default router;
