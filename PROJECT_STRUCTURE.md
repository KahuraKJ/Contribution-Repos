# Contribution Repos - Modern Project Structure

## Recommended Directory Layout

```
Contribution-Repos/
├── backend/                      # Node.js Express API
│   ├── src/
│   │   ├── config/              # Database, environment, services
│   │   ├── controllers/         # Route handlers
│   │   ├── models/              # Database models (Sequelize/TypeORM)
│   │   ├── routes/              # API routes
│   │   ├── services/            # Business logic
│   │   ├── middleware/          # Auth, validation, error handling
│   │   ├── utils/               # Helpers, validators
│   │   ├── webhooks/            # Payment webhook handlers
│   │   └── index.js             # App entry point
│   ├── migrations/              # Database migrations
│   ├── seeds/                   # Database seed data
│   ├── tests/                   # Unit & integration tests
│   ├── .env.example             # Environment template
│   ├── Dockerfile               # Container image
│   ├── package.json
│   └── README.md
│
├── frontend/                     # React SPA
│   ├── src/
│   │   ├── components/          # Reusable React components
│   │   │   ├── auth/
│   │   │   ├── dashboard/
│   │   │   ├── contributions/
│   │   │   └── common/
│   │   ├── pages/               # Page components
│   │   ├── services/            # API client services
│   │   ├── store/               # Redux or Zustand state
│   │   ├── styles/              # Global CSS/theme
│   │   ├── utils/               # Client-side helpers
│   │   ├── App.jsx              # Root component
│   │   └── main.jsx             # Entry point
│   ├── public/                  # Static assets
│   ├── tests/                   # Component & integration tests
│   ├── .env.example
│   ├── Dockerfile
│   ├── vite.config.js           # Build tool config
│   ├── package.json
│   └── README.md
│
├── shared/                      # Shared TypeScript types & utilities
│   ├── types/
│   │   ├── models.ts            # Contribution, Donation, Member types
│   │   ├── api.ts               # API request/response types
│   │   └── index.ts
│   ├── utils/
│   │   └── validators.ts        # Shared validation logic
│   └── package.json
│
├── docker-compose.yml           # Local development database setup
├── .github/                     # GitHub Actions CI/CD
│   └── workflows/
│       ├── test.yml             # Run tests on PR
│       ├── deploy.yml           # Deploy on merge to main
│       └── lint.yml             # Code quality checks
│
├── docs/                        # Documentation
│   ├── API.md                   # API endpoints reference
│   ├── DATABASE.md              # Database schema & migrations
│   ├── DEPLOYMENT.md            # Coolify deployment guide
│   └── DEVELOPMENT.md           # Local setup & development
│
├── MODERNIZATION_GUIDE.md       # Architecture & modernization plan
├── PROJECT_STRUCTURE.md         # This file
├── CLAUDE.md                    # Project guidelines
└── README.md                    # Quick overview
```

---

## Phase-by-Phase Folder Expansion

### After Phase 1 (MVP - Week 4)
```
backend/src/
├── auth/                    ← New: Authentication logic
├── contributions/           ← New: Contribution management
├── members/                 ← New: Member CRUD
└── dashboard/               ← New: Member dashboard endpoints

frontend/src/
├── pages/
│   ├── Login.jsx
│   ├── Dashboard.jsx
│   ├── ContributionForm.jsx
│   └── ContributionHistory.jsx
└── services/
    ├── authService.js       ← API calls for auth
    └── contributionService.js
```

### After Phase 2 (Payments - Week 8)
```
backend/src/
├── payments/                ← New: Payment processing
├── webhooks/               ← New: M-Pesa webhooks
├── queue/                  ← New: Message queue handlers
└── notifications/          ← New: SMS/Email notifications

frontend/src/
├── pages/
│   └── PaymentStatus.jsx   ← New: Payment tracking UI
```

### After Phase 3 (Admin - Week 12)
```
backend/src/
├── admin/                  ← New: Admin endpoints
├── reports/                ← New: Reporting engine
└── audit/                  ← New: Audit logging

frontend/src/
├── pages/
│   ├── AdminDashboard.jsx
│   ├── MemberManagement.jsx
│   ├── Reports.jsx
│   └── Settings.jsx
```

---

## File Examples

### Backend Entry Point (`backend/src/index.js`)
```javascript
const express = require('express');
const cors = require('cors');
const dotenv = require('dotenv');
const database = require('./config/database');
const routes = require('./routes');
const errorHandler = require('./middleware/errorHandler');

dotenv.config();

const app = express();

// Middleware
app.use(cors());
app.use(express.json());

// Routes
app.use('/api', routes);

// Error handling
app.use(errorHandler);

// Start server
const PORT = process.env.PORT || 3000;
database.authenticate().then(() => {
  app.listen(PORT, () => {
    console.log(`Server running on port ${PORT}`);
  });
});

module.exports = app;
```

### Frontend Entry Point (`frontend/src/main.jsx`)
```jsx
import React from 'react'
import ReactDOM from 'react-dom/client'
import { Provider } from 'react-redux'
import store from './store'
import App from './App'
import './styles/index.css'

ReactDOM.createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    <Provider store={store}>
      <App />
    </Provider>
  </React.StrictMode>,
)
```

### Docker Compose (Local Dev - `docker-compose.yml`)
```yaml
version: '3.8'

services:
  postgres:
    image: postgres:15-alpine
    environment:
      POSTGRES_DB: contributions_dev
      POSTGRES_USER: dev
      POSTGRES_PASSWORD: devpass
    ports:
      - "5432:5432"
    volumes:
      - postgres_data:/var/lib/postgresql/data

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"

  rabbitmq:
    image: rabbitmq:3.12-alpine
    environment:
      RABBITMQ_DEFAULT_USER: guest
      RABBITMQ_DEFAULT_PASS: guest
    ports:
      - "5672:5672"
      - "15672:15672"

volumes:
  postgres_data:
```

### Environment Template (`backend/.env.example`)
```env
# Server
NODE_ENV=development
PORT=3000
LOG_LEVEL=debug

# Database
DATABASE_URL=postgresql://dev:devpass@localhost:5432/contributions_dev
DATABASE_POOL_MIN=2
DATABASE_POOL_MAX=10

# Cache
REDIS_URL=redis://localhost:6379

# Queue
RABBITMQ_URL=amqp://guest:guest@localhost:5672

# JWT
JWT_SECRET=your-super-secret-key-change-in-production
JWT_ACCESS_EXPIRY=15m
JWT_REFRESH_EXPIRY=7d

# M-Pesa
MPESA_API_KEY=xxxx
MPESA_API_SECRET=xxxx
MPESA_SHORTCODE=xxxx
MPESA_PASSKEY=xxxx

# Frontend
REACT_APP_API_URL=http://localhost:3000/api
```

---

## Getting Started Commands

### Initialize Backend
```bash
cd backend
npm init -y
npm install express cors dotenv pg sequelize redis amqplib
npm install --save-dev nodemon jest supertest
npm run migrate
npm run dev
```

### Initialize Frontend
```bash
cd frontend
npm create vite@latest . -- --template react
npm install axios react-router-dom redux react-redux
npm install --save-dev @testing-library/react jest
npm run dev
```

### Initialize Shared Types
```bash
cd shared
npm init -y
npm install --save-dev typescript
npx tsc --init
```

---

## Configuration Files to Create

### `backend/package.json` (scripts section)
```json
{
  "scripts": {
    "dev": "nodemon src/index.js",
    "start": "node src/index.js",
    "migrate": "node migrations/run.js",
    "test": "jest --coverage",
    "lint": "eslint src/",
    "build": "echo 'No build needed for Node'"
  }
}
```

### `frontend/package.json` (scripts section)
```json
{
  "scripts": {
    "dev": "vite",
    "build": "vite build",
    "preview": "vite preview",
    "test": "vitest",
    "lint": "eslint src/"
  }
}
```

### GitHub Actions Workflow (`.github/workflows/test.yml`)
```yaml
name: Test & Lint

on: [push, pull_request]

jobs:
  backend:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: actions/setup-node@v3
        with:
          node-version: '18'
      - run: cd backend && npm install && npm run lint && npm test
  
  frontend:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: actions/setup-node@v3
        with:
          node-version: '18'
      - run: cd frontend && npm install && npm run lint && npm test
```

---

## Next Steps

1. **Create this structure** in your local repo
2. **Install dependencies** for each module
3. **Set up database** (PostgreSQL via docker-compose)
4. **Implement Phase 1** (Auth + Member Dashboard)
5. **Deploy to Coolify** once MVP is ready

---

## Resources

- Express.js guide: https://expressjs.com/en/starter/installing.html
- React setup: https://react.dev/learn/installation
- Vite config: https://vitejs.dev/config/
- Docker Compose: https://docs.docker.com/compose/
- GitHub Actions: https://docs.github.com/en/actions
