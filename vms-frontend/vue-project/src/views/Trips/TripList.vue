<template>
  <div class="p-4">
    <!-- Filters -->
    <div class="flex flex-col md:flex-row gap-4 mb-4 items-center justify-between">
      <input
        v-model="search"
        type="text"
        placeholder="Search by location or vehicle..."
        class="border px-3 py-2 rounded w-full md:w-64"
      />
            <router-link
        to="/trips/create"
        class="btn-primary"
      >
        + New Trip
      </router-link>

      <div class="flex gap-2 flex-wrap">
        <select v-model="selectedRange" class="border rounded px-2 py-1">
          <option value="all">All Time</option>
          <option value="24h">Last 24 Hours</option>
          <option value="7d">Last 7 Days</option>
          <option value="30d">Last 30 Days</option>
          <option value="3m">Last 3 Months</option>
          <option value="6m">Last 6 Months</option>
          <option value="1y">This Year</option>
        </select>
        <button
          @click="sortOrder = sortOrder === 'asc' ? 'desc' : 'asc'"
          class="btn-secondary"
        >
          Sort: {{ sortOrder === 'asc' ? 'Oldest First' : 'Newest First' }}
        </button>
      </div>

      <select v-model="selectedStatus" class="border rounded px-2 py-1">
  <option value="all">All Statuses</option>
  <option value="in_progress">In Progress</option>
  <option value="completed">Completed</option>
</select>



    </div>

    <!-- Table -->
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
            <th class="p-2 border">Status</th>
            <th class="p-2 border">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="trip in filteredTrips" :key="trip.id" class="hover:bg-gray-50">
            <td class="p-2 border">{{ trip.driver?.user?.name || 'N/A' }}</td>
            <td class="p-2 border">
              {{ trip.vehicle?.manufacturer || 'N/A' }} -
              {{ trip.vehicle?.model || 'N/A' }}
              ({{ trip.vehicle?.plate_number || 'N/A' }})
            </td>
            <td class="p-2 border">{{ trip.start_location }}</td>
            <td class="p-2 border">{{ trip.end_location }}</td>
            <td class="p-2 border">{{ calculateDuration(trip.start_time, trip.end_time) }}</td>
            <td class="p-2 border">{{ formatDate(trip.start_time) }}</td>
            <td class="p-2 border">{{ formatDate(trip.end_time) }}</td>
            <td class="p-2 border">
  <span
    :class="[
      'px-2 py-1 rounded-full text-xs font-semibold',
      trip.status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
    ]"
  >
    {{ trip.status === 'completed' ? '✅ Completed' : '🟡 In Progress' }}
  </span>
</td>

            <td class="p-2 border space-x-2">
              <router-link :to="`/trips/${trip.id}/edit`" class="text-blue-600 hover:underline">
                Edit
              </router-link>
              <button @click="deleteTrip(trip.id)" class="text-red-600 hover:underline">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-else class="text-center py-10 text-gray-500">No trips found.</div>

      <!-- Pagination -->
      <div class="mt-4 flex justify-center items-center space-x-2 flex-wrap text-sm">
        <button @click="prevPage" :disabled="!pagination.prev_page_url" class="btn-pagination">Previous</button>
        <button
          v-for="p in pages"
          :key="p"
          @click="fetchTrips(`/trips?page=${p}`)"
          :class="['btn-pagination', { 'bg-blue-600 text-white': p === pagination.current_page }]"
        >
          {{ p }}
        </button>
        <button @click="nextPage" :disabled="!pagination.next_page_url" class="btn-pagination">Next</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import dayjs from 'dayjs'
import axios from '@/axios'

const trips = ref([])
const search = ref('')
const loading = ref(true)
const pagination = ref({ current_page: 1, next_page_url: null, prev_page_url: null, last_page: 1 })
const selectedRange = ref('all')
const sortOrder = ref('desc')

const selectedStatus = ref('all')


const fetchTrips = async (url = '/trips') => {
  try {
    loading.value = true
    const res = await axios.get(url)
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

const filteredTrips = computed(() => {
  const now = dayjs()
  let filtered = [...trips.value]

  // Filter by search
  if (search.value.trim()) {
    const query = search.value.toLowerCase()
    filtered = filtered.filter(trip =>
      [trip.start_location, trip.end_location, trip.vehicle?.plate_number]
        .filter(Boolean)
        .some(val => val.toLowerCase().includes(query))
    )
  }

  // Filter by time range
  const ranges = {
    '24h': now.subtract(1, 'day'),
    '7d': now.subtract(7, 'day'),
    '30d': now.subtract(30, 'day'),
    '3m': now.subtract(3, 'month'),
    '6m': now.subtract(6, 'month'),
    '1y': now.startOf('year')
  }

  if (selectedRange.value !== 'all') {
    const from = ranges[selectedRange.value]
    filtered = filtered.filter(trip => dayjs(trip.start_time).isAfter(from))
  }

  // After time range filtering
if (selectedStatus.value !== 'all') {
  filtered = filtered.filter(trip => trip.status === selectedStatus.value)
}

  // Sort
  filtered.sort((a, b) => {
    const aTime = new Date(a.created_at).getTime()
    const bTime = new Date(b.created_at).getTime()
    return sortOrder.value === 'asc' ? aTime - bTime : bTime - aTime
  })

  return filtered
})

const formatDate = (str) => (str ? new Date(str).toLocaleString() : 'N/A')
const calculateDuration = (start, end) => {
  if (!start || !end) return 'N/A'
  const diff = new Date(end) - new Date(start)
  if (isNaN(diff) || diff < 0) return 'Invalid'
  const mins = Math.floor(diff / 60000)
  return `${Math.floor(mins / 60)}h ${mins % 60}m`
}

const deleteTrip = async (id) => {
  if (!confirm('Are you sure?')) return
  try {
    await axios.delete(`/trips/${id}`)
    fetchTrips(`/trips?page=${pagination.value.current_page}`)
  } catch (err) {
    console.error(err)
    alert('Failed to delete trip.')
  }
}

const pages = computed(() => {
  const total = pagination.value.last_page || 1
  const current = pagination.value.current_page || 1
  const visible = 5
  const range = []

  let start = Math.max(1, current - Math.floor(visible / 2))
  let end = Math.min(start + visible - 1, total)

  if (end - start < visible - 1) {
    start = Math.max(1, end - visible + 1)
  }

  for (let i = start; i <= end; i++) {
    range.push(i)
  }

  return range
})

const nextPage = () => {
  if (pagination.value.next_page_url) fetchTrips(pagination.value.next_page_url)
}
const prevPage = () => {
  if (pagination.value.prev_page_url) fetchTrips(pagination.value.prev_page_url)
}

onMounted(fetchTrips)
</script>
