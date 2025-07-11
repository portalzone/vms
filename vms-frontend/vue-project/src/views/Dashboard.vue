<template>
  <div v-if="auth.user">
    <div class="max-w-7xl mx-auto px-4">
      <h2 class="text-2xl font-bold mb-2">
        Welcome {{ auth.user.role }}: {{ auth.user.name }}
      </h2>
      <p class="text-sm text-gray-500 mb-6">
        Here is your dashboard overview:
      </p>

      <!-- Stats Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <router-link
          to="/vehicles"
          class="bg-gradient-to-br from-blue-100 to-blue-200 p-4 rounded-2xl shadow hover:shadow-md transition transform hover:-translate-y-1"
        >
          <h3 class="text-sm font-medium flex items-center gap-2">
            🚗 Total Vehicles
          </h3>
          <p class="text-2xl font-bold">{{ stats.vehicles ?? 0 }}</p>
        </router-link>

        <router-link
          to="/drivers"
          class="bg-gradient-to-br from-green-100 to-green-200 p-4 rounded-2xl shadow hover:shadow-md transition transform hover:-translate-y-1"
        >
          <h3 class="text-sm font-medium flex items-center gap-2">
            👨‍✈️ Total Drivers
          </h3>
          <p class="text-2xl font-bold">{{ stats.drivers ?? 0 }}</p>
        </router-link>

        <router-link
          to="/expenses"
          class="bg-gradient-to-br from-yellow-100 to-yellow-200 p-4 rounded-2xl shadow hover:shadow-md transition transform hover:-translate-y-1"
        >
          <h3 class="text-sm font-medium flex items-center gap-2">
            💸 Expenses
          </h3>
          <p class="text-2xl font-bold">₦{{ stats.expenses ?? 0 }}</p>
        </router-link>

        <router-link
          to="/maintenance"
          class="bg-gradient-to-br from-red-100 to-red-200 p-4 rounded-2xl shadow hover:shadow-md transition transform hover:-translate-y-1"
        >
          <h3 class="text-sm font-medium flex items-center gap-2">
            🚲 Maintenance Tasks
          </h3>
          <p class="text-2xl font-bold">{{ stats.maintenances ?? 0 }}</p>
        </router-link>
      </div>

      <p class="text-xs text-gray-400 mt-2">
        Stats refresh every 60 seconds.
      </p>

      <!-- Trends Chart -->
      <div class="mt-10">
        <TrendsChart />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useAuthStore } from '@/stores/auth'
import axios from '@/axios'
import TrendsChart from '@/components/TrendsChart.vue'

const auth = useAuthStore()

const stats = ref({
  vehicles: 0,
  drivers: 0,
  expenses: 0,
  maintenances: 0,
})

let intervalId = null

const fetchStats = async () => {
  try {
    const res = await axios.get('/dashboard/stats')
    stats.value = res.data
  } catch (error) {
    console.error('❌ Error loading dashboard stats:', error)
    if (error.response && error.response.status === 401) {
      console.warn('⚠️ Unauthorized access. Token may be missing or expired.')
    } else if (error.response && error.response.status === 500) {
      console.warn('⚠️ Server error while fetching stats.')
    }
  }
}

onMounted(() => {
  if (auth.user) {
    fetchStats()
    intervalId = setInterval(fetchStats, 60000)
  }
})

onBeforeUnmount(() => {
  if (intervalId) {
    clearInterval(intervalId)
    intervalId = null
  }
})
</script>
