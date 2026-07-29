import { Link } from 'react-router-dom'
import '../styles/notfound.css'

function NotFound() {
  return (
    <div className="notfound-container">
      <h1>404</h1>
      <p>Page not found</p>
      <Link to="/">Go home</Link>
    </div>
  )
}

export default NotFound
