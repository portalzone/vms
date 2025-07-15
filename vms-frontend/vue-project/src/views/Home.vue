<template>
  <div class="page-wrapper">
    <div class="container">
      <div class="header">
        <h1>Welcome to the Vehicle Management System</h1>
        <div v-if="auth.user" class="user-info">
          You're logged in as <span>{{ auth.user.name }}</span>.
        </div>
        <div v-if="auth.user" class="dashboard-link">
          <RouterLink to="/dashboard">Go to Dashboard</RouterLink>
        </div>
      </div>

      <h2 class="section-title">About the Vehicle Management System</h2>

      <div class="features-grid">
        <div v-for="(item, index) in features" :key="index" class="feature-card">
          <div class="feature-top">
            <div class="feature-icon">
              <component :is="item.icon" />
            </div>
            <h3>{{ item.title }}</h3>
          </div>
          <p class="feature-description">{{ item.description }}</p>
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

<style scoped>
.page-wrapper {
  min-height: 100vh;
  background-color: #f3f4f6; /* gray-100 */
  padding: 2.5rem 1rem;
}

@media (min-width: 768px) {
  .page-wrapper {
    padding: 4rem 2rem;
  }
}

.container {
  max-width: 112rem; /* max-w-7xl */
  margin: 0 auto;
}

.header {
  text-align: center;
  margin: 0.5rem;
}

.header h1 {
  font-size: 1.5rem;
  font-weight: bold;
  color: #1f2937; /* gray-800 */
  margin-bottom: 0.5rem;
}

@media (min-width: 768px) {
  .header h1 {
    font-size: 1.875rem;
  }
}

.user-info {
  font-size: 1rem;
  color: #047857; /* green-700 */
  font-weight: 500;
}

.user-info span {
  font-weight: 600;
}

.dashboard-link {
  margin-top: 1rem;
}

.dashboard-link a {
  display: inline-block;
  background-color: #16a34a; /* green-600 */
  color: #fff;
  padding: 0.625rem 1.25rem;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  text-decoration: none;
  transition: background-color 0.2s ease;
}

.dashboard-link a:hover {
  background-color: #15803d; /* green-700 */
}

.section-title {
  text-align: center;
  font-size: 1.5rem;
  font-weight: bold;
  color: #1f2937;
  margin-bottom: 1.5rem;
}

@media (min-width: 768px) {
  .section-title {
    font-size: 1.875rem;
  }
}

.features-grid {
  display: grid;
  gap: 1.5rem;
  grid-template-columns: repeat(1, 1fr);
}

@media (min-width: 768px) {
  .features-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (min-width: 1024px) {
  .features-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

.feature-card {
  background-color: #fff;
  border-radius: 0.75rem;
  padding: 1.5rem;
  box-shadow: 0 2px 4px rgba(0,0,0,0.04), 0 4px 6px rgba(0,0,0,0.03);
  transition: 0.2s ease all;
}

.feature-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 20px rgba(0,0,0,0.08);
}

.feature-top {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 0.75rem;
}

.feature-icon {
  width: 3rem;
  height: 3rem;
  border-radius: 9999px;
  background-color: #f3f4f6;
  color: #4b5563;
  font-size: 1.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.feature-top h3 {
  font-size: 1.125rem;
  font-weight: 600;
  color: #111827;
}

.feature-description {
  font-size: 0.875rem;
  color: #4b5563;
}
</style>
