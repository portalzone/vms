<template>
  <div class="register max-w-md mx-auto mt-10">
    <h2 class="text-2xl font-bold mb-4">Register</h2>
    <form @submit.prevent="register">
      <div class="mb-4">
        <label>Name:</label>
        <input v-model="name" type="text" class="border p-2 w-full" required />
      </div>
      <div class="mb-4">
        <label>Email:</label>
        <input v-model="email" type="email" class="border p-2 w-full" required />
      </div>
      <div class="mb-4">
        <label>Password:</label>
        <input v-model="password" type="password" class="border p-2 w-full" required />
      </div>
      <div class="mb-4">
        <label>Confirm Password:</label>
        <input v-model="password_confirmation" type="password" class="border p-2 w-full" required />
      </div>
      <br>
      <button type="submit" class="bg-green-600 text-white px-4 py-2">Register</button>
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

const name = ref('')
const email = ref('')
const password = ref('')
const password_confirmation = ref('')
const error = ref(null)

const register = async () => {
  error.value = null
  try {
    const response = await axios.post('/register', {
      name: name.value,
      email: email.value,
      password: password.value,
      password_confirmation: password_confirmation.value,
    })

    const token = response.data.token
    if (token) {
      auth.setToken(token)              // ✅ Save to Pinia store
      await auth.fetchUser()            // ✅ Fetch user and set in store
      router.push('/dashboard')         // ✅ Now redirect will work immediately
    } else {
      error.value = 'Registration succeeded but no token returned.'
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Registration failed'
  }
}

</script>
