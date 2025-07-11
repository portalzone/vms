<template>
  <div class="min-h-screen flex flex-col items-center justify-center bg-gray-100 p-6">
    <div class="bg-white shadow-xl rounded-2xl p-10 max-w-6xl w-full text-center">

      <!-- Welcome Title -->
      <h1>Welcome to the Vehicle Management System</h1>

      <!-- Logged in Message + Button -->
      <div v-if="auth.user" class="mb-4">
        <p class="text-green-600 font-medium mb-2">
          You're logged in as {{ auth.user.name }}.
        </p>
        <RouterLink
          to="/dashboard"
          class="inline-block bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg"
        >
          Go to Dashboard
        </RouterLink>
      </div>

      <!-- Features Overview -->
      <TheWelcome class="mb-6" />
    </div>
  </div>
</template>

<script setup>
import { useAuthStore } from '@/stores/auth'
import TheWelcome from '@/components/TheWelcome.vue'

const auth = useAuthStore()
</script>
