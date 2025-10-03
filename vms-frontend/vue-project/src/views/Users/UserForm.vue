<!-- src/components/UserForm.vue -->
<script setup>
import { ref, watch } from 'vue'
import axios from '@/axios'

const props = defineProps({
  user: Object,
  roles: Array,
})

const emit = defineEmits(['saved'])

const form = ref({
  name: '',
  email: '',
  password: '',
  role: '',
})

const errors = ref({})

// Prefill when editing
watch(
  () => props.user,
  (user) => {
    form.value = {
      name: user?.name || '',
      email: user?.email || '',
      password: '',
      role: user?.roles?.[0]?.name || '',
    }
  },
  { immediate: true },
)

const capitalize = (str) => str?.charAt(0).toUpperCase() + str.slice(1)

const submit = async () => {
  errors.value = {}
  try {
    if (props.user?.id) {
      await axios.put(`/users/${props.user.id}`, form.value)
    } else {
      await axios.post('/users', form.value)
    }
    emit('saved')
  } catch (err) {
    if (err.response?.status === 422) {
      errors.value = err.response.data.errors || {}
    } else {
      console.error('❌ Error saving user:', err)
    }
  }
}
</script>

<template>
  <form @submit.prevent="submit">
    <div class="mb-3">
      <label>Name</label>
      <input v-model="form.name" type="text" class="w-full p-2 border rounded" required />
      <p v-if="errors.name" class="text-xs text-red-600">{{ errors.name[0] }}</p>
    </div>

    <div class="mb-3">
      <label>Email</label>
      <input v-model="form.email" type="email" class="w-full p-2 border rounded" required />
      <p v-if="errors.email" class="text-xs text-red-600">{{ errors.email[0] }}</p>
    </div>

    <div class="mb-3">
      <label>Password</label>
      <input
        v-model="form.password"
        type="password"
        class="w-full p-2 border rounded"
        :required="!props.user?.id"
      />
      <p v-if="errors.password" class="text-xs text-red-600">{{ errors.password[0] }}</p>
    </div>

    <div class="mb-3">
      <label>Role</label>
      <select v-model="form.role" class="w-full p-2 border rounded" required>
        <option disabled value="">Select Role</option>
        <option v-for="role in roles" :key="role.id" :value="role.name">
          {{ capitalize(role.name) }}
        </option>
      </select>
      <p v-if="errors.role" class="text-xs text-red-600">{{ errors.role[0] }}</p>
    </div>

    <div class="flex justify-end">
      <button type="submit" class="px-4 py-2 text-white bg-green-600 rounded">
        {{ props.user?.id ? 'Update' : 'Create' }}
      </button>
    </div>
  </form>
</template>
