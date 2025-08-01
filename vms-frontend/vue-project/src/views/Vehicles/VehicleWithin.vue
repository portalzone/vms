<template>
  <div class="p-4">
    <h1 class="text-2xl font-bold mb-6">Vehicles Within Premises</h1>

    <div class="bg-white rounded shadow p-4">
      <h2 class="text-xl font-semibold mb-4">Current Vehicles</h2>

      <div v-if="vehiclesWithin.length">
        <table class="min-w-full text-left text-sm">
          <thead class="bg-gray-100">
            <tr>
              <th class="p-2">Vehicle</th>
              <th class="p-2">Driver</th>
              <th class="p-2">Check-In Time</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="vehicle in vehiclesWithin" :key="vehicle.id" class="border-t hover:bg-gray-50">
              <td class="p-2">{{ vehicle.vehicle?.manufacturer }} - {{ vehicle.vehicle?.plate_number }}</td>
              <td class="p-2">{{ vehicle.driver?.user?.name ?? '—' }}</td>
              <td class="p-2">{{ formatDate(vehicle.checked_in_at) }}</td>
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

onMounted(() => {
  fetchVehiclesWithin()
})
</script>

<style scoped>
table {
  width: 100%;
}
</style>
