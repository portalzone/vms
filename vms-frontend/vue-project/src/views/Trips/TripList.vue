<template>
  <div class="overflow-x-auto bg-white rounded shadow">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-100">
        <tr>
          <th class="px-4 py-2 text-left">#</th>
          <th class="px-4 py-2 text-left">Driver</th>
          <th class="px-4 py-2 text-left">Vehicle</th>
          <th class="px-4 py-2 text-left">Start</th>
          <th class="px-4 py-2 text-left">End</th>
          <th class="px-4 py-2 text-left">Start Time</th>
          <th class="px-4 py-2 text-right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="(trip, i) in trips"
          :key="trip.id"
          class="hover:bg-gray-50 transition-colors"
        >
          <td class="px-4 py-2">{{ i + 1 }}</td>
          <td class="px-4 py-2">
            {{ trip.driver?.user?.name || '—' }}
          </td>
          <td class="px-4 py-2">
            {{ trip.vehicle?.plate_number || '—' }}
          </td>
          <td class="px-4 py-2">{{ trip.start_location }}</td>
          <td class="px-4 py-2">{{ trip.end_location }}</td>
          <td class="px-4 py-2">
            {{ trip.start_time ? new Date(trip.start_time).toLocaleString() : '—' }}
          </td>
          <td class="px-4 py-2 text-right space-x-2">
<button @click="$emit('edit', trip.id)" class="btn btn-edit">Edit</button>
<button @click="$emit('remove', trip.id)" class="btn btn-delete">Delete</button>

          </td>
        </tr>

        <tr v-if="!trips || trips.length === 0">
          <td colspan="7" class="px-4 py-4 text-center text-gray-500">
            No trips found.
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
defineProps(['trips'])
defineEmits(['edit', 'remove'])
</script>
