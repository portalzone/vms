<template>
  <div class="layout">
    <header class="navbar">
      <div class="logo">🚗 VMS Portal</div>
      <button class="menu-toggle" @click="toggleMenu">
        <span v-if="!menuOpen">☰</span>
        <span v-else>✖</span>
      </button>

      <nav :class="{ open: menuOpen }">
        <RouterLink to="/" exact-active-class="active">Home</RouterLink>
        <RouterLink to="/dashboard" exact-active-class="active">Dashboard</RouterLink>
        <RouterLink v-if="hasRole(['admin', 'manager'])" to="/vehicles" exact-active-class="active">Vehicles</RouterLink>
        <RouterLink v-if="hasRole(['admin', 'manager'])" to="/drivers" exact-active-class="active">Drivers</RouterLink>
        <RouterLink to="/checkins" exact-active-class="active">Check-Ins</RouterLink>
        <RouterLink v-if="hasRole(['admin', 'manager', 'driver'])" to="/trips" exact-active-class="active">Trips</RouterLink>
        <RouterLink v-if="hasRole(['admin', 'manager', 'vehicle_owner'])" to="/maintenance" exact-active-class="active">Maintenance</RouterLink>
        <RouterLink v-if="hasRole(['admin', 'manager'])" to="/expenses" exact-active-class="active">Expenses</RouterLink>
        <RouterLink v-if="hasRole(['admin', 'manager'])" to="/users" exact-active-class="active">Users</RouterLink>
        <RouterLink to="/about" exact-active-class="active">About</RouterLink>
        <RouterLink to="/support" exact-active-class="active">Support</RouterLink>
        <a href="javascript:void(0)" @click="logout">Logout</a>
      </nav>
    </header>

    <main class="main-content">
      <slot />
    </main>

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

.navbar {
  background-color: #1f2937;
  color: white;
  padding: 1rem 2rem;
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  align-items: center;
}

.logo {
  font-size: 1.25rem;
  font-weight: bold;
}

.menu-toggle {
  display: none;
  background: none;
  border: none;
  font-size: 1.5rem;
  color: white;
  cursor: pointer;
}

nav {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
}

nav a {
  color: #d1d5db;
  text-decoration: none;
  transition: color 0.3s;
}

nav a:hover,
nav a.active {
  color: white;
  font-weight: bold;
  text-decoration: underline;
}

.main-content {
  flex: 1;
  width: 100%;
  padding: 2rem;
  background-color: #f9fafb;
  box-sizing: border-box;
}

.footer {
  background-color: #1f2937;
  color: white;
  text-align: center;
  padding: 1rem;
}

@media (max-width: 768px) {
  .menu-toggle {
    display: block;
  }

  nav {
    display: none;
    flex-direction: column;
    width: 100%;
    margin-top: 1rem;
  }

  nav.open {
    display: flex;
  }

  nav a {
    padding: 0.5rem 0;
  }
}
</style>
