<template>
  <div>
    <div v-if="successMessage" class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded mb-4">
  {{ successMessage }}
</div>

    <!-- Search & Add -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
      <input
        v-model="search"
        type="text"
        placeholder="Search by name, email, license, or vehicle..."
        class="border border-gray-300 rounded px-4 py-2 w-full md:w-1/2"
      />

      <router-link
        to="/drivers/new"
        class="btn-primary text-center"
      >
        ➕ Add Driver
      </router-link>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto rounded shadow bg-white">
      <table class="min-w-full table-auto text-sm">
        <thead class="bg-gray-100 text-left">
          <tr>
            <th class="px-4 py-2">#</th>
            <th class="px-4 py-2">User</th>
            <th class="px-4 py-2">Email</th>
            <th class="px-4 py-2">License No.</th>
            <th class="px-4 py-2">Phone</th>
            <th class="px-4 py-2">Address</th>
            <th class="px-4 py-2">Sex</th>
            <th class="px-4 py-2">Vehicle</th>
            <th class="px-4 py-2">Date Added</th>
            <th class="px-4 py-2 text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(driver, index) in paginatedDrivers" :key="driver.id" class="hover:bg-gray-50 even:bg-gray-50">
            <td class="px-4 py-2">{{ start + index + 1 }}</td>
            <td class="px-4 py-2">{{ driver.user?.name || '—' }}</td>
            <td class="px-4 py-2">{{ driver.user?.email || '—' }}</td>
            <td class="px-4 py-2">{{ driver.license_number }}</td>
            <td class="px-4 py-2">{{ driver.phone_number }}</td>
            <td class="px-4 py-2">{{ driver.home_address }}</td>
            <td class="px-4 py-2">{{ driver.sex }}</td>
            <td class="px-4 py-2">
              <div v-if="driver.vehicle">
                <div>{{ driver.vehicle.plate_number }}</div>
                <div class="text-xs text-gray-500">
                  {{ driver.vehicle.manufacturer }} {{ driver.vehicle.model }}
                </div>
              </div>
              <div v-else>—</div>
            </td>
            <td class="px-4 py-2">{{ new Date(driver.created_at).toLocaleDateString() }}</td>
            <td class="px-4 py-2 text-right space-x-2">
              <button class="btn-edit" @click="edit(driver.id)">Edit</button>
              <button class="btn-delete" @click="remove(driver.id)">Delete</button>
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
        @click="page++"
        class="btn-pagination"
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

const successMessage = ref('')

function showSuccess(msg) {
  successMessage.value = msg
  setTimeout(() => {
    successMessage.value = ''
  }, 3000)
}


const router = useRouter()
const auth = useAuthStore()

const allDrivers = ref([])
const search = ref('')
const page = ref(1)
const perPage = 10

const fetchDrivers = async () => {
  try {
    const res = await axios.get('/drivers')
    allDrivers.value = Array.isArray(res.data) ? res.data : res.data.data || []
  } catch (err) {
    console.error('❌ Error fetching drivers:', err)
    alert('Failed to load drivers.')
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
    for (let i = start; i <= end; i++) pages.push(i)
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
      showSuccess('✅ Driver deleted successfully.')
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
</script>
