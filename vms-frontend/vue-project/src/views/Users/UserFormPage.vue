<!-- src/views/Users/UserFormPage.vue -->
<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from '@/axios'
import UserForm from './UserForm.vue'
import { useAuthStore } from '@/stores/auth'
const auth = useAuthStore()

function hasRole(allowedRoles) {
  return allowedRoles.includes(auth.user?.role)
}

const route = useRoute()
const router = useRouter()

const user = ref(null)
const roles = ref([])

const loadRoles = async () => {
  const res = await axios.get('/roles')
  roles.value = res.data
}

const loadUser = async () => {
  const userId = route.params.id
  if (userId && userId !== 'new') {
    try {
      const res = await axios.get(`/users/${userId}`)
      user.value = res.data
    } catch (e) {
      console.error('Failed to load user:', e)
    }
  }
}

const handleSaved = () => {
  router.push('/users')
}

onMounted(() => {
  loadRoles()
  loadUser()
})
</script>

<template>
  <div class="auth-card">
    <h2 class="mb-4 text-2xl font-bold">
      {{ route.params.id === 'new' ? 'Create User' : 'Edit User' }}
    </h2>
    <UserForm :user="user" :roles="roles" @saved="handleSaved" />
  </div>
</template>
