<template>
  <div class="bg-white p-6 rounded shadow space-y-4">
    <h2 class="text-xl font-bold mb-4">{{ isEdit ? 'Edit Maintenance' : 'New Maintenance' }}</h2>

    <form @submit.prevent="submit">
      <div>
        <label class="block mb-1">Vehicle</label>
        <select v-model="form.vehicle_id" required class="border p-2 w-full rounded">
          <option value="">Select Vehicle</option>
          <option v-for="v in vehicles" :key="v.id" :value="v.id">
            {{ v.plate_number }} - {{ v.manufacturer }}
          </option>
        </select>
      </div>

      <div>
        <label class="block mb-1">Description</label>
        <input v-model="form.description" required class="border p-2 w-full rounded" />
      </div>

      <div>
        <label class="block mb-1">Status</label>
        <select v-model="form.status" required class="border p-2 w-full rounded">
          <option value="">Select Status</option>
          <option value="Pending">Pending</option>
          <option value="Completed">Completed</option>
        </select>
      </div>

      <button class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
        {{ isEdit ? 'Update' : 'Save' }}
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
const isEdit = !!route.params.id
const form = ref({ vehicle_id: '', description: '', status: '' })
const vehicles = ref([])

const fetchVehicles = async () => {
  const res = await axios.get('/vehicles')
  vehicles.value = res.data
}

const fetchMaintenance = async () => {
  const res = await axios.get(`/maintenances/${route.params.id}`)
  form.value = {
    vehicle_id: res.data.vehicle_id,
    description: res.data.description,
    status: res.data.status
  }
}

const submit = async () => {
  if (isEdit) {
    await axios.put(`/maintenances/${route.params.id}`, form.value)
  } else {
    await axios.post('/maintenances', form.value)
  }
  router.push('/maintenances')
}

onMounted(() => {
  fetchVehicles()
  if (isEdit) fetchMaintenance()
})
</script>
