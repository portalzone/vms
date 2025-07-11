<!-- src/views/Users/UserFormPage.vue -->
<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from '@/axios'
import UserForm from './UserForm.vue'

const route = useRoute()
const router = useRouter()

const user = ref(null)
const roles = ref([])

const loadRoles = async () => {
  const res = await axios.get('/roles')
  roles.value = res.data
}

const loadUser = async () => {
  if (route.params.id !== 'new') {
    const res = await axios.get(`/users/${route.params.id}`)
    user.value = res.data
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
  <div>
    <h2 class="text-2xl font-bold mb-4">
      {{ route.params.id === 'new' ? 'Create User' : 'Edit User' }}
    </h2>
    <UserForm :user="user" :roles="roles" @saved="handleSaved" />
  </div>
</template>
