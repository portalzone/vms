<template>
  <form @submit.prevent="handleSubmit" class="space-y-4 bg-white p-6 rounded shadow">
    <!-- User Selection -->
    <div>
      <label for="user_id">Select User</label>
      <select v-model="form.user_id" id="user_id" class="w-full border rounded px-3 py-2" required>
        <option value="">-- Select User --</option>
        <option v-for="user in users" :key="user.id" :value="user.id">
          {{ user.name }} ({{ user.email }})
        </option>
      </select>
      <p v-if="errors.user_id" class="text-red-600 text-sm">{{ errors.user_id[0] }}</p>
    </div>

    <!-- License Number -->
    <div>
      <label for="license_number">Driver License Number</label>
      <input v-model="form.license_number" type="text" id="license_number" class="w-full border rounded px-3 py-2" required />
      <p v-if="errors.license_number" class="text-red-600 text-sm">{{ errors.license_number[0] }}</p>
    </div>

    <!-- Phone Number -->
    <div>
      <label for="phone_number">Phone Number</label>
      <input v-model="form.phone_number" type="text" id="phone_number" class="w-full border rounded px-3 py-2" required />
      <p v-if="errors.phone_number" class="text-red-600 text-sm">{{ errors.phone_number[0] }}</p>
    </div>

    <!-- Home Address -->
    <div>
      <label for="home_address">Home Address</label>
      <input v-model="form.home_address" type="text" id="home_address" class="w-full border rounded px-3 py-2" />
      <p v-if="errors.home_address" class="text-red-600 text-sm">{{ errors.home_address[0] }}</p>
    </div>

    <!-- Sex -->
    <div>
      <label for="sex">Sex</label>
      <select v-model="form.sex" id="sex" class="w-full border rounded px-3 py-2" required>
        <option value="">Select</option>
        <option value="male">Male</option>
        <option value="female">Female</option>
        <option value="other">Other</option>
      </select>
      <p v-if="errors.sex" class="text-red-600 text-sm">{{ errors.sex[0] }}</p>
    </div>

    <!-- Vehicle Dropdown -->
    <div>
      <label for="vehicle_id">Assign Vehicle</label>
      <select v-model="form.vehicle_id" id="vehicle_id" class="w-full border rounded px-3 py-2">
        <option value="">Select Vehicle</option>
        <option v-for="vehicle in vehicles" :key="vehicle.id" :value="vehicle.id">
          {{ vehicle.plate_number }} - {{ vehicle.manufacturer }} {{ vehicle.model }}
        </option>
      </select>
      <p v-if="errors.vehicle_id" class="text-red-600 text-sm">{{ errors.vehicle_id[0] }}</p>
    </div>

    <!-- Submit -->
    <div>
      <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
        {{ isEdit ? 'Update Driver' : 'Create Driver' }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from '@/axios'

const props = defineProps({
  driverId: [Number, String],
  isEdit: Boolean,
})

const router = useRouter()
const form = ref({
  user_id: '',
  license_number: '',
  phone_number: '',
  home_address: '',
  sex: '',
  vehicle_id: '',
})
const errors = ref({})
const users = ref([])
const vehicles = ref([])

// const loadUsers = async () => {
//   const res = await axios.get('/available-users')
//   users.value = res.data
// }

const loadUsers = async () => {
  const url = props.isEdit
    ? `/users-available-for-drivers?driver_id=${props.driverId}`
    : '/users-available-for-drivers'

  const res = await axios.get(url)
  users.value = res.data
}



const loadVehicles = async () => {
  const res = await axios.get('/vehicles')
  vehicles.value = res.data
}

const loadDriver = async () => {
  if (props.isEdit && props.driverId) {
    const res = await axios.get(`/drivers/${props.driverId}`)
    const d = res.data
    form.value = {
      user_id: d.user_id,
      license_number: d.license_number || '',
      phone_number: d.phone_number || '',
      home_address: d.home_address || '',
      sex: d.sex || '',
      vehicle_id: d.vehicle_id || '',
    }
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

onMounted(() => {
  loadUsers()
  loadVehicles()
  loadDriver()
})
</script>
