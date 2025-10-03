<template>
  <div class="dashboard-container">
    <!-- Header with Actions -->
    <div class="flex items-center justify-between mb-2">
      <div>
        <h2 class="dashboard-heading">Gate Security Dashboard</h2>
        <p class="dashboard-subtitle">Monitor vehicle entry and exit activities</p>
      </div>
      <div class="flex gap-2">
        <button @click="fetchStatsAndLogs" class="btn-secondary" :disabled="loading">
          {{ loading ? '🔄 Refreshing...' : '🔄 Refresh' }}
        </button>
        <router-link to="/checkins/new" class="btn-primary"> ➕ New Check-In </router-link>
      </div>
    </div>

    <!-- Error State -->
    <div v-if="error" class="px-4 py-3 mb-4 text-red-700 bg-red-100 border border-red-400 rounded">
      {{ error }}
    </div>

    <!-- Summary Cards -->
    <div class="stats-grid">
      <div class="stat-card checkin-today" title="Total check-ins recorded today">
        <h3 class="stat-title">📥 Checked In Today</h3>
        <p class="stat-value">{{ stats.vehicles_checked_in_today ?? 0 }}</p>
      </div>

      <div class="stat-card checkout-today" title="Total check-outs recorded today">
        <h3 class="stat-title">📤 Checked Out Today</h3>
        <p class="stat-value">{{ stats.vehicles_checked_out_today ?? 0 }}</p>
      </div>

      <div class="stat-card active-trips" title="Number of trips currently in progress">
        <h3 class="stat-title">🚚 Active Trips</h3>
        <p class="stat-value">{{ stats.active_trips ?? 0 }}</p>
      </div>

      <router-link
        to="/vehicle-within"
        class="stat-card vehicles-inside"
        title="Vehicles currently within premises"
      >
        <h3 class="stat-title">🏢 Currently Inside</h3>
        <p class="stat-value">{{ vehiclesWithin.length }}</p>
      </router-link>
    </div>

    <p class="refresh-note">Dashboard auto-refreshes every 60 seconds.</p>

    <!-- Vehicles Within Premises -->
    <div class="section-card">
      <div class="section-header">
        <h2 class="section-title">
          🏢 Vehicles Within Premises
          <span class="count-badge">{{ vehiclesWithin.length }}</span>
        </h2>
        <input v-model="searchWithin" placeholder="Search vehicles..." class="search-input" />
      </div>

      <div v-if="filteredVehiclesWithin.length" class="table-container">
        <table class="data-table">
          <thead>
            <tr>
              <th>Vehicle</th>
              <th>Plate Number</th>
              <th>Driver</th>
              <th>Check-In Time</th>
              <th>Duration</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="vehicle in filteredVehiclesWithin" :key="vehicle.id">
              <td>
                <div class="vehicle-info">
                  <span class="font-medium">{{ vehicle.vehicle?.manufacturer }}</span>
                  <span class="text-xs text-gray-500">{{ vehicle.vehicle?.model }}</span>
                </div>
              </td>
              <td>
                <span class="plate-number">{{ vehicle.vehicle?.plate_number }}</span>
              </td>
              <td>{{ vehicle.driver?.user?.name ?? '—' }}</td>
              <td>{{ formatDate(vehicle.checked_in_at) }}</td>
              <td>
                <span :class="getDurationClass(vehicle.checked_in_at)">
                  {{ getDuration(vehicle.checked_in_at) }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="empty-state">
        {{
          searchWithin
            ? 'No vehicles match your search.'
            : 'No vehicles currently inside the premises.'
        }}
      </div>
    </div>

    <!-- Recent Logs -->
    <div class="section-card">
      <div class="section-header">
        <h2 class="section-title">
          📋 Recent Check-Ins/Outs
          <span class="count-badge">Last {{ logsLimit }}</span>
        </h2>
        <div class="flex gap-2">
          <input v-model="searchLogs" placeholder="Search logs..." class="search-input" />
          <select v-model="logsLimit" @change="fetchStatsAndLogs" class="select-input">
            <option :value="10">10</option>
            <option :value="20">20</option>
            <option :value="50">50</option>
          </select>
        </div>
      </div>

      <div v-if="filteredRecentLogs.length" class="table-container">
        <table class="data-table">
          <thead>
            <tr>
              <th>Vehicle</th>
              <th>Plate Number</th>
              <th>Driver</th>
              <th>Checked In</th>
              <th>Checked Out</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="log in filteredRecentLogs" :key="log.id">
              <td>
                <div class="vehicle-info">
                  <span class="font-medium">{{ log.vehicle?.manufacturer }}</span>
                  <span class="text-xs text-gray-500">{{ log.vehicle?.model }}</span>
                </div>
              </td>
              <td>
                <span class="plate-number">{{ log.vehicle?.plate_number }}</span>
              </td>
              <td>{{ log.driver?.user?.name ?? '—' }}</td>
              <td>{{ formatDate(log.checked_in_at) }}</td>
              <td>{{ formatDate(log.checked_out_at) }}</td>
              <td>
                <span
                  :class="log.checked_out_at ? 'status-badge status-out' : 'status-badge status-in'"
                >
                  {{ log.checked_out_at ? 'OUT' : 'IN' }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="empty-state">
        {{ searchLogs ? 'No logs match your search.' : 'No recent logs found.' }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, computed, onBeforeUnmount } from 'vue'
import axios from '@/axios'

const stats = ref({
  vehicles_checked_in_today: 0,
  vehicles_checked_out_today: 0,
  active_trips: 0,
})

const recentLogs = ref([])
const vehiclesWithin = ref([])
const loading = ref(false)
const error = ref(null)
const searchWithin = ref('')
const searchLogs = ref('')
const logsLimit = ref(10)
let intervalId = null

// Computed: Filtered vehicles within premises
const filteredVehiclesWithin = computed(() => {
  if (!searchWithin.value) return vehiclesWithin.value
  const query = searchWithin.value.toLowerCase()
  return vehiclesWithin.value.filter(
    (v) =>
      v.vehicle?.manufacturer?.toLowerCase().includes(query) ||
      v.vehicle?.plate_number?.toLowerCase().includes(query) ||
      v.driver?.user?.name?.toLowerCase().includes(query),
  )
})

// Computed: Filtered recent logs
const filteredRecentLogs = computed(() => {
  if (!searchLogs.value) return recentLogs.value
  const query = searchLogs.value.toLowerCase()
  return recentLogs.value.filter(
    (log) =>
      log.vehicle?.manufacturer?.toLowerCase().includes(query) ||
      log.vehicle?.plate_number?.toLowerCase().includes(query) ||
      log.driver?.user?.name?.toLowerCase().includes(query),
  )
})

const fetchStatsAndLogs = async () => {
  loading.value = true
  error.value = null
  try {
    const [statsRes, logsRes, withinRes] = await Promise.all([
      axios.get('/gate-security/stats'),
      axios.get(`/gate-security/recent-logs?limit=${logsLimit.value}`),
      axios.get('/vehicles/within-premises'),
    ])
    stats.value = statsRes.data
    recentLogs.value = logsRes.data
    vehiclesWithin.value = withinRes.data
  } catch (err) {
    console.error('Error loading dashboard data:', err)
    error.value = 'Failed to load dashboard data. Please try again.'
  } finally {
    loading.value = false
  }
}

const formatDate = (dateStr) => {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
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
  fetchStatsAndLogs()
  intervalId = setInterval(fetchStatsAndLogs, 60000)
})

onBeforeUnmount(() => {
  if (intervalId) {
    clearInterval(intervalId)
    intervalId = null
  }
})
</script>

<style scoped>
/* Dashboard Container */
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

/* Headings */
.dashboard-heading {
  font-size: 1.5rem;
  font-weight: bold;
  margin-bottom: 0.25rem;
}

.dashboard-subtitle {
  font-size: 0.875rem;
  color: #6b7280;
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(1, 1fr);
  gap: 1.5rem;
  margin-bottom: 1rem;
}

@media (min-width: 768px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (min-width: 1024px) {
  .stats-grid {
    grid-template-columns: repeat(4, 1fr);
  }
}

/* Stat Cards */
.stat-card {
  padding: 1rem;
  border-radius: 1rem;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
  transition: all 0.2s ease;
  text-decoration: none;
  color: inherit;
  display: block;
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

.stat-card.checkin-today {
  background: linear-gradient(to bottom right, #fef3c7, #fde68a);
}

.stat-card.checkout-today {
  background: linear-gradient(to bottom right, #fecaca, #fca5a5);
}

.stat-card.active-trips {
  background: linear-gradient(to bottom right, #d1fae5, #a7f3d0);
}

.stat-card.vehicles-inside {
  background: linear-gradient(to bottom right, #e0e7ff, #c7d2fe);
}

.refresh-note {
  font-size: 0.75rem;
  color: #9ca3af;
  margin-bottom: 2rem;
}

/* Section Cards */
.section-card {
  background: white;
  border-radius: 0.75rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  padding: 1.5rem;
  margin-bottom: 2rem;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.section-title {
  font-size: 1.25rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.count-badge {
  font-size: 0.875rem;
  font-weight: normal;
  color: #6b7280;
}

.search-input {
  padding: 0.5rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  width: 250px;
}

.select-input {
  padding: 0.5rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  font-size: 0.875rem;
}

/* Table */
.table-container {
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}

.data-table thead {
  background-color: #f9fafb;
  border-bottom: 2px solid #e5e7eb;
}

.data-table th {
  text-align: left;
  padding: 0.75rem 1rem;
  font-weight: 600;
  color: #374151;
}

.data-table td {
  padding: 0.75rem 1rem;
  border-top: 1px solid #e5e7eb;
}

.data-table tbody tr:hover {
  background-color: #f9fafb;
}

.vehicle-info {
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
}

.plate-number {
  font-family: monospace;
  background-color: #f3f4f6;
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  font-size: 0.875rem;
}

.status-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
}

.status-badge.status-in {
  background-color: #d1fae5;
  color: #065f46;
}

.status-badge.status-out {
  background-color: #f3f4f6;
  color: #4b5563;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 3rem 1rem;
  color: #6b7280;
  font-size: 0.875rem;
}
</style>
