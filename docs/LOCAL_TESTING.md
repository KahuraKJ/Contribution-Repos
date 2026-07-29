# Local Testing Guide - Phase 1

Complete step-by-step guide to test the Contribution Repos application locally.

**Estimated Time**: 30-45 minutes

---

## Prerequisites

Before starting, ensure you have:

- ✅ Docker & Docker Compose installed
- ✅ Node.js 18+ installed
- ✅ Git (for cloning)
- ✅ Terminal/Command Prompt access
- ✅ Browser (Chrome, Firefox, Safari, Edge)

### Verify Installation

```bash
docker --version
# Docker version 20.10+

docker-compose --version
# Docker Compose version 2.0+

node --version
# v18.0.0+

npm --version
# 9.0.0+
```

---

## Step 1: Clone & Setup Project (5 minutes)

### 1.1 Clone Repository

```bash
git clone https://github.com/KahuraKJ/Contribution-Repos.git
cd Contribution-Repos
```

### 1.2 Create Environment Files

**Backend .env:**
```bash
cd backend
cp .env.example .env
cd ..
```

**Frontend .env:**
```bash
cd frontend
cp .env.example .env
cd ..
```

### 1.3 Verify Structure

```bash
# From Contribution-Repos root directory
ls -la

# Should show:
# backend/
# frontend/
# docs/
# docker-compose.yml
# CLAUDE.md
# MODERNIZATION_GUIDE.md
# PHASE_1_COMPLETE.md
```

---

## Step 2: Start Database Services (5 minutes)

### 2.1 Start Docker Services

```bash
docker-compose up -d
```

**Output:**
```
Creating contribution_postgres ... done
Creating contribution_redis ... done
Creating contribution_rabbitmq ... done
```

### 2.2 Verify Services Running

```bash
docker-compose ps
```

**Should show:**
```
NAME                 STATUS
contribution_postgres   Up (healthy)
contribution_redis      Up (healthy)
contribution_rabbitmq   Up (healthy)
```

### 2.3 View Logs (if needed)

```bash
# PostgreSQL logs
docker-compose logs postgres

# Redis logs
docker-compose logs redis

# RabbitMQ logs
docker-compose logs rabbitmq
```

---

## Step 3: Start Backend Server (10 minutes)

### 3.1 Install Dependencies

```bash
cd backend
npm install
```

**Output:**
```
added 23 packages in 5s
```

### 3.2 Create .env File (if not done)

```bash
# backend/.env (already copied from .env.example)
# No changes needed - defaults work locally
```

**Key variables:**
```env
NODE_ENV=development
PORT=3000
DATABASE_HOST=localhost
DATABASE_PORT=5432
DATABASE_NAME=contributions_dev
DATABASE_USER=dev
DATABASE_PASSWORD=devpass
JWT_SECRET=dev-secret-change-in-production
```

### 3.3 Start Development Server

```bash
npm run dev
```

**Wait for this output:**
```
[INFO] 2024-07-29T14:30:00.000Z - Database connection established
[INFO] 2024-07-29T14:30:01.000Z - Database models synchronized
[INFO] 2024-07-29T14:30:02.000Z - Server running on http://localhost:3000
[INFO] 2024-07-29T14:30:02.000Z - Environment: development
```

### 3.4 Test Backend Health Check

**Open new terminal/tab** and test:

```bash
curl http://localhost:3000/health
```

**Expected response:**
```json
{
  "status": "healthy",
  "timestamp": "2024-07-29T14:30:00Z",
  "database": "connected"
}
```

✅ **Backend is running!** Keep this terminal open.

---

## Step 4: Start Frontend Server (5 minutes)

### 4.1 Open New Terminal

Open a **new terminal/tab** (don't close the backend terminal).

### 4.2 Install Dependencies

```bash
cd frontend
npm install
```

**Output:**
```
added 15 packages in 3s
```

### 4.3 Start Development Server

```bash
npm run dev
```

**Wait for this output:**
```
  ➜  Local:   http://localhost:5173/
  ➜  press h to show help
```

✅ **Frontend is running!** Keep this terminal open.

---

## Step 5: Open in Browser (1 minute)

### 5.1 Open Application

Open your browser and go to:

```
http://localhost:5173
```

**You should see:**
- Contribution Repos login page
- Email input field
- Password input field
- "Register here" link

---

## Step 6: Test User Registration (5 minutes)

### 6.1 Create Test Account

Click **"Register here"** or go to `http://localhost:5173/register`

**Fill in form:**
```
Email:       test@example.com
Password:    TestPass123!
First Name:  Test
Last Name:   User
Phone:       +254712345678
ID Number:   12345678
```

**Click "Register"**

**Expected:**
- Redirect to login page
- Message: "Registration successful! Please login."

### 6.2 Verify Database

Check that user was created:

```bash
# In a new terminal
psql -h localhost -U dev -d contributions_dev

# Password: devpass

# In PostgreSQL prompt:
SELECT id, email, first_name, last_name, role FROM members;

# Should show:
# id | email             | first_name | last_name | role
# 1  | test@example.com  | Test       | User      | member
```

---

## Step 7: Test Authentication Flow (5 minutes)

### 7.1 Login with Test Account

Go back to browser at `http://localhost:5173/login`

**Enter credentials:**
```
Email:    test@example.com
Password: TestPass123!
```

**Click "Login"**

**Expected:**
- Redirect to dashboard
- Welcome message: "Welcome, Test User!"
- Display email: "Email: test@example.com"
- Dashboard cards shown

### 7.2 Check Tokens in Browser

Open **DevTools** (F12) → **Application** → **Local Storage**

**You should see:**
```
Key: accessToken
Value: eyJhbGciOiJIUzI1NiIs...

Key: user
Value: {"id":1,"email":"test@example.com","firstName":"Test",...}
```

---

## Step 8: Test Member Profile (5 minutes)

### 8.1 Click "Profile" Button

In dashboard, click **"Profile"** button

**Expected:**
- Profile page loads
- Shows current information:
  - First Name: Test
  - Last Name: User
  - Email: test@example.com (disabled)
  - Phone: +254712345678
  - ID Number: 12345678

### 8.2 Update Profile

**Change values:**
```
First Name:  TestUpdated
Last Name:   UserUpdated
Phone:       +254712345679
ID Number:   87654321
```

**Click "Update Profile"**

**Expected:**
- Success message: "Profile updated successfully!"
- Values updated on page
- localStorage updated (check DevTools)

### 8.3 Verify Database Update

```bash
# In PostgreSQL:
SELECT id, first_name, last_name, phone FROM members WHERE id = 1;

# Should show updated values:
# id | first_name    | last_name   | phone
# 1  | TestUpdated   | UserUpdated | +254712345679
```

---

## Step 9: Test API Endpoints (10 minutes)

### 9.1 Get Access Token

```bash
# In new terminal, login via API
curl -X POST http://localhost:3000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "TestPass123!"
  }'
```

**Response:**
```json
{
  "message": "Login successful",
  "accessToken": "eyJhbGciOiJIUzI1NiIs...",
  "member": {
    "id": 1,
    "email": "test@example.com",
    "firstName": "TestUpdated",
    "lastName": "UserUpdated",
    "role": "member"
  }
}
```

**Copy the accessToken value** (you'll use it below)

### 9.2 Test Get Profile Endpoint

```bash
# Replace YOUR_TOKEN with the actual token
curl -X GET http://localhost:3000/api/members/profile \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Expected response:**
```json
{
  "member": {
    "id": 1,
    "email": "test@example.com",
    "firstName": "TestUpdated",
    "lastName": "UserUpdated",
    "phone": "+254712345679",
    "idNumber": "87654321",
    "role": "member",
    "isActive": true
  }
}
```

### 9.3 Test Update Profile Endpoint

```bash
curl -X PUT http://localhost:3000/api/members/profile \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "firstName": "FinalTest",
    "lastName": "FinalUser"
  }'
```

**Expected response:**
```json
{
  "message": "Profile updated successfully",
  "member": {
    "firstName": "FinalTest",
    "lastName": "FinalUser",
    ...
  }
}
```

### 9.4 Test Get All Members (Admin)

```bash
# First, create an admin account via the UI or database
# For now, test will fail with member role (expected)
curl -X GET http://localhost:3000/api/members \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Expected (member role):**
```json
{
  "message": "Insufficient permissions"
}
```

---

## Step 10: Test Logout & Redirects (3 minutes)

### 10.1 Click Logout

In dashboard, click **"Logout"** button

**Expected:**
- Redirect to login page
- localStorage cleared (DevTools → Application)
- Access token removed

### 10.2 Try Accessing Protected Route

Try to access `http://localhost:5173/dashboard` directly

**Expected:**
- Redirect to login page
- Cannot access without logging in

### 10.3 Test 404 Page

Go to: `http://localhost:5173/nonexistent`

**Expected:**
- 404 page shown
- "Go home" link available

---

## Step 11: Test Error Handling (5 minutes)

### 11.1 Invalid Login

Go to login page and try:
```
Email:    wrong@example.com
Password: WrongPassword
```

**Expected:**
- Error message: "Invalid credentials"
- Stay on login page

### 11.2 Invalid Email Registration

Go to register page and try:
```
Email:    notanemail
Password: TestPass123
```

**Expected:**
- Error message about invalid email
- Stay on registration page

### 11.3 Short Password

Try password less than 8 characters:
```
Email:    test2@example.com
Password: Short1
```

**Expected:**
- Error message: "Password must be at least 8 characters"

### 11.4 Network Error Handling

Stop the backend server (Ctrl+C in backend terminal)

Try to login in browser:

**Expected:**
- Error message: "Failed to connect to server" or similar
- UI remains usable

Start backend again: `npm run dev`

---

## Step 12: Performance & Monitoring (5 minutes)

### 12.1 Check Backend Logs

In backend terminal, you should see:
```
[INFO] GET /health - 200 (2ms)
[INFO] POST /api/auth/login - 200 (45ms)
[INFO] GET /api/members/profile - 200 (8ms)
[INFO] PUT /api/members/profile - 200 (15ms)
```

### 12.2 Monitor Database

```bash
# Check active connections
psql -h localhost -U dev -d contributions_dev

SELECT datname, count(*) FROM pg_stat_activity GROUP BY datname;
```

### 12.3 Check Network Performance

Open **DevTools** → **Network tab**

Perform actions and observe:
- Request times (should be < 100ms)
- Response sizes
- No failed requests

---

## Complete Testing Checklist

- [ ] Backend server running on port 3000
- [ ] Frontend running on port 5173
- [ ] Database services healthy
- [ ] User registration works
- [ ] User login works
- [ ] Profile page loads
- [ ] Profile update works
- [ ] API endpoints respond correctly
- [ ] Error messages display
- [ ] Logout works
- [ ] Protected routes redirect properly
- [ ] No console errors
- [ ] Response times acceptable

---

## Common Issues & Solutions

### Backend won't start: "Port 3000 already in use"

```bash
# Find process using port
lsof -i :3000

# Kill process
kill -9 <PID>

# Or change port in backend/.env
PORT=3001
```

### Database connection error

```bash
# Check PostgreSQL is running
docker-compose ps

# Check connection
psql -h localhost -U dev -d contributions_dev

# Restart services
docker-compose restart postgres
```

### Frontend shows blank page

```bash
# Check for errors in console (DevTools)
# Check network requests (DevTools → Network tab)
# Clear cache: Ctrl+Shift+Delete
# Restart frontend: Ctrl+C and npm run dev
```

### Cannot login: "Database not synchronized"

```bash
# Models auto-sync in development, wait ~5 seconds
# Check backend logs for sync message
# Or manually sync:
# (Not needed in dev mode)
```

### Token expired / "Unauthorized"

```bash
# Tokens last 15 minutes
# Log out and log back in
# Or use the refresh endpoint:
curl -X POST http://localhost:3000/api/auth/refresh \
  -H "Content-Type: application/json" \
  -d '{"refreshToken": "YOUR_REFRESH_TOKEN"}'
```

---

## Testing Summary

You've successfully tested:

✅ **User Registration**
- Email validation
- Password requirements
- Database storage

✅ **Authentication**
- Login flow
- JWT token generation
- Token storage in localStorage

✅ **Profile Management**
- Get profile
- Update profile
- Database persistence

✅ **Protected Routes**
- Authentication required
- Redirect on unauthorized
- Permission checks

✅ **Error Handling**
- Invalid credentials
- Validation errors
- Network errors

✅ **API Endpoints**
- All 9 Phase 1 endpoints
- Request/response format
- Error responses

✅ **Performance**
- Response times acceptable
- No memory leaks
- Proper logging

---

## Next Steps

### If Tests Passed ✅

1. **Stop local services:**
   ```bash
   docker-compose down
   ```

2. **Commit your testing results:**
   ```bash
   git add .
   git commit -m "test: verify Phase 1 local testing complete"
   git push origin master
   ```

3. **Move to Phase 2:**
   - Review DEPLOYMENT.md for production setup
   - Plan payment integration
   - Set up Coolify/Azure

### If Tests Failed ❌

1. **Check error logs:**
   ```bash
   # Backend logs
   npm run dev  # Look at terminal output
   
   # Browser console
   DevTools → Console tab
   
   # Network requests
   DevTools → Network tab
   ```

2. **Review docs:**
   - DEVELOPMENT.md - Setup issues
   - API.md - Endpoint format
   - Troubleshooting section above

3. **Check database:**
   ```bash
   docker-compose ps
   psql -h localhost -U dev -d contributions_dev
   SELECT * FROM members;
   ```

---

## Testing Environment Details

### Services

| Service | URL | Username | Password | Notes |
|---------|-----|----------|----------|-------|
| Frontend | http://localhost:5173 | - | - | React SPA |
| Backend | http://localhost:3000 | - | - | Express API |
| PostgreSQL | localhost:5432 | dev | devpass | contributions_dev DB |
| Redis | localhost:6379 | - | - | Cache (Phase 2) |
| RabbitMQ | localhost:5672 | guest | guest | Queue (Phase 2) |

### Test User Account

Created during registration test:
```
Email:    test@example.com
Password: TestPass123!
```

Can register additional test accounts as needed.

---

## Performance Expectations

| Operation | Expected Time | Status |
|-----------|---------------|--------|
| User Registration | < 100ms | ✅ |
| User Login | < 50ms | ✅ |
| Get Profile | < 20ms | ✅ |
| Update Profile | < 30ms | ✅ |
| Page Load | < 500ms | ✅ |

---

## Security Testing (Phase 2)

- [ ] SQL injection attempts
- [ ] XSS attempts
- [ ] CSRF protection
- [ ] Token expiration
- [ ] Invalid token handling
- [ ] Password hashing
- [ ] Rate limiting

---

## Duration Summary

| Task | Time |
|------|------|
| Setup & Dependencies | 10 min |
| Start Services | 5 min |
| Backend Testing | 10 min |
| Frontend Testing | 10 min |
| API Testing | 5 min |
| **Total** | **40 min** |

---

**Ready to test? Start with Step 1!**

Questions? Check DEVELOPMENT.md or API.md for more details.
