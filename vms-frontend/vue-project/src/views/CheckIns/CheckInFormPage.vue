<template>
  <div>
    <h2 class="text-xl font-bold mb-4">{{ isEdit ? 'Edit Check-In' : 'New Check-In' }}</h2>
    <CheckInForm :isEdit="isEdit" :checkInId="checkInId" />
  </div>
</template>

<script setup>
import CheckInForm from './CheckInForm.vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const checkInId = route.params.id
const isEdit = route.name === 'CheckInEdit'
</script>
