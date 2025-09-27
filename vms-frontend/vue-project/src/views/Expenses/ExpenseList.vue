<template>
  <div>
    <!-- Search + Filters -->
    <div class="flex flex-col gap-4 mb-4 md:flex-row md:items-center md:justify-between">
      <input
        v-model="search"
        type="text"
        placeholder="Search by vehicle, description, or amount..."
        class="w-full px-4 py-2 border border-gray-300 rounded md:w-1/2"
      />

      <div class="flex items-center gap-2">
        <select v-model="dateRange" class="px-3 py-2 text-sm border border-gray-300 rounded">
          <option value="all">All Time</option>
          <option value="1">Today</option>
          <option value="7">Last 7 Days</option>
          <option value="30">Last 30 Days</option>
        </select>

        <router-link
          v-if="['admin', 'manager'].includes(auth.user?.role)"
          to="/expenses/new"
          class="btn-primary"
        >
          ➕ Add Expense
        </router-link>
      </div>
    </div>

    <!-- Per Page Dropdown -->
    <div class="flex items-center mb-4 space-x-2">
      <label class="font-medium">Items per page:</label>
      <select v-model="perPage" class="px-3 py-1 border rounded">
        <option v-for="n in [5, 10, 20, 50, 100]" :key="n" :value="n">{{ n }}</option>
      </select>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto bg-white rounded shadow">
      <table class="w-full text-sm table-auto">
        <thead class="text-left bg-gray-100">
          <tr>
            <th class="px-4 py-2">#</th>
            <th class="px-4 py-2">Vehicle</th>
            <th class="px-4 py-2">Description</th>
            <th class="px-4 py-2">Amount (₦)</th>
            <th class="px-4 py-2">Date</th>
            <th class="px-4 py-2">Maintenance</th>
            <th class="px-4 py-2">Created By</th>
            <th class="px-4 py-2">Updated By</th>
            <th class="px-4 py-2">Created Time</th>
            <th class="px-4 py-2">Updated Time</th>
            <th v-if="hasRole(['admin', 'manager'])" class="px-4 py-2 text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="(e, index) in paginatedExpenses"
            :key="e.id"
            class="hover:bg-gray-50 even:bg-gray-50"
          >
            <td class="px-4 py-2">{{ start + index + 1 }}</td>
            <td class="px-4 py-2">{{ e.vehicle?.plate_number ?? '—' }}</td>
            <td class="px-4 py-2">{{ e.description }}</td>
            <td class="px-4 py-2">₦{{ e.amount?.toLocaleString() ?? '0.00' }}</td>
            <td class="px-4 py-2">{{ e.date }}</td>
            <td class="px-4 py-2">
              <router-link
                v-if="e.maintenance_id"
                :to="`/maintenance/${e.maintenance_id}/edit`"
                class="text-sm text-blue-600 hover:underline"
              >
                View
              </router-link>
              <span v-else class="text-gray-400">—</span>
            </td>
            <td class="px-4 py-2">{{ e.creator?.name ?? 'N/A' }}</td>
            <td class="px-4 py-2">{{ e.updater?.name ?? 'N/A' }}</td>
            <td class="px-4 py-2">{{ formatDate(e.created_at) }}</td>
            <td class="px-4 py-2">{{ formatDate(e.updated_at) }}</td>
            <td v-if="hasRole(['admin', 'manager'])" class="px-4 py-2 space-x-2 text-right">
              <button class="btn-edit" @click="edit(e.id)">Edit</button>
              <button class="btn-delete" @click="remove(e.id)">Delete</button>
            </td>
          </tr>
          <tr v-if="paginatedExpenses.length === 0">
            <td colspan="10" class="py-4 text-center text-gray-500">No expense records found.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="flex flex-wrap items-center justify-center gap-2 mt-6 text-sm">
      <button :disabled="page === 1" @click="page--" class="btn-pagination">Prev</button>
      <button
        v-for="p in visiblePages"
        :key="`page-${p}`"
        @click="typeof p === 'number' && (page = p)"
        :class="[
          'btn-pagination',
          {
            'bg-blue-600 text-white': p === page,
            'pointer-events-none text-gray-500': p === '...',
          },
        ]"
        :disabled="p === '...'"
      >
        {{ p }}
      </button>
      <button :disabled="page === totalPages" @click="page++" class="btn-pagination">Next</button>
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

function hasRole(allowedRoles) {
  return allowedRoles.includes(auth.user?.role)
}

const allExpenses = ref([])
const search = ref('')
const dateRange = ref('all')
const page = ref(1)
const perPage = ref(10)

const fetchExpenses = async () => {
  if (!auth.token) return
  try {
    const res = await axios.get('/expenses')
    allExpenses.value = Array.isArray(res.data) ? res.data : res.data.data || []
  } catch (err) {
    console.error('❌ Error fetching expenses:', err.response?.data || err.message)
    alert('Unauthorized or failed to load expenses. Please log in again.')
  }
}

const filteredExpenses = computed(() => {
  const keyword = search.value.toLowerCase()
  const days = parseInt(dateRange.value)
  const now = new Date()
  const cutoff = new Date(now.setDate(now.getDate() - days))

  return allExpenses.value.filter((e) => {
    const matchesSearch =
      (e.description || '').toLowerCase().includes(keyword) ||
      (e.vehicle?.plate_number || '').toLowerCase().includes(keyword) ||
      String(e.amount ?? '').includes(keyword)

    const matchesDate = dateRange.value === 'all' || new Date(e.date) >= cutoff

    return matchesSearch && matchesDate
  })
})

const start = computed(() => (page.value - 1) * perPage.value)
const paginatedExpenses = computed(() =>
  filteredExpenses.value.slice(start.value, start.value + perPage.value),
)

const totalPages = computed(() =>
  Math.max(1, Math.ceil(filteredExpenses.value.length / perPage.value)),
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

watch([search, dateRange, perPage], () => {
  page.value = 1
})

watch(page, fetchExpenses)
onMounted(fetchExpenses)

const formatDate = (dateStr) => {
  if (!dateStr) return 'N/A'
  return new Date(dateStr).toLocaleString()
}

const edit = (id) => router.push(`/expenses/${id}/edit`)
const remove = async (id) => {
  if (confirm('Are you sure you want to delete this expense record?')) {
    try {
      await axios.delete(`/expenses/${id}`)
      await fetchExpenses()
    } catch (err) {
      console.error('❌ Delete failed:', err.response?.data || err.message)
      alert('Failed to delete record.')
    }
  }
}
</script>
