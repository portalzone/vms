<template>
  <div class="auth-card">
    <!-- <h2 class="mb-4 text-xl font-semibold">
      {{ isEdit ? 'Edit Income' : 'Add Income' }}
    </h2> -->

    <form @submit.prevent="submitForm">
      <!-- Trip Selection -->
      <div class="mb-4">
        <label class="block mb-1 font-medium">Trip (optional)</label>
        <select v-model="form.trip_id" class="w-full px-3 py-2 border rounded">
          <option value="">-- Select Trip --</option>
          <option v-for="trip in trips" :key="trip?.id" :value="trip?.id">
            {{ trip.start_location }} ➝ {{ trip.end_location }} ({{ trip.status }})
          </option>
        </select>
        <p v-if="errors.trip_id" class="text-sm text-red-600">{{ errors.trip_id[0] }}</p>
      </div>

      <!-- Vehicle -->
      <div class="mb-4">
        <label class="block mb-1 font-medium">Vehicle</label>
        <select v-model="form.vehicle_id" class="w-full px-3 py-2 border rounded">
          <option value="">-- Select Vehicle --</option>
          <option v-for="vehicle in vehicles" :key="vehicle?.id" :value="vehicle?.id">
            {{ vehicle.plate_number }}
          </option>
        </select>
        <p v-if="errors.vehicle_id" class="text-sm text-red-600">{{ errors.vehicle_id[0] }}</p>
      </div>

      <!-- Driver -->
      <div class="mb-4">
        <label class="block mb-1 font-medium">Driver</label>
        <select v-model="form.driver_id" class="w-full px-3 py-2 border rounded">
          <option value="">-- Select Driver --</option>
          <option v-for="driver in drivers" :key="driver?.id" :value="driver?.id">
            {{ driver.user?.name || 'Unnamed' }} ({{ driver.license_number || 'No License' }})
          </option>
        </select>
        <p v-if="errors.driver_id" class="text-sm text-red-600">{{ errors.driver_id[0] }}</p>
      </div>

      <!-- Source -->
      <div class="mb-4">
        <label class="block mb-1 font-medium">Source</label>
        <input v-model="form.source" type="text" class="w-full px-3 py-2 border rounded" />
        <p v-if="errors.source" class="text-sm text-red-600">{{ errors.source[0] }}</p>
      </div>

      <!-- Amount -->
      <div class="mb-4">
        <label class="block mb-1 font-medium">Amount</label>
        <input
          v-model="form.amount"
          type="number"
          step="0.01"
          class="w-full px-3 py-2 border rounded"
        />
        <p v-if="errors.amount" class="text-sm text-red-600">{{ errors.amount[0] }}</p>
      </div>

      <!-- Description -->
      <div class="mb-4">
        <label class="block mb-1 font-medium">Description</label>
        <textarea v-model="form.description" rows="3" class="w-full px-3 py-2 border rounded" />
        <p v-if="errors.description" class="text-sm text-red-600">{{ errors.description[0] }}</p>
      </div>

      <!-- Date -->
      <div class="mb-4">
        <label class="block mb-1 font-medium">Date</label>
        <input v-model="form.date" type="date" class="w-full px-3 py-2 border rounded" />
        <p v-if="errors.date" class="text-sm text-red-600">{{ errors.date[0] }}</p>
      </div>

      <!-- Submit -->
      <div class="mt-6">
        <button type="submit" class="px-4 py-2 text-white bg-blue-600 rounded hover:bg-blue-700">
          {{ isEdit ? 'Update' : 'Save' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from '@/axios'

const route = useRoute()
const router = useRouter()

const isEdit = computed(() => !!route.params.id)
const form = ref({
  vehicle_id: '',
  driver_id: '',
  trip_id: '',
  source: '',
  amount: '',
  description: '',
  date: '',
})
const errors = ref({})

const vehicles = ref([])
const drivers = ref([])
const trips = ref([])

const fetchDropdowns = async () => {
  try {
    const [vehicleRes, driverRes, tripRes] = await Promise.all([
      axios.get('/vehicles'),
      axios.get('/drivers'),
      axios.get('/trips?per_page=all'),
    ])

    vehicles.value = (vehicleRes.data.data || []).filter((v) => v && v.id)
    drivers.value = (driverRes.data.data || []).filter((d) => d && d.id)
    trips.value = (tripRes.data.data || []).filter((t) => t && t.id)
  } catch (error) {
    console.error('Failed to fetch dropdown data:', error)
  }
}

const fetchIncome = async () => {
  if (!route.params.id) return // guard clause

  try {
    const { data } = await axios.get(`/incomes/${route.params.id}`)
    form.value = {
      vehicle_id: data.vehicle_id,
      driver_id: data.driver_id,
      trip_id: data.trip_id,
      source: data.source,
      amount: data.amount,
      description: data.description,
      date: data.date,
    }
  } catch (err) {
    console.error('Failed to fetch income:', err)
  }
}

const submitForm = async () => {
  errors.value = {}

  try {
    if (isEdit.value) {
      await axios.put(`/incomes/${route.params.id}`, form.value)
    } else {
      await axios.post('/incomes', form.value)
    }
    router.push('/incomes')
  } catch (err) {
    if (err.response?.data?.errors) {
      errors.value = err.response.data.errors
    }
  }
}

onMounted(async () => {
  await fetchDropdowns()

  // Only try to fetch income if we are editing and the id is valid
  if (isEdit.value && route.params.id) {
    await fetchIncome()
  }
})
</script>
