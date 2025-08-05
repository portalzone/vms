<template>
  <div class="layout">
    <!-- Header -->
    <header class="navbar">
      <div class="logo">🚗 VMS Portal</div>

      <button class="menu-toggle" @click="toggleMenu" aria-label="Toggle Menu">
        <span v-if="!menuOpen">☰</span>
        <span v-else>✖</span>
      </button>

      <!-- Navigation -->
      <nav :class="{ open: menuOpen }">
        <RouterLink to="/" exact-active-class="active" @click="closeMenu">Home</RouterLink>
        <RouterLink to="/dashboard" exact-active-class="active" @click="closeMenu">Dashboard</RouterLink>
        <RouterLink v-if="hasRole(['admin', 'manager', 'vehicle_owner'])" to="/vehicles" exact-active-class="active" @click="closeMenu">Vehicles</RouterLink>
        <RouterLink v-if="hasRole(['admin', 'manager', 'gate_security'])" to="/vehicle-within" exact-active-class="active" @click="closeMenu">Vehicles Within</RouterLink>
        <RouterLink v-if="hasRole(['admin', 'manager', 'vehicle_owner', 'gate_security'])" to="/drivers" exact-active-class="active" @click="closeMenu">Drivers</RouterLink>
        <RouterLink v-if="hasRole(['admin', 'manager'])" to="/incomes" exact-active-class="active" @click="closeMenu">Income</RouterLink>
        <RouterLink v-if="hasRole(['admin', 'manager'])" to="/recent-activity" exact-active-class="active" @click="closeMenu">Recent Activity</RouterLink>
        <RouterLink v-if="hasRole(['admin', 'manager'])" to="/audit-trail" exact-active-class="active" @click="closeMenu">Audit Trail</RouterLink>
        <RouterLink v-if="hasRole(['admin', 'manager', 'gate_security'])" to="/checkins" exact-active-class="active" @click="closeMenu">Check-Ins</RouterLink>
        <RouterLink v-if="hasRole(['admin', 'manager', 'driver', 'vehicle_owner'])" to="/trips" exact-active-class="active" @click="closeMenu">Trips</RouterLink>
        <RouterLink v-if="hasRole(['admin', 'manager', 'vehicle_owner', 'driver'])" to="/maintenance" exact-active-class="active" @click="closeMenu">Maintenance</RouterLink>
        <RouterLink v-if="hasRole(['admin', 'manager', 'vehicle_owner', 'driver'])" to="/expenses" exact-active-class="active" @click="closeMenu">Expenses</RouterLink>
        <RouterLink v-if="hasRole(['admin', 'manager', 'gate_security'])" to="/users" exact-active-class="active" @click="closeMenu">Users</RouterLink>
        <router-link to="/profile" class="text-sm text-gray-600 hover:text-blue-500" @click="closeMenu">My Profile</router-link>
        <RouterLink to="/about" exact-active-class="active" @click="closeMenu">About</RouterLink>
        <RouterLink to="/support" exact-active-class="active" @click="closeMenu">Support</RouterLink>
        <a href="javascript:void(0)" @click="logout">Logout</a>
      </nav>
    </header>

    <!-- Main Content -->
    <main class="main-content">
      <slot />
    </main>

    <!-- Footer -->
    <footer class="footer">
      &copy; {{ year }} Vehicle Management System. All rights reserved.
    </footer>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'

const props = defineProps(['user', 'logout'])
const year = new Date().getFullYear()
const menuOpen = ref(false)
const router = useRouter()
const auth = useAuthStore()

function logout() {
  auth.logout(router)
}

function toggleMenu() {
  menuOpen.value = !menuOpen.value
}

function closeMenu() {
  menuOpen.value = false
}


function hasRole(allowedRoles) {
  return allowedRoles.includes(props.user?.role)
}


</script>

<style scoped>
.layout {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

/* NAVBAR */
.navbar {
  background-color: #1f2937;
  color: white;
  padding: 1rem 1.5rem;
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  align-items: center;
  position: relative;
}

.logo {
  font-size: 1.25rem;
  font-weight: bold;
}

/* Toggle button */
.menu-toggle {
  display: none;
  background: none;
  border: none;
  font-size: 1.75rem;
  color: white;
  cursor: pointer;
}

/* Navigation menu */
nav {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  align-items: center;
}

nav a {
  color: #d1d5db;
  text-decoration: none;
  transition: color 0.3s ease;
}

nav a:hover,
nav a.active {
  color: white;
  font-weight: bold;
  text-decoration: underline;
}

/* Main content */
.main-content {
  flex: 1;
  width: 100%;
  padding: 1.5rem;
  background-color: #f9fafb;
  box-sizing: border-box;
}

/* Footer */
.footer {
  background-color: #1f2937;
  color: white;
  text-align: center;
  padding: 1rem;
}

/* ================================
   📱 Responsive Styles (Mobile First)
   ================================ */
@media (max-width: 768px) {
  .menu-toggle {
    display: block;
  }

  nav {
    display: none;
    flex-direction: column;
    align-items: flex-start;
    width: 100%;
    background-color: #1f2937;
    padding: 1rem 0;
    position: absolute;
    top: 100%;
    left: 0;
    z-index: 999;
  }

  nav.open {
    display: flex;
  }

  nav a {
    width: 100%;
    padding: 0.75rem 1.5rem;
    font-size: 1rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
  }
}
</style>
