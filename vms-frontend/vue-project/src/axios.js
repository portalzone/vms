// axios.js
import axios from 'axios'

const instance = axios.create({
  baseURL: 'https://munext-production.up.railway.app/api',
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
