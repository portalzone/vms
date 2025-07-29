<template>
  <div class="p-6 max-w-6xl mx-auto">
    <div class="mb-6">
      <h2 class="text-3xl font-bold text-gray-800">
        Welcome, {{ auth.user?.name }}
      </h2>
      <p class="text-gray-600 mt-1">You are viewing your registered vehicles.</p>
    </div>

    <div v-if="loading" class="text-center text-gray-500">
      Loading your vehicles...
    </div>

    <div v-else-if="vehicles.length">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-xl font-semibold text-gray-700">Your Vehicles ({{ vehicles.length }})</h3>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="vehicle in vehicles"
          :key="vehicle.id"
          class="bg-white rounded-lg shadow hover:shadow-md transition p-4 border"
        >
          <h3 class="text-lg font-semibold text-blue-700">
            {{ vehicle.plate_number || 'Plate N/A' }}
          </h3>
          <p class="text-sm text-gray-600 mt-1">
            {{ vehicle.manufacturer || 'Unknown' }} {{ vehicle.model || '' }}
            <span v-if="vehicle.year">({{ vehicle.year }})</span>
          </p>
          <p class="text-xs text-gray-500 mt-2">
            Ownership Type: <span class="font-medium">{{ vehicle.ownership_type || 'N/A' }}</span>
          </p>
        </div>
      </div>
    </div>

    <p v-else class="text-gray-500">You haven’t registered any vehicles yet.</p>

    <div v-if="error" class="mt-6 text-red-500">
      ⚠️ {{ error }}
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from '@/axios'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const vehicles = ref([])
const loading = ref(true)
const error = ref('')

onMounted(async () => {
  try {
    const res = await axios.get('/vehicles/mine')
    vehicles.value = res.data
  } catch (err) {
    console.error('Error loading vehicles:', err)
    error.value = 'Failed to load your vehicles. Please try again later.'
  } finally {
    loading.value = false
  }
})
</script>
