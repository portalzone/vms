<template>
  <div class="login max-w-md mx-auto mt-10">
    <h2 class="text-2xl font-bold mb-4">Login</h2>
    <form @submit.prevent="login">
      <div class="mb-4">
        <label>Email:</label>
        <input v-model="email" type="email" class="border p-2 w-full" required />
      </div>
      <div class="mb-4">
        <label>Password:</label>
        <input v-model="password" type="password" class="border p-2 w-full" required />
      </div>
      <br>
      <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Login</button>
    </form>
    <p v-if="error" class="text-red-500 mt-2">{{ error }}</p>
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
const error = ref(null)

const login = async () => {
  error.value = null
  try {
    const response = await axios.post('/login', {
      email: email.value,
      password: password.value
    })

    const token = response.data.token
    auth.setToken(token) // ✅ Save token using Pinia store
    await auth.fetchUser() // ✅ Load authenticated user

    router.push('/dashboard') // ✅ Redirect after login
  } catch (err) {
    error.value = err.response?.data?.message || 'Invalid credentials'
  }
}
</script>
