<template>
  <div>
    <!-- Search + Add -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
      <input
        v-model="search"
        type="text"
        placeholder="Search by manufacturer, model, or plate..."
        class="border rounded px-4 py-2 w-full md:w-1/2"
      />
      <router-link
        to="/vehicles/new"
        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-center"
      >
        ➕ Add Vehicle
      </router-link>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto rounded shadow bg-white">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Manufacturer</th>
            <th>Model</th>
            <th>Year</th>
            <th>Plate No.</th>
            <th class="text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(vehicle, index) in paginatedVehicles" :key="vehicle.id">
            <td>{{ start + index + 1 }}</td>
            <td>{{ vehicle.manufacturer }}</td>
            <td>{{ vehicle.model }}</td>
            <td>{{ vehicle.year }}</td>
            <td>{{ vehicle.plate_number }}</td>
            <td class="text-right space-x-2">
   <button class="btn-edit" @click="edit(vehicle.id)">Edit</button>
<button class="btn-delete" @click="remove(vehicle.id)">Delete</button>
          </td>
          </tr>
          <tr v-if="paginatedVehicles.length === 0">
            <td colspan="6" class="text-center text-gray-500 py-4">No vehicles found.</td>
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
import { ref, computed, onMounted, watch } from 'vue'
import axios from '@/axios'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

const allVehicles = ref([])
const search = ref('')
const page = ref(1)
const perPage = 10

const fetchVehicles = async () => {
  if (!auth.token) return
  try {
    const res = await axios.get('/vehicles')
    allVehicles.value = Array.isArray(res.data) ? res.data : res.data.data || []
  } catch (err) {
    console.error('❌ Failed to fetch vehicles:', err)
    alert('Error loading vehicles. Please log in again.')
  }
}

const filteredVehicles = computed(() => {
  const term = search.value.toLowerCase()
  return allVehicles.value.filter(v =>
    v.manufacturer?.toLowerCase().includes(term) ||
    v.model?.toLowerCase().includes(term) ||
    v.plate_number?.toLowerCase().includes(term)
  )
})

const start = computed(() => (page.value - 1) * perPage)
const paginatedVehicles = computed(() =>
  filteredVehicles.value.slice(start.value, start.value + perPage)
)
const totalPages = computed(() =>
  Math.max(1, Math.ceil(filteredVehicles.value.length / perPage))
)

watch(search, () => (page.value = 1))
watch(page, fetchVehicles)

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

const edit = (id) => router.push(`/vehicles/${id}/edit`)
const remove = async (id) => {
  if (confirm('Are you sure you want to delete this vehicle?')) {
    try {
      await axios.delete(`/vehicles/${id}`)
      await fetchVehicles()
    } catch (err) {
      console.error('❌ Failed to delete vehicle:', err)
      alert('Failed to delete vehicle. Please try again.')
    }
  }
}

onMounted(fetchVehicles)
</script>
