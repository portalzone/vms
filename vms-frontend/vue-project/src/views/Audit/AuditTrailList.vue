<template>
  <div class="p-4">
    <h2 class="text-2xl font-semibold mb-4">Audit Trail</h2>

    <!-- Search -->
    <div class="mb-4">
      <input
  v-model="search"
  @input="fetchLogs"
  type="text"
  placeholder="Search by log name or description"
  class="audit-search-input"
/>

<div class="flex flex-wrap gap-3 items-center mb-4">
  <input
    v-model="search"
    type="text"
    placeholder="Search logs..."
    class="border px-3 py-2 rounded w-full md:w-64"
  />

  <select v-model="selectedModule" class="border px-3 py-2 rounded">
    <option value="all">All Modules</option>
    <option v-for="name in moduleOptions" :key="name" :value="name">
      {{ name }}
    </option>
  </select>

  <select v-model="selectedTimeRange" class="border px-3 py-2 rounded">
    <option value="all">All Time</option>
    <option value="24h">Last 24 Hours</option>
    <option value="7d">Last 7 Days</option>
    <option value="30d">Last 30 Days</option>
    <option value="custom">Custom Range</option>
  </select>

  <div v-if="selectedTimeRange === 'custom'" class="flex gap-2">
    <input type="date" v-model="startDate" class="border px-2 py-1 rounded" />
    <input type="date" v-model="endDate" class="border px-2 py-1 rounded" />
  </div>
</div>


<table class="audit-table">
  <thead>
          <tr>
            <th class="p-3 border">Date</th>
            <th class="p-3 border">User</th>
            <th class="p-3 border">Action</th>
            <th class="p-3 border">Model</th>
            <th class="p-3 border text-right">View</th>
          </tr>
        </thead>
  <tbody>
    <tr
      v-for="log in logs.data"
      :key="log.id"
      class="audit-table-row"
    >
            <td class="p-3 border">{{ formatDate(log.created_at) }}</td>
            <td class="p-3 border">{{ log.causer?.name || 'System' }}</td>
            <td class="p-3 border">{{ log.description }}</td>
            <td class="p-3 border">{{ log.subject_type?.split('\\').pop() || '—' }}</td>
            <td class="text-right">
        <button @click="openModal(log)" class="btn-edit">👁 View</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6 flex justify-center items-center gap-2 flex-wrap text-sm">
      <button
        :disabled="page === 1"
        @click="page-- && fetchLogs()"
        class="btn-pagination"
      >
        Prev
      </button>

      <button
        v-for="p in visiblePages"
        :key="`page-${p}`"
        @click="typeof p === 'number' && (page = p, fetchLogs())"
        :class="[
          'btn-pagination',
          {
            'bg-blue-600 text-white': p === page,
            'pointer-events-none text-gray-500': p === '...'
          }
        ]"
        :disabled="p === '...'"
      >
        {{ p }}
      </button>

      <button
        :disabled="page === totalPages"
        @click="page++ && fetchLogs()"
        class="btn-pagination"
      >
        Next
      </button>
    </div>

    <!-- Modal -->
<div v-if="selectedLog" class="modal-overlay">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Audit Log Details</h3>
      <button @click="selectedLog = null" class="modal-close-btn">✖</button>
    </div>
    <div class="modal-body">
      <p><strong>User:</strong> {{ selectedLog.causer?.name || 'System' }}</p>
      <p><strong>Action:</strong> {{ selectedLog.description }}</p>
      <p><strong>Model:</strong> {{ selectedLog.subject_type?.split('\\').pop() || '—' }}</p>
      <p><strong>Date:</strong> {{ formatDate(selectedLog.created_at) }}</p>
      <div>
        <strong>Old Values:</strong>
        <pre>{{ formatJSON(selectedLog.properties?.old) }}</pre>
      </div>
      <div>
        <strong>New Values:</strong>
        <pre>{{ formatJSON(selectedLog.properties?.attributes) }}</pre>
      </div>
    </div>
  </div>
</div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axios from '@/axios'
import dayjs from 'dayjs'

const logs = ref({ data: [] })
const page = ref(1)
const search = ref('')
const selectedModule = ref('all')
const selectedTimeRange = ref('all')
const startDate = ref('')
const endDate = ref('')
const selectedLog = ref(null)
const moduleOptions = ref([])

const fetchLogs = async () => {
  try {
    const { data } = await axios.get('/audit-trail', {
      params: {
        page: page.value,
        search: search.value,
        log_name: selectedModule.value,
        time_range: selectedTimeRange.value,
        start_date: selectedTimeRange.value === 'custom' ? startDate.value : null,
        end_date: selectedTimeRange.value === 'custom' ? endDate.value : null
      }
    })

    logs.value = data

    // Extract module options dynamically from logs
    const modules = [...new Set(data.data.map(log => log.log_name))].filter(Boolean)
    moduleOptions.value = Array.from(new Set([...moduleOptions.value, ...modules]))
  } catch (error) {
    console.error('Failed to fetch logs:', error)
  }
}

const formatDate = (date) => {
  return date ? dayjs(date).format('MMM D, YYYY h:mm A') : '—'
}

const formatJSON = (obj) => {
  return obj ? JSON.stringify(obj, null, 2) : '{}'
}

const openModal = (log) => {
  selectedLog.value = log
}

const totalPages = computed(() => logs.value?.last_page || 1)

const visiblePages = computed(() => {
  const total = totalPages.value
  const current = page.value
  const delta = 2
  const pages = []

  for (let i = Math.max(1, current - delta); i <= Math.min(total, current + delta); i++) {
    pages.push(i)
  }

  if (pages[0] > 2) pages.unshift('...')
  if (pages[0] !== 1) pages.unshift(1)
  if (pages[pages.length - 1] < total - 1) pages.push('...')
  if (pages[pages.length - 1] !== total) pages.push(total)

  return pages
})

// Trigger fetch on mount & when filters change
onMounted(fetchLogs)

watch([search, selectedModule, selectedTimeRange, startDate, endDate], () => {
  page.value = 1 // Reset to page 1
  fetchLogs()
})

watch(page, fetchLogs)
</script>
