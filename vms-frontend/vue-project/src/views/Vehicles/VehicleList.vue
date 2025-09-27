<template>
  <div class="space-y-6">
    <!-- Filters -->
    <div class="flex flex-wrap gap-4">
      <input
        v-model="search"
        type="text"
        placeholder="Search by ID, manufacturer, model, plate..."
        class="w-full px-4 py-2 border rounded md:w-1/3"
      />

      <select
        v-if="hasRole(['admin', 'manager'])"
        v-model="filters.ownership_type"
        class="px-4 py-2 border rounded"
      >
        <option value="">All Ownership Types</option>
        <option value="individual">Individual</option>
        <option value="organization">Organization</option>
      </select>

      <select
        v-if="filters.ownership_type === 'individual'"
        v-model="filters.individual_type"
        class="px-4 py-2 border rounded"
      >
        <option value="">All Individual Types</option>
        <option value="staff">Staff</option>
        <option value="visitor">Visitor</option>
        <option value="vehicle_owner">Vehicle Owner</option>
      </select>

      <select
        v-if="hasRole(['admin', 'manager'])"
        v-model="filters.driver_id"
        class="px-4 py-2 border rounded"
      >
        <option value="">All Drivers</option>
        <option v-for="driver in drivers" :key="driver.id" :value="driver.id">
          {{ driver.user?.name || driver.name }}
        </option>
      </select>

      <router-link
        v-if="hasRole(['admin', 'manager', 'gate_security'])"
        to="/vehicles/new"
        class="text-center btn-primary"
        >➕ Add Vehicle</router-link
      >
    </div>

    <!-- Sort Dropdown -->
    <div class="mb-4">
      <label for="sort" class="mr-2 font-medium">Sort by:</label>
      <select v-model="sortBy" id="sort" class="px-3 py-2 border border-gray-300 rounded">
        <option value="newest">Newest</option>
        <option value="oldest">Oldest</option>
        <option value="manufacturer-asc">Manufacturer A–Z</option>
        <option value="manufacturer-desc">Manufacturer Z–A</option>
      </select>
    </div>

    <!-- Search by ID -->
    <div
      v-if="hasRole(['admin', 'manager'])"
      class="flex flex-col items-center gap-4 mb-4 md:flex-row"
    >
      <input
        type="number"
        v-model="searchId"
        placeholder="Search Vehicle by ID"
        class="w-full px-4 py-2 border border-gray-300 rounded md:w-1/3"
      />
      <button @click="searchById(searchId)" class="btn-primary">Search by ID</button>
    </div>

    <!-- Vehicles Table -->
    <div class="overflow-x-auto bg-white rounded shadow">
      <table class="min-w-full text-sm table-auto">
        <thead class="text-left bg-gray-100">
          <tr>
            <th @click="sort('id')" class="p-2 cursor-pointer">ID</th>
            <th @click="sort('manufacturer')" class="p-2 cursor-pointer">Manufacturer</th>
            <th @click="sort('model')" class="p-2 cursor-pointer">Model</th>
            <th @click="sort('plate_number')" class="p-2 cursor-pointer">Plate Number</th>
            <th class="p-2">Ownership</th>
            <th class="p-2">Driver</th>
            <th class="p-2">Created</th>
            <th v-if="hasRole(['admin', 'manager'])" class="p-2 text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="vehicle in vehicles"
            :key="vehicle.id"
            class="hover:bg-gray-50 even:bg-gray-50"
          >
            <td class="p-2">{{ vehicle.id }}</td>
            <td class="p-2">{{ vehicle.manufacturer }}</td>
            <td class="p-2">{{ vehicle.model }}</td>
            <td class="p-2">{{ vehicle.plate_number }}</td>
            <td class="p-2">
              {{ formatOwnership(vehicle.ownership_type, vehicle.individual_type, vehicle.owner) }}
            </td>
            <td class="p-2">{{ vehicle.driver?.user?.name || '—' }}</td>
            <td class="p-2">{{ formatDate(vehicle.created_at) }}</td>
            <td class="p-2 space-x-2 text-right">
              <button
                v-if="hasRole(['admin', 'manager'])"
                @click="$router.push(`/vehicles/${vehicle.id}/edit`)"
                class="btn-edit"
              >
                Edit
              </button>
              <button
                v-if="hasRole(['admin', 'manager'])"
                @click="openDeleteModal(vehicle)"
                class="btn-delete"
              >
                Delete
              </button>
            </td>
          </tr>
          <tr v-if="vehicles.length === 0">
            <td colspan="8" class="py-4 text-center text-gray-500">No vehicles found.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <Pagination v-if="meta && meta.last_page > 1" :meta="meta" @page-changed="fetchVehicles" />

    <!-- Modal -->
    <ModalNotification
      :show="modalShow"
      :title="modalTitle"
      :message="modalMessage"
      :show-confirm="modalConfirm"
      @close="closeModal"
      @confirm="confirmDeleteVehicle"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from '@/axios'
import Pagination from '@/components/Pagination.vue'
import ModalNotification from '@/components/ModalNotification.vue'
import { format } from 'date-fns'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
function hasRole(allowedRoles) {
  return allowedRoles.includes(auth.user?.role)
}

// Refs & state
const vehicles = ref([])
const drivers = ref([])
const meta = ref(null)
const search = ref('')
const searchId = ref('')
const filters = ref({ ownership_type: '', individual_type: '', driver_id: '' })
const sortField = ref('id')
const sortOrder = ref('desc')
const sortBy = ref('newest')

// Modal state
const modalShow = ref(false)
const modalTitle = ref('')
const modalMessage = ref('')
const modalConfirm = ref(false)
let selectedVehicle = null

// Fetch vehicles
function fetchVehicles(page = 1) {
  axios
    .get('/vehicles', {
      params: {
        search: search.value,
        ownership_type: filters.value.ownership_type,
        individual_type: filters.value.individual_type,
        driver_id: filters.value.driver_id,
        sort_by: sortField.value,
        order: sortOrder.value,
        page,
      },
    })
    .then((res) => {
      vehicles.value = res.data.data
      meta.value = res.data.meta
    })
}

// Fetch drivers
function fetchDrivers() {
  axios.get('/drivers').then((res) => (drivers.value = res.data.data))
}

// Sorting
function sort(field) {
  if (sortField.value === field) sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
  else {
    sortField.value = field
    sortOrder.value = 'asc'
  }
  fetchVehicles()
}

// Watch sortBy dropdown
watch(sortBy, (value) => {
  switch (value) {
    case 'newest':
      sortField.value = 'id'
      sortOrder.value = 'desc'
      break
    case 'oldest':
      sortField.value = 'id'
      sortOrder.value = 'asc'
      break
    case 'manufacturer-asc':
      sortField.value = 'manufacturer'
      sortOrder.value = 'asc'
      break
    case 'manufacturer-desc':
      sortField.value = 'manufacturer'
      sortOrder.value = 'desc'
      break
  }
  fetchVehicles()
})

// Format helpers
const formatDate = (d) => format(new Date(d), 'dd MMM yyyy, hh:mm a')
const formatIndividualType = (t) =>
  ({ visitor: 'Visitor', staff: 'Staff', vehicle_owner: 'Vehicle Owner' })[t] || t || 'None'
const formatOwnership = (ownership, type, owner) => {
  if (ownership === 'organization') return 'Organization'
  if (ownership === 'individual')
    return type === 'vehicle_owner' && owner
      ? `Vehicle Owner - ${owner.name}`
      : `Individual - ${formatIndividualType(type)}`
  return 'N/A'
}

// Search by ID
async function searchById(id) {
  if (!id) return openModal('Please enter a vehicle ID.', 'Missing Input')
  try {
    const res = await axios.get(`/vehicles/${id}`)
    const v = res.data
    const msg = `
      <h2 class="mb-2 text-lg font-bold">🚗 Vehicle Found</h2>
      <p>Manufacturer: ${v.manufacturer}</p>
      <p>Model: ${v.model}</p>
      <p>Plate Number: ${v.plate_number}</p>
      <p>Year: ${v.year}</p>
      <p>Created By: ${v.creator?.name ?? 'N/A'}</p>
      <p>Created Time: ${formatDate(v.created_at)}</p>
      <p>Last Edited By: ${v.editor?.name ?? 'N/A'}</p>
      <p>Last Edited Time: ${formatDate(v.updated_at)}</p>
      <p><a href="/vehicles/${v.id}/edit" class="inline-block mt-2 text-blue-500 underline">✏️ Edit Vehicle Info</a></p>
    `
    openModal(msg, 'Vehicle Info')
  } catch {
    openModal('❌ Vehicle not found or error occurred.', 'Error')
  }
}

// Open modal helper
function openModal(message, title, confirm = false) {
  modalMessage.value = message
  modalTitle.value = title
  modalConfirm.value = !!confirm
  modalShow.value = true
}

// Close modal
function closeModal() {
  modalShow.value = false
  modalConfirm.value = false
  selectedVehicle = null
}

// Delete modal
function openDeleteModal(vehicle) {
  selectedVehicle = vehicle
  openModal(
    `Are you sure you want to delete ${vehicle.manufacturer} - ${vehicle.plate_number}?`,
    'Confirm Deletion',
    true,
  )
}

// Confirm deletion
async function confirmDeleteVehicle() {
  if (!selectedVehicle) return
  try {
    await axios.delete(`/vehicles/${selectedVehicle.id}`)
    fetchVehicles()
    openModal('✅ Vehicle deleted successfully.', 'Success')
  } catch (err) {
    console.error(err)
    openModal('❌ Failed to delete vehicle.', 'Error')
  } finally {
    selectedVehicle = null
    modalConfirm.value = false
  }
}

// Watchers
watch([search, filters], () => fetchVehicles(), { deep: true })

onMounted(() => {
  fetchVehicles()
  fetchDrivers()
})
</script>
