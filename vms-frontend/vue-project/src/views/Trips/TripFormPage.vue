<template>
  <div class="p-4">
    <div v-if="loading">Loading...</div>
    <!-- Pass empty trip object if creating -->
    <TripForm v-else :trip="trip" :isEdit="isEdit" />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import axios from '@/axios'
import TripForm from '../Trips/TripForm.vue'

const route = useRoute()
const trip = ref(null)
const loading = ref(true)

const id = route.params.id
const isEdit = computed(() => !!id)

onMounted(async () => {
  if (!isEdit.value) {
    // 👇 When creating a new trip, use empty object
    trip.value = {
      driver_id: '',
      vehicle_id: '',
      start_location: '',
      end_location: '',
      start_time: '',
      end_time: ''
    }
    loading.value = false
    return
  }

  try {
    const res = await axios.get(`/trips/${id}`)
    trip.value = res.data
  } catch (e) {
    console.error('Trip not found', e)
  } finally {
    loading.value = false
  }
})
</script>
