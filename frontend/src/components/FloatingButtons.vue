<template>
  <div v-if="!isMapPage" class="fixed bottom-24 right-4 flex flex-col gap-4 z-40">
    <DarkModeToggle />
    
    <!-- Language Toggle -->
    <button @click="toggleLang" 
            class="w-12 h-12 rounded-full bg-white dark:bg-gray-800 shadow-lg flex items-center justify-center text-gray-600 dark:text-gray-300 transform active:scale-90 transition-all border border-gray-100 dark:border-gray-700">
      <span class="text-xs font-bold">{{ locale.toUpperCase() }}</span>
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import DarkModeToggle from './DarkModeToggle.vue'

const route = useRoute()
const { locale } = useI18n()

const isMapPage = computed(() => route.path === '/map')

const toggleLang = () => {
  locale.value = locale.value === 'fr' ? 'en' : 'fr'
}
</script>

