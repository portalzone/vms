<template>
  <div class="bg-white p-4 rounded shadow mt-8">
    <h3 class="text-lg font-bold mb-4">Overview Chart</h3>
    <Bar :data="chartData" :options="chartOptions" />
  </div>
</template>

<script setup>
import { Bar } from 'vue-chartjs'
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
  stats: Object
})

const chartData = {
  labels: ['Vehicles', 'Drivers', 'Expenses (₦)', 'Maintenances'],
  datasets: [
    {
      label: 'Dashboard Metrics',
      backgroundColor: ['#3b82f6', '#10b981', '#facc15', '#ef4444'],
      data: [
        props.stats.vehicles || 0,
        props.stats.drivers || 0,
        props.stats.expenses || 0,
        props.stats.maintenances || 0
      ]
    }
  ]
}

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
