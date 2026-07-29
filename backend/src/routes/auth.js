import express from 'express';
import authController from '../controllers/authController.js';
import { authenticate } from '../middleware/auth.js';
import {
  validateRegister,
  validateLogin,
  validateChangePassword
} from '../middleware/validation.js';

const router = express.Router();

// Public routes
router.post('/register', validateRegister, (req, res) => authController.register(req, res));
router.post('/login', validateLogin, (req, res) => authController.login(req, res));
router.post('/refresh', (req, res) => authController.refreshToken(req, res));

// Protected routes
router.post('/change-password', authenticate, validateChangePassword, (req, res) =>
  authController.changePassword(req, res)
);
router.post('/logout', authenticate, (req, res) => authController.logout(req, res));

export default router;
