<template>
  <div class="p-4">
    <div class="flex flex-col md:flex-row justify-between items-center mb-4 gap-4">
      <input
        v-model="search"
        type="text"
        placeholder="Search by location or vehicle..."
        class="border px-3 py-2 rounded w-full md:w-64"
      />
      <router-link
        to="/trips/create"
        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
      >
        + New Trip
      </router-link>
    </div>

    <div v-if="loading" class="text-center py-10">Loading...</div>

    <div v-else>
      <table v-if="filteredTrips.length" class="min-w-full table-auto border border-gray-300">
        <thead class="bg-gray-100">
          <tr>
            <th class="p-2 border">Driver</th>
            <th class="p-2 border">Vehicle</th>
            <th class="p-2 border">From</th>
            <th class="p-2 border">To</th>
            <th class="p-2 border">Start Time</th>
            <th class="p-2 border">End Time</th>
            <th class="p-2 border">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="trip in filteredTrips" :key="trip.id" class="hover:bg-gray-50">
            <td class="p-2 border">
              {{ trip.driver?.name || 'N/A' }}
            </td>
            <td class="p-2 border">
              {{ trip.vehicle?.manufacturer || 'N/A' }} -
              {{ trip.vehicle?.model || 'N/A' }}
              ({{ trip.vehicle?.plate_number || 'N/A' }})
            </td>
            <td class="p-2 border">{{ trip.start_location || 'N/A' }}</td>
            <td class="p-2 border">{{ trip.end_location || 'N/A' }}</td>
            <td class="p-2 border">{{ formatDate(trip.start_time) }}</td>
            <td class="p-2 border">{{ formatDate(trip.end_time) }}</td>
            <td class="p-2 border space-x-2">
              <router-link
                :to="`/trips/${trip.id}/edit`"
                class="text-blue-600 hover:underline"
              >
                Edit
              </router-link>
              <button
                @click="deleteTrip(trip.id)"
                class="text-red-600 hover:underline"
              >
                Delete
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-else class="text-center py-10 text-gray-500">No trips found.</div>

      <div class="mt-4 flex justify-center items-center space-x-4">
        <button
          @click="prevPage"
          :disabled="!pagination.prev_page_url"
          class="px-4 py-2 border rounded disabled:opacity-50"
        >
          Previous
        </button>
        <span class="px-4 py-2">Page {{ pagination.current_page || 1 }}</span>
        <button
          @click="nextPage"
          :disabled="!pagination.next_page_url"
          class="px-4 py-2 border rounded disabled:opacity-50"
        >
          Next
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import axios from '@/axios'

const trips = ref([])
const search = ref('')
const loading = ref(true)
const pagination = ref({
  current_page: 1,
  next_page_url: null,
  prev_page_url: null
})

// Fetch trips from API
const fetchTrips = async (url = '/trips') => {
  try {
    loading.value = true
    const res = await axios.get(url)
    trips.value = res.data?.data || []
    pagination.value = res.data?.meta || {}
  } catch (err) {
    console.error('Error loading trips:', err)
  } finally {
    loading.value = false
  }
}

// Computed filtered list based on search
const filteredTrips = computed(() => {
  if (!search.value.trim()) return trips.value
  const query = search.value.toLowerCase()
  return trips.value.filter(trip =>
    [trip.start_location, trip.end_location, trip.vehicle?.plate_number]
      .filter(Boolean)
      .some(field => String(field).toLowerCase().includes(query))
  )
})

// Delete a trip
const deleteTrip = async (id) => {
  if (confirm('Are you sure you want to delete this trip?')) {
    try {
      await axios.delete(`/trips/${id}`)
      fetchTrips(`/trips?page=${pagination.value.current_page}`)
    } catch (err) {
      console.error('Failed to delete trip:', err)
      alert('An error occurred while deleting the trip.')
    }
  }
}

// Pagination
const nextPage = () => {
  if (pagination.value.next_page_url) fetchTrips(pagination.value.next_page_url)
}
const prevPage = () => {
  if (pagination.value.prev_page_url) fetchTrips(pagination.value.prev_page_url)
}

// Format timestamp
const formatDate = (dateStr) => {
  if (!dateStr) return 'N/A'
  return new Date(dateStr).toLocaleString()
}

// Initial load
onMounted(fetchTrips)
</script>
