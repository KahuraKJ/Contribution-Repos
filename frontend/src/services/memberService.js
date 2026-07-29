import api from './api'

export const memberService = {
  async getProfile() {
    const response = await api.get('/members/profile')
    return response.data.member
  },

  async updateProfile(profileData) {
    const response = await api.put('/members/profile', profileData)
    return response.data.member
  },

  async getMember(id) {
    const response = await api.get(`/members/${id}`)
    return response.data.member
  },

  async getAllMembers(limit = 10, offset = 0) {
    const response = await api.get('/members', {
      params: { limit, offset }
    })
    return response.data
  }
}

export default memberService
