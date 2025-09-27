<template>
  <form @submit.prevent="handleSubmit" class="p-6 space-y-4 bg-white rounded shadow">
    <!-- User Selection -->
    <div>
      <label for="user_id">Select User</label>
      <div v-if="form.user_id">
        <p class="text-sm text-gray-600">
          Selected: {{ selectedUser?.name }} ({{ selectedUser?.email }})
        </p>
      </div>

      <select v-model="form.user_id" id="user_id" class="w-full px-3 py-2 border rounded" required>
        <option value="">-- Select User --</option>
        <option v-for="user in users" :key="user.id" :value="user.id">
          {{ user.name }} ({{ user.email }})
        </option>
      </select>
      <p v-if="errors.user_id" class="text-sm text-red-600">{{ errors.user_id[0] }}</p>
    </div>

    <!-- License Number -->
    <div>
      <label for="license_number">Driver License Number</label>
      <input
        v-model="form.license_number"
        type="text"
        id="license_number"
        class="w-full px-3 py-2 border rounded"
        required
      />
      <p v-if="errors.license_number" class="text-sm text-red-600">
        {{ errors.license_number[0] }}
      </p>
    </div>

    <!-- Phone Number -->
    <div>
      <label for="phone_number">Phone Number</label>
      <input
        v-model="form.phone_number"
        type="text"
        id="phone_number"
        class="w-full px-3 py-2 border rounded"
        required
      />
      <p v-if="errors.phone_number" class="text-sm text-red-600">{{ errors.phone_number[0] }}</p>
    </div>

    <!-- Home Address -->
    <div>
      <label for="home_address">Home Address</label>
      <input
        v-model="form.home_address"
        type="text"
        id="home_address"
        class="w-full px-3 py-2 border rounded"
      />
      <p v-if="errors.home_address" class="text-sm text-red-600">{{ errors.home_address[0] }}</p>
    </div>

    <!-- Sex -->
    <div>
      <label for="sex">Sex</label>
      <select v-model="form.sex" id="sex" class="w-full px-3 py-2 border rounded" required>
        <option value="">Select</option>
        <option value="male">Male</option>
        <option value="female">Female</option>
        <option value="other">Other</option>
      </select>
      <p v-if="errors.sex" class="text-sm text-red-600">{{ errors.sex[0] }}</p>
    </div>
    <!-- Driver type-->
    <label class="block mb-1 font-medium">Driver Type</label>
    <select
      v-model="form.driver_type"
      class="w-full px-4 py-2 mb-4 border rounded"
      :disabled="isGateSecurity"
    >
      <option v-if="hasRole(['admin', 'manager'])" value="staff">Staff</option>
      <option value="visitor">Visitor</option>
      <option v-if="hasRole(['admin', 'manager'])" value="organization">Organization</option>
      <option v-if="hasRole(['admin', 'manager'])" value="vehicle_owner">Vehicle Owner</option>
    </select>

    <!-- Vehicle Dropdown -->
    <div>
      <label for="vehicle_id">Assign Vehicle</label>
      <select v-model="form.vehicle_id" id="vehicle_id" class="w-full px-3 py-2 border rounded">
        <option value="">Select Vehicle</option>
        <option v-for="vehicle in vehicles" :key="vehicle.id" :value="vehicle.id">
          {{ vehicle.manufacturer }} {{ vehicle.model }} ({{ vehicle.plate_number }})
        </option>
      </select>
      <p v-if="errors.vehicle_id" class="text-sm text-red-600">{{ errors.vehicle_id[0] }}</p>
    </div>

    <!-- Submit -->
    <div>
      <button type="submit" class="px-4 py-2 text-white bg-blue-600 rounded hover:bg-blue-700">
        {{ isEdit ? 'Update Driver' : 'Create Driver' }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import axios from '@/axios'

import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
function hasRole(allowedRoles) {
  return allowedRoles.includes(auth.user?.role)
}

const props = defineProps({
  driverId: [Number, String],
  isEdit: Boolean,
})

const router = useRouter()

const user = JSON.parse(localStorage.getItem('user') || '{}')
const isGateSecurity = user?.role === 'gate_security'

const form = ref({
  user_id: '',
  license_number: '',
  phone_number: '',
  home_address: '',
  sex: '',
  vehicle_id: '',
  driver_type: '', // Default
})

const errors = ref({})
const users = ref([])
const vehicles = ref([])

const selectedUser = computed(() => users.value.find((u) => u.id === form.value.user_id))

const loadUsers = async () => {
  const url = props.isEdit
    ? `/users-available-for-drivers?driver_id=${props.driverId}`
    : '/users-available-for-drivers'
  const res = await axios.get(url)
  users.value = res.data
}

const loadVehicles = async () => {
  const url = props.isEdit
    ? `/vehicles-available-for-drivers?driver_id=${props.driverId}`
    : '/vehicles-available-for-drivers'
  const res = await axios.get(url)
  vehicles.value = res.data
}

const loadDriver = async () => {
  if (props.isEdit && props.driverId) {
    const res = await axios.get(`/drivers/${props.driverId}`)
    const d = res.data

    form.value.user_id = d.user_id || ''
    form.value.license_number = d.license_number || ''
    form.value.phone_number = d.phone_number || ''
    form.value.home_address = d.home_address || ''
    form.value.sex = d.sex || ''
    form.value.vehicle_id = d.vehicle?.id || ''

    // Correct fallback
    form.value.driver_type = d.driver_type ?? (isGateSecurity ? 'visitor' : 'staff')
  }
}

const handleSubmit = async () => {
  errors.value = {}
  try {
    if (props.isEdit) {
      await axios.put(`/drivers/${props.driverId}`, form.value)
    } else {
      await axios.post('/drivers', form.value)
    }

    router.push('/drivers')
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors
    } else {
      console.error(error)
      alert('Failed to save driver')
    }
  }
}
// You can also force the driver_type value
onMounted(async () => {
  await Promise.all([loadUsers(), loadVehicles()])

  if (props.isEdit) {
    await loadDriver() // Load from DB
  } else if (isGateSecurity) {
    // Only set default for new driver
    form.value.driver_type = 'visitor'
  } else {
    form.value.driver_type = 'staff' // default for new driver
  }
})
</script>
