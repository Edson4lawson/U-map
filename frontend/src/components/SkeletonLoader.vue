<template>
  <div class="animate-pulse" :class="containerClass">
    <div 
      v-for="i in count" 
      :key="i" 
      class="rounded-lg bg-gray-200 dark:bg-gray-700"
      :class="itemClass"
      :style="{ width: i === count && lastItemWidth ? lastItemWidth : '' }"
    ></div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  count?: number
  itemClass?: string
  containerClass?: string
  lastItemWidth?: string
}

withDefaults(defineProps<Props>(), {
  count: 3,
  itemClass: 'h-4 mb-2',
  containerClass: '',
  lastItemWidth: '60%'
})
</script>
