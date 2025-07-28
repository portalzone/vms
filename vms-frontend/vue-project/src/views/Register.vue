<template>
  <div class="register max-w-md mx-auto mt-10">
    <h2 class="text-2xl font-bold mb-4">Register</h2>
    <form @submit.prevent="register">
<div class="mb-4">
  <label>Name:</label>
  <input v-model="name" type="text" class="border p-2 w-full" @input="error.name = null" required />
  <p v-if="error.name" class="error-text">{{ error.name[0] }}</p>
</div>

<div class="mb-4">
  <label>Email:</label>
  <input v-model="email" type="email" class="border p-2 w-full" @input="error.email = null" required />
  <p v-if="error.email" class="error-text">{{ error.email[0] }}</p>
</div>

<div class="mb-4">
  <label>Password:</label>
  <input v-model="password" type="password" class="border p-2 w-full" @input="error.password = null" required />
  <p v-if="error.password" class="error-text">{{ error.password[0] }}</p>
</div>

<div class="mb-4">
  <label>Confirm Password:</label>
  <input v-model="password_confirmation" type="password" class="border p-2 w-full" @input="error.password_confirmation = null" required />
  <p v-if="error.password_confirmation" class="error-text">{{ error.password_confirmation[0] }}</p>
</div>

      <br>
      <button type="submit" class="bg-green-600 text-white px-4 py-2">Register</button>
    </form>
<p v-if="error.general" class="error-text">{{ error.general }}</p>
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
const error = ref({})


const register = async () => {
  error.value = {} // ✅ don't set to null
  try {
    const response = await axios.post('/register', {
      name: name.value,
      email: email.value,
      password: password.value,
      password_confirmation: password_confirmation.value,
    })

    const token = response.data.token
    if (token) {
      auth.setToken(token)
      await auth.fetchUser()
      router.push('/dashboard')
    } else {
      error.value = { general: 'Registration succeeded but no token returned.' }
    }
  } catch (err) {
    if (err.response && err.response.data.errors) {
      error.value = err.response.data.errors
    } else {
      error.value = { general: err.response?.data?.message || 'Registration failed' }
    }
  }
}

</script>
