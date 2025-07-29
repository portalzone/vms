<template>
  <ModalNotification ref="modalRef" />

  <div>
    <!-- Search + Add -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
      <input
        v-model="search"
        type="text"
        placeholder="Search by manufacturer, model, or plate..."
        class="border border-gray-300 rounded px-4 py-2 w-full md:w-1/2"
      />
      <router-link to="/vehicles/new" class="btn-primary text-center">
        ➕ Add Vehicle
      </router-link>
    </div>
<!-- Filter by Owner -->
<div class="mb-4 flex gap-4 items-center">
  <label class="font-medium">Filter by:</label>

  <!-- Ownership type -->
  <select v-model="selectedOwnership" class="border border-gray-300 rounded px-3 py-2">
    <option value="">All Ownership</option>
    <option value="individual">Individual</option>
    <option value="organization">Organization</option>
  </select>

  <!-- Vehicle owner (only for individual) -->
  <select
    v-if="selectedOwnership === 'individual'"
    v-model="selectedOwnerId"
    class="border border-gray-300 rounded px-3 py-2"
  >
    <option value="">All Owners</option>
    <option
      v-for="owner in vehicleOwners"
      :key="owner.id"
      :value="owner.id"
    >
      {{ owner.name }}
    </option>
  </select>

  <!-- Filter by Driver -->
  <select
    v-model="selectedDriverId"
    class="border border-gray-300 rounded px-3 py-2"
  >
    <option value="">All Drivers</option>
    <option
      v-for="driver in drivers"
      :key="driver.id"
      :value="driver.id"
    >
      {{ driver.user?.name || `Driver #${driver.id}` }}
    </option>
  </select>
</div>

    <!-- Sort Dropdown -->
    <div class="mb-4">
      <label for="sort" class="mr-2 font-medium">Sort by:</label>
      <select v-model="sortBy" id="sort" class="border border-gray-300 rounded px-3 py-2">
        <option value="newest">Newest</option>
        <option value="oldest">Oldest</option>
        <option value="manufacturer-asc">Manufacturer A–Z</option>
        <option value="manufacturer-desc">Manufacturer Z–A</option>
      </select>
    </div>
    <div class="flex flex-col md:flex-row gap-4 items-center mb-4">
  <input
    type="number"
    v-model="searchId"
    placeholder="Search Vehicle by ID"
    class="border border-gray-300 rounded px-4 py-2 w-full md:w-1/3"
  />
  <button @click="searchById(searchId)" class="btn-primary">Search by ID</button>
</div>

    <button @click="window.$toast?.showToast('Test toast!')">Test Toast</button>

    <!-- Table -->
    <div class="overflow-x-auto rounded shadow bg-white">
      <table class="w-full table-auto text-sm">
        <thead class="bg-gray-100 text-left">
          <tr>
            <th class="px-4 py-2">#</th>
            <th class="px-4 py-2">Manufacturer</th>
            <th class="px-4 py-2">Model</th>
            <th class="px-4 py-2">Year</th>
            <th class="px-4 py-2">Plate No.</th>
            <th class="px-4 py-2">Ownership</th>
            <th class="px-4 py-2">Created By</th>
            <th class="px-4 py-2">Created Time</th>
            <th class="px-4 py-2">Last Edited By</th>
            <th class="px-4 py-2">Last Edited Time</th>
            <th class="px-4 py-2 text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="(vehicle, index) in paginatedVehicles"
            :key="vehicle.id"
            class="hover:bg-gray-50 even:bg-gray-50"
          >
            <td class="px-4 py-2">{{ start + index + 1 }}</td>
            <td class="px-4 py-2">{{ vehicle.manufacturer }}</td>
            <td class="px-4 py-2">{{ vehicle.model }}</td>
            <td class="px-4 py-2">{{ vehicle.year }}</td>
            <td class="px-4 py-2">{{ vehicle.plate_number }}</td>
<td class="px-4 py-2">
  <template v-if="vehicle.ownership_type === 'individual'">
    Owner: {{ vehicle.owner?.name || `User #${vehicle.owner_id}` }}
  </template>
  <template v-else>
    Organization
  </template>
</td>

            <td class="px-4 py-2">{{ vehicle.creator?.name ?? 'N/A' }}</td>
            <td class="px-4 py-2">{{ formatDate(vehicle.created_at) }}</td>
            <td class="px-4 py-2">{{ vehicle.editor?.name ?? 'N/A' }}</td>
            <td class="px-4 py-2">{{ formatDate(vehicle.updated_at) }}</td>
            <td class="px-4 py-2 text-right space-x-2">
              <button class="btn-edit" @click="edit(vehicle.id)">Edit</button>
              <button class="btn-delete" @click="remove(vehicle.id)">Delete</button>
            </td>
          </tr>
          <tr v-if="paginatedVehicles.length === 0">
            <td colspan="10" class="text-center text-gray-500 py-4">No vehicles found.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6 flex justify-center items-center gap-2 flex-wrap text-sm">
      <button :disabled="page === 1" @click="page--" class="btn-pagination">Prev</button>

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

      <button :disabled="page === totalPages" @click="page++" class="btn-pagination">Next</button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axios from '@/axios'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

import ModalNotification from '@/components/ModalNotification.vue' // adjust path if different
const modalRef = ref(null)


const router = useRouter()
const auth = useAuthStore()

const allVehicles = ref([])
const search = ref('')
const searchId = ref('')
const sortBy = ref('newest')
const page = ref(1)
const perPage = 20

const selectedOwnership = ref('')
const selectedOwnerId = ref('')
const selectedDriverId = ref('')
const vehicleOwners = ref([])
const drivers = ref([])


const formatDate = (dateStr) => {
  if (!dateStr) return 'N/A'
  return new Date(dateStr).toLocaleString()
}

const fetchVehicles = async () => {
  if (!auth.token) return
 try {
  const res = await axios.get('/vehicles')
  allVehicles.value = Array.isArray(res.data) ? res.data : res.data.data || []
} catch (err) {
  if (err.response?.status === 403) {
    modalRef.value?.show('You are not authorized to view vehicles.', 'Access Denied')
    router.push('/') // or logout or redirect
  } else {
    console.error('❌ Failed to fetch vehicles:', err)
    alert('Error loading vehicles. Please try again.')
  }
}

}

// Filter by search input
const filteredVehicles = computed(() => {
  const term = search.value.toLowerCase()

  return allVehicles.value.filter(v => {
    const matchesSearch = 
      v.manufacturer?.toLowerCase().includes(term) ||
      v.model?.toLowerCase().includes(term) ||
      v.plate_number?.toLowerCase().includes(term)

    const matchesOwnership = !selectedOwnership.value || v.ownership_type === selectedOwnership.value
    const matchesOwner = !selectedOwnerId.value || v.owner_id == selectedOwnerId.value
    const matchesDriver = !selectedDriverId.value || v.driver?.id == selectedDriverId.value

    return matchesSearch && matchesOwnership && matchesOwner && matchesDriver
  })
})


// Sort after filtering
const sortedVehicles = computed(() => {
  const vehicles = [...filteredVehicles.value]
  switch (sortBy.value) {
    case 'newest':
      return vehicles.sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
    case 'oldest':
      return vehicles.sort((a, b) => new Date(a.created_at) - new Date(b.created_at))
    case 'manufacturer-asc':
      return vehicles.sort((a, b) => a.manufacturer?.localeCompare(b.manufacturer))
    case 'manufacturer-desc':
      return vehicles.sort((a, b) => b.manufacturer?.localeCompare(a.manufacturer))
    default:
      return vehicles
  }
})

// Pagination
const start = computed(() => (page.value - 1) * perPage)
const paginatedVehicles = computed(() =>
  sortedVehicles.value.slice(start.value, start.value + perPage)
)

const totalPages = computed(() =>
  Math.max(1, Math.ceil(sortedVehicles.value.length / perPage))
)

watch([search, sortBy], () => (page.value = 1))
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

const searchById = async (id) => {
  if (!id) {
    modalRef.value?.show('Please enter a vehicle ID.', 'Missing Input')
    return
  }

  try {
    const res = await axios.get(`/vehicles/${id}`)
    const vehicle = res.data

    const message = `
<h2 class="font-bold text-lg mb-2">🚗 Vehicle Found</h2>
<p>Manufacturer: ${vehicle.manufacturer}</p>
<p>Model: ${vehicle.model}</p>
<p>Plate Number: ${vehicle.plate_number}</p>
<p>Year: ${vehicle.year}</p>
<p>Created By: ${vehicle.creator?.name ?? 'N/A'}</p>
<p>Created Time: ${formatDate(vehicle.created_at)}</p>
<p>Last Edited By: ${vehicle.editor?.name ?? 'N/A'}</p>
<p>Last Edited Time: ${formatDate(vehicle.updated_at)}</p>

<p><a href="/vehicles/${vehicle.id}/edit" class="text-blue-500 underline mt-2 inline-block">✏️ Edit Vehicle Info</a></p>
    `

    modalRef.value?.show(message, 'Vehicle Info')
    return vehicle
  } catch (err) {
    modalRef.value?.show('❌ Vehicle not found or error occurred.', 'Error')
    console.error(err)
    return null
  }
}


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

const fetchVehicleOwners = async () => {
  try {
    const res = await axios.get('/users?role=vehicle_owner')
    vehicleOwners.value = res.data
  } catch (err) {
    console.error('Failed to fetch vehicle owners:', err)
  }
}

const fetchDrivers = async () => {
  try {
    const res = await axios.get('/drivers?include=user')
    drivers.value = res.data
  } catch (err) {
    console.error('Failed to fetch drivers:', err)
  }
}


onMounted(async () => {
  await fetchVehicles()
  await fetchVehicleOwners()
  await fetchDrivers()
})

</script>
