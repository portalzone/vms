<template>
  <div>
    <div class="flex justify-between mb-4">
      <h1 class="text-xl font-bold">Trip Records</h1>
      <router-link to="/trips/new" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
        ➕ Add Trip
      </router-link>
    </div>

    <TripList :trips="trips" @edit="editTrip" @remove="removeTrip" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import TripList from './TripList.vue'
import { useRouter } from 'vue-router'
import axios from '@/axios'

const trips = ref([])
const router = useRouter()

const fetchTrips = async () => {
  const res = await axios.get('/trips')
  trips.value = res.data
}

const editTrip = (id) => router.push(`/trips/${id}/edit`)
const removeTrip = async (id) => {
  if (confirm('Are you sure you want to delete this trip?')) {
    await axios.delete(`/trips/${id}`)
    await fetchTrips()
  }
}

onMounted(fetchTrips)
</script>
