import { validationResult } from 'express-validator';
import memberService from '../services/memberService.js';
import logger from '../config/logger.js';

export class MemberController {
  async getProfile(req, res) {
    try {
      const memberId = req.user.id;
      const member = await memberService.getProfile(memberId);

      res.json({ member });
    } catch (error) {
      logger.error('Get profile error:', error);
      res.status(500).json({ message: error.message });
    }
  }

  async updateProfile(req, res) {
    try {
      const errors = validationResult(req);
      if (!errors.isEmpty()) {
        return res.status(400).json({ errors: errors.array() });
      }

      const memberId = req.user.id;
      const member = await memberService.updateProfile(memberId, req.body);

      res.json({
        message: 'Profile updated successfully',
        member
      });
    } catch (error) {
      logger.error('Update profile error:', error);
      res.status(500).json({ message: error.message });
    }
  }

  async getMember(req, res) {
    try {
      const { id } = req.params;
      const member = await memberService.getMemberById(id);

      if (!member) {
        return res.status(404).json({ message: 'Member not found' });
      }

      res.json({ member });
    } catch (error) {
      logger.error('Get member error:', error);
      res.status(500).json({ message: error.message });
    }
  }

  async getAllMembers(req, res) {
    try {
      const { limit = 10, offset = 0 } = req.query;
      const result = await memberService.getAllMembers(
        parseInt(limit),
        parseInt(offset)
      );

      res.json(result);
    } catch (error) {
      logger.error('Get all members error:', error);
      res.status(500).json({ message: error.message });
    }
  }
}

export default new MemberController();
