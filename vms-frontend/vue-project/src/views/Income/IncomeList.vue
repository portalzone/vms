<template>
  <div>
    <!-- Top Controls -->
    <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-4">
      <input
        v-model="search"
        placeholder="Search by description or source..."
        class="border rounded px-4 py-2 w-full md:w-1/2"
      />

      <div class="flex items-center gap-2">
        <label class="text-sm">Sort by:</label>
        <select v-model="sortBy" class="border rounded px-2 py-1 text-sm">
          <option value="newest">Newest</option>
          <option value="oldest">Oldest</option>
          <option value="amount-asc">Amount ↑</option>
          <option value="amount-desc">Amount ↓</option>
        </select>
        <label class="text-sm">Per page:</label>
        <select v-model="perPage" class="border rounded px-2 py-1 text-sm">
          <option v-for="n in [5, 10, 20, 50]" :key="n" :value="n">{{ n }}</option>
        </select>
      </div>

      <router-link to="/incomes/create" class="btn-primary">
        ➕ Add Income
      </router-link>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto bg-white shadow rounded">
      <table class="min-w-full table-auto">
        <thead class="bg-gray-100">
          <tr>
            <th class="px-4 py-2 text-left">Trip ID</th>
            <th class="px-4 py-2 text-left">Amount</th>
            <th class="px-4 py-2 text-left">Source</th>
            <th class="px-4 py-2 text-left">Description</th>
            <th class="px-4 py-2 text-left">Date</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="income in sortedIncomes" :key="income.id" class="border-t">
            <td class="px-4 py-2">{{ income.trip?.id ?? '—' }}</td>
            <td class="px-4 py-2">₦{{ income.amount.toLocaleString() }}</td>
            <td class="px-4 py-2">{{ income.source }}</td>
            <td class="px-4 py-2">{{ income.description }}</td>
            <td class="px-4 py-2">{{ formatDate(income.date) }}</td>
          </tr>
          <tr v-if="sortedIncomes.length === 0">
            <td colspan="5" class="text-center text-gray-500 py-4">No income records found.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="mt-4 flex justify-center gap-2 flex-wrap text-sm">
      <button :disabled="page === 1" @click="page--" class="btn-pagination">Prev</button>
      <button
        v-for="p in visiblePages"
        :key="p"
        @click="typeof p === 'number' && (page = p)"
        :class="[
          'btn-pagination',
          { 'bg-blue-600 text-white': p === page, 'pointer-events-none text-gray-400': p === '...' }
        ]"
        :disabled="p === '...'"
      >
        {{ p }}
      </button>
      <button :disabled="page === totalPages" @click="page++" class="btn-pagination">Next</button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import axios from '@/axios';

const incomes = ref([]);
const search = ref('');
const sortBy = ref('newest');
const perPage = ref(10);
const page = ref(1);

const formatDate = (date) => new Date(date).toLocaleDateString();

const fetchIncomes = async () => {
  const res = await axios.get('/incomes');
  incomes.value = Array.isArray(res.data.data) ? res.data.data : res.data;
};

onMounted(fetchIncomes);

const filteredIncomes = computed(() => {
  const term = search.value.toLowerCase();
  return incomes.value.filter(i =>
    i.description?.toLowerCase().includes(term) || i.source?.toLowerCase().includes(term)
  );
});

const sortedIncomes = computed(() => {
  let data = [...filteredIncomes.value];

  switch (sortBy.value) {
    case 'newest':
      data.sort((a, b) => new Date(b.date) - new Date(a.date));
      break;
    case 'oldest':
      data.sort((a, b) => new Date(a.date) - new Date(b.date));
      break;
    case 'amount-asc':
      data.sort((a, b) => a.amount - b.amount);
      break;
    case 'amount-desc':
      data.sort((a, b) => b.amount - a.amount);
      break;
  }

  const start = (page.value - 1) * perPage.value;
  return data.slice(start, start + perPage.value);
});

const totalPages = computed(() => Math.ceil(filteredIncomes.value.length / perPage.value));

const visiblePages = computed(() => {
  const total = totalPages.value;
  const current = page.value;
  const range = [];

  if (total <= 6) {
    for (let i = 1; i <= total; i++) range.push(i);
  } else {
    range.push(1);
    if (current > 3) range.push('...');
    for (let i = Math.max(2, current - 1); i <= Math.min(total - 1, current + 1); i++) range.push(i);
    if (current < total - 2) range.push('...');
    range.push(total);
  }

  return range;
});

watch([search, perPage], () => page.value = 1);
</script>
