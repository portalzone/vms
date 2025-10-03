<template>
  <div class="p-4">
    <h1 class="mb-6 text-2xl font-bold">Vehicles Within Premises</h1>

    <div class="p-4 bg-white rounded shadow">
      <h2 class="mb-4 text-xl font-semibold">Current Vehicles</h2>

      <div v-if="vehiclesWithin.length">
        <table class="min-w-full text-sm text-left">
          <thead class="bg-gray-100">
            <tr>
              <th class="p-2">Vehicle</th>
              <th class="p-2">Driver</th>
              <th class="p-2">Check-In Time</th>
              <th class="p-2">Duration</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="vehicle in vehiclesWithin"
              :key="vehicle.id"
              class="border-t hover:bg-gray-50"
            >
              <td class="p-2">
                {{ vehicle.vehicle?.manufacturer }} {{ vehicle.vehicle?.model }} -
                {{ vehicle.vehicle?.plate_number }}
              </td>
              <td class="p-2">{{ vehicle.driver?.user?.name ?? '—' }}</td>
              <td class="p-2">{{ formatDate(vehicle.checked_in_at) }}</td>
              <td>
                <span :class="getDurationClass(vehicle.checked_in_at)">
                  {{ getDuration(vehicle.checked_in_at) }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-else class="text-gray-500">No vehicles currently inside the premises.</div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import axios from '@/axios'

const vehiclesWithin = ref([])

const fetchVehiclesWithin = async () => {
  try {
    const res = await axios.get('/vehicles/within-premises')
    vehiclesWithin.value = res.data
  } catch (error) {
    console.error('❌ Error loading vehicles within premises:', error)
  }
}

const formatDate = (dateStr) => {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleString()
}

const getDuration = (checkedInAt) => {
  if (!checkedInAt) return '—'
  const start = new Date(checkedInAt)
  const now = new Date()
  const diffMs = now - start
  const hours = Math.floor(diffMs / (1000 * 60 * 60))
  const minutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60))

  if (hours > 0) {
    return `${hours}h ${minutes}m`
  }
  return `${minutes}m`
}

const getDurationClass = (checkedInAt) => {
  if (!checkedInAt) return ''
  const start = new Date(checkedInAt)
  const now = new Date()
  const hours = (now - start) / (1000 * 60 * 60)

  if (hours > 8) return 'text-red-600 font-semibold'
  if (hours > 4) return 'text-orange-600 font-semibold'
  return 'text-green-600'
}

onMounted(() => {
  fetchVehiclesWithin()
})
</script>

<style scoped>
table {
  width: 100%;
}
</style>
