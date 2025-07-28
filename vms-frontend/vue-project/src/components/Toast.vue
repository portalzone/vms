<template>
  <transition name="fade">
    <div
      v-if="visible"
      class="fixed top-5 right-5 z-50 px-4 py-2 rounded shadow text-white"
      :class="bgColor"
    >
      {{ message }}
    </div>
  </transition>
</template>

<script setup>
import { ref } from 'vue'

const message = ref('')
const visible = ref(false)
const bgColor = ref('bg-green-600')

const showToast = (msg, duration = 3000, color = 'success') => {
  message.value = msg
  visible.value = true

  bgColor.value = color === 'error' ? 'bg-red-600' : 'bg-green-600'

  setTimeout(() => {
    visible.value = false
  }, duration)
}

defineExpose({ showToast })
</script>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.5s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>
