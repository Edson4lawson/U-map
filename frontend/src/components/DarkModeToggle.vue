<template>
  <button
    @click="toggleDark"
    class="p-3 rounded-full bg-white dark:bg-gray-800 shadow-lg text-gray-800 dark:text-yellow-400 hover:scale-110 transition-transform duration-300 focus:outline-none"
    aria-label="Toggle Dark Mode"
  >
    <Icon :icon="isDark ? 'ph:sun-bold' : 'ph:moon-bold'" class="w-6 h-6" />
  </button>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Icon } from '@iconify/vue'

const isDark = ref(false)

const toggleDark = () => {
  isDark.value = !isDark.value
  if (isDark.value) {
    document.documentElement.classList.add('dark')
    localStorage.setItem('theme', 'dark')
  } else {
    document.documentElement.classList.remove('dark')
    localStorage.setItem('theme', 'light')
  }
}

onMounted(() => {
  if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    isDark.value = true
    document.documentElement.classList.add('dark')
  } else {
    isDark.value = false
    document.documentElement.classList.remove('dark')
  }
})
</script>
