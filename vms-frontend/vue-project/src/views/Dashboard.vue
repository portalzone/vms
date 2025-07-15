<template>
  <div v-if="auth.user">
    <div class="dashboard-container">
      <h2 class="dashboard-heading">
        Welcome {{ auth.user.role }}: {{ auth.user.name }}
      </h2>
      <p class="dashboard-subtitle">
        Here is your dashboard overview:
      </p>

      <!-- Stats Grid -->
      <div class="stats-grid">
        <router-link to="/vehicles" class="stat-card vehicles">
          <h3 class="stat-title">🚗 Total Vehicles</h3>
          <p class="stat-value">{{ stats.vehicles ?? 0 }}</p>
        </router-link>

        <router-link to="/drivers" class="stat-card drivers">
          <h3 class="stat-title">👨‍✈️ Total Drivers</h3>
          <p class="stat-value">{{ stats.drivers ?? 0 }}</p>
        </router-link>

        <router-link to="/expenses" class="stat-card expenses">
          <h3 class="stat-title">💸 Expenses</h3>
          <p class="stat-value">₦{{ stats.expenses ?? 0 }}</p>
        </router-link>

        <router-link to="/maintenance" class="stat-card maintenance">
          <h3 class="stat-title">🚲 Maintenance Tasks</h3>
          <p class="stat-value">
            {{
              (stats.maintenances?.pending ?? 0) +
              (stats.maintenances?.in_progress ?? 0)
            }}
          </p>
        </router-link>
      </div>

      <p class="refresh-note">
        Stats refresh every 60 seconds.
      </p>

      <!-- Trends Chart -->
      <div class="chart-wrapper">
        <TrendsChart />
      </div>
            <div class="chart-wrapper">
        <StatsChart :stats="stats" />
      </div>
    </div>
  </div>
</template>


<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useAuthStore } from '@/stores/auth'
import axios from '@/axios'
import TrendsChart from '@/components/TrendsChart.vue'
import StatsChart from '@/components/StatsChart.vue'
import RecentActivity from '@/components/RecentActivity.vue'

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

// onMounted(async () => {
//   try {
//     const response = await axios.get('/dashboard/monthly-trends')
//     console.log('📊 Monthly Trend Data:', response.data)
//   } catch (e) {
//     console.error('❌ Error loading trends:', e)
//   }
// })
// onMounted(async () => {
//   try {
//     const response = await axios.get('/dashboard/stats')
//     console.log('📊 Stats Data:', response.data)
//   } catch (e) {
//     console.error('❌ Error loading stats:', e)
//   }
// })
// onMounted(async () => {
//   try {
//     const response = await axios.get('/dashboard/activity')
//     console.log('📊 Activities Data:', response.data)
//   } catch (e) {
//     console.error('❌ Error loading Activities:', e)
//   }
// })


onBeforeUnmount(() => {
  if (intervalId) {
    clearInterval(intervalId)
    intervalId = null
  }
})
</script>
<style scoped>
.dashboard-container {
  max-width: 1120px; /* max-w-7xl */
  margin: 0 auto;
  padding: 1rem;
}

@media (min-width: 640px) {
  .dashboard-container {
    padding-left: 1.5rem;
    padding-right: 1.5rem;
  }
}

@media (min-width: 1024px) {
  .dashboard-container {
    padding-left: 2rem;
    padding-right: 2rem;
  }
}

.dashboard-heading {
  font-size: 1.5rem;
  font-weight: bold;
  margin-bottom: 0.5rem;
}

.dashboard-subtitle {
  font-size: 0.875rem;
  color: #6b7280; /* gray-500 */
  margin-bottom: 1.5rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(1, 1fr);
  gap: 1.5rem;
}

@media (min-width: 640px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (min-width: 1024px) {
  .stats-grid {
    grid-template-columns: repeat(4, 1fr);
  }
}

.stat-card {
  padding: 1rem;
  border-radius: 1rem;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
  transition: all 0.2s ease;
  text-decoration: none;
  color: inherit;
}

.stat-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  transform: translateY(-4px);
}

.stat-title {
  font-size: 0.875rem;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.25rem;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: bold;
}

/* Gradient backgrounds */
.stat-card.vehicles {
  background: linear-gradient(to bottom right, #dbeafe, #bfdbfe); /* blue-100 to blue-200 */
}

.stat-card.drivers {
  background: linear-gradient(to bottom right, #d1fae5, #a7f3d0); /* green-100 to green-200 */
}

.stat-card.expenses {
  background: linear-gradient(to bottom right, #fef9c3, #fef08a); /* yellow-100 to yellow-200 */
}

.stat-card.maintenance {
  background: linear-gradient(to bottom right, #fecaca, #fca5a5); /* red-100 to red-200 */
}

.refresh-note {
  font-size: 0.75rem;
  color: #9ca3af; /* gray-400 */
  margin-top: 0.5rem;
}

.chart-wrapper {
  margin-top: 2.5rem;
}
</style>
