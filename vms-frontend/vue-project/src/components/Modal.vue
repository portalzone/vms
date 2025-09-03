<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-96">
      <h2 class="text-lg font-bold mb-4">{{ title }}</h2>
      <p class="mb-6" v-html="message"></p>
      <div class="flex justify-end gap-2">
        <button class="btn-secondary" @click="$emit('close')">Cancel</button>
        <button class="btn-danger" @click="$emit('confirm')">Delete</button>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: String,
  message: String,
})
</script>
