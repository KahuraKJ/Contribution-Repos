# Development Guide

## Quick Start (5 minutes)

### Prerequisites
- Node.js 18+ ([download](https://nodejs.org/))
- Docker & Docker Compose ([download](https://www.docker.com/products/docker-desktop/))
- Git

### 1. Clone & Setup

```bash
git clone https://github.com/kjkahura/Contribution-Repos.git
cd Contribution-Repos

# Create .env files from examples
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env
```

### 2. Start Database & Services

```bash
# Start PostgreSQL, Redis, RabbitMQ in Docker
docker-compose up -d

# Verify services are running
docker-compose ps
```

### 3. Start Backend

```bash
cd backend
npm install
npm run dev
```

Backend runs on `http://localhost:3000`

### 4. Start Frontend (New Terminal)

```bash
cd frontend
npm install
npm run dev
```

Frontend runs on `http://localhost:5173`

### 5. Test the Application

Open browser: `http://localhost:5173`

**Test Account:**
- Email: `test@example.com`
- Password: `TestPass123`

(Create one via Register if it doesn't exist)

---

## Directory Structure

```
Contribution-Repos/
├── backend/                  # Node.js API server
│   ├── src/
│   │   ├── config/          # Configuration files
│   │   ├── controllers/     # Route handlers
│   │   ├── models/          # Sequelize models
│   │   ├── services/        # Business logic
│   │   ├── routes/          # API routes
│   │   ├── middleware/      # Express middleware
│   │   └── index.js         # Entry point
│   ├── package.json
│   ├── Dockerfile
│   └── README.md
│
├── frontend/                 # React SPA
│   ├── src/
│   │   ├── components/      # React components
│   │   ├── pages/           # Page components
│   │   ├── services/        # API calls
│   │   ├── store/           # Redux state
│   │   ├── styles/          # CSS files
│   │   └── main.jsx
│   ├── package.json
│   ├── Dockerfile
│   ├── nginx.conf
│   └── index.html
│
├── docs/                     # Documentation
│   ├── API.md               # API reference
│   ├── DEVELOPMENT.md       # This file
│   └── DATABASE.md          # Database schema
│
└── docker-compose.yml        # Local dev services
```

---

## Backend Development

### Project Structure

```
backend/src/
├── config/              # Database, logger, JWT config
├── controllers/         # HTTP request handlers
├── models/              # Database models (Sequelize)
├── services/            # Business logic layer
├── routes/              # API endpoints
├── middleware/          # Auth, validation, error handling
└── index.js             # App entry point
```

### Key Files

**src/index.js** - Express app setup
```javascript
import express from 'express'
import routes from './routes'

const app = express()
app.use('/api', routes)
app.listen(3000)
```

**src/config/database.js** - Sequelize configuration
```javascript
import { Sequelize } from 'sequelize'

export const sequelize = new Sequelize({
  dialect: 'postgres',
  host: process.env.DATABASE_HOST,
  // ...
})
```

**src/models/Member.js** - Database model
```javascript
const Member = sequelize.define('Member', {
  email: { type: DataTypes.STRING, unique: true },
  passwordHash: DataTypes.STRING,
  // ...
})
```

**src/services/authService.js** - Business logic
```javascript
async register(userData) {
  // Validate, hash password, create member
}

async login(email, password) {
  // Verify credentials, generate tokens
}
```

**src/routes/auth.js** - Route definitions
```javascript
router.post('/register', validateRegister, authController.register)
router.post('/login', validateLogin, authController.login)
```

### Common Tasks

#### Create a New Route

1. **Add to model** (`src/models/MyModel.js`)
```javascript
const MyModel = sequelize.define('MyModel', {
  name: DataTypes.STRING,
  // ...
})
```

2. **Create service** (`src/services/myService.js`)
```javascript
export class MyService {
  async getAll() { /* ... */ }
}
```

3. **Create controller** (`src/controllers/myController.js`)
```javascript
export class MyController {
  async getAll(req, res) {
    const items = await myService.getAll()
    res.json(items)
  }
}
```

4. **Add route** (`src/routes/myroute.js`)
```javascript
router.get('/', myController.getAll)
```

5. **Register in** `src/index.js`
```javascript
app.use('/api/myroute', myRoutes)
```

#### Add Authentication to a Route

```javascript
import { authenticate, authorize } from '../middleware/auth.js'

// Protected route
router.get('/admin-only', authenticate, authorize(['admin']), controller.action)

// On controller:
const memberId = req.user.id  // From JWT payload
```

#### Add Validation

```javascript
import { body, validationResult } from 'express-validator'

router.post('/create', [
  body('email').isEmail(),
  body('age').isInt({ min: 18 })
], controller.create)

// In controller:
const errors = validationResult(req)
if (!errors.isEmpty()) return res.status(400).json({ errors })
```

#### Run Tests

```bash
npm run test
npm run test:watch  # Watch mode
```

#### Check Logs

```bash
# Development logs (with timestamps and levels)
NODE_ENV=development npm run dev

# Set log level
LOG_LEVEL=debug npm run dev
```

---

## Frontend Development

### Project Structure

```
frontend/src/
├── pages/               # Full page components
│   ├── Login.jsx
│   ├── Dashboard.jsx
│   └── ...
├── components/          # Reusable components
│   ├── Header.jsx
│   ├── Forms/
│   └── ...
├── services/            # API calls
│   ├── authService.js
│   └── api.js           # Axios instance
├── store/               # Redux state
│   ├── authSlice.js
│   └── ...
├── styles/              # CSS files
│   ├── index.css
│   ├── auth.css
│   └── ...
├── App.jsx              # Root component
└── main.jsx             # Entry point
```

### Key Files

**src/main.jsx** - React app bootstrap
```jsx
import { Provider } from 'react-redux'
import { BrowserRouter } from 'react-router-dom'
import store from './store'
import App from './App'

ReactDOM.createRoot(document.getElementById('root')).render(
  <Provider store={store}>
    <BrowserRouter>
      <App />
    </BrowserRouter>
  </Provider>
)
```

**src/App.jsx** - Route definitions
```jsx
<Routes>
  <Route path="/login" element={<Login />} />
  <Route path="/dashboard" element={<Dashboard />} />
  // ...
</Routes>
```

**src/services/api.js** - Axios HTTP client
```javascript
const api = axios.create({ baseURL: import.meta.env.VITE_API_URL })

// Auto-adds token to requests
api.interceptors.request.use(config => {
  config.headers.Authorization = `Bearer ${token}`
  return config
})

// Auto-refreshes token on 401
api.interceptors.response.use(
  response => response,
  error => { /* handle 401, refresh token */ }
)
```

**src/store/authSlice.js** - Redux state management
```javascript
const authSlice = createSlice({
  name: 'auth',
  initialState: { isAuthenticated: false, user: null },
  reducers: {
    loginSuccess: (state, action) => {
      state.isAuthenticated = true
      state.user = action.payload
    }
  }
})
```

### Common Tasks

#### Create a New Page

1. **Create page component** (`src/pages/MyPage.jsx`)
```jsx
function MyPage() {
  const [data, setData] = useState(null)
  
  useEffect(() => {
    // Fetch data
  }, [])
  
  return (
    <div>
      {/* Content */}
    </div>
  )
}

export default MyPage
```

2. **Add route** (`src/App.jsx`)
```jsx
<Route path="/mypage" element={<MyPage />} />
```

3. **Add navigation link**
```jsx
<Link to="/mypage">My Page</Link>
```

#### Call API

```jsx
import authService from '../services/authService'

async function handleLogin() {
  try {
    const response = await authService.login(email, password)
    dispatch(loginSuccess(response))
  } catch (error) {
    setError(error.response.data.message)
  }
}
```

#### Use Redux State

```jsx
import { useSelector, useDispatch } from 'react-redux'
import { updateUser } from '../store/authSlice'

function MyComponent() {
  const user = useSelector(state => state.auth.user)
  const dispatch = useDispatch()
  
  const handleUpdate = (newData) => {
    dispatch(updateUser(newData))
  }
  
  return <div>{user?.name}</div>
}
```

#### Add Styling

```jsx
import '../styles/mypage.css'

// In mypage.css
.my-component {
  background: white;
  padding: 20px;
  border-radius: 8px;
}
```

#### Run Tests

```bash
npm run test
npm run test:ui  # UI mode
```

#### Build for Production

```bash
npm run build
# Output: dist/ folder
npm run preview  # Test production build
```

---

## Database

### Connect to PostgreSQL

```bash
# From local machine
psql -h localhost -U dev -d contributions_dev

# Password: devpass
```

### View Current Schema

```sql
\dt  -- List all tables
\d members  -- Describe table
SELECT * FROM members;
```

### Create Tables

Database tables are auto-created on first run via Sequelize `.sync()`.

For production, use migrations:
```bash
npm run migrate
```

---

## Environment Variables

### Backend (.env)

```env
NODE_ENV=development
PORT=3000
DATABASE_URL=postgresql://dev:devpass@localhost:5432/contributions_dev
REDIS_URL=redis://localhost:6379
RABBITMQ_URL=amqp://guest:guest@localhost:5672
JWT_SECRET=dev-secret-change-in-production
FRONTEND_URL=http://localhost:5173
```

### Frontend (.env)

```env
VITE_API_URL=http://localhost:3000/api
VITE_APP_NAME=Contribution Repos
VITE_LOG_LEVEL=debug
```

---

## Debugging

### Backend Debug Logs

```bash
LOG_LEVEL=debug npm run dev
```

### Frontend Redux DevTools

Install [Redux DevTools](https://chrome.google.com/webstore) browser extension.

### Network Requests

Open browser DevTools → Network tab

### Database Queries

```javascript
// In config/database.js, set logging:
logging: console.log  // Logs all SQL queries
```

---

## Useful Commands

### Backend

```bash
npm run dev              # Start dev server
npm run start            # Start production server
npm run test             # Run tests
npm run lint             # Check code style
npm run migrate          # Run database migrations
npm run seed             # Seed initial data
```

### Frontend

```bash
npm run dev              # Start dev server
npm run build            # Build for production
npm run preview          # Preview production build
npm run test             # Run tests
npm run lint             # Check code style
npm run format           # Format code
```

### Docker

```bash
docker-compose up -d     # Start services
docker-compose down      # Stop services
docker-compose logs -f   # View logs
docker ps                # List running containers
```

---

## Troubleshooting

### Port Already in Use

```bash
# Change port in .env or find process
lsof -i :3000  # macOS/Linux
netstat -ano | findstr :3000  # Windows
```

### Database Connection Error

```bash
# Check PostgreSQL is running
docker-compose ps
docker-compose logs postgres

# Test connection
psql -h localhost -U dev -d contributions_dev
```

### Modules Not Found

```bash
# Reinstall dependencies
rm -rf node_modules package-lock.json
npm install
```

### API Not Responding

```bash
# Check logs
npm run dev

# Test endpoint
curl http://localhost:3000/health
```

---

## Next Steps

1. ✅ You now have a working dev environment
2. 📝 Review [API.md](./API.md) for available endpoints
3. 🏗️ Check [DATABASE.md](./DATABASE.md) for schema details
4. 🚀 Ready to add Phase 2 features (payments)

---

## Get Help

- **API Issues**: Check [API.md](./API.md)
- **Database Issues**: Check [DATABASE.md](./DATABASE.md)
- **Code Issues**: Check project README.md
- **Deployment**: Check [DEPLOYMENT.md](./DEPLOYMENT.md)

---

## Tips

- Use `npm run dev` to auto-reload on file changes
- Commit frequently with clear messages
- Run tests before pushing code
- Keep `.env` out of git (use `.env.example`)
- Use Redux DevTools for state debugging
- Check browser console for frontend errors

Happy coding! 🚀
