import api from './api'

export const authService = {
  async register(userData) {
    const response = await api.post('/auth/register', userData)
    return response.data
  },

  async login(email, password) {
    const response = await api.post('/auth/login', { email, password })
    return response.data
  },

  async logout() {
    await api.post('/auth/logout')
  },

  async changePassword(oldPassword, newPassword) {
    const response = await api.post('/auth/change-password', {
      oldPassword,
      newPassword
    })
    return response.data
  },

  async refreshToken(refreshToken) {
    const response = await api.post('/auth/refresh', { refreshToken })
    return response.data
  }
}

export default authService
