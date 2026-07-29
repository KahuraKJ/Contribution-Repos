import express from 'express';
import memberController from '../controllers/memberController.js';
import { authenticate, authorize } from '../middleware/auth.js';
import { validateUpdateProfile } from '../middleware/validation.js';

const router = express.Router();

// Protected routes
router.get('/profile', authenticate, (req, res) => memberController.getProfile(req, res));
router.put('/profile', authenticate, validateUpdateProfile, (req, res) =>
  memberController.updateProfile(req, res)
);
router.get('/:id', authenticate, (req, res) => memberController.getMember(req, res));

// Admin only
router.get('/', authenticate, authorize(['admin', 'super_admin']), (req, res) =>
  memberController.getAllMembers(req, res)
);

export default router;
