<template>
  <div class="relative w-full max-w-md mx-auto">
    <div class="relative flex items-center w-full h-12 rounded-full focus-within:shadow-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden transition-shadow duration-300">
      <div class="grid place-items-center h-full w-12 text-gray-300">
        <Icon icon="ph:magnifying-glass" class="w-6 h-6 text-gray-400" />
      </div>

      <input
        class="peer h-full w-full outline-none text-sm text-gray-700 dark:text-gray-200 pr-2 bg-transparent"
        type="text"
        :placeholder="placeholder"
        v-model="searchQuery"
        @input="$emit('update:modelValue', searchQuery)"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps({
  modelValue: String,
  placeholder: {
    type: String,
    default: 'Rechercher un lieu...'
  }
})

const emit = defineEmits(['update:modelValue'])

const searchQuery = ref(props.modelValue)

watch(() => props.modelValue, (newVal) => {
  searchQuery.value = newVal
})
</script>
