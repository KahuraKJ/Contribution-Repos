# Contribution Repos

Legacy PHP web application for managing contributions and donations across member organizations.

## Project Overview

This repository contains a PHP-based portal system that manages:
- Member contributions and donation processing
- User registration and authentication
- Member profiles and beneficiary management
- Financial reports and analytics
- Payment processing integration

## Technology Stack

- **Language**: PHP
- **Frontend**: HTML, CSS, JavaScript
- **Database**: MySQL/MariaDB (inferred from PHP patterns)
- **Payment Processing**: STK integration (M-Pesa likely)

## Directory Structure

```
/
├── Login*.php              # Authentication pages
├── contribution*.php       # Contribution management
├── donation*.php          # Donation management
├── members*.php           # Member operations
├── advocacy*.php          # Advocacy features
├── reports/               # Financial reports
├── images/                # Logos and assets
└── utilities/             # Helper files
```

## Security Guidelines

- **No hardcoded secrets**: Use environment variables for API keys, database credentials
- **Input validation**: Always validate and sanitize user inputs
- **SQL injection prevention**: Use prepared statements
- **.env file**: Configure database and payment credentials in .env (git-ignored)

## Deployment

Deployed on Coolify via Azure infrastructure using `*.wak.link` domain pattern.

## Development

1. Set up `.env` with database and payment credentials
2. Configure PHP environment (local or Docker)
3. Database migrations should be tracked in `migrations/` directory
4. Follow PSR-12 coding standards for PHP

## Git Workflow

- Work on feature branches: `feat/<description>`
- Use conventional commits: `feat:`, `fix:`, `refactor:`, `docs:`, `test:`
- Submit PRs against `main` branch for review
