import bcryptjs from 'bcryptjs';
import jwt from 'jsonwebtoken';
import { Member } from '../models/index.js';
import { jwtConfig } from '../config/jwt.js';
import logger from '../config/logger.js';

export class AuthService {
  async register(userData) {
    try {
      // Check if user already exists
      const existingMember = await Member.findOne({
        where: { email: userData.email }
      });

      if (existingMember) {
        throw new Error('Email already registered');
      }

      // Hash password
      const salt = await bcryptjs.genSalt(10);
      const passwordHash = await bcryptjs.hash(userData.password, salt);

      // Create member
      const member = await Member.create({
        email: userData.email,
        firstName: userData.firstName,
        lastName: userData.lastName,
        phone: userData.phone || null,
        idNumber: userData.idNumber || null,
        passwordHash,
        role: 'member'
      });

      logger.info(`New member registered: ${member.email}`);

      return {
        id: member.id,
        email: member.email,
        firstName: member.firstName,
        lastName: member.lastName,
        role: member.role
      };
    } catch (error) {
      logger.error('Registration error:', error);
      throw error;
    }
  }

  async login(email, password) {
    try {
      const member = await Member.findOne({
        where: { email }
      });

      if (!member) {
        throw new Error('Invalid credentials');
      }

      if (!member.isActive) {
        throw new Error('Account is inactive');
      }

      // Verify password
      const isPasswordValid = await bcryptjs.compare(password, member.passwordHash);
      if (!isPasswordValid) {
        throw new Error('Invalid credentials');
      }

      // Update last login
      await member.update({ lastLogin: new Date() });

      // Generate tokens
      const tokens = this.generateTokens(member);

      logger.info(`Member logged in: ${member.email}`);

      return {
        member: {
          id: member.id,
          email: member.email,
          firstName: member.firstName,
          lastName: member.lastName,
          role: member.role
        },
        ...tokens
      };
    } catch (error) {
      logger.error('Login error:', error);
      throw error;
    }
  }

  generateTokens(member) {
    const accessToken = jwt.sign(
      {
        id: member.id,
        email: member.email,
        role: member.role
      },
      jwtConfig.secret,
      { expiresIn: jwtConfig.accessExpiry }
    );

    const refreshToken = jwt.sign(
      { id: member.id },
      jwtConfig.secret,
      { expiresIn: jwtConfig.refreshExpiry }
    );

    return { accessToken, refreshToken };
  }

  async refreshToken(refreshToken) {
    try {
      const decoded = jwt.verify(refreshToken, jwtConfig.secret);
      const member = await Member.findByPk(decoded.id);

      if (!member || !member.isActive) {
        throw new Error('Invalid token or inactive member');
      }

      const newTokens = this.generateTokens(member);
      return newTokens;
    } catch (error) {
      logger.error('Token refresh error:', error);
      throw error;
    }
  }

  async changePassword(memberId, oldPassword, newPassword) {
    try {
      const member = await Member.findByPk(memberId);

      if (!member) {
        throw new Error('Member not found');
      }

      // Verify old password
      const isOldPasswordValid = await bcryptjs.compare(oldPassword, member.passwordHash);
      if (!isOldPasswordValid) {
        throw new Error('Current password is incorrect');
      }

      // Hash and update new password
      const salt = await bcryptjs.genSalt(10);
      const newPasswordHash = await bcryptjs.hash(newPassword, salt);
      await member.update({ passwordHash: newPasswordHash });

      logger.info(`Password changed for member: ${member.email}`);
      return { message: 'Password changed successfully' };
    } catch (error) {
      logger.error('Password change error:', error);
      throw error;
    }
  }
}

export default new AuthService();
