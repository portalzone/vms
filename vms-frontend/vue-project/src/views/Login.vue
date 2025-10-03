<template>
  <div class="auth-card">
    <h2 class="mb-4 text-2xl font-bold text-center">Login</h2>

    <div v-if="error" class="mb-4 text-center text-red-600 error-text">
      {{ error }}
    </div>

    <form @submit.prevent="login">
      <div class="mb-6">
        <label class="block mb-2 text-gray-700">Email:</label>
        <input
          v-model="email"
          type="email"
          class="w-full px-4 py-2 border border-gray-300 rounded"
          required
        />
      </div>

      <div class="mb-6">
        <label class="block mb-2 text-gray-700">Password:</label>
        <input
          v-model="password"
          type="password"
          class="w-full px-4 py-2 border border-gray-300 rounded"
          required
        />
      </div>
      <br />
      <button
        type="submit"
        class="w-full px-4 py-2 text-white bg-blue-600 rounded hover:bg-blue-700"
        :disabled="loading"
      >
        <span v-if="loading">Logging in...</span>
        <span v-else>Login</span>
      </button>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import axios from '@/axios'

const router = useRouter()
const auth = useAuthStore()

const email = ref('')
const password = ref('')
const error = ref('')

const loading = ref(false)

const login = async () => {
  error.value = ''
  loading.value = true
  try {
    const response = await axios.post('/login', {
      email: email.value,
      password: password.value,
    })

    const token = response.data.token
    auth.setToken(token)
    await auth.fetchUser()
    router.push('/dashboard')
  } catch (err) {
    error.value = err.response?.data?.message || 'Login failed. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>
<style scoped></style>
