<template>
  <div class="bg-white rounded shadow p-6">
    <!-- <h2 class="text-xl font-bold mb-4">
      {{ props.id ? 'Edit Vehicle' : 'Add New Vehicle' }}
    </h2> -->

    <form @submit.prevent="submit">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div>
          <label class="block mb-1 font-medium text-gray-700">Manufacturer</label>
          <input
            v-model="vehicle.manufacturer"
            type="text"
            required
            class="w-full border rounded px-3 py-2"
            placeholder="Toyota, Ford..."
          />
        </div>

        <div>
          <label class="block mb-1 font-medium text-gray-700">Model</label>
          <input
            v-model="vehicle.model"
            type="text"
            required
            class="w-full border rounded px-3 py-2"
            placeholder="Camry, F-150..."
          />
        </div>

        <div>
          <label class="block mb-1 font-medium text-gray-700">Year</label>
          <input
            v-model.number="vehicle.year"
            type="number"
            required
            class="w-full border rounded px-3 py-2"
            placeholder="2020"
          />
        </div>

        <div>
          <label class="block mb-1 font-medium text-gray-700">Plate Number</label>
          <input
            v-model="vehicle.plate_number"
            type="text"
            required
            class="w-full border rounded px-3 py-2"
            placeholder="ABC-123-XY"
          />
        </div>
      </div>
      <br>

      <div class="flex justify-end mt-6 gap-2">
        <button type="submit" class="btn-submit">
          {{ props.id ? 'Update' : 'Create' }}
        </button>

        <router-link to="/vehicles" class="btn-cancel">
          Cancel
        </router-link>
      </div>
    </form>
  </div>
</template>


<script setup>
import { reactive, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from '@/axios'

const props = defineProps({ id: [String, Number] })
const router = useRouter()
const route = useRoute()

const vehicle = reactive({
  manufacturer: '',
  model: '',
  year: '',
  plate_number: '',
})

onMounted(async () => {
  if (props.id) {
    try {
      const res = await axios.get(`/vehicles/${props.id}`)
      Object.assign(vehicle, res.data)
    } catch (err) {
      console.error('❌ Failed to load vehicle:', err)
      alert('Error loading vehicle. Please try again.')
    }
  }
})

const submit = async () => {
  try {
    if (props.id) {
      await axios.put(`/vehicles/${props.id}`, vehicle)
    } else {
      await axios.post('/vehicles', vehicle)
    }
    router.push('/vehicles')
  } catch (err) {
    console.error('❌ Failed to save vehicle:', err)
    alert('Failed to save vehicle. Please check your input and try again.')
  }
}
</script>
