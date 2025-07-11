<template>
  <form @submit.prevent="submit">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
      <div>
        <label>Vehicle</label>
        <select v-model="expense.vehicle_id" required class="input">
          <option value="">Select Vehicle</option>
          <option v-for="v in vehicles" :key="v.id" :value="v.id">
            {{ v.plate_number }} - {{ v.manufacturer }}
          </option>
        </select>
      </div>

      <div>
        <label>Amount (₦)</label>
        <input
          v-model.number="expense.amount"
          type="number"
          required
          class="input"
          placeholder="Enter amount"
        />
      </div>

      <div>
        <label>Description</label>
        <input
          v-model="expense.description"
          type="text"
          required
          class="input"
          placeholder="Description"
        />
      </div>

      <div>
        <label>Date</label>
        <input
          v-model="expense.date"
          type="date"
          required
          class="input"
        />
      </div>
    </div>

    <div class="flex justify-end mt-4 gap-2">
      <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
        {{ props.id ? 'Update' : 'Create' }}
      </button>
      <router-link to="/expenses" class="px-4 py-2 border rounded hover:bg-gray-100">Cancel</router-link>
    </div>
  </form>
</template>

<script setup>
import { reactive, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from '@/axios'

const props = defineProps({ id: [String, Number] })
const router = useRouter()
const route = useRoute()

const expense = reactive({
  vehicle_id: '',
  amount: '',
  description: '',
  date: new Date().toISOString().slice(0, 10),
})

const vehicles = ref([])

onMounted(async () => {
  const vRes = await axios.get('/vehicles')
  vehicles.value = vRes.data

  if (props.id) {
    const res = await axios.get(`/expenses/${props.id}`)
    Object.assign(expense, res.data)
  }
})

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
