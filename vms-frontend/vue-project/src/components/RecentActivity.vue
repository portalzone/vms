<template>
  <div class="mt-10">
    <h3 class="text-lg font-bold mb-4">Recent Activity</h3>
    <ul class="space-y-4">
      <li
        v-for="(activity, index) in activities"
        :key="index"
        class="flex items-start gap-4 p-4 bg-white rounded shadow"
      >
        <span
          class="inline-flex items-center justify-center w-8 h-8 rounded-full text-white"
          :class="{
            'bg-blue-500': activity.type === 'Check-In',
            'bg-red-500': activity.type === 'Maintenance',
            'bg-yellow-500': activity.type === 'Expense',
          }"
        >
          <template v-if="activity.type === 'Check-In'">🚗</template>
          <template v-else-if="activity.type === 'Maintenance'">🛠️</template>
          <template v-else>📌</template>
        </span>
        <div>
          <p class="text-sm text-gray-800 leading-5">
            <span class="font-semibold">{{ activity.type }}:</span>
            {{ activity.message }}
          </p>
          <p class="text-xs text-gray-500 mt-1">{{ activity.time }}</p>
        </div>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from '@/axios'

const activities = ref([])

onMounted(async () => {
  try {
    const res = await axios.get('/dashboard/recent')
    activities.value = res.data
  } catch (err) {
    console.error('Failed to fetch recent activity:', err)
  }
})
</script>
