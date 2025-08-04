<template>
  <div class="checkin-container">
    <h2 class="title">Vehicle Check In / Check-Out</h2>

    <!-- Mode Switcher -->
    <div class="mode-switch">
      <button
        :class="['switch-btn', mode === 'dropdown' ? 'active' : '']"
        @click="mode = 'dropdown'"
      >
        Select Vehicle
      </button>
      <button
        :class="['switch-btn', mode === 'search' ? 'active' : '']"
        @click="mode = 'search'"
      >
        Search by Plate
      </button>
    </div>

    <form @submit.prevent>
      <!-- Dropdown Mode -->
      <div v-if="mode === 'dropdown'">
        <label class="label">Vehicle</label>
        <select v-model.number="form.vehicle_id" @change="handleDropdownChange" class="input select-input" required>
          <option disabled value="">-- Select Vehicle --</option>
          <option v-for="v in vehicles" :key="v.id" :value="v.id">
            {{ v.plate_number }} - ({{ v.manufacturer }} {{ v.model }})
          </option>
        </select>
      </div>

      <!-- Search by Plate Mode -->
      <div v-else>
        <label class="label">Enter Plate Number</label>
        <input
          v-model="plateSearch"
          type="text"
          class="input search-input"
          placeholder="Type plate number..."
        />

        <!-- Show search results -->
        <ul v-if="searchResults.length" class="search-results">
          <li
            v-for="v in searchResults"
            :key="v.id"
            @click="selectVehicle(v)"
            class="search-item"
          >
            {{ v.plate_number }} - {{ v.manufacturer }} {{ v.model }}
          </li>
        </ul>

        <div v-if="selectedVehicle" class="found-text">
          Selected: {{ selectedVehicle.plate_number }} ({{ selectedVehicle.manufacturer }} {{ selectedVehicle.model }})
        </div>
      </div>

      <!-- Status Messages -->
      <div v-if="isAlreadyCheckedIn" class="status-text text-yellow-600">
        This vehicle is currently checked in.
      </div>
      <div v-if="wasRecentlyCheckedOut" class="status-text text-green-600">
        This vehicle was recently checked out.
      </div>

      <!-- Action Buttons -->
      <div class="flex gap-4 mt-4">
        <button
          class="btn-primary"
          type="button"
          @click="isAlreadyCheckedIn ? submitCheckout() : submitCheckin()"
          :disabled="loading"
        >
          {{ loading ? (isAlreadyCheckedIn ? 'Checking out...' : 'Checking in...') : (isAlreadyCheckedIn ? 'Submit Check-Out' : 'Submit Check-In') }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { debounce } from 'lodash-es'
import axios from '@/axios'

const mode = ref('dropdown')
const vehicles = ref([])
const selectedVehicle = ref(null)
const plateSearch = ref('')
const searchResults = ref([])

const form = ref({ vehicle_id: '' })

const isAlreadyCheckedIn = ref(false)
const wasRecentlyCheckedOut = ref(false)
const activeCheckInId = ref(null)

const loading = ref(false)
const isSubmitting = ref(false)

const toast = useToast()
const router = useRouter()

const fetchVehicles = async () => {
  try {
    const res = await axios.get('/vehicles/with-drivers')
    vehicles.value = res.data
    if (form.value.vehicle_id) {
      await checkIfCheckedIn(form.value.vehicle_id)
    }
  } catch (e) {
    toast.error('Failed to load vehicles.')
  }
}

const searchByPlate = debounce(async () => {
  if (plateSearch.value.trim().length < 2) {
    searchResults.value = []
    return
  }
  try {
    const res = await axios.get('/vehicles/search-by-plate', {
      params: { q: plateSearch.value.trim() }
    })
    searchResults.value = res.data
  } catch {
    searchResults.value = []
  }
}, 300)

watch(plateSearch, searchByPlate)

const selectVehicle = async (vehicle) => {
  plateSearch.value = ''
searchResults.value = []
  selectedVehicle.value = vehicle
  form.value.vehicle_id = vehicle.id
  await checkIfCheckedIn(vehicle.id)
}

const checkIfCheckedIn = async (vehicleId) => {
  if (!vehicleId) return
  try {
    const res = await axios.get('/checkins', {
      params: { search: vehicleId, per_page: 1 }
    })
    const latest = res.data.data?.[0]
    isAlreadyCheckedIn.value = false
    wasRecentlyCheckedOut.value = false
    activeCheckInId.value = null
    if (latest && !latest.checked_out_at && latest.vehicle_id === vehicleId) {
      isAlreadyCheckedIn.value = true
      activeCheckInId.value = latest.id
    } else if (latest && latest.checked_out_at && latest.vehicle_id === vehicleId) {
      wasRecentlyCheckedOut.value = true
    }
  } catch (e) {
    console.error('Check-in status fetch failed:', e)
    isAlreadyCheckedIn.value = false
    wasRecentlyCheckedOut.value = false
    activeCheckInId.value = null
  }
}

const submitCheckin = async () => {
  loading.value = true
  try {
    await axios.post('/checkins', form.value)
    toast.success('Check-in successful')
    isAlreadyCheckedIn.value = true
    await fetchActiveCheckInId(form.value.vehicle_id)
    await checkIfCheckedIn(form.value.vehicle_id)
  } catch (error) {
    toast.error(error.response?.data?.message || 'Check-in failed')
  } finally {
    loading.value = false
  }
}

const submitCheckout = async () => {
  loading.value = true
  try {
    if (!activeCheckInId.value) {
      await fetchActiveCheckInId(form.value.vehicle_id)
    }
if (!activeCheckInId.value) {
  toast.error('Cannot check out: no active check-in found.')
  loading.value = false
  return
}
    await axios.post(`/checkins/${activeCheckInId.value}/checkout`)
    toast.success('Check-out successful')
    isAlreadyCheckedIn.value = false
    activeCheckInId.value = null
    await checkIfCheckedIn(form.value.vehicle_id)
  } catch (error) {
    toast.error(error.response?.data?.message || 'Check-out failed')
  } finally {
    loading.value = false
  }
}

const fetchActiveCheckInId = async (vehicleId) => {
  try {
    const response = await axios.get(`/checkins/latest?vehicle_id=${vehicleId}`)
    const latest = response.data
    if (latest && !latest.checked_out_at) {
      activeCheckInId.value = latest.id
    } else {
      activeCheckInId.value = null
    }
  } catch {
    activeCheckInId.value = null
  }
}

const handleDropdownChange = () => {
  const vehicle = vehicles.value.find(v => v.id === form.value.vehicle_id)
  if (vehicle) selectVehicle(vehicle)
}

watch(
  () => form.value.vehicle_id,
  async (val) => {
    if (!val) return
    const vehicle = vehicles.value.find(v => v.id === val)
    selectedVehicle.value = vehicle || null
    await nextTick()
    await checkIfCheckedIn(val)
    await fetchActiveCheckInId(val)
  }
)

onMounted(fetchVehicles)
</script>
