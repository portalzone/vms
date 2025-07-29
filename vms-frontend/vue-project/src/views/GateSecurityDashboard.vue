<template>
  <div class="p-4">
    <h1 class="text-2xl font-bold mb-6">Gate Security Dashboard</h1>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mb-8">
      <div class="bg-blue-100 text-blue-800 p-4 rounded shadow">
        <h2 class="text-lg font-semibold">Checked In Today</h2>
        <p class="text-2xl">{{ stats.vehicles_checked_in_today }}</p>
      </div>
      <div class="bg-green-100 text-green-800 p-4 rounded shadow">
        <h2 class="text-lg font-semibold">Checked Out Today</h2>
        <p class="text-2xl">{{ stats.vehicles_checked_out_today }}</p>
      </div>
      <div class="bg-yellow-100 text-yellow-800 p-4 rounded shadow">
        <h2 class="text-lg font-semibold">Active Trips</h2>
        <p class="text-2xl">{{ stats.active_trips }}</p>
      </div>
      <div class="bg-gray-100 text-gray-800 p-4 rounded shadow">
        <h2 class="text-lg font-semibold">Total Logs</h2>
        <p class="text-2xl">{{ recentLogs.length }}</p>
      </div>
    </div>

    <!-- Vehicles Within Premises -->
    <div class="bg-white rounded shadow p-4 mb-8">
      <h2 class="text-xl font-semibold mb-4">Vehicles Within Premises</h2>

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

    <!-- Recent Logs -->
    <div class="bg-white rounded shadow p-4">
      <h2 class="text-xl font-semibold mb-4">Recent Check-Ins/Outs</h2>

      <div v-if="recentLogs.length">
        <table class="min-w-full text-left text-sm">
          <thead class="bg-gray-100">
            <tr>
              <th class="p-2">Vehicle</th>
              <th class="p-2">Driver</th>
              <th class="p-2">Checked In</th>
              <th class="p-2">Checked Out</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="log in recentLogs" :key="log.id" class="border-t hover:bg-gray-50">
              <td class="p-2">{{ log.vehicle?.manufacturer }} - {{ log.vehicle?.plate_number }}</td>
              <td class="p-2">{{ log.driver?.user?.name ?? '—' }}</td>
              <td class="p-2">{{ formatDate(log.checked_in_at) }}</td>
              <td class="p-2">{{ formatDate(log.checked_out_at) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="text-gray-500">No recent logs found.</div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import axios from '@/axios'

const stats = ref({
  vehicles_checked_in_today: 0,
  vehicles_checked_out_today: 0,
  active_trips: 0,
})

const recentLogs = ref([])
const vehiclesWithin = ref([])

const fetchStatsAndLogs = async () => {
  try {
    const [statsRes, logsRes, withinRes] = await Promise.all([
      axios.get('/gate-security/stats'),
      axios.get('/gate-security/recent-logs'),
      axios.get('/vehicles/within-premises'),
    ])
    stats.value = statsRes.data
    recentLogs.value = logsRes.data
    vehiclesWithin.value = withinRes.data
  } catch (error) {
    console.error('❌ Error loading dashboard data:', error)
  }
}

const formatDate = (dateStr) => {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleString()
}

onMounted(() => {
  fetchStatsAndLogs()
})
</script>

<style scoped>
table {
  width: 100%;
}
</style>
