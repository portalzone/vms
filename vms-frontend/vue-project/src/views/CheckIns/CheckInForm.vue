<template>
  <div class="max-w-md mx-auto bg-white p-6 rounded shadow">
    <h2 class="text-lg font-semibold mb-4">Vehicle Check-In / Check-Out</h2>

    <form @submit.prevent="submit">
      <label class="block mb-1 font-medium">Vehicle</label>
      <select v-model="form.vehicle_id" class="input w-full mb-4" required>
        <option disabled value="">-- Select Vehicle --</option>
        <option v-for="v in vehicles" :key="v.id" :value="v.id">
          {{ v.plate_number }} - ({{ v.manufacturer }} - {{ v.model }})
        </option>
      </select>

      <div v-if="isAlreadyCheckedIn" class="text-red-600 mb-4">
        This vehicle is already checked in.
      </div>

      <button
        v-if="!isAlreadyCheckedIn"
        class="btn-primary w-full"
        type="submit"
        :disabled="loading"
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

const vehicles = ref([])
const form = ref({ vehicle_id: '' })
const loading = ref(false)
const router = useRouter()
const isAlreadyCheckedIn = ref(false)
const activeCheckInId = ref(null)

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
