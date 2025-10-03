<template>
  <div class="dashboard-container">
    <!-- Header -->
    <div class="mb-6">
      <h2 class="dashboard-heading">Welcome, {{ auth.user?.name }}</h2>
      <p class="dashboard-subtitle">Manage and monitor your registered vehicles</p>
    </div>

    <!-- Summary Cards -->
    <div class="mb-8 stats-grid">
      <div class="stat-card vehicles">
        <h3 class="stat-title">🚗 My Vehicles</h3>
        <p class="stat-value">{{ vehicles.length }}</p>
      </div>

      <div class="stat-card expenses">
        <h3 class="stat-title">💰 Total Expenses</h3>
        <p class="stat-value">₦{{ totalExpenses.toLocaleString() }}</p>
      </div>

      <div class="stat-card maintenance">
        <h3 class="stat-title">🔧 Maintenance Due</h3>
        <p class="stat-value">{{ maintenanceDue }}</p>
      </div>

      <div class="stat-card trips">
        <h3 class="stat-title">🚚 Total Trips</h3>
        <p class="stat-value">{{ totalTrips }}</p>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="py-8 text-center text-gray-500">Loading your vehicles...</div>

    <!-- Error State -->
    <div v-if="error" class="px-4 py-3 mb-6 text-red-700 bg-red-100 border border-red-400 rounded">
      {{ error }}
    </div>

    <!-- Vehicles Grid -->
    <div v-if="!loading && vehicles.length" class="section-card">
      <div class="section-header">
        <h3 class="section-title">
          🚗 Your Vehicles
          <span class="count-badge">({{ vehicles.length }})</span>
        </h3>
        <input
          v-model="searchQuery"
          placeholder="Search by plate or manufacturer..."
          class="search-input"
        />
      </div>

      <div class="vehicles-grid">
        <div v-for="vehicle in filteredVehicles" :key="vehicle.id" class="vehicle-card">
          <div class="vehicle-header">
            <div>
              <h4 class="vehicle-plate">{{ vehicle.plate_number || 'N/A' }}</h4>
              <p class="vehicle-info">
                {{ vehicle.manufacturer || 'Unknown' }} {{ vehicle.model || '' }}
                <span v-if="vehicle.year" class="text-gray-500">({{ vehicle.year }})</span>
              </p>
            </div>
            <span :class="getStatusClass(vehicle.status)">
              {{ vehicle.status || 'available' }}
            </span>
          </div>

          <div class="vehicle-details">
            <div class="detail-item">
              <span class="detail-label">Ownership:</span>
              <span class="detail-value">{{ formatOwnership(vehicle.ownership_type) }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Insurance:</span>
              <span :class="getInsuranceClass(vehicle.insurance_status)">
                {{ formatInsurance(vehicle.insurance_status) }}
              </span>
            </div>
          </div>

          <!-- Vehicle Stats -->
          <div class="vehicle-stats">
            <div class="stat-item">
              <span class="stat-label">Expenses</span>
              <span class="stat-number"
                >₦{{ getVehicleExpenses(vehicle.id).toLocaleString() }}</span
              >
            </div>
            <div class="stat-item">
              <span class="stat-label">Trips</span>
              <span class="stat-number">{{ getVehicleTrips(vehicle.id) }}</span>
            </div>
            <div class="stat-item">
              <span class="stat-label">Maintenance</span>
              <span class="stat-number">{{ getVehicleMaintenance(vehicle.id) }}</span>
            </div>
          </div>

          <!-- Actions -->
          <div class="vehicle-actions">
            <button @click="viewVehicleDetails(vehicle.id)" class="btn-view">View Details</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="!loading && !vehicles.length" class="empty-state">
      <div class="empty-icon">🚗</div>
      <h3 class="empty-title">No Vehicles Registered</h3>
      <p class="empty-description">You haven't registered any vehicles yet.</p>
    </div>

    <!-- Recent Activity -->
    <div v-if="recentActivity.length" class="mt-8 section-card">
      <h3 class="mb-4 section-title">📋 Recent Activity</h3>
      <div class="activity-list">
        <div v-for="activity in recentActivity" :key="activity.id" class="activity-item">
          <div class="activity-icon" :class="getActivityIconClass(activity.type)">
            {{ getActivityIcon(activity.type) }}
          </div>
          <div class="activity-content">
            <p class="activity-description">{{ activity.description }}</p>
            <p class="activity-time">{{ formatDate(activity.created_at) }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from '@/axios'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

const auth = useAuthStore()
const router = useRouter()

const vehicles = ref([])
const expenses = ref([])
const trips = ref([])
const maintenance = ref([])
const recentActivity = ref([])
const loading = ref(true)
const error = ref('')
const searchQuery = ref('')

// Computed Properties
const filteredVehicles = computed(() => {
  if (!searchQuery.value) return vehicles.value
  const query = searchQuery.value.toLowerCase()
  return vehicles.value.filter(
    (v) =>
      v.plate_number?.toLowerCase().includes(query) ||
      v.manufacturer?.toLowerCase().includes(query) ||
      v.model?.toLowerCase().includes(query),
  )
})

const totalExpenses = computed(() => {
  return expenses.value.reduce((sum, exp) => sum + parseFloat(exp.amount || 0), 0)
})

const totalTrips = computed(() => {
  return trips.value.length
})

const maintenanceDue = computed(() => {
  return maintenance.value.filter((m) => m.status === 'pending').length
})

// Methods
const getVehicleExpenses = (vehicleId) => {
  return expenses.value
    .filter((e) => e.vehicle_id === vehicleId)
    .reduce((sum, exp) => sum + parseFloat(exp.amount || 0), 0)
}

const getVehicleTrips = (vehicleId) => {
  return trips.value.filter((t) => t.vehicle_id === vehicleId).length
}

const getVehicleMaintenance = (vehicleId) => {
  return maintenance.value.filter((m) => m.vehicle_id === vehicleId).length
}

const getStatusClass = (status) => {
  const classes = {
    available: 'status-badge status-available',
    in_use: 'status-badge status-in-use',
    maintenance: 'status-badge status-maintenance',
  }
  return classes[status] || 'status-badge'
}

const getInsuranceClass = (status) => {
  const classes = {
    active: 'text-green-600 font-semibold',
    expired: 'text-red-600 font-semibold',
    none: 'text-gray-500',
  }
  return classes[status] || 'text-gray-500'
}

const formatOwnership = (type) => {
  const types = {
    vehicle_owner: 'Personal',
    organization: 'Organization',
    staff: 'Staff',
    visitor: 'Visitor',
  }
  return types[type] || type
}

const formatInsurance = (status) => {
  return status === 'active' ? 'Active' : status === 'expired' ? 'Expired' : 'None'
}

const getActivityIcon = (type) => {
  const icons = {
    expense: '💰',
    maintenance: '🔧',
    trip: '🚚',
    checkin: '📥',
    checkout: '📤',
  }
  return icons[type] || '📋'
}

const getActivityIconClass = (type) => {
  const classes = {
    expense: 'bg-yellow-100',
    maintenance: 'bg-red-100',
    trip: 'bg-green-100',
    checkin: 'bg-blue-100',
    checkout: 'bg-gray-100',
  }
  return classes[type] || 'bg-gray-100'
}

const formatDate = (dateStr) => {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const viewVehicleDetails = (vehicleId) => {
  router.push(`/vehicles/${vehicleId}`)
}

const fetchDashboardData = async () => {
  loading.value = true
  error.value = ''

  try {
    const [vehiclesRes, expensesRes, tripsRes, maintenanceRes] = await Promise.all([
      axios.get('/vehicles/mine'),
      axios.get('/expenses'),
      axios.get('/trips'),
      axios.get('/maintenances'),
    ])

    // vehicles.value = vehiclesRes.data
    vehicles.value = vehiclesRes.data.data || vehiclesRes.data

    // Filter expenses for owner's vehicles
    const vehicleIds = vehicles.value.map((v) => v.id)
    expenses.value = (expensesRes.data.data || expensesRes.data).filter((e) =>
      vehicleIds.includes(e.vehicle_id),
    )
    trips.value = (tripsRes.data.data || tripsRes.data).filter((t) =>
      vehicleIds.includes(t.vehicle_id),
    )
    maintenance.value = (maintenanceRes.data.data || maintenanceRes.data).filter((m) =>
      vehicleIds.includes(m.vehicle_id),
    )

    // Build recent activity
    buildRecentActivity()
  } catch (err) {
    console.error('Error loading dashboard:', err)
    error.value = 'Failed to load your vehicles. Please try again later.'
  } finally {
    loading.value = false
  }
}

const buildRecentActivity = () => {
  const activities = []

  expenses.value.slice(0, 3).forEach((exp) => {
    activities.push({
      id: `exp-${exp.id}`,
      type: 'expense',
      description: `Expense of ₦${parseFloat(exp.amount).toLocaleString()} for ${exp.category}`,
      created_at: exp.created_at,
    })
  })

  trips.value.slice(0, 3).forEach((trip) => {
    activities.push({
      id: `trip-${trip.id}`,
      type: 'trip',
      description: `Trip from ${trip.start_location} to ${trip.end_location}`,
      created_at: trip.created_at,
    })
  })

  maintenance.value.slice(0, 3).forEach((maint) => {
    activities.push({
      id: `maint-${maint.id}`,
      type: 'maintenance',
      description: `${maint.type} maintenance - ${maint.description}`,
      created_at: maint.created_at,
    })
  })

  recentActivity.value = activities
    .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
    .slice(0, 5)
}

onMounted(() => {
  fetchDashboardData()
})
</script>

<style scoped>
/* Dashboard Container */
.dashboard-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 1.5rem;
}

/* Headings */
.dashboard-heading {
  font-size: 1.875rem;
  font-weight: bold;
  color: #1f2937;
}

.dashboard-subtitle {
  font-size: 0.875rem;
  color: #6b7280;
  margin-top: 0.25rem;
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(1, 1fr);
  gap: 1rem;
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

/* Stat Cards */
.stat-card {
  padding: 1.25rem;
  border-radius: 1rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  transition: all 0.2s ease;
}

.stat-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  transform: translateY(-2px);
}

.stat-title {
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
  margin-bottom: 0.5rem;
}

.stat-value {
  font-size: 1.875rem;
  font-weight: bold;
  color: #111827;
}

.stat-card.vehicles {
  background: linear-gradient(to bottom right, #dbeafe, #bfdbfe);
}

.stat-card.expenses {
  background: linear-gradient(to bottom right, #fef3c7, #fde68a);
}

.stat-card.maintenance {
  background: linear-gradient(to bottom right, #fecaca, #fca5a5);
}

.stat-card.trips {
  background: linear-gradient(to bottom right, #d1fae5, #a7f3d0);
}

/* Section Card */
.section-card {
  background: white;
  border-radius: 0.75rem;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
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
  color: #1f2937;
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
  width: 100%;
  max-width: 300px;
}

/* Vehicles Grid */
.vehicles-grid {
  display: grid;
  grid-template-columns: repeat(1, 1fr);
  gap: 1.5rem;
}

@media (min-width: 768px) {
  .vehicles-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (min-width: 1024px) {
  .vehicles-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

/* Vehicle Card */
.vehicle-card {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 0.75rem;
  padding: 1.25rem;
  transition: all 0.2s ease;
}

.vehicle-card:hover {
  border-color: #3b82f6;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
}

.vehicle-header {
  display: flex;
  justify-content: space-between;
  align-items: start;
  margin-bottom: 1rem;
}

.vehicle-plate {
  font-size: 1.125rem;
  font-weight: 700;
  color: #1f2937;
  font-family: monospace;
}

.vehicle-info {
  font-size: 0.875rem;
  color: #6b7280;
  margin-top: 0.25rem;
}

.status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: capitalize;
}

.status-available {
  background: #d1fae5;
  color: #065f46;
}

.status-in-use {
  background: #fef3c7;
  color: #92400e;
}

.status-maintenance {
  background: #fecaca;
  color: #991b1b;
}

.vehicle-details {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-bottom: 1rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #e5e7eb;
}

.detail-item {
  display: flex;
  justify-content: space-between;
  font-size: 0.875rem;
}

.detail-label {
  color: #6b7280;
}

.detail-value {
  color: #1f2937;
  font-weight: 500;
}

.vehicle-stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
  margin-bottom: 1rem;
}

.stat-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.stat-label {
  font-size: 0.75rem;
  color: #6b7280;
  margin-bottom: 0.25rem;
}

.stat-number {
  font-size: 1.125rem;
  font-weight: 700;
  color: #1f2937;
}

.vehicle-actions {
  display: flex;
  gap: 0.5rem;
}

.btn-view {
  flex: 1;
  padding: 0.5rem 1rem;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-view:hover {
  background: #2563eb;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 4rem 2rem;
}

.empty-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
}

.empty-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.5rem;
}

.empty-description {
  color: #6b7280;
}

/* Activity List */
.activity-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.activity-item {
  display: flex;
  gap: 1rem;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 0.5rem;
}

.activity-icon {
  width: 2.5rem;
  height: 2.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 0.5rem;
  font-size: 1.25rem;
}

.activity-content {
  flex: 1;
}

.activity-description {
  font-size: 0.875rem;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.activity-time {
  font-size: 0.75rem;
  color: #6b7280;
}
</style>
