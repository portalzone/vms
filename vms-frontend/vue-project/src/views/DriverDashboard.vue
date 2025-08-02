<template>
  <div class="p-6">
    <h2 class="text-2xl font-semibold mb-4">Driver Dashboard</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
      <!-- Assigned Vehicle -->
      <div class="bg-white shadow rounded-2xl p-4">
        <h3 class="text-lg font-medium mb-2">Assigned Vehicle</h3>
        <p v-if="driver && driver.vehicle">
          {{ driver.vehicle.manufacturer }} - {{ driver.vehicle.plate_number }}
        </p>
        <p v-else>No driver profile found. Please contact admin.</p>
      </div>

      <!-- Total Trips -->
      <div class="bg-white shadow rounded-2xl p-4">
        <h3 class="text-lg font-medium mb-2">Total Trips</h3>
        <p class="text-xl font-bold">{{ totalTrips }}</p>
      </div>

      <!-- Recent Trip -->
      <div class="bg-white shadow rounded-2xl p-4">
        <h3 class="text-lg font-medium mb-2">Last Trip</h3>
        <div v-if="recentTrip">
          <p><strong>From:</strong> {{ recentTrip.start_location }}</p>
          <p><strong>To:</strong> {{ recentTrip.end_location }}</p>
          <p><strong>Ended:</strong> {{ formatDate(recentTrip.end_time) }}</p>
        </div>
        <p v-else>No trips recorded</p>
      </div>
    </div>

    <!-- Link to My Trips -->
    <router-link
      to="/trips"
      class="inline-block mt-4 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition"
    >
      View My Trips
    </router-link>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import axios from '@/axios'

const driver = ref(null)
const totalTrips = ref(0)
const recentTrip = ref(null)

const formatDate = (datetime) => {
  return new Date(datetime).toLocaleString()
}

onMounted(async () => {
  try {
    const { data } = await axios.get('/driver/me')
    driver.value = data

    const tripRes = await axios.get('/trips')
    const trips = tripRes?.data?.data || []

    const myTrips = trips.filter(trip => trip.driver_id === driver.value.id)

    totalTrips.value = myTrips.length
    recentTrip.value = myTrips.length > 0
      ? myTrips.sort((a, b) => new Date(b.end_time) - new Date(a.end_time))[0]
      : null
  } catch (error) {
    console.error('Error loading driver dashboard:', error)
  }
})

</script>
