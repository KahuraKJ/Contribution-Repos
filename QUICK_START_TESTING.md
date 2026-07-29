# Quick Start Testing (Copy & Paste)

## 🚀 Start Testing in 5 Minutes

### Terminal 1: Database Services

```bash
cd "C:\Users\Lenovo\Wakandi Repos\Contribution Repos"
docker-compose up -d
docker-compose ps
```

Wait for all services to show "Up (healthy)".

---

### Terminal 2: Backend Server

```bash
cd "C:\Users\Lenovo\Wakandi Repos\Contribution Repos\backend"
npm install
npm run dev
```

Wait for: **"Server running on http://localhost:3000"**

---

### Terminal 3: Frontend Server

```bash
cd "C:\Users\Lenovo\Wakandi Repos\Contribution Repos\frontend"
npm install
npm run dev
```

Wait for: **"Local: http://localhost:5173/"**

---

## 🌐 Open in Browser

```
http://localhost:5173
```

---

## 🧪 Test These Actions

### 1. Register Account
- Click "Register here"
- Email: `test@example.com`
- Password: `TestPass123!`
- First Name: `Test`
- Last Name: `User`
- Click "Register"

### 2. Login
- Email: `test@example.com`
- Password: `TestPass123!`
- Click "Login"

### 3. View Dashboard
- Should see welcome message
- Should see dashboard cards

### 4. Update Profile
- Click "Profile"
- Change First Name to `TestUpdated`
- Click "Update Profile"
- Should see success message

### 5. Check Logout
- Click "Logout"
- Should redirect to login

---

## ✅ All Tests Passed?

When done, stop services:

```bash
# Terminal 1
docker-compose down

# Terminal 2 & 3
Ctrl+C
```

Then read: **[docs/LOCAL_TESTING.md](docs/LOCAL_TESTING.md)** for detailed testing guide.

---

## 🔗 Important URLs

| Service | URL |
|---------|-----|
| Frontend | http://localhost:5173 |
| Backend API | http://localhost:3000 |
| API Health | http://localhost:3000/health |
| API Docs | [docs/API.md](docs/API.md) |

---

## 📝 Testing Guide

For detailed testing with:
- ✅ API endpoint testing
- ✅ Error handling
- ✅ Performance monitoring
- ✅ Troubleshooting

Read: **[docs/LOCAL_TESTING.md](docs/LOCAL_TESTING.md)**

---

**Ready? Start with Terminal 1 command above!**
