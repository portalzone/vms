<template>
  <div class="about-container">
    <h1 class="about-heading">About the Vehicle Management System</h1>

    <p class="about-text">
      The Vehicle Management System (VMS) is a comprehensive web application designed to streamline and centralize the management of vehicles, drivers, trips, maintenance logs, expenses, and access control across an organization.
    </p>

    <p class="about-text">
      Built with Laravel and Vue.js, the system supports secure authentication, role-based access (Admin, Manager, Driver, Gate Security, Vehicle Owner), and robust API integration using Laravel Sanctum.
    </p>

    <p class="about-text">
      Key features include:
    </p>

    <ul class="about-list">
      <li>Vehicle and Driver Registration</li>
      <li>Check-in and Check-out Logs</li>
      <li>Trip Tracking with Location and Time</li>
      <li>Maintenance and Expense Logging</li>
      <li>Income logs</li>
      <li>Role-Based Access Control (RBAC)</li>
      <li>Dashboard with Analytics and Trends</li>
      <li>PDF and Excel Export (Planned)</li>
    </ul>

    <p class="about-text">
      This platform aims to improve visibility, accountability, and efficiency in fleet operations and vehicle oversight.
    </p>
  </div>
</template>

<script setup>
// No logic needed for now
</script>

<style scoped>
.about-container {
  max-width: 64rem; /* equivalent to max-w-5xl */
  margin: 0 auto;
  padding: 2rem 1rem;
  background-color: #ffffff;
  border-radius: 0.5rem;
}

.about-heading {
  font-size: 1.875rem; /* text-3xl */
  font-weight: bold;
  color: #1f2937; /* text-gray-800 */
  margin-bottom: 1.5rem;
  text-align: center;
}

.about-text {
  color: #374151; /* text-gray-700 */
  margin-bottom: 1rem;
  line-height: 1.6;
}

.about-list {
  list-style-type: disc;
  padding-left: 1.5rem;
  color: #374151;
  margin-bottom: 1.5rem;
}

.about-list li {
  margin-bottom: 0.5rem;
}
</style>
