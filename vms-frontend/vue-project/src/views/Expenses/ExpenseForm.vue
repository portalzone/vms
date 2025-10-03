<template>
  <div v-if="userRole === 'admin' || userRole === 'manager'" class="auth-card">
    <!-- ✅ Allowed roles can see the form -->
    <form @submit.prevent="submit" class="max-w-3xl p-6 mx-auto bg-white rounded-lg shadow-md">
      <h2 class="pb-2 mb-6 text-xl font-semibold border-b">Create / Update Expense</h2>

      <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <!-- Vehicle Dropdown -->
        <div>
          <label class="block mb-1 text-sm font-medium">Vehicle</label>
          <select
            v-model="expense.vehicle_id"
            @change="fetchMaintenances"
            required
            class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring focus:border-blue-500"
          >
            <option value="">Select Vehicle</option>
            <option v-for="v in vehicles" :key="v.id" :value="v.id">
              {{ v.plate_number }} - {{ v.manufacturer }}
            </option>
          </select>
        </div>

        <!-- Maintenance Dropdown -->
        <div>
          <label class="block mb-1 text-sm font-medium">Maintenance (optional)</label>
          <select
            v-model="expense.maintenance_id"
            class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring focus:border-blue-500"
          >
            <option value="">-- Not tied to any maintenance --</option>
            <option v-for="m in maintenances" :key="m.id" :value="m.id">
              {{ m.description }} - {{ m.date }}
            </option>
          </select>
        </div>

        <!-- Amount Input -->
        <div>
          <label class="block mb-1 text-sm font-medium">Amount (₦)</label>
          <input
            v-model.number="expense.amount"
            type="number"
            required
            placeholder="Enter amount"
            class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring focus:border-blue-500"
          />
        </div>

        <!-- Description Input -->
        <div>
          <label class="block mb-1 text-sm font-medium">Description</label>
          <input
            v-model="expense.description"
            type="text"
            required
            placeholder="Description"
            class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring focus:border-blue-500"
          />
        </div>

        <!-- Date Input -->
        <div>
          <label class="block mb-1 text-sm font-medium">Date</label>
          <input
            v-model="expense.date"
            type="date"
            required
            class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring focus:border-blue-500"
          />
        </div>
      </div>

      <div class="flex justify-end gap-3 mt-6">
        <button
          type="submit"
          class="px-5 py-2 font-semibold text-white bg-green-600 rounded shadow hover:bg-green-700"
        >
          {{ props.id ? 'Update' : 'Create' }}
        </button>
        <router-link
          to="/expenses"
          class="px-4 py-2 font-medium text-gray-800 bg-gray-200 rounded shadow hover:bg-gray-300"
        >
          Cancel
        </router-link>
      </div>
    </form>
  </div>

  <div v-else class="mt-10 font-semibold text-center text-red-600">
    You are not authorized to create or update expenses.
  </div>
</template>

<script setup>
import { reactive, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from '@/axios'

const props = defineProps({ id: [String, Number] })
const router = useRouter()

const expense = reactive({
  vehicle_id: '',
  maintenance_id: '', // ✅ New field
  amount: '',
  description: '',
  date: new Date().toISOString().slice(0, 10),
})

const vehicles = ref([])
const maintenances = ref([])

const userRole = ref(null)

onMounted(async () => {
  try {
    const response = await axios.get('/me')
    userRole.value = response.data.user.role // ✅ Correct

    const vRes = await axios.get('/vehicles')
    // vehicles.value = vRes.data;
    vehicles.value = vRes.data.data || vRes.data

    if (props.id) {
      const res = await axios.get(`/expenses/${props.id}`)
      Object.assign(expense, res.data)

      if (expense.vehicle_id) {
        await fetchMaintenances() // fetch maintenances if editing
      }
    }
  } catch (err) {
    console.error(err)
    alert('Error loading data.')
  }
})

const fetchMaintenances = async () => {
  if (!expense.vehicle_id) return
  const res = await axios.get(`/vehicles/${expense.vehicle_id}/maintenances`)
  maintenances.value = res.data
}

const submit = async () => {
  try {
    if (props.id) {
      await axios.put(`/expenses/${props.id}`, expense)
    } else {
      await axios.post('/expenses', expense)
    }
    router.push('/expenses')
  } catch (err) {
    console.error('Error:', err.response?.data?.errors || err)
    alert('Failed to submit. Please check the form.')
  }
}
</script>
