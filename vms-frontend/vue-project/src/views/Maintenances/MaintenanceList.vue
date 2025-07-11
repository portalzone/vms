<template>
  <div>
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-xl font-bold">Maintenance Records</h2>
      <router-link to="/maintenances/new" class="bg-blue-600 text-white px-4 py-2 rounded">Add</router-link>
    </div>

    <input
      v-model="search"
      type="text"
      placeholder="Search by vehicle or description"
      class="mb-4 p-2 border rounded w-full"
    />

    <table class="w-full border text-sm">
      <thead class="bg-gray-100">
        <tr>
          <th class="p-2 border">#</th>
          <th class="p-2 border">Vehicle</th>
          <th class="p-2 border">Description</th>
          <th class="p-2 border">Status</th>
          <th class="p-2 border text-right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(item, index) in filteredMaintenances" :key="item.id">
          <td class="p-2 border">{{ index + 1 }}</td>
          <td class="p-2 border">
            {{ item.vehicle?.plate_number ?? 'N/A' }} - {{ item.vehicle?.manufacturer ?? '' }}
          </td>
          <td class="p-2 border">{{ item.description }}</td>
          <td class="p-2 border">
            <span :class="item.status === 'Completed' ? 'text-green-600' : 'text-yellow-600'">
              {{ item.status }}
            </span>
          </td>
          <td class="p-2 border text-right">
            <router-link :to="`/maintenances/${item.id}/edit`" class="text-blue-600 mr-2">Edit</router-link>
            <button @click="remove(item.id)" class="text-red-600">Delete</button>
          </td>
        </tr>
        <tr v-if="filteredMaintenances.length === 0">
          <td colspan="5" class="text-center py-4 text-gray-500">No maintenance records found.</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from '@/axios'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const maintenances = ref([])
const search = ref('')

// ✅ Only fetch if authenticated
const fetch = async () => {
  if (!auth.token) return
  try {
    const res = await axios.get('/maintenances')
    maintenances.value = Array.isArray(res.data) ? res.data : res.data.data || []
  } catch (err) {
    console.error('❌ Failed to fetch maintenance records:', err.response?.data || err.message)
    alert('Failed to load maintenance records. Please log in again.')
  }
}

const remove = async (id) => {
  if (confirm('Are you sure?')) {
    try {
      await axios.delete(`/maintenances/${id}`)
      await fetch()
    } catch (err) {
      console.error('❌ Failed to delete maintenance record:', err)
      alert('Failed to delete maintenance record.')
    }
  }
}

const filteredMaintenances = computed(() => {
  const keyword = search.value.toLowerCase()
  return maintenances.value.filter(item =>
    item.description?.toLowerCase().includes(keyword) ||
    item.vehicle?.plate_number?.toLowerCase().includes(keyword)
  )
})

onMounted(fetch)
</script>
