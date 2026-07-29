# Contribution Repos - Frontend

React-based single-page application for the contribution management system.

## Quick Start

```bash
# Install dependencies
npm install

# Create .env file
cp .env.example .env

# Start development server
npm run dev
```

Application runs on `http://localhost:5173`

## Project Structure

```
src/
├── pages/           # Full page components (Login, Dashboard, etc.)
├── components/      # Reusable components
├── services/        # API communication
├── store/           # Redux state management
├── styles/          # CSS files
├── App.jsx          # Root component
└── main.jsx         # Entry point
```

## Available Scripts

- `npm run dev` - Start development server
- `npm run build` - Build for production
- `npm run preview` - Preview production build
- `npm test` - Run tests
- `npm run lint` - Check code style
- `npm run format` - Format code with Prettier

## Environment Variables

See `.env.example`:
- `VITE_API_URL` - Backend API URL
- `VITE_APP_NAME` - Application name
- `VITE_LOG_LEVEL` - Log level (debug/info)

## Pages

### Public Pages
- `/login` - Login page
- `/register` - Registration page

### Protected Pages
- `/dashboard` - Member dashboard
- `/profile` - Member profile
- `/contributions` - Contribution history (Phase 2)

## Architecture

### State Management (Redux)
```
Store
├── auth
│   ├── isAuthenticated
│   ├── user
│   ├── accessToken
│   └── refreshToken
└── member
    ├── profile
    ├── members
    └── totalMembers
```

### API Service
```
services/
├── api.js           # Axios instance with interceptors
├── authService.js   # Authentication API calls
└── memberService.js # Member API calls
```

### Routing
```
App.jsx
├── /login (public)
├── /register (public)
├── /dashboard (protected)
├── /profile (protected)
└── /404 (not found)
```

## Key Features

### Authentication Flow
1. User enters email & password
2. `authService.login()` calls backend API
3. Redux stores tokens and user info
4. Tokens auto-saved to localStorage
5. Auto-refresh on 401 response

### API Calls
```jsx
import authService from '../services/authService'

async function handleLogin(email, password) {
  try {
    const response = await authService.login(email, password)
    dispatch(loginSuccess(response))
  } catch (error) {
    setError(error.response.data.message)
  }
}
```

### Protected Routes
```jsx
<Route 
  path="/dashboard" 
  element={isAuthenticated ? <Dashboard /> : <Navigate to="/login" />} 
/>
```

## Components

### Page Components
- `Login.jsx` - User login form
- `Register.jsx` - User registration form
- `Dashboard.jsx` - Main dashboard
- `Profile.jsx` - User profile editor
- `NotFound.jsx` - 404 page

### Utilities
- `services/api.js` - HTTP client with auth
- `store/authSlice.js` - Auth state
- `store/memberSlice.js` - Member state

## Styling

CSS modules and global styles:
```
src/styles/
├── index.css        # Global styles
├── auth.css         # Auth pages
├── dashboard.css    # Dashboard
├── profile.css      # Profile page
└── notfound.css     # 404 page
```

## Development

### Create a New Page

```jsx
// src/pages/MyPage.jsx
import { useEffect, useState } from 'react'
import '../styles/mypage.css'

function MyPage() {
  const [data, setData] = useState(null)

  useEffect(() => {
    // Load data
  }, [])

  return (
    <div className="my-page">
      {/* Content */}
    </div>
  )
}

export default MyPage
```

Add route in `App.jsx`:
```jsx
<Route path="/mypage" element={<MyPage />} />
```

### Use Redux State

```jsx
import { useSelector, useDispatch } from 'react-redux'
import { updateUser } from '../store/authSlice'

function MyComponent() {
  const user = useSelector(state => state.auth.user)
  const dispatch = useDispatch()

  return <div>{user?.name}</div>
}
```

### Make API Call

```jsx
import memberService from '../services/memberService'

async function loadProfile() {
  try {
    const profile = await memberService.getProfile()
    setProfile(profile)
  } catch (error) {
    setError(error.message)
  }
}
```

## Build & Deployment

### Development Build
```bash
npm run build
npm run preview
```

### Production Build
Docker image:
```bash
docker build -t contribution-repos-frontend .
docker run -p 80:80 contribution-repos-frontend
```

Dockerfile uses multi-stage build:
1. Build stage: Node.js builds React app → `/dist`
2. Runtime stage: Nginx serves static files

## Testing

```bash
npm run test              # Run tests
npm run test:ui          # UI mode
```

## Performance Optimizations

- ✅ Vite for fast development & build
- ✅ Code splitting (route-based)
- ✅ Lazy loading components (future)
- ✅ Redux DevTools for state debugging
- ✅ CSS minification in production

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## Error Handling

Global error handling in `services/api.js`:
- Auto-refresh tokens on 401
- Redirect to login on 403
- Display error messages
- Sentry integration (future)

## Debugging

### Redux DevTools
Install browser extension to inspect state changes.

### Network Requests
Open DevTools → Network tab to inspect API calls.

### Console Logs
```javascript
// Log state changes
console.log('User:', user)

// Log API responses
console.log('API Response:', response)
```

## Troubleshooting

**Port already in use:**
```bash
npm run dev -- --port 5174
```

**Module not found:**
```bash
rm -rf node_modules package-lock.json
npm install
```

**Styles not loading:**
- Check CSS import path
- Clear browser cache (Ctrl+Shift+Delete)
- Restart dev server

**API not responding:**
- Verify backend is running (`npm run dev` in backend/)
- Check `VITE_API_URL` in .env
- Check network tab in DevTools

## Resources

- [React Docs](https://react.dev)
- [Vite Docs](https://vitejs.dev)
- [Redux Docs](https://redux.js.org)
- [Axios Docs](https://axios-http.com)

## Support

For frontend issues: frontend-support@contributions.wak.link

---

**Status**: Phase 1 Auth & Dashboard ✅ | Phase 2 Contributions 🔜 | Phase 3 Admin 🔜
