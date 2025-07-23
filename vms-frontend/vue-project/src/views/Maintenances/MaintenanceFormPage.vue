<template>
  <div class="max-w-2xl mx-auto p-6 bg-white rounded-2xl shadow-md">
    <h2 class="text-2xl font-semibold text-gray-800 mb-6">
      {{ isEdit ? 'Edit Maintenance Record' : 'New Maintenance Record' }}
    </h2>

    <form @submit.prevent="handleSubmit" class="space-y-5">
      <!-- Vehicle Selection -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle</label>
        <select v-model="form.vehicle_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option value="">Select Vehicle</option>
          <option v-for="v in vehicles" :key="v.id" :value="v.id">
            {{ v.plate_number }} - ({{ v.manufacturer }} {{ v.model }})
          </option>
        </select>
      </div>

      <!-- Description -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
        <textarea v-model="form.description" required rows="3"
          class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
      </div>

      <!-- Status -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
        <select v-model="form.status" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option value="Pending">Pending</option>
          <option value="in_progress">In Progress</option>
          <option value="Completed">Completed</option>
        </select>
      </div>

      <!-- Cost -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Cost (₦)</label>
        <input
          v-model.number="form.cost"
          type="number"
          min="0"
          placeholder="Enter cost"
          required
          class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </div>

      <!-- Date -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
        <input
          v-model="form.date"
          type="date"
          required
          class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </div>

      <!-- Submit Button -->
      <div class="pt-4">
        <button
          type="submit"
          class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg shadow transition duration-200"
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

const route = useRoute()
const router = useRouter()
const isEdit = route.name === 'MaintenanceEdit'
const id = route.params.id

const form = ref({
  vehicle_id: '',
  description: '',
  status: 'Pending',
  cost: 0,
  date: '',
})

const vehicles = ref([])

onMounted(async () => {
  const vRes = await axios.get('/vehicles')
  vehicles.value = vRes.data

  if (isEdit && id) {
    const res = await axios.get(`/maintenances/${id}`)
    const rawDate = res.data.date

    form.value = {
      vehicle_id: res.data.vehicle_id,
      description: res.data.description,
      status: res.data.status || 'Pending',
      cost: res.data.cost ?? 0,
      // Format date to yyyy-MM-dd
      date: rawDate ? new Date(rawDate).toISOString().slice(0, 10) : '',
    }
  }
})


const handleSubmit = async () => {
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
      alert('Something went wrong.')
    }
  }
}
</script>
