<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue'
import GuestLayout from '@/layouts/GuestLayout.vue'
import axios from '@/axios'
import Notification from '@/components/Notification.vue'

const router = useRouter()
const auth = useAuthStore()
const loading = ref(true)
const toast = ref(null)

onMounted(async () => {
  window.$toast = toast.value // Make toast available globally

  if (auth.token) {
    try {
      await auth.fetchUser()
    } catch (e) {
      console.error('❌ Failed to fetch user:', e)
    }
  }

  loading.value = false
})

const logout = async () => {
  try {
    await axios.post('/logout', {}, {
      headers: { Authorization: `Bearer ${auth.token}` }
    })
    window.$toast?.showToast('Logged out successfully')
  } catch (error) {
    console.warn('Logout failed:', error)
    window.$toast?.showToast('Logout failed', 4000)
  } finally {
    auth.logout()
    router.push('/')
  }
}
</script>


<template>
  <div v-if="loading" class="p-6 text-gray-700">Loading...</div>

  <component
    v-else
    :is="auth.user ? AuthenticatedLayout : GuestLayout"
    :user="auth.user"
    :logout="logout"
  >
    <RouterView />
  </component>

  <!-- Global Toast -->
  <Notification ref="notificationRef" />
</template>

<!-- Leave this empty to avoid local scoping issues -->
<style scoped></style>

