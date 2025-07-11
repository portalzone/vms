<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from '@/axios'

const props = defineProps({
  user: Object,
  roles: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['close', 'saved'])

const form = ref({
  name: '',
  email: '',
  password: '',
  role: ''
})

// Prefill when editing
watch(() => props.user, (user) => {
  if (user) {
    form.value = {
      name: user.name || '',
      email: user.email || '',
      password: '',
      role: user.roles?.[0]?.name || ''
    }
  }
}, { immediate: true })

function capitalize(str) {
  return str?.charAt(0).toUpperCase() + str.slice(1)
}

const submit = async () => {
  try {
    if (props.user?.id) {
      await axios.put(`/users/${props.user.id}`, form.value)
    } else {
      await axios.post('/users', form.value)
    }
    emit('saved')
    emit('close')
  } catch (err) {
    console.error('❌ Error saving user:', err)
  }
}
</script>

<template>
  <div class="bg-white border rounded shadow p-4 mb-6">
    <h3 class="text-lg font-semibold mb-3">
      {{ user?.id ? 'Edit User' : 'Add New User' }}
    </h3>

    <form @submit.prevent="submit">
      <div class="mb-3">
        <label>Name:</label>
        <input v-model="form.name" type="text" class="w-full border p-2 rounded" required />
      </div>

      <div class="mb-3">
        <label>Email:</label>
        <input v-model="form.email" type="email" class="w-full border p-2 rounded" required />
      </div>

      <div class="mb-3">
        <label>Password:</label>
        <input v-model="form.password" type="password" class="w-full border p-2 rounded" :required="!user?.id" />
      </div>

      <div class="mb-3">
        <label>Role</label>
        <select v-model="form.role" class="w-full border p-2 rounded" required>
          <option value="" disabled>Select Role</option>
          <option v-for="role in roles" :key="role.id" :value="role.name">
            {{ capitalize(role.name) }}
          </option>
        </select>
      </div>

      <div class="flex justify-between items-center">
        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">
          {{ user?.id ? 'Update' : 'Create' }}
        </button>
        <button type="button" class="text-gray-500" @click="$emit('close')">Cancel</button>
      </div>
    </form>
  </div>
</template>
