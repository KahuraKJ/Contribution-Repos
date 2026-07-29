import { createSlice } from '@reduxjs/toolkit'

const initialState = {
  profile: null,
  members: [],
  loading: false,
  error: null,
  totalMembers: 0
}

const memberSlice = createSlice({
  name: 'member',
  initialState,
  reducers: {
    setLoading: (state, action) => {
      state.loading = action.payload
    },
    setError: (state, action) => {
      state.error = action.payload
    },
    setProfile: (state, action) => {
      state.profile = action.payload
      state.error = null
    },
    setMembers: (state, action) => {
      state.members = action.payload.members
      state.totalMembers = action.payload.total
      state.error = null
    },
    updateProfile: (state, action) => {
      state.profile = { ...state.profile, ...action.payload }
    }
  }
})

export const { setLoading, setError, setProfile, setMembers, updateProfile } = memberSlice.actions
export default memberSlice.reducer
