<template>
  <div class="max-w-2xl mx-auto bg-white p-6 rounded shadow">
    <h2 class="text-xl font-bold mb-4">
      {{ isEdit ? "Edit Vehicle" : "Add Vehicle" }}
    </h2>

    <form @submit.prevent="handleSubmit" class="space-y-4">
      <!-- Manufacturer -->
      <div>
        <label class="block mb-1">Manufacturer</label>
        <input
          v-model="form.manufacturer"
          type="text"
          class="w-full border rounded p-2"
          required
        />
      </div>

      <!-- Model -->
      <div>
        <label class="block mb-1">Model</label>
        <input
          v-model="form.model"
          type="text"
          class="w-full border rounded p-2"
          required
        />
      </div>

      <!-- Plate Number -->
      <div>
        <label class="block mb-1">Plate Number</label>
        <input
          v-model="form.plate_number"
          type="text"
          class="w-full border rounded p-2"
          required
        />
      </div>

      <!-- Ownership Type -->
      <div>
        <label class="block mb-1">Ownership Type</label>
        <select
          v-model="form.ownership_type"
          class="w-full border rounded p-2"
          required
        >
          <option value="">Select Ownership</option>
          <option value="organization">Organization</option>
          <option value="individual">Individual</option>
        </select>
      </div>

      <!-- If Individual -->
      <div v-if="form.ownership_type === 'individual'">
        <label class="block mb-1">Individual Type</label>
        <select v-model="form.individual_type" class="w-full border rounded p-2">
          <option value="">Select Individual Type</option>
          <option value="staff">Staff</option>
          <option value="visitor">Visitor</option>
          <option value="vehicle_owner">Vehicle Owner</option>
        </select>
      </div>

      <!-- Vehicle Owner Dropdown -->
      <div v-if="form.individual_type === 'vehicle_owner'">
        <label class="block mb-1">Vehicle Owner</label>
        <select v-model="form.owner_id" class="w-full border rounded p-2">
          <option value="">Select Vehicle Owner</option>
          <option
            v-for="owner in vehicleOwners"
            :key="owner.id"
            :value="owner.id"
          >
            {{ owner.name }}
          </option>
        </select>
      </div>

      <!-- Submit Button -->
      <div>
        <button
          type="submit"
          class="bg-blue-600 text-white px-4 py-2 rounded"
        >
          {{ isEdit ? "Update" : "Create" }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue"
import { useRoute, useRouter } from "vue-router"
import axios from "@/axios"

const router = useRouter()
const route = useRoute()

// Form state
const form = ref({
  manufacturer: "",
  model: "",
  plate_number: "",
  ownership_type: "",
  individual_type: "",
  owner_id: null,
})

const vehicleOwners = ref([])
const isEdit = ref(false)

const fetchVehicleOwners = async () => {
  try {
    const res = await axios.get("/vehicle-owners")
    // handle if API returns paginated data
    vehicleOwners.value = res.data.data || res.data
  } catch (err) {
    console.error("Error fetching vehicle owners:", err)
  }
}

onMounted(async () => {
  await fetchVehicleOwners()

  if (route.params.id) {
    isEdit.value = true
    try {
      const res = await axios.get(`/vehicles/${route.params.id}`)
      form.value = res.data
    } catch (err) {
      console.error("Error loading vehicle:", err)
    }
  }
})

const handleSubmit = async () => {
  try {
    // Prepare payload
    const payload = { ...form.value }

    if (payload.ownership_type === "organization") {
      payload.individual_type = null
      payload.vehicle_owner_id = null
    } else if (payload.individual_type !== "vehicle_owner") {
      payload.vehicle_owner_id = null
    }

    if (isEdit.value) {
      await axios.put(`/vehicles/${route.params.id}`, payload)
    } else {
      await axios.post("/vehicles", payload)
    }

    router.push("/vehicles")
  } catch (err) {
    console.error("Error saving vehicle:", err.response?.data || err.message)
  }
}
</script>
