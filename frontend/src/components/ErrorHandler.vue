<template>
  <Transition name="fade">
    <div v-if="show" class="fixed inset-0 z-[1000] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click="closeOnBackdrop && close()">
      <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-2xl max-w-md w-full p-6 transform transition-all" @click.stop>
        <!-- Icon based on type -->
        <div class="flex justify-center mb-4">
          <div :class="[
            'w-16 h-16 rounded-full flex items-center justify-center',
            type === 'error' ? 'bg-red-100 dark:bg-red-900/30' : 
            type === 'success' ? 'bg-green-100 dark:bg-green-900/30' : 
            type === 'warning' ? 'bg-yellow-100 dark:bg-yellow-900/30' : 
            'bg-blue-100 dark:bg-blue-900/30'
          ]">
            <Icon 
              :icon="type === 'error' ? 'ph:x-circle-bold' : 
                     type === 'success' ? 'ph:check-circle-bold' : 
                     type === 'warning' ? 'ph:warning-bold' : 
                     'ph:info-bold'" 
              :class="[
                'w-8 h-8',
                type === 'error' ? 'text-red-500' : 
                type === 'success' ? 'text-green-500' : 
                type === 'warning' ? 'text-yellow-500' : 
                'text-blue-500'
              ]"
            />
          </div>
        </div>

        <!-- Title -->
        <h3 :class="[
          'text-xl font-bold text-center mb-2',
          type === 'error' ? 'text-red-600 dark:text-red-400' : 
          type === 'success' ? 'text-green-600 dark:text-green-400' : 
          type === 'warning' ? 'text-yellow-600 dark:text-yellow-400' : 
          'text-blue-600 dark:text-blue-400'
        ]">
          {{ title }}
        </h3>

        <!-- Message -->
        <p class="text-gray-600 dark:text-gray-300 text-center mb-6 leading-relaxed">
          {{ message }}
        </p>

        <!-- Actions -->
        <div class="flex gap-3">
          <button 
            v-if="showCancel"
            @click="onCancel"
            class="flex-1 py-3 px-4 rounded-xl font-semibold transition-all bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700"
          >
            {{ cancelText }}
          </button>
          <button 
            @click="onConfirm"
            class="flex-1 py-3 px-4 rounded-xl font-semibold transition-all text-white"
            :class="[
              type === 'error' ? 'bg-red-500 hover:bg-red-600 shadow-red-500/20' : 
              type === 'success' ? 'bg-green-500 hover:bg-green-600 shadow-green-500/20' : 
              type === 'warning' ? 'bg-yellow-500 hover:bg-yellow-600 shadow-yellow-500/20' : 
              'bg-blue-500 hover:bg-blue-600 shadow-blue-500/20'
            ]"
          >
            {{ confirmText }}
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps({
  show: Boolean,
  type: {
    type: String,
    default: 'info', // 'error', 'success', 'warning', 'info'
  },
  title: {
    type: String,
    default: 'Information'
  },
  message: {
    type: String,
    required: true
  },
  confirmText: {
    type: String,
    default: 'OK'
  },
  cancelText: {
    type: String,
    default: 'Annuler'
  },
  showCancel: {
    type: Boolean,
    default: false
  },
  closeOnBackdrop: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['confirm', 'cancel', 'close'])

const close = () => {
  emit('close')
}

const onConfirm = () => {
  emit('confirm')
  if (!props.showCancel) {
    close()
  }
}

const onCancel = () => {
  emit('cancel')
  close()
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
