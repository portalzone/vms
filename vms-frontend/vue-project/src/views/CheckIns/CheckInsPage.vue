<template>
  <div>
    <div class="flex justify-between items-center mb-4">
      <input
        v-model="search"
        type="text"
        placeholder="Search by vehicle or driver..."
        class="border px-4 py-2 rounded w-full md:w-1/2"
      />

      <router-link
        to="/checkins/new"
        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
      >
        ➕ New Check-In
      </router-link>
    </div>

    <div class="overflow-x-auto bg-white rounded shadow">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Vehicle</th>
            <th>Driver</th>
            <th>Checked In</th>
            <th>Checked Out</th>
            <th class="text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(check, index) in paginatedItems" :key="check.id">
            <td>{{ start + index + 1 }}</td>
            <td>{{ check.vehicle?.plate_number || '—' }}</td>
            <td>{{ check.driver?.name || '—' }}</td>
            <td>{{ check.checked_in_at || '—' }}</td>
            <td>{{ check.checked_out_at || '—' }}</td>
            <td class="text-right">
              <router-link :to="`/checkins/${check.id}/edit`" class="text-blue-600 hover:underline">Edit</router-link>
            </td>
          </tr>
          <tr v-if="paginatedItems.length === 0">
            <td colspan="6" class="text-center text-gray-500 py-4">No check-ins found.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="flex justify-center gap-2 mt-4 text-sm">
      <button :disabled="page === 1" @click="page--" class="px-3 py-1 border rounded">Prev</button>
      <span>Page {{ page }} of {{ totalPages }}</span>
      <button :disabled="page === totalPages" @click="page++" class="px-3 py-1 border rounded">Next</button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from '@/axios'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

const allCheckIns = ref([])
const search = ref('')
const page = ref(1)
const perPage = 10

// ✅ Only fetch check-ins if logged in
const fetchCheckIns = async () => {
  if (!auth.token) return
  try {
    const res = await axios.get('/checkins')
    allCheckIns.value = Array.isArray(res.data) ? res.data : res.data.data || []
  } catch (err) {
    console.error('❌ Error fetching check-ins:', err.response?.data || err.message)
    alert('Failed to load check-ins. Please log in again.')
  }
}

const filteredItems = computed(() => {
  const keyword = search.value.toLowerCase()
  return allCheckIns.value.filter(item =>
    (item.vehicle?.plate_number || '').toLowerCase().includes(keyword) ||
    (item.driver?.name || '').toLowerCase().includes(keyword)
  )
})

const start = computed(() => (page.value - 1) * perPage)
const totalPages = computed(() => Math.ceil(filteredItems.value.length / perPage))
const paginatedItems = computed(() =>
  filteredItems.value.slice(start.value, start.value + perPage)
)

onMounted(fetchCheckIns)
</script>
