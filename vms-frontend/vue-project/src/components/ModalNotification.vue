<!-- components/ModalNotification.vue -->
<template>
  <div v-if="visible" class="modal-overlay" @click.self="hide">
    <div class="modal-content max-w-md w-full">
      <div class="modal-header">
        <h3 class="text-lg font-semibold">{{ title }}</h3>
        <button @click="hide" class="modal-close">✖</button>
      </div>
      <div class="modal-body text-sm whitespace-pre-wrap">
        <div class="whitespace-pre-line" v-html="message"></div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const visible = ref(false)
const title = ref('')
const message = ref('')

const show = (msg, heading = 'Notification') => {
  message.value = msg
  title.value = heading
  visible.value = true
}

const hide = () => {
  visible.value = false
}

defineExpose({ show, hide })
</script>
