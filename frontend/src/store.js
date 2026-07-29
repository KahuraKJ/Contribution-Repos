import { configureStore } from '@reduxjs/toolkit'
import authSlice from './store/authSlice'
import memberSlice from './store/memberSlice'

const store = configureStore({
  reducer: {
    auth: authSlice,
    member: memberSlice
  }
})

export default store
