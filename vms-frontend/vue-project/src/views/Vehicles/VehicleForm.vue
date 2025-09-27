<template>
  <div class="max-w-2xl p-6 mx-auto bg-white rounded shadow">
    <h2 class="mb-4 text-xl font-bold">
      {{ isEdit ? 'Edit Vehicle' : 'Add Vehicle' }}
    </h2>

    <form @submit.prevent="handleSubmit" class="space-y-4">
      <!-- Manufacturer -->
      <div>
        <label class="block mb-1">Manufacturer</label>
        <input v-model="form.manufacturer" type="text" class="w-full p-2 border rounded" required />
      </div>

      <!-- Model -->
      <div>
        <label class="block mb-1">Model</label>
        <input v-model="form.model" type="text" class="w-full p-2 border rounded" required />
      </div>

      <!-- Model -->
      <div>
        <label class="block mb-1">Year</label>
        <input v-model="form.year" type="text" class="w-full p-2 border rounded" required />
      </div>

      <!-- Plate Number -->
      <div>
        <label class="block mb-1">Plate Number</label>
        <input v-model="form.plate_number" type="text" class="w-full p-2 border rounded" required />
      </div>

      <!-- Ownership Type -->
      <div>
        <label class="block mb-1">Ownership Type</label>
        <select v-model="form.ownership_type" class="w-full p-2 border rounded" required>
          <option value="">Select Ownership</option>
          <option v-if="hasRole(['admin', 'manager'])" value="organization">Organization</option>
          <option value="individual">Individual</option>
        </select>
      </div>

      <!-- If Individual -->
      <div v-if="form.ownership_type === 'individual'">
        <label class="block mb-1">Individual Type</label>
        <select v-model="form.individual_type" class="w-full p-2 border rounded">
          <option value="">Select Individual Type</option>
          <option v-if="hasRole(['admin', 'manager'])" value="staff">Staff</option>
          <option value="visitor">Visitor</option>
          <option v-if="hasRole(['admin', 'manager'])" value="vehicle_owner">Vehicle Owner</option>
        </select>
      </div>

      <!-- Vehicle Owner Dropdown -->
      <div v-if="form.individual_type === 'vehicle_owner'">
        <label class="block mb-1">Vehicle Owner</label>
        <select v-model="form.owner_id" class="w-full p-2 border rounded">
          <option value="">Select Vehicle Owner</option>
          <option v-for="owner in vehicleOwners" :key="owner.id" :value="owner.id">
            {{ owner.name }}
          </option>
        </select>
      </div>

      <!-- Submit Button -->
      <div>
        <button type="submit" class="px-4 py-2 text-white bg-blue-600 rounded">
          {{ isEdit ? 'Update' : 'Create' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from '@/axios'

import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
function hasRole(allowedRoles) {
  return allowedRoles.includes(auth.user?.role)
}

const router = useRouter()
const route = useRoute()

// Form state
const form = ref({
  manufacturer: '',
  model: '',
  plate_number: '',
  ownership_type: '',
  year: '',
  individual_type: '',
  owner_id: null,
})

const vehicleOwners = ref([])
const isEdit = ref(false)

const fetchVehicleOwners = async () => {
  try {
    const res = await axios.get('/vehicle-owners')
    // handle if API returns paginated data
    vehicleOwners.value = res.data.data || res.data
  } catch (err) {
    console.error('Error fetching vehicle owners:', err)
  }
}

onMounted(async () => {
  await fetchVehicleOwners()

  if (route.params.id) {
    isEdit.value = true
    try {
      const res = await axios.get(`/vehicles/${route.params.id}`)
      form.value = res.data
    } catch (err) {
      console.error('Error loading vehicle:', err)
    }
  }
})

const handleSubmit = async () => {
  try {
    // Prepare payload
    const payload = { ...form.value }

    if (payload.ownership_type === 'organization') {
      payload.individual_type = null
      payload.vehicle_owner_id = null
    } else if (payload.individual_type !== 'vehicle_owner') {
      payload.vehicle_owner_id = null
    }

    if (isEdit.value) {
      await axios.put(`/vehicles/${route.params.id}`, payload)
    } else {
      await axios.post('/vehicles', payload)
    }

    router.push('/vehicles')
  } catch (err) {
    console.error('Error saving vehicle:', err.response?.data || err.message)
  }
}
</script>
