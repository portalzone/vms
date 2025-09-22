<template>
  <div class="max-w-2xl mx-auto p-6 bg-white rounded-2xl shadow-md">
    <h2 class="text-2xl font-semibold text-gray-800 mb-6">
      {{ isEdit ? 'Edit Maintenance Record' : 'New Maintenance Record' }}
    </h2>

    <form @submit.prevent="handleSubmit" class="space-y-5">
      <!-- Vehicle -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle</label>
        <select
          v-model="form.vehicle_id"
          :disabled="isEdit"
          required
          class="w-full border rounded-lg px-4 py-2"
        >
          <option value="">Select Vehicle</option>
         <option v-for="v in vehicles" :key="v.id" :value="v.id">
  {{ v.plate_number }} - ({{ v.manufacturer }} {{ v.model }})
</option>




        </select>
      </div>

      <!-- Description -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
        <textarea
          v-model="form.description"
          required
          rows="3"
          class="w-full border rounded-lg px-4 py-2"
          placeholder="Describe the issue or task"
        ></textarea>
      </div>

      <!-- Status -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
        <select v-model="form.status" required class="w-full border rounded-lg px-4 py-2">
          <option value="Pending">Pending</option>
          <option value="in_progress">In Progress</option>
          <option
             v-if="hasRole(['admin', 'manager'])"
            value="Completed"
          >
            Completed
          </option>
        </select>
      </div>

      <!-- Cost -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Cost (₦)</label>
        <input
          v-model.number="form.cost"
          type="number"
          min="0"
          step="0.01"
          required
          placeholder="Enter cost"
          class="w-full border rounded-lg px-4 py-2"
        />
      </div>

      <!-- Date -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
        <input
          v-model="form.date"
          type="date"
          required
          class="w-full border rounded-lg px-4 py-2"
        />
      </div>

      <!-- Submit -->
      <div class="pt-4">
        <button
          type="submit"
          class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg"
        >
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


const route = useRoute()
const router = useRouter()
const isEdit = route.name === 'MaintenanceEdit'
const id = route.params.id

const form = ref({
  vehicle_id: '',
  description: '',
  status: 'Pending',
  cost: '',
  date: new Date().toISOString().slice(0, 10),
})

const vehicles = ref([])

onMounted(async () => {
  try {
    const vRes = await axios.get('/vehicles')
    // vehicles.value = vRes.data
    // vehicles.value = vRes.data.filter(v => v && v.id)
    vehicles.value = (vRes.data.data || []).filter(v => v && v.id)
    




    if (isEdit && id) {
      const res = await axios.get(`/maintenances/${id}`)
      const rawDate = res.data.date

      form.value = {
        vehicle_id: res.data.vehicle_id,
        description: res.data.description,
        status: res.data.status || 'Pending',
        cost: res.data.cost ?? 0,
        date: rawDate ? new Date(rawDate).toISOString().slice(0, 10) : '',
      }
    }
  } catch (error) {
    console.error('Error loading data:', error)
  }
})

const handleSubmit = async () => {
  if (form.value.status === 'Completed' && (!form.value.cost || form.value.cost <= 0)) {
    alert('Please enter a valid cost for completed maintenance.')
    return
  }

  try {
    if (isEdit) {
      await axios.put(`/maintenances/${id}`, form.value)
    } else {
      await axios.post('/maintenances', form.value)
    }
    router.push('/maintenance')
  } catch (err) {
    if (err.response?.status === 422) {
      console.error('Validation errors:', err.response.data.errors)
      alert('Please fix the validation errors and try again.')
    } else {
      console.error(err)
      alert('Something went wrong. Please try again.')
    }
  }
}
</script>
