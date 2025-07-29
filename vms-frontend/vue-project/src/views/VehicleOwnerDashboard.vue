<template>
  <div class="p-6 max-w-5xl mx-auto">
    <div class="bg-white rounded-xl shadow p-6">
      <h2 class="text-2xl font-bold text-gray-800 mb-4">Welcome, {{ user.name }}</h2>
      <p class="text-gray-600 mb-6">Here’s a summary of your vehicles and related activities.</p>

      <!-- Vehicles owned by this user -->
      <div class="mb-8">
        <h3 class="text-xl font-semibold text-gray-700 mb-2">Your Vehicles</h3>
        <div v-if="vehicles.length" class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div
            v-for="vehicle in vehicles"
            :key="vehicle.id"
            class="border rounded p-4 bg-gray-50 hover:shadow transition"
          >
            <p class="font-semibold text-gray-800">{{ vehicle.plate_number }}</p>
            <p class="text-sm text-gray-600">Model: {{ vehicle.model }}</p>
            <p class="text-sm text-gray-600">Manufacturer: {{ vehicle.manufacturer }}</p>
            <p class="text-sm text-gray-600">Ownership: {{ vehicle.ownership_type }}</p>
          </div>
        </div>
        <p v-else class="text-gray-500 italic">You have no registered vehicles.</p>
      </div>

      <!-- View Profile -->
      <router-link
        to="/profile"
        class="inline-block px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded"
      >
        View Your Profile
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from '@/axios'

const user = ref({})
const vehicles = ref([])

onMounted(async () => {
  try {
    // Get logged-in user
    const res = await axios.get('/user')
    user.value = res.data

    // Fetch vehicles owned by the user
    const vehicleRes = await axios.get('/vehicles', {
      params: {
        owner: true // Assuming your API supports filtering like /vehicles?owner=true
      }
    })
    vehicles.value = vehicleRes.data.data || []
  } catch (error) {
    console.error('Error loading vehicle owner dashboard:', error)
  }
})
</script>
