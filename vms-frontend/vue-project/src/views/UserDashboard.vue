<template>
  <div class="p-6 max-w-3xl mx-auto">
    <div class="bg-white rounded-xl shadow p-6 text-center">
      <h2 class="text-2xl font-bold text-gray-800 mb-2">Welcome, {{ user.name }}</h2>
      <p class="text-gray-600 mb-4">You currently do not have a role assigned to your account.</p>

      <div class="bg-yellow-100 text-yellow-800 p-4 rounded mb-4">
        <p class="font-medium">Note:</p>
        <p>An administrator needs to assign you a role before you can access the full system.</p>
      </div>

      <router-link
        to="/profile"
        class="inline-block mt-4 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded"
      >
        View Your Profile
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from '@/axios'

const user = ref({ name: '' })

onMounted(async () => {
  try {
    const res = await axios.get('/user')
    user.value = res.data
  } catch (error) {
    console.error('Failed to load user data:', error)
  }
})
</script>
