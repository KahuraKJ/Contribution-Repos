# Contribution Repos - Modernization Guide

## Executive Summary

Transform the legacy PHP contribution/donation portal into a modern, cloud-hosted application using Node.js + React, deployed on Coolify/Azure following Wakandi standards.

---

## 1. Architecture Overview

### Current State (Legacy PHP)
```
PHP Monolith
├── Frontend (HTML/CSS/JS inline)
├── Business Logic (PHP)
└── Database (MySQL - inferred)
```

### Target State (Modern Stack)
```
Frontend (React SPA)          →  API Gateway  →  Backend (Node/Express)
                                                  ├── Auth Service
                                                  ├── Contribution Service
                                                  ├── Payment Service
                                                  ├── Reporting Service
                                                  └── Database (MySQL/PostgreSQL)

Payment Provider (M-Pesa)     →  Webhook Handler  →  RabbitMQ/Kafka
```

---

## 2. Technology Stack (Wakandi Aligned)

### Backend
- **Runtime**: Node.js 18+
- **Framework**: Express.js or NestJS
- **ORM**: Sequelize (MySQL) or TypeORM (PostgreSQL)
- **Message Queue**: RabbitMQ or Kafka (for payment webhooks)
- **Cache**: Redis
- **Authentication**: JWT + Session management

### Frontend
- **Framework**: React 18+
- **UI Library**: Material-UI or Chakra UI
- **State Management**: Redux or Zustand
- **HTTP Client**: Axios
- **Build Tool**: Vite or Create React App

### Database
- **Production**: PostgreSQL 13+ (hosted on Azure)
- **Local Dev**: Docker container (PostgreSQL)
- **Migration Tool**: Knex.js or Sequelize

### Infrastructure
- **Hosting**: Coolify on Azure
- **Container**: Docker
- **Reverse Proxy**: Traefik
- **Domain**: `contributions.wak.link` (subdomain pattern)
- **SSL**: Automatic via Traefik

### Development Tools
- **Version Control**: Git + GitHub (kjkahura account)
- **CI/CD**: GitHub Actions
- **Package Manager**: npm or yarn
- **Code Quality**: ESLint, Prettier
- **Testing**: Jest, React Testing Library, Supertest

---

## 3. Database Design (Replace Excel)

### Why Not Excel?
- ❌ Not scalable (max ~1M rows)
- ❌ No real-time locking (concurrent access issues)
- ❌ No transaction support
- ❌ Manual backups
- ❌ No query optimization
- ❌ Security vulnerabilities

### Recommended: PostgreSQL on Azure
- ✅ Fully managed (Azure Database for PostgreSQL)
- ✅ Automatic backups
- ✅ Scalable to millions of records
- ✅ ACID transactions
- ✅ Real-time analytics queries
- ✅ Disaster recovery built-in

### Core Tables (Design)

```sql
-- Users/Members
CREATE TABLE members (
  id SERIAL PRIMARY KEY,
  email VARCHAR(255) UNIQUE NOT NULL,
  phone VARCHAR(20) UNIQUE,
  first_name VARCHAR(100),
  last_name VARCHAR(100),
  id_number VARCHAR(50) UNIQUE,
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);

-- Contributions
CREATE TABLE contributions (
  id SERIAL PRIMARY KEY,
  member_id INT NOT NULL REFERENCES members(id),
  amount DECIMAL(10, 2) NOT NULL,
  contribution_type VARCHAR(50), -- 'monthly', 'special', etc.
  payment_status VARCHAR(50), -- 'pending', 'completed', 'failed'
  payment_method VARCHAR(50), -- 'mpesa', 'bank', etc.
  mpesa_reference VARCHAR(100),
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);

-- Donations
CREATE TABLE donations (
  id SERIAL PRIMARY KEY,
  donor_id INT NOT NULL REFERENCES members(id),
  beneficiary_id INT REFERENCES members(id),
  amount DECIMAL(10, 2) NOT NULL,
  donation_type VARCHAR(100),
  payment_status VARCHAR(50),
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);

-- Payment Logs (for webhook tracking)
CREATE TABLE payment_logs (
  id SERIAL PRIMARY KEY,
  external_id VARCHAR(255),
  contribution_id INT REFERENCES contributions(id),
  donation_id INT REFERENCES donations(id),
  status VARCHAR(50),
  response_data JSONB,
  created_at TIMESTAMP DEFAULT NOW()
);

-- Reports (for analytics caching)
CREATE TABLE reports (
  id SERIAL PRIMARY KEY,
  report_type VARCHAR(100),
  period_start DATE,
  period_end DATE,
  data JSONB,
  generated_at TIMESTAMP DEFAULT NOW()
);
```

---

## 4. Feature Breakdown (By Phase)

### Phase 1: MVP (Weeks 1-4)
- [ ] User authentication (login/registration)
- [ ] Member dashboard
- [ ] Contribution submission (basic form)
- [ ] Contribution history view
- [ ] Basic reports
- **Database**: PostgreSQL with core tables

### Phase 2: Payment Integration (Weeks 5-8)
- [ ] M-Pesa STK integration
- [ ] Payment webhook handler (RabbitMQ)
- [ ] Payment status tracking
- [ ] Automatic reconciliation
- [ ] Payment failure handling

### Phase 3: Admin Features (Weeks 9-12)
- [ ] Admin dashboard
- [ ] Member management
- [ ] Contribution verification
- [ ] Batch uploads (replacing Excel)
- [ ] Advanced reporting

### Phase 4: Advanced (Weeks 13+)
- [ ] Donation module
- [ ] Beneficiary management
- [ ] Multi-currency support
- [ ] Audit logs
- [ ] Analytics dashboard

---

## 5. Hosting & Deployment Strategy

### Cloud Architecture
```
┌─────────────────────────────────────────────────────┐
│                    Azure (Coolify)                  │
├─────────────────────────────────────────────────────┤
│  Traefik (Reverse Proxy)                            │
│  │                                                   │
│  ├─ Frontend Container (React SPA)                  │
│  │   └─ contributions.wak.link                      │
│  │                                                   │
│  ├─ Backend Container (Node/Express)                │
│  │   └─ api.contributions.wak.link                  │
│  │                                                   │
│  └─ Webhook Handler Container                       │
│      └─ webhooks.contributions.wak.link             │
│                                                      │
│  ┌────────────────────────────────────────────┐    │
│  │ Services                                   │    │
│  ├─ PostgreSQL (Azure Database)              │    │
│  ├─ RabbitMQ (Message Queue)                 │    │
│  ├─ Redis (Cache)                            │    │
│  └─ Nginx (Static assets, optional)          │    │
│                                                │    │
│  ┌────────────────────────────────────────────┐    │
│  │ Monitoring & Logging                       │    │
│  ├─ Application Logs                          │    │
│  ├─ Error Tracking (Sentry)                   │    │
│  └─ Performance Metrics                       │    │
│                                                │    │
└────────────────────────────────────────────────────┘
```

### Coolify Deployment Steps

1. **Prepare Docker Images**
   ```dockerfile
   # Backend Dockerfile
   FROM node:18-alpine
   WORKDIR /app
   COPY package*.json ./
   RUN npm install --production
   COPY . .
   EXPOSE 3000
   CMD ["node", "src/index.js"]
   
   # Frontend Dockerfile
   FROM node:18-alpine AS builder
   WORKDIR /app
   COPY package*.json ./
   RUN npm install
   COPY . .
   RUN npm run build
   
   FROM nginx:alpine
   COPY --from=builder /app/dist /usr/share/nginx/html
   EXPOSE 3000
   ```

2. **Environment Variables (per environment)**
   ```env
   # .env.production
   DATABASE_URL=postgresql://user:pass@azure-host/contributions_prod
   REDIS_URL=redis://redis-host:6379
   RABBITMQ_URL=amqp://user:pass@rabbitmq-host
   MPESA_API_KEY=xxx
   MPESA_API_SECRET=xxx
   JWT_SECRET=xxx
   NODE_ENV=production
   LOG_LEVEL=info
   ```

3. **Deploy via Coolify Dashboard**
   - Connect GitHub repository (kjkahura/Contribution-Repos)
   - Configure Docker build settings
   - Set environment variables (secrets)
   - Configure health checks
   - Enable auto-restart & auto-update

4. **Domain Setup**
   - Main app: `contributions.wak.link`
   - API: `api.contributions.wak.link`
   - Admin: `admin.contributions.wak.link` (optional)

---

## 6. Development Workflow (Wakandi SDLC)

### Using wak-sdlc Skills

1. **Constitution** (First-time setup)
   ```bash
   /wak-sdlc.constitute
   ```
   - Documents repo tech stack, responsibilities, dependencies

2. **Tech Plan** (For major features)
   ```bash
   /wak-sdlc.tech-plan
   ```
   - Technical specification for feature
   - Data model design
   - API contracts

3. **Implementation**
   ```bash
   /wak-sdlc.impl-implement
   ```
   - Code generation
   - Unit tests
   - Integration tests

4. **PR Generation**
   ```bash
   /wak-sdlc.impl-pr-generation
   ```
   - Automatic PR creation
   - Test suite included

5. **QA Testing**
   ```bash
   /wak-sdlc.qa-specify
   /wak-sdlc.qa-execute
   ```
   - Test cases
   - Automation (API & UI)

### Git Workflow
```bash
# Create feature branch
git checkout -b feat/member-dashboard

# Develop locally
npm run dev

# Push to kjkahura/Contribution-Repos
git push origin feat/member-dashboard

# Open PR on GitHub
# Once approved and merged to main, Coolify auto-deploys
```

### Local Development Setup
```bash
# Clone repo
git clone https://github.com/kjkahura/Contribution-Repos.git
cd Contribution-Repos

# Install dependencies
npm install

# Start database (Docker)
docker-compose up -d postgres redis

# Create .env.local
cp .env.example .env.local
# Edit with local credentials

# Run migrations
npm run migrate

# Start development servers
npm run dev  # Starts both backend & frontend
```

---

## 7. Database Migration Strategy (From Legacy PHP)

### Phase 1: Data Extraction
1. Export data from legacy PHP MySQL database
2. Transform to PostgreSQL schema
3. Validate data integrity

### Phase 2: Parallel Running (1-2 weeks)
- Run both systems simultaneously
- Sync critical updates to new DB
- Test new system with real data

### Phase 3: Cutover
- Final data sync
- Redirect traffic to new system
- Keep legacy as read-only backup (7 days)

### Tools
```bash
# Data export/transform
npm install --save-dev pg-migrate
npm install --save-dev knex

# Schema migration
npm run migrate:latest
npm run migrate:rollback (if needed)
```

---

## 8. Security Considerations

### Application Security
- [ ] JWT token expiration (15 min access, 7 day refresh)
- [ ] HTTPS only (enforced by Traefik)
- [ ] CORS properly configured
- [ ] Rate limiting on API endpoints
- [ ] Input validation (express-validator)
- [ ] SQL injection prevention (ORM + parameterized queries)
- [ ] XSS protection (React escaping + CSP headers)
- [ ] CSRF tokens for state-changing operations

### Infrastructure Security
- [ ] Environment variables (never in code)
- [ ] Database user with minimal privileges
- [ ] Network isolation (private subnets on Azure)
- [ ] Regular backups (daily)
- [ ] SSL/TLS encryption (Traefik)

### Payment Security
- [ ] PCI DSS compliance (if storing payment data)
- [ ] Tokenize M-Pesa transactions
- [ ] Webhook signature validation
- [ ] Payment idempotency (prevent duplicates)
- [ ] Audit log for all transactions

### Compliance
- [ ] Kenya Data Protection Act (KDPA)
- [ ] AML/KYC requirements (if applicable)
- [ ] GDPR (if EU users)

---

## 9. Monitoring & Operations

### Logging
```
Application Logs → Log Aggregation (ELK or Azure Monitor)
Error Tracking → Sentry
Performance Metrics → Prometheus/Grafana
```

### Alerting
- API response time > 1000ms
- Error rate > 1%
- Database connection pool exhaustion
- Payment webhook failures
- Disk space usage > 80%

### Backup Strategy
- **Database**: Daily automated backups (Azure)
- **Retention**: 30 days
- **Recovery**: < 4 hours RTO

---

## 10. Cost Estimates (Azure + Coolify)

| Service | Tier | Cost/Month |
|---------|------|-----------|
| App Service (2 containers) | B1 | ~$55 |
| PostgreSQL | Single Server B1 | ~$50 |
| RabbitMQ | Basic | ~$20 |
| Redis | Basic | ~$15 |
| Storage | 100GB | ~$5 |
| **Total** | | ~$145/month |

*Can reduce with reserved instances (30% savings)*

---

## 11. Implementation Timeline

| Phase | Duration | Team Size | Deliverables |
|-------|----------|-----------|--------------|
| Setup & Planning | 1 week | 1 | Architecture, DB schema |
| Phase 1 (MVP) | 4 weeks | 2-3 | Auth, Dashboard, Basic Contributions |
| Phase 2 (Payments) | 4 weeks | 2-3 | M-Pesa Integration, Payment Status |
| Phase 3 (Admin) | 4 weeks | 2 | Admin Dashboard, Reporting |
| **Total** | **13 weeks** | | **Production MVP** |

---

## 12. Next Steps

1. **Week 1**: 
   - [ ] Set up project structure (Node + React template)
   - [ ] Create PostgreSQL schema
   - [ ] Configure Coolify deployment
   - [ ] Set up GitHub Actions CI/CD

2. **Week 2-4**: 
   - [ ] Authentication module
   - [ ] Basic CRUD for contributions
   - [ ] Frontend dashboard

3. **Deploy MVP to Coolify**

4. **Continue with phases 2-4**

---

## Resources

- **Wakandi Tech Stack**: See `artifacts/architecture.md`
- **Express.js Docs**: https://expressjs.com
- **React Docs**: https://react.dev
- **PostgreSQL**: https://www.postgresql.org/docs
- **Coolify**: https://coolify.io/docs
- **M-Pesa Integration**: Check existing Wakandi repos for patterns

---

## Questions?

This guide aligns with Wakandi standards. Use the **wak-sdlc skills** to implement each phase with structured design documents and automated code generation.

**Ready to start?** Proceed with Phase 1 setup or contact your tech lead for review.
