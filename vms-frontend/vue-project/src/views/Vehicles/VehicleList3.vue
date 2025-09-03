<template>
  <div class="space-y-6">
    <!-- Filters -->
    <div class="flex flex-wrap gap-4">
      <input
        v-model="search"
        type="text"
        placeholder="Search by ID, manufacturer, model, plate..."
        class="border px-4 py-2 rounded w-full md:w-1/3"
      />

      <select v-model="filters.ownership_type" class="border px-4 py-2 rounded">
        <option value="">All Ownership Types</option>
        <option value="individual">Individual</option>
        <option value="organization">Organization</option>
      </select>

      <select
        v-if="filters.ownership_type === 'individual'"
        v-model="filters.individual_type"
        class="border px-4 py-2 rounded"
      >
        <option value="">All Individual Types</option>
        <option value="staff">Staff</option>
        <option value="visitor">Visitor</option>
        <option value="vehicle_owner">Vehicle Owner</option>
      </select>

      <select
        v-if="hasRole(['admin', 'manager'])"
        v-model="filters.driver_id"
        class="border px-4 py-2 rounded"
      >
        <option value="">All Drivers</option>
        <option v-for="driver in drivers" :key="driver.id" :value="driver.id">
          {{ driver.user?.name || driver.name }}
        </option>
      </select>

      <router-link to="/vehicles/new" class="btn-primary text-center">➕ Add Vehicle</router-link>
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

    <!-- Search by ID -->
    <div v-if="hasRole(['admin', 'manager'])" class="flex flex-col md:flex-row gap-4 items-center mb-4">
      <input
        type="number"
        v-model="searchId"
        placeholder="Search Vehicle by ID"
        class="border border-gray-300 rounded px-4 py-2 w-full md:w-1/3"
      />
      <button @click="searchById(searchId)" class="btn-primary">Search by ID</button>
    </div>

    <!-- Vehicles Table -->
    <div class="overflow-x-auto rounded shadow bg-white">
      <table class="min-w-full table-auto text-sm">
        <thead class="bg-gray-100 text-left">
          <tr>
            <th @click="sort('id')" class="p-2 cursor-pointer">ID</th>
            <th @click="sort('manufacturer')" class="p-2 cursor-pointer">Manufacturer</th>
            <th @click="sort('model')" class="p-2 cursor-pointer">Model</th>
            <th @click="sort('plate_number')" class="p-2 cursor-pointer">Plate Number</th>
            <th class="p-2">Ownership</th>
            <th class="p-2">Driver</th>
            <th class="p-2">Created</th>
            <th class="p-2 text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="vehicle in vehicles" :key="vehicle.id" class="hover:bg-gray-50 even:bg-gray-50">
            <td class="p-2">{{ vehicle.id }}</td>
            <td class="p-2">{{ vehicle.manufacturer }}</td>
            <td class="p-2">{{ vehicle.model }}</td>
            <td class="p-2">{{ vehicle.plate_number }}</td>
            <td class="p-2">
              {{ formatOwnership(vehicle.ownership_type, vehicle.individual_type, vehicle.owner) }}
            </td>
            <td class="p-2">{{ vehicle.driver?.user?.name || '—' }}</td>
            <td class="p-2">{{ formatDate(vehicle.created_at) }}</td>
            <td class="p-2 text-right space-x-2">
              <button @click="$router.push(`/vehicles/${vehicle.id}/edit`)" class="btn-edit">Edit</button>
              <button @click="openDeleteModal(vehicle)" class="btn-delete">Delete</button>
            </td>
          </tr>
          <tr v-if="vehicles.length === 0">
            <td colspan="8" class="text-center text-gray-500 py-4">No vehicles found.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <Pagination v-if="meta && meta.last_page > 1" :meta="meta" @page-changed="fetchVehicles" />

    <!-- Info Modal -->
    <ModalNotification ref="modalRef" />

    <!-- Delete Confirmation Modal -->
    <Modal
      v-if="showDeleteModal"
      :title="'Confirm Deletion'"
      :message="`Are you sure you want to delete ${selectedVehicle?.manufacturer} - ${selectedVehicle?.plate_number}?`"
      @close="showDeleteModal = false"
      @confirm="deleteVehicle"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from '@/axios'
import Pagination from '@/components/Pagination.vue'
import ModalNotification from '@/components/ModalNotification.vue'
import Modal from '@/components/Modal.vue' // <-- Proper confirm modal
import { format } from 'date-fns'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
function hasRole(allowedRoles) {
  return allowedRoles.includes(auth.user?.role)
}

// Refs
const modalRef = ref(null)
const infoModalShow = ref(false)
const infoModalTitle = ref('')
const infoModalMessage = ref('')
const showDeleteModal = ref(false)
const selectedVehicle = ref(null)

// Data
const sortBy = ref('newest')
const searchId = ref('')
const vehicles = ref([])
const drivers = ref([])
const meta = ref(null)
const search = ref('')
const filters = ref({
  ownership_type: '',
  individual_type: '',
  driver_id: '',
})

// Sorting
const sortField = ref('id')
const sortOrder = ref('asc')

// Fetch vehicles
function fetchVehicles(pageNumber = 1) {
  axios
    .get('/vehicles', {
      params: {
        search: search.value,
        ownership_type: filters.value.ownership_type,
        individual_type: filters.value.individual_type,
        driver_id: filters.value.driver_id,
        sort_by: sortField.value,
        order: sortOrder.value,
        page: pageNumber,
      },
    })
    .then(res => {
      vehicles.value = res.data.data
      meta.value = res.data.meta
    })
}

// Fetch drivers for dropdown
function fetchDrivers() {
  axios.get('/drivers').then(res => {
    drivers.value = res.data.data
  })
}

// Sorting logic
function sort(field) {
  if (sortField.value === field) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortField.value = field
    sortOrder.value = 'asc'
  }
  fetchVehicles()
}

// Format ownership
const formatIndividualType = type => {
  if (!type) return 'None'
  const map = {
    visitor: 'Visitor',
    staff: 'Staff',
    vehicle_owner: 'Vehicle Owner',
  }
  return map[type] || type
}
const formatOwnership = (ownership, individualType, owner) => {
  if (ownership === 'organization') return 'Organization'
  if (ownership === 'individual') {
    if (individualType === 'vehicle_owner' && owner) {
      return `Vehicle Owner - ${owner.name}`
    }
    return `Individual - ${formatIndividualType(individualType)}`
  }
  return 'N/A'
}

// Format date
const formatDate = dateStr => format(new Date(dateStr), 'dd MMM yyyy, hh:mm a')

// Modal helpers
function openInfoModal(message, title) {
  infoModalMessage.value = message
  infoModalTitle.value = title
  infoModalShow.value = true
  modalRef.value?.show(message, title)
}

// Search vehicle by ID
const searchById = async id => {
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
  } catch {
    modalRef.value?.show('❌ Vehicle not found or error occurred.', 'Error')
  }
}

// Delete modal
function openDeleteModal(vehicle) {
  selectedVehicle.value = vehicle
  showDeleteModal.value = true
}

async function deleteVehicle() {
  try {
    await axios.delete(`/vehicles/${selectedVehicle.value.id}`)
    showDeleteModal.value = false
    selectedVehicle.value = null
    fetchVehicles()
    modalRef.value?.show('✅ Vehicle deleted successfully.', 'Success')
  } catch (err) {
    console.error(err)
    modalRef.value?.show('❌ Failed to delete vehicle.', 'Error')
  }
}

// Watchers
watch([search, sortBy, filters], () => fetchVehicles(), { deep: true })

// Mount
onMounted(() => {
  fetchVehicles()
  fetchDrivers()
})
</script>
