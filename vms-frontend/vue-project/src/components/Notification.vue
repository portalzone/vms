<template>
  <transition name="fade">
    <div
      v-if="visible"
      class="fixed top-5 right-5 z-[9999] max-w-xs w-full px-4 py-3 rounded shadow-lg text-white"
      :class="bgColor"
    >
      <pre class="whitespace-pre-line">{{ message }}</pre>
    </div>
  </transition>
</template>

<script setup>
import { ref } from 'vue'

const message = ref('')
const visible = ref(false)
const bgColor = ref('bg-green-600')

// Call this from parent via ref
const showNotification = (msg, type = 'success') => {
  message.value = msg
  bgColor.value = type === 'success' ? 'bg-green-600' : 'bg-red-600'
  visible.value = true

  setTimeout(() => {
    visible.value = false
  }, 4000)
}

defineExpose({ showNotification })
</script>
