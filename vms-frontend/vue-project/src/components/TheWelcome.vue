<script setup>
import WelcomeItem from './WelcomeItem.vue'
import DashboardIcon from './icons/IconDashboard.vue'
import AboutIcon from './icons/IconAbout.vue'
import VehicleIcon from './icons/IconVehicle.vue'
import DriverIcon from './icons/IconDriver.vue'
import CheckInOutIcon from './icons/IconCheckInOut.vue'
import MaintenanceIcon from './icons/IconMaintenance.vue'
import ExpenseIcon from './icons/IconExpense.vue'
import SupportIcon from './icons/IconSupport.vue'
</script>

<template>
  <div>
    <WelcomeItem>
      <template #icon><DashboardIcon /></template>
      <template #heading>Dashboard Overview</template>
      Quickly access summary statistics and visual trends of vehicle activities, maintenance logs, and expenses.
    </WelcomeItem>

    <WelcomeItem>
      <template #icon><VehicleIcon /></template>
      <template #heading>Manage Vehicles</template>
      Add, update, or remove vehicles. Track their status, type, and registration information in one place.
    </WelcomeItem>

    <WelcomeItem>
      <template #icon><DriverIcon /></template>
      <template #heading>Manage Drivers</template>
      Register and assign drivers to vehicles. View contact info and vehicle usage history.
    </WelcomeItem>

    <WelcomeItem>
      <template #icon><CheckInOutIcon /></template>
      <template #heading>Check-In / Check-Out</template>
      Log vehicle movement in and out of the premises. Monitor real-time usage and driver assignments.
    </WelcomeItem>

    <WelcomeItem>
      <template #icon><MaintenanceIcon /></template>
      <template #heading>Maintenance Tracking</template>
      Record and review maintenance activities. Get alerts for upcoming or overdue services.
    </WelcomeItem>

    <WelcomeItem>
      <template #icon><ExpenseIcon /></template>
      <template #heading>Expense Management</template>
      Track and manage vehicle-related expenses including fuel, repairs, and more.
    </WelcomeItem>

    <WelcomeItem>
      <template #icon><SupportIcon /></template>
      <template #heading>Support</template>
      For assistance or suggestions, contact your administrator or reach out via the support channel.
    </WelcomeItem>

    <WelcomeItem>
      <template #icon><AboutIcon /></template>
      <template #heading>About</template>
      This Vehicle Management System (VMS) streamlines tracking, maintenance, and oversight of vehicles and drivers.
    </WelcomeItem>
  </div>
</template>
