<template>
  <div class="max-w-2xl mx-auto bg-white p-6 rounded shadow">
    <h2 class="text-xl font-semibold mb-4">My Profile</h2>

    <form @submit.prevent="updateProfile" class="space-y-4" enctype="multipart/form-data">
      <!-- Name -->
      <div>
        <label class="block text-sm font-medium mb-1">Name</label>
        <input
          v-model="form.name"
          type="text"
          class="w-full border rounded px-4 py-2"
        />
        <p v-if="errors.name" class="text-red-500 text-sm">{{ errors.name[0] }}</p>
      </div>

      <!-- Email -->
      <div>
        <label class="block text-sm font-medium mb-1">Email</label>
        <input
          v-model="form.email"
          type="email"
          class="w-full border rounded px-4 py-2"
        />
        <p v-if="errors.email" class="text-red-500 text-sm">{{ errors.email[0] }}</p>
      </div>

      <!-- Phone -->
      <div>
        <label class="block text-sm font-medium mb-1">Phone</label>
        <input
          v-model="form.phone"
          type="text"
          class="w-full border rounded px-4 py-2"
        />
        <p v-if="errors.phone" class="text-red-500 text-sm">{{ errors.phone[0] }}</p>
      </div>

      <!-- Avatar -->
      <div>
        <label class="block text-sm font-medium mb-1">Avatar</label>
        <input
          type="file"
          @change="handleAvatarChange"
          class="w-full"
        />
        <p v-if="errors.avatar" class="text-red-500 text-sm">{{ errors.avatar[0] }}</p>

        <div v-if="previewUrl || avatarUrl" class="mt-2 w-16 h-16">
          <img :src="previewUrl || avatarUrl" alt="Avatar" class="h-24 w-24 rounded-full object-cover" />
        </div>
      </div>

      <!-- Password -->
      <div>
        <label class="block text-sm font-medium mb-1">New Password</label>
        <input
          v-model="form.password"
          type="password"
          class="w-full border rounded px-4 py-2"
        />
        <p v-if="errors.password" class="text-red-500 text-sm">{{ errors.password[0] }}</p>
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">Confirm Password</label>
        <input
          v-model="form.password_confirmation"
          type="password"
          class="w-full border rounded px-4 py-2"
        />
      </div>

      <!-- Submit -->
      <div class="pt-4">
        <button
          type="submit"
          class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded"
          :disabled="loading"
        >
          <span v-if="loading">Updating...</span>
          <span v-else>Update Profile</span>
        </button>

        <p v-if="successMessage" class="text-green-600 text-sm mt-2">{{ successMessage }}</p>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from '@/axios' // ✅ Replace with your axios instance

const form = ref({
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
})

const avatarFile = ref(null)
const avatarUrl = ref(null)
const previewUrl = ref(null)
const errors = ref({})
const successMessage = ref('')
const loading = ref(false)

const fetchProfile = async () => {
  try {
    const { data } = await axios.get('/profile')
    form.value.name = data.name
    form.value.email = data.email
    form.value.phone = data.phone
    avatarUrl.value = data.avatar_url
  } catch (error) {
    console.error('Failed to fetch profile:', error)
  }
}

const handleAvatarChange = (e) => {
  const file = e.target.files[0]
  if (file) {
    avatarFile.value = file
    previewUrl.value = URL.createObjectURL(file)
  }
}

const updateProfile = async () => {
  loading.value = true
  successMessage.value = ''
  errors.value = {}

  const payload = new FormData()
  payload.append('name', form.value.name)
  payload.append('email', form.value.email)
  if (form.value.phone) payload.append('phone', form.value.phone)
  if (form.value.password) {
    payload.append('password', form.value.password)
    payload.append('password_confirmation', form.value.password_confirmation)
  }
  if (avatarFile.value) {
    payload.append('avatar', avatarFile.value)
  }

  try {
    const response = await axios.post('/profile?_method=PUT', payload, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    successMessage.value = response.data.message
    avatarUrl.value = response.data.avatar_url
    form.value.password = ''
    form.value.password_confirmation = ''
    previewUrl.value = null
  } catch (err) {
    if (err.response && err.response.data && err.response.data.errors) {
      errors.value = err.response.data.errors
    }
  } finally {
    loading.value = false
  }
}

onMounted(fetchProfile)
</script>

<style scoped>
input[type="file"] {
  padding: 0.5rem 0;
}
</style>
