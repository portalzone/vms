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
        <router-link to="/vehicles" class="stat-card vehicles" title="View all vehicles">
          <h3 class="stat-title">🚗 Total Vehicles</h3>
          <p class="stat-value">{{ stats.vehicles ?? 0 }}</p>
        </router-link>

        <router-link to="/drivers" class="stat-card drivers" title="View all drivers">
          <h3 class="stat-title">🧑‍✈️ Total Drivers</h3>
          <p class="stat-value">{{ stats.drivers ?? 0 }}</p>
        </router-link>

        <router-link to="/expenses" class="stat-card expenses" title="View all expenses">
          <h3 class="stat-title">💰 Total Expenses</h3>
          <p class="stat-value">₦{{ stats.expenses ?? 0 }}</p>
        </router-link>

        <router-link to="/maintenance" class="stat-card maintenance" title="View maintenance tasks">
          <h3 class="stat-title">🔧 Maintenance Tasks</h3>
          <p class="stat-value">
            {{
              (stats.maintenances?.pending ?? 0) +
              (stats.maintenances?.in_progress ?? 0)
            }}
          </p>
          <small class="text-xs text-gray-700 block mt-1">
            {{ stats.maintenances?.pending ?? 0 }} pending /
            {{ stats.maintenances?.in_progress ?? 0 }} in progress
          </small>
        </router-link>
      </div>

      <p class="refresh-note">
        Stats refresh every 60 seconds.
      </p>

      <!-- Charts -->
      <div class="chart-wrapper">
        <TrendsChart />
      </div>

      <div class="chart-wrapper">
        <StatsChart :stats="stats" />
      </div>

      <!-- Recent Activity Section -->
      <div class="chart-wrapper">
        <h3 class="text-lg font-semibold mb-2">📋 Recent Activity</h3>
        <RecentActivity />
      </div>
    </div>

    <div v-if="loading" class="text-center mt-6 text-sm text-gray-500">
      ⏳ Loading stats...
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
const stats = ref({})
const loading = ref(false)

let intervalId = null

const fetchStats = async () => {
  loading.value = true
  try {
    const res = await axios.get('/dashboard/stats')
    stats.value = res.data
  } catch (error) {
    console.error('❌ Error loading dashboard stats:', error)
  } finally {
    loading.value = false
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

<style scoped>
/* Existing styles maintained */
.dashboard-container {
  max-width: 1120px;
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
  color: #6b7280;
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
.stat-card.vehicles {
  background: linear-gradient(to bottom right, #dbeafe, #bfdbfe);
}
.stat-card.drivers {
  background: linear-gradient(to bottom right, #d1fae5, #a7f3d0);
}
.stat-card.expenses {
  background: linear-gradient(to bottom right, #fef9c3, #fef08a);
}
.stat-card.maintenance {
  background: linear-gradient(to bottom right, #fecaca, #fca5a5);
}
.refresh-note {
  font-size: 0.75rem;
  color: #9ca3af;
  margin-top: 0.5rem;
}
.chart-wrapper {
  margin-top: 2.5rem;
}
</style>
