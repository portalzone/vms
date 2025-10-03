<template>
  <div class="trends-chart-container">
    <h3 class="mb-4 text-lg font-bold">Monthly Trends</h3>
    <Line v-if="chartData.datasets.length" :data="chartData" :options="chartOptions" />
    <p v-else class="text-sm text-gray-500">No trend data available.</p>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Line } from 'vue-chartjs'
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  LineElement,
  CategoryScale,
  LinearScale,
  PointElement,
} from 'chart.js'
import axios from '@/axios'

ChartJS.register(Title, Tooltip, Legend, LineElement, CategoryScale, LinearScale, PointElement)

const chartData = ref({
  labels: [],
  datasets: [],
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { position: 'top' },
    title: { display: true, text: '12-Month Activity Trends' },
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        precision: 0,
      },
    },
  },
}

onMounted(async () => {
  try {
    const res = await axios.get('/dashboard/monthly-trends')
    const data = res.data
    // console.log('📊 Monthly Trend Data:', data)

    chartData.value = {
      labels: data.map((entry) => entry.month),
      datasets: [
        {
          label: 'Maintenance Costs (₦)',
          data: data.map((entry) => entry.maintenances),
          borderColor: '#3b82f6',
          backgroundColor: '#3b82f660',
          tension: 0.4,
          fill: false,
        },
        {
          label: 'Expenses (₦)',
          data: data.map((entry) => entry.expenses),
          borderColor: '#10b981',
          backgroundColor: '#10b98160',
          tension: 0.4,
          fill: false,
        },
        {
          label: 'Income (₦)',
          data: data.map((entry) => entry.incomes),
          borderColor: '#ef4444',
          backgroundColor: '#ef444460',
          tension: 0.4,
          fill: false,
        },
      ],
    }
  } catch (error) {
    console.warn('Skipping trends fetch (unauthorized or error)', error)
  }
})
</script>

<style scoped>
.trends-chart-container {
  height: 400px;
  background-color: white;
  padding: 1rem;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}
</style>
