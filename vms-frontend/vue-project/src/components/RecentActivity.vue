<template>
  <div class="mt-10">
    <h3 class="text-lg font-bold mb-4">Recent Activity</h3>

    <!-- Filters -->
    <div class="flex flex-wrap gap-4 mb-6 items-end">
      <input
        v-model="search"
        type="text"
        placeholder="Search..."
        class="border px-3 py-2 rounded w-full md:w-1/4"
      />

      <select v-model="type" class="border px-3 py-2 rounded w-full md:w-1/5">
        <option value="">All Types</option>
        <option value="Check-In">Check-In</option>
        <option value="Maintenance">Maintenance</option>
        <option value="Trip">Trip</option>
        <option value="Vehicle Registered">Vehicle Registered</option>
        <option value="Driver Registered">Driver Registered</option>
        <option value="Expense">Expense</option>
      </select>

      <input
        v-model="fromDate"
        type="date"
        class="border px-3 py-2 rounded"
        placeholder="From"
      />

      <input
        v-model="toDate"
        type="date"
        class="border px-3 py-2 rounded"
        placeholder="To"
      />
    </div>

    <!-- List -->
<ul class="space-y-4">
  <li
    v-for="(activity, index) in activities"
    :key="index"
    class="flex items-start gap-4 p-4 bg-white rounded shadow"
  >
    <!-- Serial Number -->
    <div class="font-semibold w-6 text-right">{{ (currentPage - 1) * perPage + index + 1 }}.</div>

    <!-- Icon -->
    <span
      class="inline-flex items-center justify-center w-8 h-8 rounded-full text-white"
      :class="{
        'bg-blue-500': activity.type === 'Check-In' || activity.type === 'Vehicle Registered',
        'bg-red-500': activity.type === 'Maintenance',
        'bg-yellow-500': activity.type === 'Expense',
        'bg-gray-500': activity.type === 'Trip',
        'bg-green-500': activity.type === 'Driver Registered'
      }"
    >
      <template v-if="activity.type === 'Check-In'">🚗</template>
      <template v-else-if="activity.type === 'Maintenance'">🛠️</template>
      <template v-else-if="activity.type === 'Vehicle Registered'">🚙</template>
      <template v-else-if="activity.type === 'Driver Registered'">👨‍✈️</template>
      <template v-else-if="activity.type === 'Trip'">🧭</template>
      <template v-else-if="activity.type === 'Expense'">💸</template>
      <template v-else>📌</template>
    </span>

    <!-- Details -->
    <div>
      <p class="text-sm text-gray-800 leading-5">
        <span class="font-semibold">{{ activity.type }}:</span>
        {{ activity.message }}
      </p>
      <p class="text-xs text-gray-500 mt-1">
        {{ new Date(activity.time).toLocaleString() }}
      </p>
      <hr />
    </div>
  </li>
</ul>

    <!-- Pagination Controls -->
    <div class="mt-6 flex justify-center items-center gap-2 flex-wrap text-sm">
      <button
        :disabled="currentPage === 1"
        @click="currentPage--"
        class="btn-pagination"
      >
        Prev
      </button>

      <button
        v-for="p in visiblePages"
        :key="`page-${p}`"
        @click="typeof p === 'number' && (currentPage = p)"
        :class="[
          'btn-pagination',
          {
            'bg-blue-600 text-white': p === currentPage,
            'pointer-events-none text-gray-500': p === '...'
          }
        ]"
        :disabled="p === '...'"
      >
        {{ p }}
      </button>

      <button
        :disabled="currentPage === totalPages"
        @click="currentPage++"
        class="btn-pagination"
      >
        Next
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import axios from '@/axios'

const activities = ref([])
const currentPage = ref(1)
const totalPages = ref(1)
const perPage = 10

// Filters
const search = ref('')
const type = ref('')
const fromDate = ref('')
const toDate = ref('')

const fetchActivities = async () => {
  try {
    const res = await axios.get('/dashboard/recent', {
      params: {
        page: currentPage.value,
        per_page: perPage,
        search: search.value,
        type: type.value,
        from: fromDate.value,
        to: toDate.value
      }
    })
    activities.value = res.data.data
    totalPages.value = res.data.last_page
  } catch (err) {
    console.error('Failed to fetch recent activity:', err)
  }
}

onMounted(fetchActivities)

watch([search, type, fromDate, toDate], () => {
  currentPage.value = 1
  fetchActivities()
})

watch(currentPage, fetchActivities)

const visiblePages = computed(() => {
  const total = totalPages.value
  const current = currentPage.value
  const pages = []

  if (total <= 6) {
    for (let i = 1; i <= total; i++) pages.push(i)
  } else {
    pages.push(1)
    if (current > 4) pages.push('...')
    const start = Math.max(2, current - 1)
    const end = Math.min(total - 1, current + 1)
    for (let i = start; i <= end; i++) pages.push(i)
    if (current < total - 3) pages.push('...')
    pages.push(total)
  }

  return pages
})
</script>

<style scoped>
/* Header */
h3 {
  font-weight: 600;
  font-size: 1.125rem;
  margin-bottom: 1rem;
}

/* Filter inputs */
input[type='text'],
input[type='date'],
select {
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
}

input:focus,
select:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 1px #3b82f6;
}

/* Activity icons */
.inline-flex {
  font-size: 1rem;
  font-weight: 600;
  border-radius: 9999px;
  width: 2rem;
  height: 2rem;
  text-align: center;
  line-height: 2rem;
}

/* Activity item styling */
ul li {
  transition: background-color 0.3s ease;
}

ul li:hover {
  background-color: #f9fafb;
}

ul li hr {
  margin-top: 0.5rem;
  border: none;
  border-top: 1px solid #e5e7eb;
}



.text-sm {
  font-size: 0.875rem;
}
</style>
