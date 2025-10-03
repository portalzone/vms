<template>
  <div class="dashboard-container">
    <!-- Header -->
    <div class="mb-6">
      <h2 class="dashboard-heading">Welcome, {{ driver?.user?.name || 'Driver' }}</h2>
      <p class="dashboard-subtitle">Track your vehicle and trips</p>
    </div>

    <!-- Stats Grid -->
    <div class="mb-8 stats-grid">
      <!-- Assigned Vehicle -->
      <div class="stat-card vehicle">
        <h3 class="stat-title">🚗 Assigned Vehicle</h3>
        <p class="stat-value">
          <span v-if="driver && driver.vehicle">
            {{ driver.vehicle.manufacturer }} - {{ driver.vehicle.plate_number }}
          </span>
          <span v-else class="text-lg text-gray-500">Not Assigned</span>
        </p>
      </div>

      <!-- Total Trips -->
      <div class="stat-card trips">
        <h3 class="stat-title">🚚 Total Trips</h3>
        <p class="stat-value">{{ totalTrips }}</p>
      </div>

      <!-- Last Trip -->
      <div class="stat-card last-trip">
        <h3 class="stat-title">🕒 Last Trip</h3>
        <div v-if="recentTrip">
          <p class="font-medium">{{ recentTrip.start_location }} → {{ recentTrip.end_location }}</p>
          <p class="text-sm text-gray-700">Ended: {{ formatDate(recentTrip.end_time) }}</p>
        </div>
        <p v-else class="text-gray-500">No trips recorded</p>
      </div>
    </div>

    <!-- Recent Trips -->
    <div v-if="recentTrips.length" class="section-card">
      <h3 class="mb-4 section-title">📋 Recent Trips</h3>
      <div class="activity-list">
        <div v-for="trip in recentTrips" :key="trip.id" class="activity-item">
          <div class="bg-green-100 activity-icon">🚚</div>
          <div class="activity-content">
            <p class="activity-description">
              Trip from {{ trip.start_location }} → {{ trip.end_location }}
            </p>
            <p class="activity-time">{{ formatDate(trip.end_time) }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Link to My Trips -->
    <router-link
      to="/trips"
      class="inline-block px-4 py-2 mt-6 font-semibold text-white transition bg-blue-600 hover:bg-blue-700 rounded-xl"
    >
      View All My Trips
    </router-link>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import axios from '@/axios'

const driver = ref(null)
const totalTrips = ref(0)
const recentTrip = ref(null)
const recentTrips = ref([])

const formatDate = (datetime) => {
  return new Date(datetime).toLocaleString()
}

onMounted(async () => {
  try {
    const { data } = await axios.get('/driver/me')
    driver.value = data

    const tripRes = await axios.get('/trips')
    const trips = tripRes?.data?.data || []

    const myTrips = trips.filter((trip) => trip.driver_id === driver.value.id)

    totalTrips.value = myTrips.length
    recentTrip.value =
      myTrips.length > 0
        ? myTrips.sort((a, b) => new Date(b.end_time) - new Date(a.end_time))[0]
        : null

    recentTrips.value = myTrips
      .sort((a, b) => new Date(b.end_time) - new Date(a.end_time))
      .slice(0, 5)
  } catch (error) {
    console.error('Error loading driver dashboard:', error)
  }
})
</script>

<style scoped>
/* Container */
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
    grid-template-columns: repeat(3, 1fr);
  }
}

/* Stat Cards */
.stat-card {
  padding: 1.25rem;
  border-radius: 1rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  background: white;
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
  font-size: 1.5rem;
  font-weight: bold;
  color: #111827;
}

.stat-card.vehicle {
  background: linear-gradient(to bottom right, #dbeafe, #bfdbfe);
}

.stat-card.trips {
  background: linear-gradient(to bottom right, #d1fae5, #a7f3d0);
}

.stat-card.last-trip {
  background: linear-gradient(to bottom right, #fde68a, #fef3c7);
}

/* Section Card */
.section-card {
  background: white;
  border-radius: 0.75rem;
  padding: 1.5rem;
  margin-top: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.section-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
}

/* Recent Activity */
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
