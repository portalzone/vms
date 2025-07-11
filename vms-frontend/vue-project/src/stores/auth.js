// stores/auth.js
import { defineStore } from 'pinia'
import axios from '@/axios'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('token') || null,
  }),

  actions: {
    setToken(token) {
      this.token = token
      localStorage.setItem('token', token)
      axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
      this.fetchUser()
    },

    async fetchUser() {
      if (!this.token) return

      try {
        axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`
        const response = await axios.get('/me')
        this.user = response.data.user
      } catch (err) {
        console.error('🔒 Failed to fetch user:', err)
        this.logout()
      }
    },

    async login(credentials) {
      try {
        const res = await axios.post('/login', credentials)
        const { token, user } = res.data
        this.token = token
        this.user = user
        localStorage.setItem('token', token)
        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
      } catch (err) {
        throw err
      }
    },

    async logout(router) {
      try {
        await axios.post('/logout')
      } catch (e) {
        console.warn('Logout failed or was already invalid')
      }

      localStorage.removeItem('token')
      delete axios.defaults.headers.common['Authorization']
      this.token = null
      this.user = null

      if (router) {
        router.push('/') // ✅ Redirect to Home page
      }
    },

    hasRole(role) {
      return this.user?.role === role
    },

    hasAnyRole(roles) {
      return roles.includes(this.user?.role)
    }
  }
})
