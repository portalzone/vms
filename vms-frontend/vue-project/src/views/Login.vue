<template>
  <div class="login max-w-md mx-auto mt-10">
    <h2 class="text-2xl font-bold mb-4">Login</h2>

    <!-- Error Message -->
    <div v-if="error" class="error-text">
      {{ error }}
    </div>

    <form @submit.prevent="login">
      <div class="mb-4">
        <label>Email:</label>
        <input
          v-model="email"
          type="email"
          class="border border-gray-300 rounded px-3 py-2 w-full"
          required
        />
      </div>

      <div class="mb-4">
        <label>Password:</label>
        <input
          v-model="password"
          type="password"
          class="border border-gray-300 rounded px-3 py-2 w-full"
          required
        />
      </div>

<button
  type="submit"
  class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded w-full"
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
      password: password.value
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
