<!-- src/views/Users/UsersPage.vue -->
<script setup>
import { ref, onMounted } from 'vue'
import axios from '@/axios'
import { useRouter } from 'vue-router'

const router = useRouter()
const users = ref([])

const loadUsers = async () => {
  try {
    const res = await axios.get('/users')
    users.value = res.data
  } catch (err) {
    console.error('❌ Failed to load users:', err)
  }
}

const goToCreate = () => {
  router.push('/users/new')
}

const goToEdit = (user) => {
  router.push(`/users/${user.id}/edit`)
}

const deleteUser = async (id) => {
  if (confirm('Are you sure you want to delete this user?')) {
    await axios.delete(`/users/${id}`)
    loadUsers()
  }
}

onMounted(() => {
  loadUsers()
})
</script>

<template>
  <div>
    <h2 class="text-2xl font-bold mb-4">User Management</h2>

    <button class="mb-4 bg-blue-600 text-white px-4 py-2 rounded" @click="goToCreate">
      ➕ Add User
    </button>

    <table class="w-full border text-sm">
      <thead class="bg-gray-100">
        <tr>
          <th class="px-2 py-1">#</th>
          <th class="px-2 py-1">Name</th>
          <th class="px-2 py-1">Email</th>
          <th class="px-2 py-1">Role</th>
          <th class="px-2 py-1">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(user, index) in users" :key="user.id" class="border-t">
          <td class="px-2 py-1">{{ index + 1 }}</td>
          <td class="px-2 py-1">{{ user.name }}</td>
          <td class="px-2 py-1">{{ user.email }}</td>
          <td class="px-2 py-1 capitalize">{{ user.roles?.[0]?.name || 'N/A' }}</td>
          <td class="px-2 py-1 space-x-2">
            <button class="text-blue-600" @click="goToEdit(user)">✏️ Edit</button>
            <button class="text-red-600" @click="deleteUser(user.id)">🗑️ Delete</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
