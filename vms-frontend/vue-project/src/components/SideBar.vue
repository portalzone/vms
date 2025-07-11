<template>
  <div class="flex h-screen bg-gray-100">
    <!-- Sidebar -->
    <aside class="w-64 bg-white shadow-md flex flex-col">
      <div class="p-6 text-lg font-bold text-gray-800 border-b">
        VMS Dashboard
      </div>
      <nav class="flex-1 p-4 space-y-2 text-sm">
        <RouterLink to="/dashboard" class="nav-link" active-class="active">Dashboard</RouterLink>
        <RouterLink to="/vehicles" class="nav-link" active-class="active">Vehicles</RouterLink>
        <RouterLink to="/drivers" class="nav-link" active-class="active">Drivers</RouterLink>
        <RouterLink to="/checkins" class="nav-link" active-class="active">Check-Ins</RouterLink>
        <RouterLink to="/maintenances" class="nav-link" active-class="active">Maintenances</RouterLink>
        <RouterLink to="/expenses" class="nav-link" active-class="active">Expenses</RouterLink>
        <RouterLink to="/users" class="nav-link" active-class="active">Users</RouterLink>
        <button @click="logout" class="w-full text-left text-red-600 hover:text-red-800 mt-4">
          Logout
        </button>
      </nav>
    </aside>

    <!-- Page content -->
    <main class="flex-1 overflow-y-auto p-6">
      <slot />
    </main>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()

const logout = async () => {
  try {
    await axios.post('/api/logout')
    localStorage.removeItem('token')
    router.push('/login')
  } catch (error) {
    console.error('Logout error:', error)
  }
}
</script>

<style scoped>
.nav-link {
  display: block;
  padding: 0.5rem 0.75rem;
  color: #4b5563; /* text-gray-600 */
  border-radius: 0.375rem;
  transition: background-color 0.2s;
}

.nav-link:hover {
  background-color: #f3f4f6; /* bg-gray-100 */
}

.active {
  background-color: #e5e7eb; /* bg-gray-200 */
  color: #111827; /* text-gray-900 */
  font-weight: 600;
}
</style>
