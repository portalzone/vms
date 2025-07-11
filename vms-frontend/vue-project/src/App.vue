<script setup>
import { RouterView, useRouter } from 'vue-router'
import { onMounted, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue'
import GuestLayout from '@/layouts/GuestLayout.vue'
import axios from '@/axios'

const router = useRouter()
const auth = useAuthStore()
const loading = ref(true)

onMounted(async () => {
  if (auth.token) {
    await auth.fetchUser()
  }
  loading.value = false
})

const logout = async () => {
  try {
    await axios.post('/logout', {}, {
      headers: { Authorization: `Bearer ${auth.token}` }
    })
  } catch (error) {
    console.warn('Logout failed:', error)
  } finally {
    auth.logout()
    router.push('/')
  }
}
</script>

<template>
  <div v-if="loading" class="p-6">Loading...</div>
  <component
    v-else
    :is="auth.user ? AuthenticatedLayout : GuestLayout"
    :user="auth.user"
    :logout="logout"
  >
    <RouterView />
  </component>
</template>

<style scoped>
/* nothing here */
</style>

<style>
html, body, #app {
  margin: 0;
  padding: 0;
  width: 100%;
  height: 100%;
  box-sizing: border-box;
  overflow-x: hidden;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Typography */
h1, h2, h3, h4, h5, h6 {
  color: #1f2937;
  font-weight: 600;
  margin-bottom: 0.5rem;
}
h1 { font-size: 1.875rem; font-weight: 800; }
h2 { font-size: 1.5rem; }
h3 { font-size: 1.25rem; }
h4 { font-size: 1.125rem; }
h5 { font-size: 1rem; }
h6 { font-size: 0.875rem; }

label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
  display: block;
  margin-bottom: 0.25rem;
}

p {
  color: #374151;
  font-size: 1rem;
  line-height: 1.6;
  margin-bottom: 1rem;
}

table {
  width: 100%;
  border-collapse: collapse;
}
th {
  text-align: left;
  font-size: 0.875rem;
  font-weight: 600;
  color: #374151;
  padding: 0.5rem 1rem;
  background-color: #f3f4f6;
}
td {
  font-size: 0.875rem;
  color: #1f2937;
  padding: 0.5rem 1rem;
  border-top: 1px solid #e5e7eb;
}
</style>
