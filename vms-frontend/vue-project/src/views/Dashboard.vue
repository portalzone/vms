<template>
  <div>
    <component
      :is="dashboardComponent"
      :stats="stats"
      :auth="auth"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import axios from '@/axios'

import AdminDashboard from './AdminDashboard.vue'
import DriverDashboard from './DriverDashboard.vue'
import VehicleOwnerDashboard from './VehicleOwnerDashboard.vue'
import GateSecurityDashboard from './GateSecurityDashboard.vue'
import UserDashboard from './UserDashboard.vue'

const auth = useAuthStore()
const stats = ref({})

const dashboardComponent = computed(() => {
  const role = auth.user?.role?.toLowerCase()
  switch (role) {
    case 'admin':
      return AdminDashboard
    case 'manager':
      return AdminDashboard
    case 'driver':
      return DriverDashboard
    case 'vehicle_owner':
    case 'vehicleowner':
      return VehicleOwnerDashboard
    case 'gate_security':
    case 'gatesecurity':
      return GateSecurityDashboard
    default:
      return UserDashboard
  }
})

const fetchStats = async () => {
  try {
    const res = await axios.get('/dashboard/stats')
    stats.value = res.data
  } catch (error) {
    console.error('Failed to load dashboard stats:', error)
  }
}

onMounted(() => {
  fetchStats()
})
</script>

<style scoped>
/* Optionally style wrapper here */
</style>
