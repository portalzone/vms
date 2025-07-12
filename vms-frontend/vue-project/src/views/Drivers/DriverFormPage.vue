<template>
  <div>
    <h2 class="text-xl font-bold mb-4">{{ isEdit ? 'Edit Driver' : 'Add Driver' }}</h2>
    <DriverForm :driver-id="driverId" :is-edit="isEdit" />
  </div>
</template>

<script setup>
import { useRoute } from 'vue-router'
import DriverForm from './DriverForm.vue'

const route = useRoute()
const isEdit = route.name === 'DriverEdit'
const driverId = route.params.id
</script>
