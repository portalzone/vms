import axios from 'axios'

// Set base URL to your Laravel backend (adjust if needed)
const api = axios.create({
  baseURL: 'http://localhost:8000/api', // Change if backend is hosted elsewhere
  withCredentials: true,
})

// Set Authorization header if token exists
const token = localStorage.getItem('token')
if (token) {
  api.defaults.headers.common['Authorization'] = `Bearer ${token}`
}

export default api
