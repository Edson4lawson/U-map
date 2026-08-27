<template>
  <TransitionGroup
    tag="div"
    name="toast"
    class="fixed top-4 right-4 z-50 flex flex-col gap-2"
  >
    <div
      v-for="toast in toastsArray"
      :key="toast.id"
      class="flex items-center gap-3 px-4 py-3 rounded-lg shadow-lg min-w-[300px]"
      :class="toastClasses[toast.type]"
    >
      <component :is="iconComponents[toast.type]" class="w-5 h-5 flex-shrink-0" />
      <span class="flex-1">{{ toast.message }}</span>
      <button @click="removeToast(toast.id)" class="flex-shrink-0 hover:opacity-70">
        <X class="w-4 h-4" />
      </button>
    </div>
  </TransitionGroup>
</template>

<script setup>
import { computed } from 'vue'
import { CheckCircle, XCircle, AlertTriangle, Info, X } from '@lucide/vue'

const props = defineProps({
  toasts: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['remove'])

const toastsArray = computed(() => {
  return Array.isArray(props.toasts) ? props.toasts : []
})

const toastClasses = {
  success: 'bg-green-500 text-white',
  error: 'bg-red-500 text-white',
  warning: 'bg-yellow-500 text-white',
  info: 'bg-blue-500 text-white'
}

const iconComponents = {
  success: CheckCircle,
  error: XCircle,
  warning: AlertTriangle,
  info: Info
}

const removeToast = (id) => {
  emit('remove', id)
}
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}

.toast-enter-from {
  opacity: 0;
  transform: translateX(100%);
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(100%);
}
</style>
