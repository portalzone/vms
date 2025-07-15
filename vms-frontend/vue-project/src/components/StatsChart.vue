<template>
  <div class="bg-white p-4 rounded shadow mt-8" v-if="isReady">
    <h3 class="text-lg font-bold mb-4">Overview Chart</h3>
    <Bar :data="chartData" :options="chartOptions" />
  </div>
</template>

<script setup>
import { Bar } from 'vue-chartjs'
import { computed } from 'vue'
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
} from 'chart.js'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale)

const props = defineProps({
  stats: {
    type: Object,
    required: true,
    default: () => ({
      vehicles: 0,
      drivers: 0,
      expenses: 0,
      trips: 0,
      maintenances: {
        pending: 0,
        in_progress: 0,
        completed: 0
      }
    })
  }
})

const isReady = computed(() => !!props.stats)

const chartData = computed(() => ({
  labels: [
    'Vehicles',
    'Drivers',
    'Expenses',
    'Maintenances',
    'Trips'
  ],
  datasets: [
    {
      label: 'Dashboard Metrics',
      backgroundColor: ['#3b82f6', '#10b981', '#facc15', '#ef4444', '#8b5cf6'],
      data: [
        props.stats?.vehicles || 0,
        props.stats?.drivers || 0,
        props.stats?.expenses || 0,
        (props.stats?.maintenances?.pending || 0) +
        (props.stats?.maintenances?.in_progress || 0) +
        (props.stats?.maintenances?.completed || 0),
        props.stats?.trips || 0
      ]
    }
  ]
}))

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false }
  },
  scales: {
    y: {
      beginAtZero: true
    }
  }
}
</script>

<style scoped>
div {
  height: 350px;
}
</style>
