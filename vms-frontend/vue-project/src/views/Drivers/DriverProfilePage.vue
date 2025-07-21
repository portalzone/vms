<template>
  <div class="max-w-5xl mx-auto bg-white p-6 rounded shadow-md">

     <!-- Actions -->
    <div class="flex flex-wrap justify-between items-center gap-4">
      <router-link
        :to="`/drivers/${driver.id}/edit`"
        class="btn-secondary"
      >
        ✏️ Edit Driver
      </router-link>

      <button
        @click="exportTripsExcel"
        class="btn-primary"
      >
        📥 Export Trips (Excel)
      </button>
    </div>
        <h2 class="text-2xl font-semibold mb-6">Driver Profile</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
      <div>
        <h3 class="font-semibold text-lg mb-2">Personal Details</h3>
        <p><strong>Name:</strong> {{ driver.name }}</p>
        <p><strong>Email:</strong> {{ driver.email }}</p>
        <p><strong>License Number:</strong> {{ driver.license_number }}</p>
        <p><strong>Phone Number:</strong> {{ driver.phone_number }}</p>
        <p><strong>Sex:</strong> {{ driver.sex }}</p>
        <p><strong>Address:</strong> {{ driver.home_address }}</p>
      </div>

      <div>
        <h3 class="font-semibold text-lg mb-2">Vehicle Info</h3>
        <p v-if="driver.vehicle">
          <strong>Name:</strong> {{ driver.vehicle.name }}<br />
          <strong>Plate Number:</strong> {{ driver.vehicle.plate_number }}
        </p>
        <p v-else>No vehicle assigned.</p>
      </div>
    </div>

    <!-- Trips Table -->
    <div class="mb-10">
      <h3 class="text-xl font-bold mb-3">Trip History</h3>
      <table class="min-w-full text-sm text-left border border-gray-200">
        <thead class="bg-gray-100 font-medium">
          <tr>
            <th class="px-4 py-2 border">#</th>
            <th class="px-4 py-2 border">Start</th>
            <th class="px-4 py-2 border">End</th>
            <th class="px-4 py-2 border">Start Time</th>
            <th class="px-4 py-2 border">End Time</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(trip, index) in driver.trips" :key="trip.id" class="hover:bg-gray-50">
            <td class="px-4 py-2 border">{{ index + 1 }}</td>
            <td class="px-4 py-2 border">{{ trip.start_location }}</td>
            <td class="px-4 py-2 border">{{ trip.end_location }}</td>
            <td class="px-4 py-2 border">{{ formatDate(trip.start_time) }}</td>
            <td class="px-4 py-2 border">{{ formatDate(trip.end_time) }}</td>
          </tr>
          <tr v-if="driver.trips.length === 0">
            <td colspan="5" class="px-4 py-3 text-center text-gray-500">No trip records found.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Maintenance Table -->
    <div>
      <h3 class="text-xl font-bold mb-3">Maintenance Logs</h3>
      <table class="min-w-full text-sm text-left border border-gray-200">
        <thead class="bg-gray-100 font-medium">
          <tr>
            <th class="px-4 py-2 border">#</th>
            <th class="px-4 py-2 border">Description</th>
            <th class="px-4 py-2 border">Cost</th>
            <th class="px-4 py-2 border">Date</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(log, index) in driver.maintenance_logs" :key="log.id" class="hover:bg-gray-50">
            <td class="px-4 py-2 border">{{ index + 1 }}</td>
            <td class="px-4 py-2 border">{{ log.description }}</td>
            <td class="px-4 py-2 border">₦{{ log.cost.toLocaleString() }}</td>
            <td class="px-4 py-2 border">{{ formatDate(log.date) }}</td>
          </tr>
          <tr v-if="driver.maintenance_logs.length === 0">
            <td colspan="4" class="px-4 py-3 text-center text-gray-500">No maintenance logs found.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<!-- <script setup>
import { computed } from 'vue'

const props = defineProps({
  driver: Object
})

function formatDate(dateString) {
  const date = new Date(dateString)
  return date.toLocaleString()
}
</script> -->


<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from '@/axios';

const route = useRoute();
const driverId = route.params.id;
const driver = ref({
  trips: [],
  maintenance_logs: [],
  vehicle: null,
});

function formatDate(dateString) {
  const date = new Date(dateString)
  return date.toLocaleString()
}
const fetchDriver = async () => {
  try {
    const res = await axios.get(`/drivers/${driverId}`);
    driver.value = res.data;

    // Fallbacks if data is missing
    driver.value.trips = driver.value.trips || [];
    driver.value.maintenance_logs = driver.value.maintenance_logs || [];
  } catch (err) {
    console.error('Failed to fetch driver profile:', err);
  }
};

const exportTripsExcel = async () => {
  try {
    const response = await axios.get(`/drivers/${driverId}/export-trips-excel`, {
      responseType: 'blob',
    });

    const blob = new Blob([response.data], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `driver_${driverId}_trips.xlsx`;
    a.click();
    window.URL.revokeObjectURL(url);
  } catch (err) {
    console.error('Error exporting Excel:', err);
    alert('Failed to export trips.');
  }
};

onMounted(fetchDriver);
</script>
