<template>
  <div class="max-w-md mx-auto bg-white p-6 rounded shadow">
    <h2 class="text-lg font-semibold mb-4">Vehicle Check-In / Check-Out</h2>

    <!-- Mode Switcher -->
    <div class="flex justify-between mb-4">
      <button
        :class="['px-4 py-2 rounded-l', mode === 'dropdown' ? 'bg-blue-600 text-white' : 'bg-gray-200']"
        @click="mode = 'dropdown'"
      >
        Select Vehicle
      </button>
      <button
        :class="['px-4 py-2 rounded-r', mode === 'search' ? 'bg-blue-600 text-white' : 'bg-gray-200']"
        @click="mode = 'search'"
      >
        Search by Plate
      </button>
    </div>

    <form @submit.prevent="submit">
      <!-- Dropdown Mode -->
      <div v-if="mode === 'dropdown'">
        <label class="block mb-1 font-medium">Vehicle</label>
        <select v-model="form.vehicle_id" class="input w-full mb-4" required>
          <option disabled value="">-- Select Vehicle --</option>
          <option v-for="v in vehicles" :key="v.id" :value="v.id">
            {{ v.plate_number }} - ({{ v.manufacturer }} {{ v.model }})
          </option>
        </select>
      </div>

      <!-- Search by Plate Mode -->
      <div v-else>
        <label class="block mb-1 font-medium">Enter Plate Number</label>
        <input
          v-model="plateSearch"
          type="text"
          class="input w-full mb-4"
          placeholder="Enter plate number"
        />
        <button type="button" class="btn-secondary w-full mb-4" @click="findVehicleByPlate">
          Find Vehicle
        </button>

        <div v-if="selectedVehicle">
          <p class="text-green-600 mb-2">
            Found: {{ selectedVehicle.plate_number }} ({{ selectedVehicle.manufacturer }} {{ selectedVehicle.model }})
          </p>
        </div>
      </div>

      <!-- Error / Status -->
      <div v-if="isAlreadyCheckedIn" class="text-red-600 mb-4">
        This vehicle is already checked in.
      </div>

      <!-- Action Buttons -->
      <button
        v-if="!isAlreadyCheckedIn"
        class="btn-primary w-full"
        type="submit"
        :disabled="loading || !form.vehicle_id"
      >
        {{ loading ? 'Checking in...' : 'Submit Check-In' }}
      </button>

      <button
        v-else
        class="btn-secondary w-full"
        type="button"
        @click="submitCheckout"
        :disabled="loading"
      >
        {{ loading ? 'Checking out...' : 'Submit Check-Out' }}
      </button>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from '@/axios'
import { useRouter } from 'vue-router'

const mode = ref('dropdown') // dropdown or search
const vehicles = ref([])
const plateSearch = ref('')
const selectedVehicle = ref(null)
const form = ref({ vehicle_id: '' })
const loading = ref(false)
const isAlreadyCheckedIn = ref(false)
const activeCheckInId = ref(null)
const router = useRouter()

const fetchVehicles = async () => {
  try {
    const res = await axios.get('/vehicles/with-drivers')
    vehicles.value = res.data
  } catch (e) {
    alert('Failed to load vehicles.')
  }
}

const checkIfCheckedIn = async (vehicleId) => {
  if (!vehicleId) return
  try {
    const res = await axios.get(`/checkins?search=${vehicleId}&per_page=1`)
    const latest = res.data.data?.[0]
    if (latest && latest.vehicle?.id === vehicleId && !latest.checked_out_at) {
      isAlreadyCheckedIn.value = true
      activeCheckInId.value = latest.id
    } else {
      isAlreadyCheckedIn.value = false
      activeCheckInId.value = null
    }
  } catch (e) {
    console.error('Failed to verify check-in status.')
    isAlreadyCheckedIn.value = false
  }
}

const findVehicleByPlate = () => {
  const match = vehicles.value.find(v => v.plate_number.toLowerCase() === plateSearch.value.toLowerCase())
  if (match) {
    selectedVehicle.value = match
    form.value.vehicle_id = match.id
    checkIfCheckedIn(match.id)
  } else {
    selectedVehicle.value = null
    form.value.vehicle_id = ''
    isAlreadyCheckedIn.value = false
    alert('No matching vehicle found.')
  }
}

const submit = async () => {
  try {
    loading.value = true
    await axios.post('/checkins', form.value)
    alert('Check-in successful')
    router.push('/checkins')
  } catch (err) {
    alert(err.response?.data?.message || 'Check-in failed')
  } finally {
    loading.value = false
  }
}

const submitCheckout = async () => {
  if (!activeCheckInId.value) return
  try {
    loading.value = true
    await axios.post(`/checkins/${activeCheckInId.value}/checkout`)
    alert('Check-out successful')
    router.push('/checkins')
  } catch (err) {
    alert(err.response?.data?.message || 'Check-out failed')
  } finally {
    loading.value = false
  }
}

watch(() => form.value.vehicle_id, checkIfCheckedIn)

onMounted(fetchVehicles)
</script>
