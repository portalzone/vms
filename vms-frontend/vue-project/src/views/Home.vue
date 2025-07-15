<template>
  <div class="min-h-screen flex flex-col items-center justify-center bg-gray-100 px-4 py-8">
    <div class="bg-white shadow-xl rounded-2xl px-6 py-10 md:px-12 w-full max-w-7xl text-center">
      <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-4">
        Welcome to the Vehicle Management System
      </h1>

      <div v-if="auth.user" class="mb-6">
        <p class="text-green-700 font-medium text-base md:text-lg mb-3">
          You're logged in as <span class="font-semibold">{{ auth.user.name }}</span>.
        </p>
        <RouterLink
          to="/dashboard"
          class="inline-block bg-green-600 hover:bg-green-700 text-white px-5 py-2.5 rounded-lg text-sm md:text-base transition"
        >
          Go to Dashboard
        </RouterLink>
      </div>

      <h2 class="text-3xl font-bold text-gray-800 mt-10 mb-6">About the Vehicle Management System</h2>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-left">
        <div
          v-for="(item, index) in features"
          :key="index"
          class="flex items-start p-5 bg-white rounded-xl shadow hover:shadow-md transition h-full"
        >
          <div class="w-12 h-12 bg-gray-100 text-gray-600 rounded-full flex items-center justify-center mr-4 text-xl">
            <component :is="item.icon" />
          </div>
          <div class="flex-1">
            <h3 class="text-lg font-semibold text-gray-900">
              {{ item.title }}
            </h3>
            <p class="text-sm text-gray-600 mt-1">
              {{ item.description }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useAuthStore } from '@/stores/auth'

import IconDashboard from '@/components/icons/IconDashboard.vue'
import IconVehicle from '@/components/icons/IconVehicle.vue'
import IconDriver from '@/components/icons/IconDriver.vue'
import IconCheckInOut from '@/components/icons/IconCheckInOut.vue'
import IconMaintenance from '@/components/icons/IconMaintenance.vue'
import IconExpense from '@/components/icons/IconExpense.vue'
import IconSupport from '@/components/icons/IconSupport.vue'
import IconAbout from '@/components/icons/IconAbout.vue'

const auth = useAuthStore()

const features = [
  {
    icon: IconDashboard,
    title: 'Dashboard Overview',
    description: 'Quickly access summary statistics and visual trends of vehicle activities, maintenance logs, and expenses.',
  },
  {
    icon: IconVehicle,
    title: 'Manage Vehicles',
    description: 'Add, update, or remove vehicles. Track their status, type, and registration information in one place.',
  },
  {
    icon: IconDriver,
    title: 'Manage Drivers',
    description: 'Register and assign drivers to vehicles. View contact info and vehicle usage history.',
  },
  {
    icon: IconCheckInOut,
    title: 'Check-In / Check-Out',
    description: 'Log vehicle movement in and out of the premises. Monitor real-time usage and driver assignments.',
  },
  {
    icon: IconMaintenance,
    title: 'Maintenance Tracking',
    description: 'Record and review maintenance activities. Get alerts for upcoming or overdue services.',
  },
  {
    icon: IconExpense,
    title: 'Expense Management',
    description: 'Track and manage vehicle-related expenses including fuel, repairs, and more.',
  },
  {
    icon: IconSupport,
    title: 'Support',
    description: 'For assistance or suggestions, contact your administrator or reach out via the support channel.',
  },
  {
    icon: IconAbout,
    title: 'About',
    description: 'This Vehicle Management System streamlines tracking, maintenance, and oversight of vehicles and drivers.',
  },
]
</script>
