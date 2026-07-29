import { useSelector, useDispatch } from 'react-redux'
import { useNavigate } from 'react-router-dom'
import { logout } from '../store/authSlice'
import '../styles/dashboard.css'

function Dashboard() {
  const { user } = useSelector(state => state.auth)
  const dispatch = useDispatch()
  const navigate = useNavigate()

  const handleLogout = () => {
    dispatch(logout())
    navigate('/login')
  }

  return (
    <div className="dashboard-container">
      <nav className="dashboard-nav">
        <h1>Contribution Repos</h1>
        <div className="nav-links">
          <button onClick={() => navigate('/profile')}>Profile</button>
          <button onClick={handleLogout}>Logout</button>
        </div>
      </nav>

      <div className="dashboard-content">
        <h2>Welcome, {user?.firstName} {user?.lastName}!</h2>
        <p>Email: {user?.email}</p>

        <div className="dashboard-grid">
          <div className="dashboard-card">
            <h3>Contributions</h3>
            <p>Track and manage your contributions</p>
            <button onClick={() => navigate('/contributions')}>View</button>
          </div>

          <div className="dashboard-card">
            <h3>Reports</h3>
            <p>View contribution reports and statistics</p>
            <button>View</button>
          </div>

          <div className="dashboard-card">
            <h3>Profile</h3>
            <p>Update your personal information</p>
            <button onClick={() => navigate('/profile')}>Edit</button>
          </div>
        </div>
      </div>
    </div>
  )
}

export default Dashboard
