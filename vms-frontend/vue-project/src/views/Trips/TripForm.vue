<template>
  <div class="max-w-2xl mx-auto bg-white p-6 rounded shadow">
    <h2 class="text-xl font-semibold mb-4">{{ isEdit ? 'Edit Trip' : 'Create New Trip' }}</h2>

    <form @submit.prevent="submit">
      <!-- Vehicle Select -->
      <div class="mb-4">
        <label class="block font-medium mb-1">Vehicle</label>
        <select v-model="trip.vehicle_id" class="w-full border rounded px-3 py-2">
          <option disabled value="">Select a vehicle</option>
          <option v-for="vehicle in vehicles" :key="vehicle.id" :value="vehicle.id">
            {{ vehicle.plate_number }}
          </option>
        </select>
        <div v-if="errors.vehicle_id" class="text-red-600 text-sm">{{ errors.vehicle_id[0] }}</div>
      </div>

      <!-- Auto-filled Driver Info -->
      <input type="hidden" v-model="trip.driver_id" />
      <div class="mb-4" v-if="driverName">
        <label class="block font-medium text-green-700">
          Driver: {{ driverName }}
        </label>
      </div>

      <!-- Start Location -->
      <div class="mb-4">
        <label class="block font-medium mb-1">Start Location</label>
        <input v-model="trip.start_location" type="text" class="w-full border rounded px-3 py-2" />
        <div v-if="errors.start_location" class="text-red-600 text-sm">{{ errors.start_location[0] }}</div>
      </div>

      <!-- End Location -->
      <div class="mb-4">
        <label class="block font-medium mb-1">End Location</label>
        <input v-model="trip.end_location" type="text" class="w-full border rounded px-3 py-2" />
        <div v-if="errors.end_location" class="text-red-600 text-sm">{{ errors.end_location[0] }}</div>
      </div>

      <!-- Start Time -->
      <div class="mb-4">
        <label class="block font-medium mb-1">Start Time</label>
        <input v-model="trip.start_time" type="datetime-local" class="w-full border rounded px-3 py-2" />
        <div v-if="errors.start_time" class="text-red-600 text-sm">{{ errors.start_time[0] }}</div>
      </div>

      <!-- End Time -->
      <div class="mb-4">
        <label class="block font-medium mb-1">End Time</label>
        <input v-model="trip.end_time" type="datetime-local" class="w-full border rounded px-3 py-2" />
        <div v-if="errors.end_time" class="text-red-600 text-sm">{{ errors.end_time[0] }}</div>
      </div>

      <!-- Submit -->
      <div>
        <button
          type="submit"
          class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded"
          :disabled="loading"
        >
          {{ isEdit ? 'Update Trip' : 'Create Trip' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from '@/axios'

const route = useRoute()
const router = useRouter()

const trip = ref({
  id: null,
  vehicle_id: '',
  driver_id: '',
  start_location: '',
  end_location: '',
  start_time: '',
  end_time: ''
})

const vehicles = ref([])
const errors = ref({})
const loading = ref(false)
const isEdit = ref(false)

// Helper: Format date to "YYYY-MM-DDTHH:MM" for datetime-local
const formatForInput = (dateStr) => {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  const pad = (n) => n.toString().padStart(2, '0')

  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`
}

// Fetch assigned vehicles
const fetchVehicles = async () => {
  try {
    const res = await axios.get('/assigned-vehicles')
    vehicles.value = res.data
  } catch (err) {
    console.error('Error loading vehicles', err)
  }
}

// Autofill driver_id
watch(() => trip.value.vehicle_id, vehicleId => {
  const selected = vehicles.value.find(v => v.id === vehicleId)
  trip.value.driver_id = selected?.driver?.user_id || ''
})

// Driver display
const driverName = computed(() => {
  const selected = vehicles.value.find(v => v.id === trip.value.vehicle_id)
  return selected?.driver?.user?.name || ''
})

// Submit form
const submit = async () => {
  loading.value = true
  errors.value = {}

  try {
    const payload = {
      ...trip.value,
      start_time: new Date(trip.value.start_time).toISOString(),
      end_time: new Date(trip.value.end_time).toISOString()
    }

    if (isEdit.value) {
      await axios.put(`/trips/${trip.value.id}`, payload)
    } else {
      await axios.post('/trips', payload)
    }

    router.push('/trips')
  } catch (err) {
    if (err.response?.status === 422) {
      errors.value = err.response.data.errors || {}
    } else {
      alert(err.response?.data?.message || 'Failed to save trip.')
    }
  } finally {
    loading.value = false
  }
}

// Fetch trip for editing
const fetchTrip = async () => {
  try {
    const res = await axios.get(`/trips/${route.params.id}`)
    trip.value = {
      ...res.data,
      start_time: formatForInput(res.data.start_time),
      end_time: formatForInput(res.data.end_time)
    }
    isEdit.value = true
  } catch (err) {
    console.error('Trip not found', err)
  }
}

onMounted(async () => {
  await fetchVehicles()
  if (route.params.id) {
    await fetchTrip()
  }
})
</script>
