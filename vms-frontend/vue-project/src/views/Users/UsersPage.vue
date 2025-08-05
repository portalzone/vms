<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import axios from '@/axios'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
const auth = useAuthStore()

function hasRole(allowedRoles) {
  return allowedRoles.includes(auth.user?.role)
}

const router = useRouter()
const users = ref([])
const currentPage = ref(1)
const totalPages = ref(1)
const perPage = ref(10)

const sortBy = ref('created_at')
const sortDir = ref('desc')
const roleFilter = ref('')
const sortOption = ref('newest')

const loadUsers = async () => {
  try {
    const res = await axios.get('/users', {
      params: {
        page: currentPage.value,
        per_page: perPage.value,
        sort_by: sortBy.value,
        sort_dir: sortDir.value,
        role: roleFilter.value || undefined,
      }
    })

    users.value = res.data.data
    currentPage.value = res.data.current_page
    totalPages.value = res.data.last_page
  } catch (err) {
    console.error('❌ Failed to load users:', err)
  }
}

const applySortOption = () => {
  switch (sortOption.value) {
    case 'name_asc':
      sortBy.value = 'name'
      sortDir.value = 'asc'
      break
    case 'name_desc':
      sortBy.value = 'name'
      sortDir.value = 'desc'
      break
    case 'newest':
      sortBy.value = 'created_at'
      sortDir.value = 'desc'
      break
    case 'oldest':
      sortBy.value = 'created_at'
      sortDir.value = 'asc'
      break
  }
  loadUsers()
}

const changePage = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page
    loadUsers()
  }
}

const deleteUser = async (id) => {
  if (confirm('Are you sure you want to delete this user?')) {
    await axios.delete(`/users/${id}`)
    loadUsers()
  }
}

watch([roleFilter, perPage], () => {
  currentPage.value = 1
  loadUsers()
})

watch(sortOption, () => {
  currentPage.value = 1
  applySortOption()
})

onMounted(() => {
  applySortOption()
})

const goToCreate = () => router.push('/users/new')
const goToEdit = (user) => router.push(`/users/${user.id}/edit`)

const paginationPages = computed(() => {
  const pages = []
  const total = totalPages.value
  const current = currentPage.value

  if (total <= 5) {
    for (let i = 1; i <= total; i++) pages.push(i)
  } else {
    pages.push(1)

    if (current > 3) pages.push('...')

    const start = Math.max(2, current - 1)
    const end = Math.min(total - 1, current + 1)

    for (let i = start; i <= end; i++) {
      pages.push(i)
    }

    if (current < total - 2) pages.push('...')

    pages.push(total)
  }

  return pages
})
</script>


<template>
  <div>
    <h2 class="text-2xl font-bold mb-4">User Management</h2>

    <!-- Filters & Controls -->
    <div class="mb-4 flex flex-wrap items-center gap-4">
      <select v-model="roleFilter" class="px-3 py-2 border rounded-md text-sm">
        <option value="">All Roles</option>
        <option v-if="hasRole(['admin'])" value="admin">Admin</option>
        <option v-if="hasRole(['admin'])" value="manager">Manager</option>
        <option v-if="hasRole(['admin', 'manager'])" value="driver">Driver</option>
        <option v-if="hasRole(['admin', 'manager'])" value="vehicle_owner">Vehicle Owner</option>
        <option value="staff">Staff</option>
        <option value="visitor">Visitor</option>
        <option v-if="hasRole(['admin', 'manager'])" value="gate_security">Security Officer</option>
      </select>

      <select v-model="sortOption" class="px-3 py-2 border rounded-md text-sm">
        <option value="name_asc">A - Z</option>
        <option value="name_desc">Z - A</option>
        <option value="newest">Newest</option>
        <option value="oldest">Oldest</option>
      </select>

      <select v-model="perPage" class="px-3 py-2 border rounded-md text-sm">
        <option :value="5">5</option>
        <option :value="10">10</option>
        <option :value="25">25</option>
        <option :value="50">50</option>
        <option :value="100">100</option>
      </select>

      <button class="btn-primary" @click="goToCreate">➕ Add User</button>
    </div>

    <!-- Users Table -->
    <table class="w-full border text-sm">
      <thead class="bg-gray-100">
        <tr>
          <th class="px-3 py-2 text-left">Name</th>
          <th class="px-3 py-2 text-left">Email</th>
          <th class="px-3 py-2 text-left">Role</th>
          <th class="px-3 py-2 text-left">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="user in users" :key="user.id" class="border-t hover:bg-gray-50">
          <td class="px-3 py-2">{{ user.name }}</td>
          <td class="px-3 py-2 break-words">{{ user.email }}</td>
          <td class="px-3 py-2 capitalize">{{ user.roles?.[0]?.name || 'N/A' }}</td>
          <td class="px-3 py-2 space-x-2">
            <button class="text-blue-600 hover:underline" @click="goToEdit(user)">✏️ Edit</button>
            <button class="text-red-600 hover:underline" @click="deleteUser(user.id)">🗑️ Delete</button>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Pagination -->
<!-- Pagination -->
<div class="mt-4 flex flex-wrap items-center gap-2">
  <button
    class="btn-pagination"
    :disabled="currentPage === 1"
    @click="changePage(currentPage - 1)"
  >
    Prev
  </button>

  <template v-for="(page, index) in paginationPages" :key="index">
    <span
      v-if="page === '...'"
      class="btn-pagination"
    >
      ...
    </span>
    <button
      v-else
      @click="changePage(page)"
      :class="[
        'btn-pagination',
        { 'bg-blue-600 text-white': currentPage === page }
      ]"
    >
      {{ page }}
    </button>
  </template>

  <button
    class="btn-pagination"
    :disabled="currentPage === totalPages"
    @click="changePage(currentPage + 1)"
  >
    Next
  </button>
</div>


  </div>
</template>
