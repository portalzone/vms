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

<select
  v-model="type"
  class="border px-3 py-2 rounded w-full md:w-1/5"
>
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

        <div>
          <p class="text-sm text-gray-800 leading-5">
            <span class="font-semibold">{{ activity.type }}:</span>
            {{ activity.message }}
          </p>
          <p class="text-xs text-gray-500 mt-1">
            {{ new Date(activity.time).toLocaleString() }}
          </p>
          <hr>
        </div>
      </li>
    </ul>

    <!-- Pagination Controls -->
    <div class="flex justify-between mt-6">
      <button
        @click="prevPage"
        :disabled="currentPage === 1"
        class="px-4 py-2 bg-gray-200 text-gray-700 rounded disabled:opacity-50"
      >
        Previous
      </button>
      <span class="text-sm text-gray-600 self-center">
        Page {{ currentPage }} of {{ totalPages }}
      </span>
      <button
        @click="nextPage"
        :disabled="currentPage === totalPages"
        class="px-4 py-2 bg-blue-500 text-white rounded disabled:opacity-50"
      >
        Next
      </button>
    </div>
  </div>
</template>


<script setup>
import { ref, onMounted, watch } from 'vue'
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

const nextPage = () => {
  if (currentPage.value < totalPages.value) currentPage.value++
}

const prevPage = () => {
  if (currentPage.value > 1) currentPage.value--
}

watch(currentPage, fetchActivities)
</script>
