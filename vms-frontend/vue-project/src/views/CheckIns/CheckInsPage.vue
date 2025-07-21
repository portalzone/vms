<template>
  <div>
    <!-- Search + Add -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
      <input
        v-model="search"
        type="text"
        placeholder="Search by vehicle or driver..."
        class="border border-gray-300 rounded px-4 py-2 w-full md:w-1/2"
      />
      <router-link to="/checkins/new" class="btn-primary text-center">
        ➕ New Check-In
      </router-link>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto rounded shadow bg-white">
      <table class="w-full table-auto text-sm">
        <thead class="bg-gray-100 text-left">
          <tr>
  <th class="px-4 py-2">#</th>
  <th class="px-4 py-2">Vehicle</th>
  <th class="px-4 py-2">Driver</th>
  <th class="px-4 py-2">Checked In</th>
  <th class="px-4 py-2">Checked Out</th>
  <th class="px-4 py-2">Checked In By</th>
  <th class="px-4 py-2">Checked Out By</th>
  <th class="px-4 py-2 text-right">Actions</th>
</tr>

        </thead>
        <tbody>
<tr
  v-for="(check, index) in checkIns"
  :key="check.id"
  class="hover:bg-gray-50 even:bg-gray-50"
>
  <td class="px-4 py-2">{{ (meta.current_page - 1) * meta.per_page + index + 1 }}</td>
  <td class="px-4 py-2">{{ check.vehicle?.plate_number || '—' }}</td>
  <td class="px-4 py-2">{{ check.driver?.user?.name || '—' }}</td>
  <td class="px-4 py-2">{{ formatDate(check.checked_in_at) || '—' }}</td>
  <td class="px-4 py-2">{{ formatDate(check.checked_out_at) || '—' }}</td>
  <td class="px-4 py-2">{{ check.checked_in_by_user?.name || '—' }}</td>
  <td class="px-4 py-2">{{ check.checked_out_by_user?.name || '—' }}</td>
  <td class="px-4 py-2 text-right">
    <button
      v-if="!check.checked_out_at"
      class="text-blue-600 hover:underline"
      @click="checkout(check.id)"
    >
      Checkout
    </button>
    <span v-else class="text-gray-400">—</span>
  </td>
</tr>

        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6 flex justify-center items-center gap-2 flex-wrap text-sm">
      <button
        :disabled="meta.current_page === 1"
        @click="page--"
        class="btn-pagination"
      >
        Prev
      </button>

      <button
        v-for="p in visiblePages"
        :key="`page-${p}`"
        @click="typeof p === 'number' && (page = p)"
        :class="[
          'btn-pagination',
          {
            'bg-blue-600 text-white': p === meta.current_page,
            'pointer-events-none text-gray-500': p === '...'
          }
        ]"
        :disabled="p === '...'"
      >
        {{ p }}
      </button>

      <button
        :disabled="meta.current_page === meta.last_page"
        @click="page++"
        class="btn-pagination"
      >
        Next
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import axios from '@/axios'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const checkIns = ref([])
const meta = ref({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0
})

// Format timestamp
const formatDate = (dateStr) => {
  if (!dateStr) return 'N/A'
  return new Date(dateStr).toLocaleString()
}

const search = ref('')
const page = ref(1)
const perPage = 10

const fetchCheckIns = async () => {
  if (!auth.token) return

  try {
    const res = await axios.get('/checkins', {
      params: {
        search: search.value,
        page: page.value,
        per_page: perPage,
      },
    })

    checkIns.value = res.data.data
    meta.value = {
      current_page: res.data.current_page,
      last_page: res.data.last_page,
      per_page: res.data.per_page,
      total: res.data.total,
    }
  } catch (err) {
    console.error('❌ Error fetching check-ins:', err.response?.data || err.message)
    alert('Failed to load check-ins.')
  }
}

const checkout = async (checkInId) => {
  if (!confirm('Are you sure you want to check out this vehicle?')) return

  try {
    await axios.post(`/checkins/${checkInId}/checkout`)
    alert('Checkout successful!')
    fetchCheckIns()
  } catch (err) {
    console.error('❌ Checkout error:', err.response?.data || err.message)
    alert(err.response?.data?.message || 'Checkout failed.')
  }
}

const visiblePages = computed(() => {
  const pages = []
  const total = meta.value.last_page
  const current = meta.value.current_page

  if (total <= 6) {
    for (let i = 1; i <= total; i++) pages.push(i)
  } else {
    pages.push(1)
    if (current > 4) pages.push('...')
    const start = Math.max(2, current - 1)
    const end = Math.min(total - 1, current + 1)
    for (let i = start; i <= end; i++) {
      pages.push(i)
    }
    if (current < total - 3) pages.push('...')
    pages.push(total)
  }

  return pages
})

watch([search, page], fetchCheckIns)
onMounted(fetchCheckIns)
</script>
