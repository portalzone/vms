<template>
  <div>
    <h1 class="text-xl font-bold mb-4">{{ isEdit ? 'Edit Trip' : 'New Trip' }}</h1>
    <TripForm :isEdit="isEdit" :tripId="tripId" />
  </div>
</template>

<script setup>
import TripForm from './TripForm.vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const tripId = route.params.id
const isEdit = !!tripId
</script>
