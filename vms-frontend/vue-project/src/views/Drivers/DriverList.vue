<template>
  <div>
    <!-- Search & Add -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
      <input
        v-model="search"
        type="text"
        placeholder="Search by name, email, license or vehicle..."
        class="border rounded px-4 py-2 w-full md:w-1/2"
      />

      <router-link
        to="/drivers/new"
        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-center"
      >
        ➕ Add Driver
      </router-link>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto rounded shadow bg-white">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>User</th>
            <th>Email</th>
            <th>License No.</th>
            <th>Phone</th>
            <th>Address</th>
            <th>Sex</th>
            <th>Vehicle</th>
            <th>Date Added</th>
            <th class="text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(driver, index) in paginatedDrivers" :key="driver.id">
            <td>{{ start + index + 1 }}</td>
            <td>{{ driver.user?.name || '—' }}</td>
            <td>{{ driver.user?.email || '—' }}</td>
            
            <td>{{ driver.license_number }}</td>
            <td>{{ driver.phone_number }}</td>
            <td>{{ driver.home_address }}</td>
            <td>{{ driver.sex }}</td>
            <td>
              <div v-if="driver.vehicle">
                <div>{{ driver.vehicle.plate_number }}</div>
                <div class="text-xs text-gray-500">
                  {{ driver.vehicle.manufacturer }} {{ driver.vehicle.model }}
                </div>
              </div>
              <div v-else>—</div>
            </td>
            <td>{{ new Date(driver.created_at).toLocaleDateString() }}</td>
            <td class="text-right space-x-2">
              <button class="text-blue-600 hover:underline" @click="edit(driver.id)">Edit</button>
              <button class="text-red-600 hover:underline" @click="remove(driver.id)">Delete</button>
            </td>
          </tr>

          <tr v-if="paginatedDrivers.length === 0">
            <td colspan="10" class="text-center text-gray-500 py-4">No drivers found.</td>
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
        :key="`page-${p}`"
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
import { ref, watch, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import axios from '@/axios'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

const allDrivers = ref([])
const search = ref('')
const page = ref(1)
const perPage = 10

const fetchDrivers = async () => {
  if (!auth.token) return
  try {
    const res = await axios.get('/drivers')
    allDrivers.value = Array.isArray(res.data) ? res.data : res.data.data || []
  } catch (err) {
    console.error('❌ Error fetching drivers:', err)
    alert('Failed to load drivers. Please log in again.')
  }
}

const filteredDrivers = computed(() => {
  const keyword = search.value.toLowerCase()
  return allDrivers.value.filter(d =>
    d.user?.name?.toLowerCase().includes(keyword) ||
    d.user?.email?.toLowerCase().includes(keyword) ||
    d.license_number?.toLowerCase().includes(keyword) ||
    d.phone_number?.toLowerCase().includes(keyword) ||
    d.vehicle?.plate_number?.toLowerCase().includes(keyword) ||
    d.vehicle?.manufacturer?.toLowerCase().includes(keyword) ||
    d.vehicle?.model?.toLowerCase().includes(keyword)
  )
})

const start = computed(() => (page.value - 1) * perPage)
const paginatedDrivers = computed(() =>
  filteredDrivers.value.slice(start.value, start.value + perPage)
)

const totalPages = computed(() =>
  Math.max(1, Math.ceil(filteredDrivers.value.length / perPage))
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
    const start = Math.max(2, current - 1)
    const end = Math.min(total - 1, current + 1)
    for (let i = start; i <= end; i++) {
      if (i !== 1 && i !== total) pages.push(i)
    }
    if (current < total - 3) pages.push('...')
    pages.push(total)
  }

  return pages
})

const edit = (id) => router.push(`/drivers/${id}/edit`)
const remove = async (id) => {
  if (confirm('Are you sure you want to delete this driver?')) {
    try {
      await axios.delete(`/drivers/${id}`)
      await fetchDrivers()
    } catch (err) {
      console.error('❌ Failed to delete driver:', err)
      alert('Unable to delete driver.')
    }
  }
}

watch(search, () => {
  page.value = 1
})

onMounted(fetchDrivers)
watch(page, fetchDrivers)
</script>
