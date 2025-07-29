<template>
  <div class="bg-white rounded shadow p-6">
    <form @submit.prevent="submit">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <!-- Manufacturer -->
        <div>
          <label class="block mb-1 font-medium text-gray-700">Manufacturer</label>
          <input
            v-model="vehicle.manufacturer"
            type="text"
            required
            class="w-full border rounded px-3 py-2"
            placeholder="Toyota, Ford..."
          />
          <p v-if="errors.manufacturer" class="text-red-600 text-sm mt-1">{{ errors.manufacturer[0] }}</p>
        </div>

        <!-- Model -->
        <div>
          <label class="block mb-1 font-medium text-gray-700">Model</label>
          <input
            v-model="vehicle.model"
            type="text"
            required
            class="w-full border rounded px-3 py-2"
            placeholder="Camry, F-150..."
          />
          <p v-if="errors.model" class="text-red-600 text-sm mt-1">{{ errors.model[0] }}</p>
        </div>

        <!-- Year -->
        <div>
          <label class="block mb-1 font-medium text-gray-700">Year</label>
          <input
            v-model.number="vehicle.year"
            type="number"
            required
            class="w-full border rounded px-3 py-2"
            placeholder="2020"
          />
          <p v-if="errors.year" class="text-red-600 text-sm mt-1">{{ errors.year[0] }}</p>
        </div>

        <!-- Plate Number -->
        <div>
          <label class="block mb-1 font-medium text-gray-700">Plate Number</label>
          <input
            v-model="vehicle.plate_number"
            type="text"
            required
            class="w-full border rounded px-3 py-2"
            placeholder="ABC-123-XY"
            @input="errors.plate_number = null"
          />
          <p v-if="errors.plate_number" class="text-red-600 text-sm mt-1">{{ errors.plate_number[0] }}</p>
        </div>

        <!-- Ownership Type -->
        <div>
          <label class="block mb-1 font-medium text-gray-700">Ownership</label>
          <select v-model="vehicle.ownership_type" class="w-full border rounded px-3 py-2">
            <option value="organization">Organization</option>
            <option value="individual">Vehicle Owner</option>
          </select>
        </div>

        <!-- Vehicle Owner selection (admin/manager only) -->
        <div
          v-if="vehicle.ownership_type === 'individual' && isAdminOrManager"
        >
          <label class="block mb-1 font-medium text-gray-700">Select Vehicle Owner</label>
          <select v-model="vehicle.owner_id" class="w-full border rounded px-3 py-2">
            <option value="">-- Select Owner --</option>
            <option v-for="u in vehicleOwners" :key="u.id" :value="u.id">{{ u.name }}</option>
          </select>
          <p v-if="errors.owner_id" class="text-red-600 text-sm mt-1">{{ errors.owner_id[0] }}</p>
        </div>
      </div>

      <!-- Buttons -->
      <div class="flex justify-end mt-6 gap-2">
        <button type="submit" class="btn-submit">
          {{ props.id ? 'Update' : 'Create' }}
        </button>
        <router-link to="/vehicles" class="btn-cancel">Cancel</router-link>
      </div>
    </form>
  </div>
</template>

<script setup>
import { reactive, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from '@/axios'
import { useAuthStore } from '@/stores/auth'

const props = defineProps({ id: [String, Number] })
const router = useRouter()
const auth = useAuthStore()

const vehicle = reactive({
  manufacturer: '',
  model: '',
  year: '',
  plate_number: '',
  ownership_type: 'organization',
  owner_id: null,
})

const errors = reactive({})
const vehicleOwners = ref([])

const isAdminOrManager = ['admin', 'manager'].includes(auth.user?.role)

const fetchVehicleOwners = async () => {
  if (!isAdminOrManager) return
  try {
    const res = await axios.get('/vehicle-owners')
    vehicleOwners.value = res.data
  } catch (error) {
    console.error('❌ Failed to load vehicle owners:', error)
  }
}

onMounted(async () => {
  await fetchVehicleOwners()

  if (props.id) {
    try {
      const res = await axios.get(`/vehicles/${props.id}`)
      Object.assign(vehicle, res.data)
    } catch (err) {
      console.error('❌ Failed to load vehicle:', err)
      alert('Error loading vehicle. Please try again.')
    }
  } else {
    // If vehicle_owner, default ownership to individual and owner_id to self
    if (auth.user?.role === 'vehicle_owner') {
      vehicle.ownership_type = 'individual'
      vehicle.owner_id = auth.user.id
    }
  }
})

const submit = async () => {
  Object.keys(errors).forEach(key => delete errors[key])

  if (vehicle.ownership_type === 'organization') {
    vehicle.owner_id = null
  } else if (!isAdminOrManager) {
    vehicle.owner_id = auth.user.id
  }

  try {
    if (props.id) {
      await axios.put(`/vehicles/${props.id}`, vehicle)
      window.$toast?.showToast('✅ Vehicle updated successfully!')
    } else {
      await axios.post('/vehicles', vehicle)
      window.$toast?.showToast('✅ Vehicle created successfully!')
    }
    router.push('/vehicles')
  } catch (err) {
    if (err.response?.status === 422) {
      Object.assign(errors, err.response.data.errors)
    } else {
      window.$toast?.showToast('❌ Failed to save vehicle. Please try again.', 5000)
    }
  }
}
</script>
