<template>
  <div>
    <!-- Search & Add Button -->
    <div class="flex justify-between items-center mb-4 gap-4">
      <input
        v-model="search"
        type="text"
        placeholder="Search description or amount..."
        class="border px-4 py-2 rounded w-full md:w-1/2"
      />
<router-link to="/expenses/new" class="btn-primary">
  ➕ Add Expense
</router-link>
    </div>

    <!-- Expenses Table -->
    <div class="overflow-x-auto rounded shadow bg-white">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Vehicle</th>
            <th>Amount (₦)</th>
            <th>Description</th>
            <th>Date</th>
            <th class="text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(expense, index) in paginated" :key="expense.id">
            <td>{{ start + index + 1 }}</td>
            <td>{{ expense.vehicle?.plate_number || 'N/A' }}</td>
            <td>₦{{ Number(expense.amount).toFixed(2) }}</td>
            <td>{{ expense.description }}</td>
            <td>{{ expense.date }}</td>
            <td class="text-right space-x-2">
<button @click="edit(expense.id)" class="btn-link btn-edit">Edit</button> | 
<button @click="remove(expense.id)" class="btn-link btn-delete">Delete</button>

            </td>
          </tr>
          <tr v-if="paginated.length === 0">
            <td colspan="6" class="text-center text-gray-500 py-4">No expenses found.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6 flex justify-center gap-2 flex-wrap">
      <button :disabled="page === 1" @click="page--" class="btn">Prev</button>
      <button
        v-for="p in visiblePages"
        :key="p"
        @click="typeof p === 'number' && (page = p)"
        :class="{
          'bg-blue-600 text-white': p === page,
          'btn': true,
          'text-gray-400 pointer-events-none': typeof p !== 'number',
        }"
      >
        {{ p }}
      </button>
      <button :disabled="page === totalPages" @click="page++" class="btn">Next</button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import axios from '@/axios'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

const allExpenses = ref([])
const search = ref('')
const page = ref(1)
const perPage = 10

// ✅ Fetch only if authenticated
const fetchExpenses = async () => {
  if (!auth.token) return
  try {
    const res = await axios.get('/expenses')
    allExpenses.value = Array.isArray(res.data) ? res.data : res.data.data || []
  } catch (err) {
    console.error('❌ Failed to fetch expenses:', err.response?.data || err.message)
    alert('Error loading expenses. Please log in again.')
  }
}

onMounted(() => {
  if (auth.token) {
    fetchExpenses()
  }
})

const filtered = computed(() => {
  const term = search.value.toLowerCase()
  return allExpenses.value.filter(exp =>
    (exp.description || '').toLowerCase().includes(term) ||
    (exp.amount || '').toString().includes(term)
  )
})

const start = computed(() => (page.value - 1) * perPage)
const paginated = computed(() => filtered.value.slice(start.value, start.value + perPage))
const totalPages = computed(() => Math.max(1, Math.ceil(filtered.value.length / perPage)))

const visiblePages = computed(() => {
  const total = totalPages.value
  const current = page.value
  if (total <= 6) return [...Array(total).keys()].map(n => n + 1)
  if (current <= 3) return [1, 2, 3, '...', total]
  if (current >= total - 2) return [1, '...', total - 2, total - 1, total]
  return [1, '...', current - 1, current, current + 1, '...', total]
})

watch(search, () => (page.value = 1))

const edit = id => router.push(`/expenses/${id}/edit`)
const remove = async id => {
  if (confirm('Delete this expense?')) {
    try {
      await axios.delete(`/expenses/${id}`)
      await fetchExpenses()
    } catch (err) {
      console.error('❌ Delete failed:', err)
      alert('Failed to delete expense.')
    }
  }
}
</script>
