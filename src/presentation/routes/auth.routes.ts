import { Router } from 'express';
import { AuthController } from '../controllers/auth.controller.js';
import { validate } from '../../application/middleware/validation.middleware.js';
import { RegisterDTO, LoginDTO, RefreshDTO } from '../../application/dto/auth.dto.js';

const router = Router();
const authController = new AuthController();

router.post('/register', validate(RegisterDTO), authController.register);
router.post('/login', validate(LoginDTO), authController.login);
router.post('/refresh', validate(RefreshDTO), authController.refresh);
router.post('/logout', validate(RefreshDTO), authController.logout);

export default router;
