# Contribution Repos - Backend API

Node.js + Express API server for the contribution management system.

## Quick Start

```bash
# Install dependencies
npm install

# Create .env file
cp .env.example .env

# Start development server
npm run dev
```

Server runs on `http://localhost:3000`

## Project Structure

```
src/
├── config/          # Configuration (database, logger, JWT)
├── controllers/     # HTTP request handlers
├── models/          # Sequelize database models
├── routes/          # API endpoint definitions
├── services/        # Business logic layer
├── middleware/      # Express middleware
└── index.js         # Application entry point
```

## Available Scripts

- `npm run dev` - Start development server with auto-reload
- `npm run start` - Start production server
- `npm test` - Run unit tests
- `npm run lint` - Check code style
- `npm run migrate` - Run database migrations
- `npm run seed` - Seed initial data

## Environment Variables

See `.env.example` for all available options:
- `NODE_ENV` - development/production
- `PORT` - Server port (default: 3000)
- `DATABASE_URL` - PostgreSQL connection string
- `JWT_SECRET` - Secret key for JWT signing
- `FRONTEND_URL` - Frontend origin for CORS

## API Endpoints

### Authentication
- `POST /api/auth/register` - Create account
- `POST /api/auth/login` - Login
- `POST /api/auth/refresh` - Refresh token
- `POST /api/auth/change-password` - Change password
- `POST /api/auth/logout` - Logout

### Members
- `GET /api/members/profile` - Get authenticated member's profile
- `PUT /api/members/profile` - Update profile
- `GET /api/members/:id` - Get member by ID
- `GET /api/members` - List all members (admin only)

See [docs/API.md](../docs/API.md) for detailed documentation.

## Authentication

Protected endpoints require JWT token in Authorization header:
```
Authorization: Bearer {accessToken}
```

Tokens expire after 15 minutes. Use refresh token to get new access token.

## Database

PostgreSQL with Sequelize ORM. Models auto-sync in development.

For production, use migrations:
```bash
npm run migrate
```

## Error Handling

All errors return structured JSON response:
```json
{
  "message": "Error description",
  "errors": [ /* validation errors */ ]
}
```

## Testing

```bash
npm run test              # Run all tests
npm run test:watch       # Watch mode
npm run test -- --coverage
```

## Development

### Add a New Route

1. Create model in `src/models/`
2. Create service in `src/services/`
3. Create controller in `src/controllers/`
4. Create routes in `src/routes/`
5. Register routes in `src/index.js`

### Add Middleware

```javascript
import { authenticate, authorize } from './middleware/auth'

router.post('/admin', authenticate, authorize(['admin']), controller.action)
```

### Add Validation

```javascript
import { body, validationResult } from 'express-validator'

router.post('/action', [
  body('email').isEmail(),
  body('age').isInt({ min: 18 })
], controller.action)
```

## Deployment

See [docs/DEPLOYMENT.md](../docs/DEPLOYMENT.md) for production deployment instructions.

Docker image:
```bash
docker build -t contribution-repos-backend .
docker run -p 3000:3000 --env-file .env contribution-repos-backend
```

## Logging

Logs are written to stdout with timestamp and level:
```
[INFO] 2024-07-29T14:30:00.000Z - Server running on http://localhost:3000
[DEBUG] 2024-07-29T14:30:01.000Z - Database connection established
```

Set `LOG_LEVEL` environment variable:
- `error` - Only errors
- `warn` - Warnings and errors
- `info` - General info (default)
- `debug` - Detailed debugging

## Performance

- Database connection pooling enabled
- Request logging middleware
- JWT-based authentication (stateless)
- Health check endpoint for monitoring

## Security

- Password hashing with bcrypt
- JWT tokens with expiration
- CORS configured for frontend origin
- SQL injection prevention via ORM
- Input validation on all endpoints
- Rate limiting (future phase)

## Troubleshooting

**Port already in use:**
```bash
lsof -i :3000  # Find process
kill -9 <PID>   # Kill process
```

**Database connection error:**
```bash
docker-compose ps          # Check PostgreSQL
psql -h localhost -U dev   # Test connection
```

**Module not found:**
```bash
rm -rf node_modules package-lock.json
npm install
```

## Resources

- [Express.js Docs](https://expressjs.com)
- [Sequelize Docs](https://sequelize.org)
- [PostgreSQL Docs](https://www.postgresql.org/docs)
- [JWT Best Practices](https://tools.ietf.org/html/rfc8725)

## Support

For issues or questions: api-support@contributions.wak.link

---

**Status**: Phase 1 Authentication ✅ | Phase 2 Payments 🔜 | Phase 3 Admin 🔜
