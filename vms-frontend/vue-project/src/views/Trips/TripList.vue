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
            <th class="p-2 border">Duration</th>
            <th class="p-2 border">Start Time</th>
            <th class="p-2 border">End Time</th>
            <th class="p-2 border">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="trip in filteredTrips" :key="trip.id" class="hover:bg-gray-50">
            <td class="p-2 border">
              {{ trip.driver?.user?.name || 'N/A' }}
            </td>
            <td class="p-2 border">
              {{ trip.vehicle?.manufacturer || 'N/A' }} -
              {{ trip.vehicle?.model || 'N/A' }}
              ({{ trip.vehicle?.plate_number || 'N/A' }})
            </td>
            <td class="p-2 border">{{ trip.start_location || 'N/A' }}</td>
            <td class="p-2 border">{{ trip.end_location || 'N/A' }}</td>
            <td class="p-2 border">
              {{ calculateDuration(trip.start_time, trip.end_time) }}
            </td>
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

<!-- Pagination -->
<div class="mt-4 flex justify-center items-center space-x-2 flex-wrap text-sm">
  <button
    @click="prevPage"
    :disabled="!pagination.prev_page_url"
    class="btn-pagination"
  >
    Previous
  </button>

  <button
    v-for="p in pages"
    :key="p"
    @click="fetchTrips(`/trips?page=${p}`)"
    :class="[
      'btn-pagination',
      { 'bg-blue-600 text-white': p === pagination.current_page }
    ]"
  >
    {{ p }}
  </button>

  <button
    @click="nextPage"
    :disabled="!pagination.next_page_url"
    class="btn-pagination"
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

const pages = computed(() => {
  const total = pagination.value.last_page || 1
  const current = pagination.value.current_page || 1
  const maxVisible = 5
  const pagesToShow = []

  let start = Math.max(1, current - Math.floor(maxVisible / 2))
  let end = Math.min(start + maxVisible - 1, total)

  if (end - start < maxVisible - 1) {
    start = Math.max(1, end - maxVisible + 1)
  }

  for (let i = start; i <= end; i++) {
    pagesToShow.push(i)
  }

  return pagesToShow
})


// Fetch trips from API
const fetchTrips = async (url = '/trips') => {
  try {
    loading.value = true
    const res = await axios.get(url)

    // Laravel pagination structure (not using resource collection)
    trips.value = res.data.data || []
pagination.value = {
  current_page: res.data.current_page,
  next_page_url: res.data.next_page_url,
  prev_page_url: res.data.prev_page_url,
  last_page: res.data.last_page
}

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

const calculateDuration = (start, end) => {
  if (!start || !end) return 'N/A'

  const startTime = new Date(start)
  const endTime = new Date(end)

  const diffMs = endTime - startTime
  if (isNaN(diffMs) || diffMs < 0) return 'Invalid'

  const totalMinutes = Math.floor(diffMs / (1000 * 60))
  const hours = Math.floor(totalMinutes / 60)
  const minutes = totalMinutes % 60

  return `${hours}h ${minutes}m`
}


// Initial load
onMounted(fetchTrips)
</script>
