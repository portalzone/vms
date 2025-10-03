<template>
  <div>
    <!-- <h2 class="mb-4">{{ route.params.id ? 'Edit Expense' : 'Add Expense' }}</h2> -->
    <ExpenseForm :id="route.params.id" />
  </div>
</template>

<script setup>
import { useRoute } from 'vue-router'
import ExpenseForm from './ExpenseForm.vue'

const route = useRoute()
</script>
