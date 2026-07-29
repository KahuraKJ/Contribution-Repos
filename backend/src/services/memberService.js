import { Member } from '../models/index.js';
import logger from '../config/logger.js';

export class MemberService {
  async getProfile(memberId) {
    try {
      const member = await Member.findByPk(memberId, {
        attributes: { exclude: ['passwordHash'] }
      });

      if (!member) {
        throw new Error('Member not found');
      }

      return member;
    } catch (error) {
      logger.error('Get profile error:', error);
      throw error;
    }
  }

  async updateProfile(memberId, updateData) {
    try {
      const member = await Member.findByPk(memberId);

      if (!member) {
        throw new Error('Member not found');
      }

      // Only allow updating specific fields
      const allowedFields = ['firstName', 'lastName', 'phone', 'idNumber'];
      const updateFields = {};

      for (const field of allowedFields) {
        if (updateData[field] !== undefined) {
          updateFields[field] = updateData[field];
        }
      }

      await member.update(updateFields);
      logger.info(`Profile updated for member: ${member.email}`);

      return member;
    } catch (error) {
      logger.error('Update profile error:', error);
      throw error;
    }
  }

  async getMemberById(memberId) {
    try {
      const member = await Member.findByPk(memberId, {
        attributes: { exclude: ['passwordHash'] }
      });

      return member;
    } catch (error) {
      logger.error('Get member error:', error);
      throw error;
    }
  }

  async getAllMembers(limit = 10, offset = 0) {
    try {
      const { count, rows } = await Member.findAndCountAll({
        attributes: { exclude: ['passwordHash'] },
        limit,
        offset,
        order: [['createdAt', 'DESC']]
      });

      return { total: count, members: rows };
    } catch (error) {
      logger.error('Get all members error:', error);
      throw error;
    }
  }
}

export default new MemberService();
