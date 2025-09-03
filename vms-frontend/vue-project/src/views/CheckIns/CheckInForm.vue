<template>
  <div class="checkin-container">
    <h2 class="title">Vehicle Check In / Check-Out</h2>

    <!-- Mode Switcher -->
    <div class="mode-switch">
      <button
        :class="['switch-btn', useDropdown ? 'active' : '']"
        @click="switchMode(true)"
        type="button"
      >
        Select Vehicle
      </button>
      <button
        :class="['switch-btn', !useDropdown ? 'active' : '']"
        @click="switchMode(false)"
        type="button"
      >
        Search by Plate
      </button>
    </div>

    <form @submit.prevent>
      <!-- Dropdown -->
      <div v-if="useDropdown">
        <label class="label">Vehicle</label>
        <select
          v-model="form.vehicle_id"
          @change="handleDropdownChange"
          class="input select-input"
          required
        >
          <option disabled value="">-- Select Vehicle --</option>
          <option
            v-for="vehicle in vehicles"
            :key="vehicle.id"
            :value="vehicle.id"
          >
            {{ vehicle.plate_number }} - ({{ vehicle.manufacturer }} {{ vehicle.model }})
          </option>
        </select>
      </div>

      <!-- Plate search -->
      <div v-else>
        <label class="label">Enter Plate Number</label>
        <input
          v-model="plateSearch"
          type="text"
          placeholder="Start typing plate..."
          class="input search-input"
        />
        <ul v-if="searchResults.length" class="search-results">
          <li
            v-for="vehicle in searchResults"
            :key="vehicle.id"
            @click="selectVehicle(vehicle)"
            class="search-item"
          >
            {{ vehicle.plate_number }} - {{ vehicle.manufacturer }} {{ vehicle.model }} 
            (Driver: {{ vehicle.driver?.user?.name || 'Unknown' }})
          </li>
        </ul>
        <div v-else-if="plateSearch.length >= 2" class="text-sm text-gray-500 mt-1">
          No results found.
        </div>
      </div>

      <!-- Selected Info -->
      <transition name="fade" mode="out-in">
        <div v-if="selectedVehicle" class="found-text" key="info">
          Selected: {{ selectedVehicle.plate_number }} 
          ({{ selectedVehicle.manufacturer }} {{ selectedVehicle.model }})<br />
          Driver: {{ selectedVehicle.driver?.user?.name || 'Unknown' }}
        </div>
      </transition>

      <!-- Status Messages -->
      <div v-if="isAlreadyCheckedIn" class="status-text text-yellow-600">
        This vehicle is currently checked in.
      </div>
      <div v-if="wasRecentlyCheckedOut" class="status-text text-green-600">
        This vehicle was recently checked out.
      </div>

      <!-- Action Button -->
      <div class="flex gap-4 mt-4">
        <button
          type="button"
          class="btn-primary"
          @click="isAlreadyCheckedIn ? submitCheckout() : submitCheckin()"
          :disabled="!form.vehicle_id || loading"
        >
          <span v-if="loading">Processing...</span>
          <span v-else>{{ isAlreadyCheckedIn ? 'Submit Check-Out' : 'Submit Check-In' }}</span>
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from '@/axios'
import { useToast } from 'vue-toastification'

const toast = useToast()
const useDropdown = ref(true)
const plateSearch = ref('')
const searchResults = ref([])
const selectedVehicle = ref(null)
const vehicles = ref([])
const isAlreadyCheckedIn = ref(false)
const wasRecentlyCheckedOut = ref(false)
const activeCheckInId = ref(null)
const loading = ref(false)

const form = ref({
  vehicle_id: '',
})

const fetchVehicles = async () => {
  try {
    const res = await axios.get('/vehicles/with-drivers')
    vehicles.value = res.data
  } catch (err) {
    toast.error('Failed to load vehicles')
  }
}

const handleDropdownChange = () => {
  const vehicle = vehicles.value.find(v => v.id === form.value.vehicle_id)
  if (vehicle) selectVehicle(vehicle)
}

const selectVehicle = async (vehicle) => {
  selectedVehicle.value = vehicle
  form.value.vehicle_id = vehicle.id
  plateSearch.value = ''
  searchResults.value = []

  try {
    const res = await axios.get(`/checkins/latest?vehicle_id=${vehicle.id}`)
    const latest = res.data

    isAlreadyCheckedIn.value = latest && !latest.checked_out_at
    wasRecentlyCheckedOut.value = latest && !!latest.checked_out_at
    activeCheckInId.value = latest && !latest.checked_out_at ? latest.id : null
  } catch (err) {
    if (err.response?.status === 404) {
      // No check-in found → just reset state (not really an error)
      isAlreadyCheckedIn.value = false
      wasRecentlyCheckedOut.value = false
      activeCheckInId.value = null
    } else {
      // Actual server/network error
      toast.error(err.response?.data?.message || 'Failed to fetch check-in status')
    }
  }
}


const submitCheckin = async () => {
  loading.value = true
  try {
    await axios.post('/checkins', { vehicle_id: form.value.vehicle_id })
    toast.success('Vehicle checked in successfully!')
    isAlreadyCheckedIn.value = true
    await selectVehicle(selectedVehicle.value)
  } catch (err) {
    toast.error(err.response?.data?.message || 'Check-in failed')
  }
  loading.value = false
}

const submitCheckout = async () => {
  loading.value = true
  if (!activeCheckInId.value) {
    toast.error('Cannot check out: no active check-in found.')
    loading.value = false
    return
  }

  try {
    await axios.post(`/checkins/${activeCheckInId.value}/checkout`)
    toast.success('Vehicle checked out successfully!')
    isAlreadyCheckedIn.value = false
    activeCheckInId.value = null
    await selectVehicle(selectedVehicle.value)
  } catch (err) {
    toast.error(err.response?.data?.message || 'Check-out failed')
  }
  loading.value = false
}

watch(plateSearch, async (val) => {
  if (val.length < 2) {
    searchResults.value = []
    return
  }

  try {
    const res = await axios.get(`/vehicles/search-by-plate?q=${val}`)
    searchResults.value = res.data
  } catch {
    searchResults.value = []
    toast.error('Failed to search for plate.')
  }
})

const switchMode = (toDropdown) => {
  useDropdown.value = toDropdown
  selectedVehicle.value = null
  form.value.vehicle_id = ''
  plateSearch.value = ''
  searchResults.value = []
  isAlreadyCheckedIn.value = false
  wasRecentlyCheckedOut.value = false
  activeCheckInId.value = null
}

onMounted(fetchVehicles)
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
