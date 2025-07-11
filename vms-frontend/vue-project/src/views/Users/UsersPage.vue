<script setup>
import { ref, onMounted } from 'vue'
import axios from '@/axios'
import UserForm from './UserForm.vue'

const users = ref([])
const roles = ref([])
const showForm = ref(false)
const selectedUser = ref(null)

const loadUsers = async () => {
  const res = await axios.get('/users')
  users.value = res.data
}

const loadRoles = async () => {
  try {
    const res = await axios.get('/roles')
    roles.value = res.data
  } catch (e) {
    console.error('❌ Failed to load roles:', e)
  }
}

const editUser = (user) => {
  selectedUser.value = { ...user }
  showForm.value = true
}

const deleteUser = async (id) => {
  if (confirm('Are you sure?')) {
    await axios.delete(`/users/${id}`)
    loadUsers()
  }
}

const closeForm = () => {
  showForm.value = false
  selectedUser.value = null
}

onMounted(() => {
  loadUsers()
  loadRoles()
})
</script>

<template>
  <div>
    <h2 class="text-2xl font-bold mb-4">User Management</h2>

    <button class="mb-4 bg-blue-600 text-white px-4 py-2 rounded" @click="showForm = true">➕ Add User</button>

    <UserForm
      v-if="showForm"
      :key="selectedUser?.id || 'new'"
      :user="selectedUser"
      :roles="roles"
      @close="closeForm"
      @saved="loadUsers"
    />

    <table class="w-full border text-sm mt-4">
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
            <button class="text-blue-600" @click="editUser(user)">✏️ Edit</button>
            <button class="text-red-600" @click="deleteUser(user.id)">🗑️ Delete</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
