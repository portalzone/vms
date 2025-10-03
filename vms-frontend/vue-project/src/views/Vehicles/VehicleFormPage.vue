<template>
  <div>
    <!-- <h2 class="mb-4">{{ route.params.id ? 'Edit Vehicle' : 'Add Vehicle' }}</h2> -->
    <VehicleForm :id="route.params.id" />
  </div>
</template>

<script setup>
import { useRoute } from 'vue-router'
import VehicleForm from './VehicleForm.vue'

const route = useRoute()
</script>
