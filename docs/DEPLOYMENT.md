# Deployment Guide - Coolify on Azure

## Pre-Deployment Checklist

- [ ] Code pushed to main branch on GitHub (kjkahura/Contribution-Repos)
- [ ] All tests passing (`npm test`)
- [ ] Lint checks passing (`npm run lint`)
- [ ] Environment variables set in Azure
- [ ] Database migrations tested locally
- [ ] Docker images build successfully
- [ ] Review checklist completed

---

## Architecture

```
┌─────────────────────────────────────────────────────────┐
│                      Azure Container Registry           │
│  (Stores Docker images for backend & frontend)          │
└─────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────┐
│                     Coolify (Orchestrator)              │
│  ┌──────────────────────────────────────────────────┐   │
│  │  Traefik (Reverse Proxy + HTTPS)                │   │
│  │  - contributions.wak.link                        │   │
│  │  - api.contributions.wak.link                    │   │
│  └──────────────────────────────────────────────────┘   │
│                            ↓                             │
│  ┌──────────────────────────────────────────────────┐   │
│  │        Application Containers                    │   │
│  ├──────────────┬──────────────┬────────────────┐   │   │
│  │  Frontend    │   Backend    │  Webhook Svc   │   │   │
│  │  (Nginx)     │  (Node.js)   │  (RabbitMQ)    │   │   │
│  └──────────────┴──────────────┴────────────────┘   │   │
│                            ↓                         │   │
│  ┌──────────────────────────────────────────────────┐   │
│  │       Managed Services                           │   │
│  ├──────────────┬──────────────┬────────────────┐   │   │
│  │ PostgreSQL   │   Redis      │   RabbitMQ     │   │   │
│  │ (Azure DB)   │  (Redis)     │   (Azure)      │   │   │
│  └──────────────┴──────────────┴────────────────┘   │   │
│                                                      │   │
└──────────────────────────────────────────────────────────┘
```

---

## Prerequisites

### Wakandi Infrastructure
- Access to Azure subscription (Wakandi tenant)
- Coolify installation on Azure VM
- Traefik reverse proxy configured
- Domain: `*.wak.link`

### Local Setup
- Docker installed locally
- Docker Compose installed
- GitHub CLI (`gh`) installed
- Access to kjkahura GitHub account

---

## Step 1: Build Docker Images

### Option A: Local Build (for testing)

```bash
cd Contribution-Repos

# Backend
cd backend
docker build -t contribution-repos-backend:1.0.0 .
docker run -p 3000:3000 --env-file ../.env contribution-repos-backend:1.0.0

# Frontend
cd ../frontend
docker build -t contribution-repos-frontend:1.0.0 .
docker run -p 80:80 contribution-repos-frontend:1.0.0
```

### Option B: Push to Azure Container Registry

```bash
# Set variables
REGISTRY_NAME="wakandiregistry"
REGISTRY_URL="${REGISTRY_NAME}.azurecr.io"

# Login to ACR
az acr login --name $REGISTRY_NAME

# Build and push backend
cd backend
docker build -t ${REGISTRY_URL}/contribution-repos-backend:1.0.0 .
docker push ${REGISTRY_URL}/contribution-repos-backend:1.0.0

# Build and push frontend
cd ../frontend
docker build -t ${REGISTRY_URL}/contribution-repos-frontend:1.0.0 .
docker push ${REGISTRY_URL}/contribution-repos-frontend:1.0.0
```

---

## Step 2: Configure Coolify Deployment

### Via Coolify Dashboard

1. **Login to Coolify**
   - URL: https://coolify.yourdomain.com (or your Coolify instance)
   - Credentials: Provided by Wakandi DevOps

2. **Create New Project**
   - Name: `Contribution Repos`
   - Description: `Contribution management system`

3. **Add Backend Service**
   - **Type**: Docker Compose Service
   - **Image**: `wakandiregistry.azurecr.io/contribution-repos-backend:1.0.0`
   - **Port**: 3000
   - **Environment Variables**:
     ```env
     NODE_ENV=production
     DATABASE_URL=postgresql://user:pass@postgres.database.azure.com:5432/contributions_prod
     REDIS_URL=redis://redis-host:6379
     RABBITMQ_URL=amqp://user:pass@rabbitmq-host:5672
     JWT_SECRET=<long-random-string>
     FRONTEND_URL=https://contributions.wak.link
     LOG_LEVEL=info
     ```
   - **Health Check**: `GET /health`
   - **Restart Policy**: Always
   - **CPU Limit**: 1 core
   - **Memory Limit**: 512 MB

4. **Add Frontend Service**
   - **Type**: Docker Compose Service
   - **Image**: `wakandiregistry.azurecr.io/contribution-repos-frontend:1.0.0`
   - **Port**: 80
   - **Environment**: None (frontend uses Vite env)
   - **Health Check**: `GET /health`
   - **Restart Policy**: Always
   - **CPU Limit**: 512 MB
   - **Memory Limit**: 256 MB

5. **Configure Domains**
   - **Frontend**: `contributions.wak.link`
   - **API**: `api.contributions.wak.link` (or use path `/api` on main domain)
   - **SSL**: Auto (via Traefik/Let's Encrypt)

### Via Docker Compose File

Create `coolify-compose.yml`:

```yaml
version: '3.8'

services:
  backend:
    image: wakandiregistry.azurecr.io/contribution-repos-backend:1.0.0
    container_name: contribution_backend_prod
    ports:
      - "3000:3000"
    environment:
      NODE_ENV: production
      DATABASE_URL: postgresql://user:pass@postgres.database.azure.com/contributions_prod
      REDIS_URL: redis://redis-prod:6379
      RABBITMQ_URL: amqp://user:pass@rabbitmq-prod:5672
      JWT_SECRET: ${JWT_SECRET}
      FRONTEND_URL: https://contributions.wak.link
    restart: always
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:3000/health"]
      interval: 30s
      timeout: 10s
      retries: 3
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.contrib-api.rule=Host(`api.contributions.wak.link`)"
      - "traefik.http.routers.contrib-api.entrypoints=websecure"
      - "traefik.http.routers.contrib-api.tls.certresolver=letsencrypt"

  frontend:
    image: wakandiregistry.azurecr.io/contribution-repos-frontend:1.0.0
    container_name: contribution_frontend_prod
    ports:
      - "80:80"
    restart: always
    healthcheck:
      test: ["CMD", "wget", "--quiet", "--tries=1", "--spider", "http://localhost/health"]
      interval: 30s
      timeout: 10s
      retries: 3
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.contrib-web.rule=Host(`contributions.wak.link`)"
      - "traefik.http.routers.contrib-web.entrypoints=websecure"
      - "traefik.http.routers.contrib-web.tls.certresolver=letsencrypt"
```

---

## Step 3: Setup Database (Azure PostgreSQL)

### Create Managed PostgreSQL

```bash
az postgres server create \
  --resource-group wakandi-rg \
  --name contribution-repos-db \
  --location eastus \
  --admin-user dbadmin \
  --admin-password $(openssl rand -base64 32) \
  --sku-name B_Gen5_1 \
  --storage-size 51200
```

### Create Database

```bash
az postgres db create \
  --resource-group wakandi-rg \
  --server-name contribution-repos-db \
  --name contributions_prod
```

### Configure Firewall

```bash
# Allow Azure services
az postgres server firewall-rule create \
  --resource-group wakandi-rg \
  --server-name contribution-repos-db \
  --name AllowAzureServices \
  --start-ip-address 0.0.0.0 \
  --end-ip-address 0.0.0.0

# Allow your IP
az postgres server firewall-rule create \
  --resource-group wakandi-rg \
  --server-name contribution-repos-db \
  --name AllowMyIP \
  --start-ip-address YOUR_IP \
  --end-ip-address YOUR_IP
```

### Run Initial Migrations

```bash
# From local machine
DATABASE_URL=postgresql://dbadmin:password@contribution-repos-db.postgres.database.azure.com:5432/contributions_prod npm run migrate

# Or from container
docker run --rm \
  -e DATABASE_URL="postgresql://..." \
  wakandiregistry.azurecr.io/contribution-repos-backend:1.0.0 \
  npm run migrate
```

---

## Step 4: Environment Variables (Production)

Store secrets in **Azure Key Vault** or **Coolify Secrets**:

```env
# Database
DATABASE_URL=postgresql://user:pass@contribution-repos-db.postgres.database.azure.com/contributions_prod

# Cache & Queue
REDIS_URL=redis://redis-prod.redis.cache.windows.net:6379
RABBITMQ_URL=amqp://user:pass@rabbitmq-prod:5672

# Security
JWT_SECRET=<use `openssl rand -base64 32` to generate>

# URLs
FRONTEND_URL=https://contributions.wak.link
NODE_ENV=production
LOG_LEVEL=info

# M-Pesa (Phase 2)
MPESA_API_KEY=<from payment provider>
MPESA_API_SECRET=<from payment provider>
```

**⚠️ NEVER commit secrets to Git. Use environment variables only.**

---

## Step 5: Deploy via GitHub Actions (CI/CD)

Create `.github/workflows/deploy.yml`:

```yaml
name: Deploy to Coolify

on:
  push:
    branches: [main]
  workflow_dispatch:

jobs:
  deploy:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Build and Push Backend
        run: |
          docker build -t ${{ secrets.ACR_URL }}/contribution-repos-backend:${{ github.sha }} backend/
          docker push ${{ secrets.ACR_URL }}/contribution-repos-backend:${{ github.sha }}
      
      - name: Build and Push Frontend
        run: |
          docker build -t ${{ secrets.ACR_URL }}/contribution-repos-frontend:${{ github.sha }} frontend/
          docker push ${{ secrets.ACR_URL }}/contribution-repos-frontend:${{ github.sha }}
      
      - name: Deploy via Coolify Webhook
        run: |
          curl -X POST ${{ secrets.COOLIFY_WEBHOOK_URL }} \
            -H "Content-Type: application/json" \
            -d '{"tag":"${{ github.sha }}"}'
```

**Setup GitHub Secrets:**
- `ACR_URL`: Azure Container Registry URL
- `COOLIFY_WEBHOOK_URL`: Coolify deployment webhook
- `ACR_USERNAME`: Registry username
- `ACR_PASSWORD`: Registry password

---

## Step 6: Monitor Deployment

### Coolify Dashboard

1. Navigate to your project
2. Watch container startup logs
3. Verify health checks pass
4. Check CPU/Memory usage

### Manual Health Check

```bash
# Frontend
curl https://contributions.wak.link/health

# API
curl https://api.contributions.wak.link/health
```

### View Logs

```bash
# Via Coolify dashboard or CLI
coolify logs -p contribution-repos -s backend
coolify logs -p contribution-repos -s frontend
```

---

## Step 7: Setup Monitoring & Alerts

### Application Performance Monitoring

```bash
# Option 1: Azure Monitor
az monitor metrics create \
  --resource-group wakandi-rg \
  --name contribution-repos-alerts

# Option 2: Sentry (Error tracking)
# Set SENTRY_DSN in environment variables
```

### Logs & Metrics

- **Azure Monitor**: Container CPU, Memory, Network
- **Application Logs**: Check stdout/stderr
- **Error Tracking**: Sentry integration (Phase 2)

### Set Alerts

- CPU > 80%
- Memory > 90%
- API response time > 2s
- Error rate > 1%
- Container restarts > 3/hour

---

## Step 8: SSL/HTTPS

Traefik auto-generates certificates via Let's Encrypt:

```yaml
# In Coolify Traefik config
certificatesResolvers:
  letsencrypt:
    acme:
      email: admin@contributions.wak.link
      storage: /data/acme.json
      httpChallenge:
        entryPoint: web
```

**Domains covered:**
- `contributions.wak.link`
- `api.contributions.wak.link`

---

## Step 9: Backup & Disaster Recovery

### Database Backups

```bash
# Azure PostgreSQL auto-backups (7 days retention)
az postgres server backup show \
  --resource-group wakandi-rg \
  --server-name contribution-repos-db
```

### Restore Database

```bash
az postgres server restore \
  --resource-group wakandi-rg \
  --source-server contribution-repos-db \
  --name contribution-repos-db-restore \
  --restore-point-in-time 2024-07-29T10:00:00
```

---

## Step 10: Post-Deployment

✅ **Verify in Production:**

1. Navigate to `https://contributions.wak.link`
2. Register a new account
3. Login and access dashboard
4. Check API at `https://api.contributions.wak.link/health`
5. Monitor logs for errors

---

## Rollback Procedure

If deployment fails:

```bash
# Option 1: Coolify Dashboard
# → Select previous version
# → Click "Redeploy"

# Option 2: CLI
coolify deploy -p contribution-repos -v 1.0.0
```

---

## Scaling (Phase 2+)

As user load grows:

```yaml
# Increase replicas
backend:
  deploy:
    replicas: 3

# Increase resource limits
resources:
  limits:
    cpus: '2'
    memory: 2G
```

---

## Troubleshooting

### Container Won't Start

```bash
coolify logs -p contribution-repos -s backend
# Check for:
# - Database connection errors
# - Missing environment variables
# - Port already in use
```

### High Memory Usage

```bash
# Restart container
coolify restart -p contribution-repos -s backend

# Or increase memory limit in Coolify
```

### Database Connection Timeout

```bash
# Check Azure firewall
az postgres server firewall-rule list \
  --resource-group wakandi-rg \
  --server-name contribution-repos-db

# Add Coolify server IP if missing
```

---

## Support & Runbooks

- **Coolify Docs**: https://coolify.io/docs
- **Azure PostgreSQL**: https://docs.microsoft.com/azure/postgresql
- **Traefik Docs**: https://doc.traefik.io
- **Let's Encrypt**: https://letsencrypt.org

---

## Checklist for Production

- [ ] Database backups configured
- [ ] Monitoring & alerts enabled
- [ ] SSL certificates auto-renewing
- [ ] Logs being aggregated
- [ ] Team has deployment access
- [ ] Runbooks documented
- [ ] Load testing completed
- [ ] Security review passed
- [ ] Disaster recovery tested

---

**Deployment Status**: Ready for Phase 1 MVP

Next Phase: Setup payment webhook handling and M-Pesa integration.
