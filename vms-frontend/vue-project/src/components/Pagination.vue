<!-- src/components/Pagination.vue -->
<template>
  <div v-if="pages.length > 1" class="flex justify-center mt-4 space-x-2">
    <button
      @click="changePage(meta.current_page - 1)"
      :disabled="meta.current_page === 1"
      class="px-3 py-1 border rounded"
    >
      Prev
    </button>

    <button
      v-for="page in pages"
      :key="page"
      @click="changePage(page)"
      :class="[
        'px-3 py-1 border rounded',
        page === meta.current_page ? 'bg-blue-500 text-white' : 'bg-white'
      ]"
    >
      {{ page }}
    </button>

    <button
      @click="changePage(meta.current_page + 1)"
      :disabled="meta.current_page === meta.last_page"
      class="px-3 py-1 border rounded"
    >
      Next
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue'
const props = defineProps({
  meta: {
    type: Object,
    required: true
  }
})
const emit = defineEmits(['page-changed'])

const pages = computed(() => {
  if (!props.meta || !props.meta.last_page) return []
  return Array.from({ length: props.meta.last_page }, (_, i) => i + 1)
})

function changePage(page) {
  if (page >= 1 && page <= props.meta.last_page) {
    emit('page-changed', page)
  }
}
</script>
