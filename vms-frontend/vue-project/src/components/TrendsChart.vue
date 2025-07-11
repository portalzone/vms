<template>
  <div>
    <h3 class="text-lg font-bold mb-4">Monthly Trends</h3>
    <Line :data="chartData" :options="chartOptions" />
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
  plugins: {
    legend: { position: 'top' },
    title: { display: true, text: '12-Month Activity Trends' },
  },
}

onMounted(async () => {
  try {
    const res = await axios.get('/dashboard/trends')
    const data = res.data

    chartData.value.labels = data.map(entry => entry.month)
    chartData.value.datasets = [
      {
        label: 'Vehicles Registered',
        data: data.map(entry => entry.vehicles),
        borderColor: '#3b82f6',
        fill: false,
      },
      {
        label: 'Maintenance Count',
        data: data.map(entry => entry.maintenances),
        borderColor: '#ef4444',
        fill: false,
      },
      {
        label: 'Expenses (₦)',
        data: data.map(entry => entry.expenses),
        borderColor: '#facc15',
        fill: false,
      }
    ]
  } catch (error) {
    console.warn('Skipping trends fetch (unauthorized or error)', error)
  }
})
</script>
