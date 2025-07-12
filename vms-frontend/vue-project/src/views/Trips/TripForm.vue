<template>
  <div class="max-w-xl mx-auto p-4 bg-white rounded shadow">
    <h2 class="text-2xl font-bold mb-4">
      {{ trip.id ? 'Edit Trip' : 'New Trip' }}
    </h2>

    <form @submit.prevent="handleSubmit" class="space-y-4">
      <div>
        <label class="block font-medium mb-1">Start Location</label>
        <input v-model="trip.start_location" type="text" class="form-input w-full" required />
      </div>

      <div>
        <label class="block font-medium mb-1">End Location</label>
        <input v-model="trip.end_location" type="text" class="form-input w-full" required />
      </div>

      <div>
        <label class="block font-medium mb-1">Start Time</label>
        <input v-model="trip.start_time" type="datetime-local" class="form-input w-full" required />
      </div>

      <div>
        <label class="block font-medium mb-1">End Time</label>
        <input v-model="trip.end_time" type="datetime-local" class="form-input w-full" />
      </div>

      <div>
        <label class="block font-medium mb-1">Vehicle</label>
        <select v-model="trip.vehicle_id" class="form-select w-full" required>
          <option disabled value="">-- Select Vehicle --</option>
          <option v-for="vehicle in vehicles" :key="vehicle.id" :value="vehicle.id">
            {{ vehicle.plate_number }} ({{ vehicle.manufacturer }} {{ vehicle.model }})
          </option>
        </select>
      </div>

      <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
        {{ trip.id ? 'Update Trip' : 'Create Trip' }}
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

const trip = ref({
  start_location: '',
  end_location: '',
  start_time: '',
  end_time: '',
  vehicle_id: '',
  user_id: '',
})

const vehicles = ref([])

const fetchVehicles = async () => {
  try {
    const res = await axios.get('/vehicles')
    vehicles.value = res.data
  } catch (err) {
    console.error('❌ Error fetching vehicles:', err)
  }
}

const fetchDriverInfo = async () => {
  try {
    const res = await axios.get('/drivers/me')
    const driver = res.data
    trip.value.user_id = driver.user_id
    trip.value.vehicle_id = driver.vehicle_id
  } catch (err) {
    console.warn('🔒 Not a driver or failed to load driver info:', err)
  }
}

const fetchTrip = async (id) => {
  try {
    const res = await axios.get(`/trips/${id}`)
    const data = res.data
    trip.value = {
      id: data.id,
      start_location: data.start_location,
      end_location: data.end_location,
      start_time: data.start_time?.slice(0, 16),
      end_time: data.end_time?.slice(0, 16),
      vehicle_id: data.vehicle_id,
      user_id: data.driver?.user_id || '',
    }
  } catch (err) {
    console.error('❌ Failed to load trip:', err)
  }
}

const handleSubmit = async () => {
  try {
    const payload = { ...trip.value }

    if (trip.value.id) {
      await axios.put(`/trips/${trip.value.id}`, payload)
      alert('Trip updated!')
    } else {
      await axios.post('/trips', payload)
      alert('Trip created!')
    }

    router.push('/trips')
  } catch (err) {
    console.error('❌ Failed to submit trip:', err)
    alert('Error saving trip.')
  }
}

onMounted(async () => {
  await fetchVehicles()

  if (route.params.id) {
    await fetchTrip(route.params.id)
  } else {
    await fetchDriverInfo()
  }
})
</script>

<style scoped>
.form-input,
.form-select {
  border: 1px solid #ccc;
  padding: 0.5rem;
  border-radius: 4px;
}
</style>
