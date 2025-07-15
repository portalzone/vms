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
        <RouterLink v-if="route.path !== '/'" to="/" exact-active-class="active">Home</RouterLink>
        <RouterLink to="/about" exact-active-class="active">About</RouterLink>
        <RouterLink to="/support" exact-active-class="active">Support</RouterLink>
        <RouterLink to="/login" exact-active-class="active">Login</RouterLink>
        <RouterLink to="/register" exact-active-class="active">Register</RouterLink>
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
import { ref } from 'vue'
import { useRoute } from 'vue-router'

const year = new Date().getFullYear()
const menuOpen = ref(false)
const route = useRoute()

function toggleMenu() {
  menuOpen.value = !menuOpen.value
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
