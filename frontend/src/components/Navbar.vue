<template>
  <nav class="fixed bottom-0 left-0 w-full bg-white/90 dark:bg-gray-900/90 backdrop-blur-lg border-t border-gray-200 dark:border-gray-800 py-3 px-6 z-50 transition-colors duration-300">
    <ul class="flex justify-between items-center max-w-md mx-auto">
      <li>
        <router-link to="/" class="nav-item" active-class="active">
          <Icon icon="ph:house-bold" class="w-6 h-6" />
          <span class="text-[10px] mt-1 uppercase font-bold">{{ $t('common.welcome') }}</span>
        </router-link>
      </li>
      <li>
        <router-link to="/map" class="nav-item" active-class="active">
          <Icon icon="ph:map-trifold-bold" class="w-6 h-6" />
          <span class="text-[10px] mt-1 uppercase font-bold">{{ $t('common.explore') }}</span>
        </router-link>
      </li>
      <li>
           <router-link to="/lieux" class="flex flex-col items-center justify-center w-12 h-12 bg-primary text-white rounded-full shadow-lg shadow-blue-500/30 transform transition-transform active:scale-95" active-class="ring-4 ring-blue-200 dark:ring-blue-900">
             <Icon icon="ph:magnifying-glass-bold" class="w-5 h-5" />
           </router-link>
      </li>
      <li>
        <router-link to="/visites" class="nav-item" active-class="active">
          <Icon icon="ph:check-circle-bold" class="w-6 h-6" />
          <span class="text-[10px] mt-1 uppercase font-bold">{{ $t('home.stats.visited') }}</span>
        </router-link>
      </li>
      <li>
        <router-link to="/chat" class="nav-item" active-class="active">
          <Icon icon="ph:chat-circle-dots-bold" class="w-6 h-6" />
          <span class="text-[10px] mt-1 uppercase font-bold">{{ $t('chat.title') }}</span>
        </router-link>
      </li>
    </ul>
  </nav>
</template>

<script setup>
import { Icon } from '@iconify/vue'
</script>



<style scoped>
.nav-item {
  @apply flex flex-col items-center text-gray-400 dark:text-gray-500 hover:text-primary dark:hover:text-primary transition-colors duration-200;
}
.nav-item.active {
  @apply text-primary dark:text-primary;
}
</style>
