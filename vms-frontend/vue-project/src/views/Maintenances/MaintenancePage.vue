<template>
  <div>
    <!-- Header + Search + Add Button -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
      <input
        v-model="search"
        type="text"
        placeholder="Search by description or status..."
        class="border rounded px-4 py-2 w-full md:w-1/2"
      />

      <router-link
        to="/maintenance/new"
        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-center"
      >
        ➕ Add Maintenance
      </router-link>
    </div>

    <!-- Maintenance Table -->
    <div class="overflow-x-auto rounded shadow bg-white">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Vehicle</th>
            <th>Description</th>
            <th>Status</th>
            <th class="text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(m, index) in paginatedMaintenances" :key="m.id">
            <td>{{ start + index + 1 }}</td>
            <td>{{ m.vehicle?.plate_number ?? '—' }}</td>
            <td>{{ m.description }}</td>
            <td>{{ m.status }}</td>
            <td class="text-right space-x-2">
              <button class="text-blue-600 hover:underline" @click="edit(m.id)">Edit</button>
              <button class="text-red-600 hover:underline" @click="remove(m.id)">Delete</button>
            </td>
          </tr>
          <tr v-if="paginatedMaintenances.length === 0">
            <td colspan="5" class="text-center text-gray-500 py-4">
              No maintenance records found.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6 flex justify-center items-center gap-2 flex-wrap text-sm">
      <button
        :disabled="page === 1"
        @click="page--"
        class="px-3 py-1 rounded border bg-white hover:bg-gray-100 disabled:opacity-50"
      >
        Prev
      </button>

      <button
        v-for="p in visiblePages"
        :key="p"
        @click="typeof p === 'number' && (page = p)"
        class="px-3 py-1 rounded border"
        :class="{
          'bg-blue-600 text-white': p === page,
          'bg-white hover:bg-gray-100': typeof p === 'number' && p !== page,
          'pointer-events-none text-gray-500': p === '...'
        }"
        :disabled="p === '...'"
      >
        {{ p }}
      </button>

      <button
        :disabled="page === totalPages"
        @click="page++"
        class="px-3 py-1 rounded border bg-white hover:bg-gray-100 disabled:opacity-50"
      >
        Next
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import axios from '@/axios'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

const allMaintenances = ref([])
const search = ref('')
const page = ref(1)
const perPage = 10

// ✅ Secure fetch with error handling
const fetchMaintenances = async () => {
  if (!auth.token) return // Skip if no token

  try {
    const res = await axios.get('/maintenances')
    allMaintenances.value = Array.isArray(res.data) ? res.data : res.data.data || []
  } catch (err) {
    console.error('❌ Error fetching maintenances:', err.response?.data || err.message)
    alert('Unauthorized or failed to load maintenances. Please log in again.')
  }
}

const filteredMaintenances = computed(() => {
  const keyword = search.value.toLowerCase()
  return allMaintenances.value.filter(m =>
    (m.description || '').toLowerCase().includes(keyword) ||
    (m.status || '').toLowerCase().includes(keyword) ||
    (m.vehicle?.plate_number || '').toLowerCase().includes(keyword)
  )
})

const start = computed(() => (page.value - 1) * perPage)
const paginatedMaintenances = computed(() =>
  filteredMaintenances.value.slice(start.value, start.value + perPage)
)

const totalPages = computed(() =>
  Math.max(1, Math.ceil(filteredMaintenances.value.length / perPage))
)

const visiblePages = computed(() => {
  const total = totalPages.value
  const current = page.value
  const pages = []

  if (total <= 6) {
    for (let i = 1; i <= total; i++) pages.push(i)
  } else {
    pages.push(1)
    if (current > 4) pages.push('...')
    for (let i = Math.max(2, current - 1); i <= Math.min(total - 1, current + 1); i++) {
      pages.push(i)
    }
    if (current < total - 3) pages.push('...')
    pages.push(total)
  }

  return pages
})

watch(search, () => (page.value = 1))
watch(page, fetchMaintenances)
onMounted(fetchMaintenances)

const edit = (id) => router.push(`/maintenance/${id}/edit`)
const remove = async (id) => {
  if (confirm('Are you sure you want to delete this maintenance record?')) {
    try {
      await axios.delete(`/maintenances/${id}`)
      await fetchMaintenances()
    } catch (err) {
      console.error('❌ Delete failed:', err.response?.data || err.message)
      alert('Failed to delete record.')
    }
  }
}
</script>
