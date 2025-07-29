<template>
  <div>
    <!-- Search & Add -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
      <input
        v-model="search"
        type="text"
        placeholder="Search by name, email, license, or vehicle..."
        class="border border-gray-300 rounded px-4 py-2 w-full md:w-1/2"
      />
      <router-link v-if="hasRole(['admin', 'manager'])"
        to="/drivers/new"
        class="btn-primary text-center"
      >
        ➕ Add Driver
      </router-link>
    </div>

    <!-- Filter Controls -->
    <div v-if="hasRole(['admin', 'manager'])" class="flex flex-wrap gap-4 mb-4">
      <input
        v-model="searchDriverIdInput"
        type="number"
        placeholder="Search Driver by ID"
        class="border border-gray-300 rounded px-4 py-2"
      />
      <button @click="searchDriverById(searchDriverIdInput)" class="btn-primary">
        Search by ID
      </button>

<!-- Only show ownership filter for admin and manager -->
<div v-if="hasRole(['admin', 'manager'])" class="mb-4 flex gap-4 items-center">
  <label class="font-medium">Filter Drivers by Ownership Type:</label>

  <!-- Ownership type -->
  <select v-model="selectedOwnership" class="border border-gray-300 rounded px-3 py-2">
    <option value="">All Ownership</option>
    <option value="individual">Vehicle Owner</option>
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


</div>

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
            <th class="px-4 py-2">Latest Update</th>
            <th class="px-4 py-2">Registered By</th>
            <th class="px-4 py-2">Last Edited By</th>
            <th class="px-4 py-2 text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="(driver, index) in paginatedDrivers"
            :key="driver.id"
            class="hover:bg-gray-50 even:bg-gray-50"
          >
            <td class="px-4 py-2">{{ start + index + 1 }}</td>
            <td class="px-4 py-2">
              <router-link
                :to="{ name: 'DriverProfile', params: { id: driver.id } }"
                class="text-blue-600 hover:underline"
              >
                {{ driver.user?.name || '—' }}
              </router-link>
            </td>
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
            <td class="px-4 py-2">{{ formatDate(driver.created_at) }}</td>
            <td class="px-4 py-2">{{ formatDate(driver.updated_at) }}</td>
            <td class="px-4 py-2">{{ driver.creator?.name || '—' }}</td>
            <td class="px-4 py-2">{{ driver.editor?.name || '—' }}</td>
            <td class="px-4 py-2 text-right space-x-2">
              <button class="btn-edit" @click="edit(driver.id)">Edit</button>
              <button class="btn-delete" @click="remove(driver.id)">Delete</button>
            </td>
          </tr>
          <tr v-if="paginatedDrivers.length === 0">
            <td colspan="13" class="text-center text-gray-500 py-4">No drivers found.</td>
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

    <!-- Modal -->
    <ModalNotification ref="modalRef" />
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import axios from '@/axios'
import ModalNotification from '@/components/ModalNotification.vue'

import { useAuthStore } from '@/stores/auth'
const auth = useAuthStore()

function hasRole(allowedRoles) {
  return allowedRoles.includes(auth.user?.role)
}

const modalRef = ref(null)
const router = useRouter()

const search = ref('')
const page = ref(1)
const perPage = 10
const searchDriverIdInput = ref('')

const selectedFilterType = ref('')
const selectedOwnership = ref('')
const selectedOwnerId = ref('')
const selectedOrganizationId = ref('')

const vehicleOwners = ref([])
const drivers = ref([]) // Your driver data


const fetchDrivers = async () => {
  try {
    let res
    if (hasRole(['admin', 'manager'])) {
      res = await axios.get('/drivers')
    } else if (auth.user?.role === 'vehicle_owner') {
      // Fetch only drivers assigned to the current vehicle owner
      res = await axios.get(`/drivers?owner_id=${auth.user.id}`)
    }

    const fetched = Array.isArray(res.data) ? res.data : res.data.data || []
    drivers.value = fetched
  } catch (err) {
    console.error('Error fetching drivers:', err)
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
const filteredDrivers = computed(() => {
  return drivers.value.filter(driver => {
    const vehicle = driver.vehicle

    // No vehicle assigned → only show if no filters are applied
    if (!vehicle) {
      return !selectedOwnership.value && !selectedOwnerId.value && !selectedOrganizationId.value
    }

    const matchesOwnership = !selectedOwnership.value || vehicle.ownership_type === selectedOwnership.value
    const matchesOwner = !selectedOwnerId.value || vehicle.owner_id == selectedOwnerId.value
    const matchesOrg = !selectedOrganizationId.value || vehicle.organization_id == selectedOrganizationId.value

    return matchesOwnership && matchesOwner && matchesOrg
  })
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

const formatDate = (dateStr) =>
  new Date(dateStr).toLocaleString()

const searchDriverById = async (id) => {
  if (!id) {
    modalRef.value?.show('Please enter a Driver ID.', 'Missing Input')
    return
  }

  try {
    const res = await axios.get(`/drivers/${id}`)
    const driver = res.data
    const vehicle = driver.vehicle

    const vehicleInfo = vehicle
      ? `
        <p>Plate: ${vehicle.plate_number}</p>
        <p>Vehicle Name: ${vehicle.name}</p>
        <a href="/drivers/${driver.id}/edit" class="text-blue-500 underline">Edit Driver</a>
        `
      : '<p>No vehicle assigned.</p>'

    const message = `
      <h2 class="font-bold text-lg mb-2">✅ Driver Found</h2>
      <p>Name: ${driver.name}</p>
      <p>Email: ${driver.email}</p>
      <p>Phone: ${driver.phone_number}</p>
      <p>License: ${driver.license_number}</p>
      ${vehicleInfo}
    `
    modalRef.value?.show(message, 'Driver Info')
  } catch (err) {
    modalRef.value?.show('❌ Driver not found or error occurred.', 'Error')
    console.error(err)
  }
}

const edit = (id) => router.push(`/drivers/${id}/edit`)
const remove = async (id) => {
  if (confirm('Are you sure you want to delete this driver?')) {
    try {
      await axios.delete(`/drivers/${id}`)
      await fetchDrivers()
    } catch (err) {
      console.error('Failed to delete driver:', err)
    }
  }
}

watch(search, () => {
  page.value = 1
})

onMounted(async () => {
  if (hasRole(['admin', 'manager'])) {
    await fetchVehicleOwners()
  }
  await fetchDrivers() // load all drivers
})

</script>
