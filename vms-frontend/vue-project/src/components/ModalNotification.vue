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

      <!-- Show confirm/cancel only if enabled -->
      <div v-if="showConfirm" class="modal-footer flex justify-end gap-2 mt-4">
        <button @click="hide" class="btn-secondary">Cancel</button>
        <button @click="confirm" class="btn-danger">Confirm</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'

// Props from parent
const props = defineProps({
  show: { type: Boolean, default: false },
  title: { type: String, default: 'Notification' },
  message: { type: String, default: '' },
  showConfirm: { type: Boolean, default: false },
})

// Emit close & confirm
const emit = defineEmits(['close', 'confirm'])

// Local state for visibility
const visible = ref(props.show)
watch(() => props.show, (val) => visible.value = val)

function hide() {
  visible.value = false
  emit('close')
}

function confirm() {
  visible.value = false
  emit('confirm')
}
</script>
