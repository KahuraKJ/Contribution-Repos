import { validationResult } from 'express-validator';
import authService from '../services/authService.js';
import logger from '../config/logger.js';

export class AuthController {
  async register(req, res) {
    try {
      // Check for validation errors
      const errors = validationResult(req);
      if (!errors.isEmpty()) {
        return res.status(400).json({ errors: errors.array() });
      }

      const { email, password, firstName, lastName, phone, idNumber } = req.body;

      const member = await authService.register({
        email,
        password,
        firstName,
        lastName,
        phone,
        idNumber
      });

      res.status(201).json({
        message: 'Member registered successfully',
        member
      });
    } catch (error) {
      logger.error('Register error:', error);
      res.status(400).json({ message: error.message });
    }
  }

  async login(req, res) {
    try {
      const errors = validationResult(req);
      if (!errors.isEmpty()) {
        return res.status(400).json({ errors: errors.array() });
      }

      const { email, password } = req.body;
      const result = await authService.login(email, password);

      // Set refresh token as httpOnly cookie
      res.cookie('refreshToken', result.refreshToken, {
        httpOnly: true,
        secure: process.env.NODE_ENV === 'production',
        sameSite: 'strict',
        maxAge: 7 * 24 * 60 * 60 * 1000 // 7 days
      });

      res.json({
        message: 'Login successful',
        accessToken: result.accessToken,
        member: result.member
      });
    } catch (error) {
      logger.error('Login error:', error);
      res.status(401).json({ message: error.message });
    }
  }

  async refreshToken(req, res) {
    try {
      const { refreshToken } = req.body;

      if (!refreshToken) {
        return res.status(400).json({ message: 'Refresh token required' });
      }

      const tokens = await authService.refreshToken(refreshToken);

      res.json({
        accessToken: tokens.accessToken,
        refreshToken: tokens.refreshToken
      });
    } catch (error) {
      logger.error('Token refresh error:', error);
      res.status(401).json({ message: error.message });
    }
  }

  async changePassword(req, res) {
    try {
      const errors = validationResult(req);
      if (!errors.isEmpty()) {
        return res.status(400).json({ errors: errors.array() });
      }

      const { oldPassword, newPassword } = req.body;
      const memberId = req.user.id;

      const result = await authService.changePassword(memberId, oldPassword, newPassword);

      res.json(result);
    } catch (error) {
      logger.error('Password change error:', error);
      res.status(400).json({ message: error.message });
    }
  }

  async logout(req, res) {
    res.clearCookie('refreshToken');
    res.json({ message: 'Logged out successfully' });
  }
}

export default new AuthController();
