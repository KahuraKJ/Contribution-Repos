import { createSlice } from '@reduxjs/toolkit'

const initialState = {
  isAuthenticated: !!localStorage.getItem('accessToken'),
  user: localStorage.getItem('user') ? JSON.parse(localStorage.getItem('user')) : null,
  accessToken: localStorage.getItem('accessToken') || null,
  refreshToken: localStorage.getItem('refreshToken') || null,
  loading: false,
  error: null
}

const authSlice = createSlice({
  name: 'auth',
  initialState,
  reducers: {
    setLoading: (state, action) => {
      state.loading = action.payload
    },
    setError: (state, action) => {
      state.error = action.payload
    },
    loginSuccess: (state, action) => {
      const { user, accessToken, refreshToken } = action.payload
      state.isAuthenticated = true
      state.user = user
      state.accessToken = accessToken
      state.refreshToken = refreshToken
      state.error = null

      localStorage.setItem('user', JSON.stringify(user))
      localStorage.setItem('accessToken', accessToken)
      localStorage.setItem('refreshToken', refreshToken)
    },
    logout: (state) => {
      state.isAuthenticated = false
      state.user = null
      state.accessToken = null
      state.refreshToken = null
      state.error = null

      localStorage.removeItem('user')
      localStorage.removeItem('accessToken')
      localStorage.removeItem('refreshToken')
    },
    updateUser: (state, action) => {
      state.user = { ...state.user, ...action.payload }
      localStorage.setItem('user', JSON.stringify(state.user))
    }
  }
})

export const { setLoading, setError, loginSuccess, logout, updateUser } = authSlice.actions
export default authSlice.reducer
