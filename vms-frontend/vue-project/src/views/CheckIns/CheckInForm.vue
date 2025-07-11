<template>
  <form @submit.prevent="handleSubmit" class="bg-white p-6 rounded shadow space-y-4">
    <div>
      <label>Vehicle</label>
      <select v-model="form.vehicle_id" required class="w-full border rounded px-3 py-2">
        <option value="">Select Vehicle</option>
        <option v-for="v in vehicles" :key="v.id" :value="v.id">
          {{ v.plate_number }} - {{ v.manufacturer }}
        </option>
      </select>
    </div>

    <div>
      <label>Driver</label>
      <select v-model="form.driver_id" required class="w-full border rounded px-3 py-2">
        <option value="">Select Driver</option>
        <option v-for="d in drivers" :key="d.id" :value="d.id">
          {{ d.name }}
        </option>
      </select>
    </div>

    <div v-if="isEdit">
      <label>Checked In At</label>
      <input type="datetime-local" v-model="form.checked_in_at" class="w-full border rounded px-3 py-2" />
    </div>

    <div class="flex gap-4">
      <button
        v-if="!isEdit"
        type="submit"
        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
      >
        Check In
      </button>

      <button
        v-if="isEdit"
        type="submit"
        class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700"
      >
        Update Check-In
      </button>

      <button
        v-if="isEdit"
        type="button"
        @click="handleCheckOut"
        class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700"
      >
        ✅ Check Out
      </button>
    </div>
  </form>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from '@/axios'

const props = defineProps({
  checkInId: [Number, String],
  isEdit: Boolean,
})

const form = ref({
  vehicle_id: '',
  driver_id: '',
  checked_in_at: new Date().toISOString().slice(0, 16), // Set now as default
})

const vehicles = ref([])
const drivers = ref([])
const router = useRouter()

const fetchData = async () => {
  const [vRes, dRes] = await Promise.all([
    axios.get('/vehicles'),
    axios.get('/drivers'),
  ])
  vehicles.value = vRes.data
  drivers.value = dRes.data

  if (props.isEdit && props.checkInId) {
    const res = await axios.get(`/checkins/${props.checkInId}`)
    form.value = {
      vehicle_id: res.data.vehicle_id,
      driver_id: res.data.driver_id,
      checked_in_at: res.data.checked_in_at?.slice(0, 16) || '',
    }
  }
}

const handleSubmit = async () => {
  if (props.isEdit) {
    await axios.put(`/checkins/${props.checkInId}`, form.value)
  } else {
    await axios.post('/checkins', {
      ...form.value,
      checked_in_at: new Date().toISOString(), // Auto check-in time
    })
  }
  router.push('/checkins')
}

const handleCheckOut = async () => {
  if (!props.checkInId) return
  await axios.put(`/checkins/${props.checkInId}`, {
    checked_out_at: new Date().toISOString(),
  })
  router.push('/checkins')
}

onMounted(fetchData)
</script>
