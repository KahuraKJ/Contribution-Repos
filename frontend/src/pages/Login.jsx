import { useState } from 'react'
import { useNavigate, Link } from 'react-router-dom'
import { useDispatch, useSelector } from 'react-redux'
import { loginSuccess, setError } from '../store/authSlice'
import authService from '../services/authService'
import '../styles/auth.css'

function Login() {
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [localError, setLocalError] = useState('')
  const dispatch = useDispatch()
  const navigate = useNavigate()
  const { loading } = useSelector(state => state.auth)

  const handleSubmit = async (e) => {
    e.preventDefault()
    setLocalError('')

    try {
      const response = await authService.login(email, password)
      dispatch(loginSuccess({
        user: response.member,
        accessToken: response.accessToken,
        refreshToken: response.refreshToken || localStorage.getItem('refreshToken')
      }))
      navigate('/dashboard')
    } catch (error) {
      const message = error.response?.data?.message || 'Login failed'
      setLocalError(message)
      dispatch(setError(message))
    }
  }

  return (
    <div className="auth-container">
      <div className="auth-card">
        <h1>Contribution Repos</h1>
        <h2>Login</h2>

        {localError && <div className="error-message">{localError}</div>}

        <form onSubmit={handleSubmit}>
          <input
            type="email"
            placeholder="Email"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            required
          />
          <input
            type="password"
            placeholder="Password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            required
          />
          <button type="submit" disabled={loading}>
            {loading ? 'Logging in...' : 'Login'}
          </button>
        </form>

        <p>Don't have an account? <Link to="/register">Register here</Link></p>
      </div>
    </div>
  )
}

export default Login
