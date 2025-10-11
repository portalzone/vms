// axios.js
import axios from 'axios'

const instance = axios.create({
  baseURL: 'https://vms-production-6ebc.up.railway.app/api',
  // baseURL: 'http://localhost:8000/api',
})

instance.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// ✅ Improved 401 handling
instance.interceptors.response.use(
  (res) => res,
  (err) => {
    const originalRequest = err.config

    // Don't redirect if the request is to the login endpoint
    const isLoginAttempt = originalRequest?.url?.includes('/login')

    if (err.response?.status === 401 && !isLoginAttempt) {
      localStorage.removeItem('token')
      window.location.href = '/'
    }

    return Promise.reject(err)
  },
)

export default instance
