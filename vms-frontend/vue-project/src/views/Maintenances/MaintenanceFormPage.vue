<template>
  <div class="p-6 bg-white rounded shadow">
    <h2 class="text-xl font-bold mb-4">
      {{ isEdit ? 'Edit Maintenance Record' : 'New Maintenance Record' }}
    </h2>

    <form @submit.prevent="handleSubmit" class="space-y-4">
      <!-- Vehicle Selection -->
      <div>
        <label>Vehicle</label>
        <select v-model="form.vehicle_id" required class="w-full border px-3 py-2 rounded">
          <option value="">Select Vehicle</option>
          <option v-for="v in vehicles" :key="v.id" :value="v.id">
            {{ v.plate_number }} - {{ v.manufacturer }}
          </option>
        </select>
      </div>

      <!-- Description -->
      <div>
        <label>Description</label>
        <textarea v-model="form.description" required class="w-full border px-3 py-2 rounded"></textarea>
      </div>

      <!-- Status -->
      <div>
        <label>Status</label>
        <select v-model="form.status" required class="w-full border px-3 py-2 rounded">
          <option value="Pending">Pending</option>
          <option value="in_progress">In Progress</option>
          <option value="Completed">Completed</option>
        </select>
      </div>

      <!-- Cost -->
      <div>
        <label>Cost (₦)</label>
        <input
          v-model.number="form.cost"
          type="number"
          min="0"
          placeholder="Enter cost"
          required
          class="w-full border px-3 py-2 rounded"
        />
      </div>

      <!-- Date -->
      <div>
        <label>Date</label>
        <input
          v-model="form.date"
          type="date"
          required
          class="w-full border px-3 py-2 rounded"
        />
      </div>

<button class="btn btn-primary">
  {{ isEdit ? 'Update' : 'Create' }}
</button>

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
    form.value = {
      vehicle_id: res.data.vehicle_id,
      description: res.data.description,
      status: res.data.status || 'Pending',
      cost: res.data.cost ?? 0,
      date: res.data.date ?? '',
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
