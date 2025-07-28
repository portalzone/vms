<template>
  <div class="bg-white shadow rounded-2xl p-4 w-full">
    <h3 class="text-lg font-medium mb-2">{{ title }}</h3>
    <div class="text-2xl font-bold text-gray-800">
      <slot />
    </div>
    <div v-if="subtitle" class="text-sm text-gray-500 mt-1">{{ subtitle }}</div>
  </div>
</template>

<script setup>
defineProps({
  title: String,
  subtitle: String
})
</script>
