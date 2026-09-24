<template>
  <div
    v-if="!isHidden"
    ref="fabContainerRef"
    class="fixed bottom-24 right-5 z-40 flex flex-col-reverse items-end"
  >
    <!-- FAB Main Trigger Button -->
    <button
      type="button"
      @click="toggleMenu"
      :aria-expanded="isOpen"
      aria-haspopup="true"
      aria-label="Menu d'actions rapides"
      class="relative w-14 h-14 min-w-[56px] min-h-[56px] rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 text-white shadow-xl shadow-indigo-500/30 hover:shadow-indigo-500/50 hover:scale-105 active:scale-95 focus:outline-none focus:ring-4 focus:ring-indigo-300 dark:focus:ring-indigo-800 transition-all duration-300 flex items-center justify-center cursor-pointer select-none"
    >
      <div
        class="transition-transform duration-300 ease-out transform flex items-center justify-center"
        :class="{ 'rotate-45': isOpen }"
      >
        <Icon icon="ph:plus-bold" class="w-7 h-7" />
      </div>
    </button>

    <!-- Sub Actions Container with Stagger Transition -->
    <div
      class="flex flex-col-reverse items-end gap-3 mb-3 pointer-events-none"
      :class="{ 'pointer-events-auto': isOpen }"
    >
      <!-- Action 1: Language Switch (Closest to FAB, index 0) -->
      <div
        class="flex items-center gap-2 transition-all duration-200 ease-out"
        :style="getItemStyle(0)"
      >
        <span
          v-if="isOpen"
          class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-gray-900/80 dark:bg-gray-100/90 text-white dark:text-gray-900 shadow-md backdrop-blur-sm pointer-events-none transition-opacity duration-200"
        >
          {{ locale === 'fr' ? 'Français' : 'English' }}
        </span>
        <button
          type="button"
          @click="handleToggleLang"
          :aria-label="`Changer de langue. Langue actuelle : ${locale.toUpperCase()}`"
          class="w-12 h-12 min-w-[48px] min-h-[48px] rounded-full bg-white dark:bg-slate-800 text-gray-800 dark:text-gray-100 shadow-lg border border-slate-200/80 dark:border-slate-700/80 hover:bg-slate-50 dark:hover:bg-slate-700 hover:scale-110 active:scale-95 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all flex items-center justify-center cursor-pointer select-none"
        >
          <span class="text-xs font-black tracking-wider text-blue-600 dark:text-blue-400">
            {{ locale.toUpperCase() }}
          </span>
        </button>
      </div>

      <!-- Action 2: Theme Toggle (Dark / Light, index 1) -->
      <div
        class="flex items-center gap-2 transition-all duration-200 ease-out"
        :style="getItemStyle(1)"
      >
        <span
          v-if="isOpen"
          class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-gray-900/80 dark:bg-gray-100/90 text-white dark:text-gray-900 shadow-md backdrop-blur-sm pointer-events-none transition-opacity duration-200"
        >
          Thème
        </span>
        <DarkModeToggle class="!w-12 !h-12 !min-w-[48px] !min-h-[48px] flex items-center justify-center !p-0 border border-slate-200/80 dark:border-slate-700/80" />
      </div>

      <!-- Action 3: Profile / Login (index 2) -->
      <div
        class="flex items-center gap-2 transition-all duration-200 ease-out"
        :style="getItemStyle(2)"
      >
        <span
          v-if="isOpen"
          class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-gray-900/80 dark:bg-gray-100/90 text-white dark:text-gray-900 shadow-md backdrop-blur-sm pointer-events-none transition-opacity duration-200"
        >
          {{ isAuthenticated ? (currentUser?.name || 'Mon Profil') : 'Se connecter' }}
        </span>
        <button
          type="button"
          @click="handleProfileClick"
          :aria-label="isAuthenticated ? 'Accéder à mon profil' : 'Se connecter'"
          class="w-12 h-12 min-w-[48px] min-h-[48px] rounded-full bg-white dark:bg-slate-800 text-indigo-600 dark:text-indigo-400 shadow-lg border border-slate-200/80 dark:border-slate-700/80 hover:bg-slate-50 dark:hover:bg-slate-700 hover:scale-110 active:scale-95 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all flex items-center justify-center cursor-pointer select-none"
        >
          <Icon :icon="isAuthenticated ? 'ph:user-circle-gear-bold' : 'ph:user-circle-bold'" class="w-5 h-5" />
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { Icon } from '@iconify/vue'
import { authService } from '../services/authService'
import DarkModeToggle from './DarkModeToggle.vue'

const route = useRoute()
const router = useRouter()
const { locale } = useI18n()

const fabContainerRef = ref(null)
const isOpen = ref(false)

// Hide on map route or active chat view
const isHidden = computed(() => {
  return route.path === '/map' || (route.path === '/chat' && !!route.query.chat)
})

const isAuthenticated = computed(() => {
  return authService.isAuthenticated()
})

const currentUser = computed(() => {
  return authService.getCurrentUser()
})

const toggleMenu = () => {
  isOpen.value = !isOpen.value
}

const closeMenu = () => {
  isOpen.value = false
}

// Stagger effect: 45ms delay per item, 200ms transition duration
const getItemStyle = (index) => {
  if (!isOpen.value) {
    return {
      opacity: '0',
      transform: 'translateY(12px) scale(0.85)',
      pointerEvents: 'none',
      transitionDelay: '0ms'
    }
  }

  return {
    opacity: '1',
    transform: 'translateY(0) scale(1)',
    pointerEvents: 'auto',
    transitionDelay: `${index * 45}ms`
  }
}

// Toggle language (persist to localStorage)
const handleToggleLang = () => {
  locale.value = locale.value === 'fr' ? 'en' : 'fr'
  localStorage.setItem('u_map_lang', locale.value)
}

// Navigate to Profile or Login
const handleProfileClick = () => {
  closeMenu()
  if (isAuthenticated.value) {
    router.push('/settings/security')
  } else {
    router.push('/login')
  }
}

// Click outside handling, explicitly ignoring the FAB trigger
const handlePointerDown = (event) => {
  if (!isOpen.value) return
  // Prevent closing when clicking within the FAB container (which includes the trigger)
  // The trigger itself will toggle the menu because of its @click event.
  // Wait, if we click the trigger while open, it will trigger pointerdown (ignored here), then click (toggles it closed).
  // If we click outside the container, pointerdown will close it immediately.
  if (fabContainerRef.value && !fabContainerRef.value.contains(event.target)) {
    closeMenu()
  }
}

// Keyboard navigation (Escape to close)
const handleKeyDown = (event) => {
  if (event.key === 'Escape' && isOpen.value) {
    closeMenu()
  }
}

onMounted(() => {
  document.addEventListener('pointerdown', handlePointerDown)
  document.addEventListener('keydown', handleKeyDown)
})

onUnmounted(() => {
  document.removeEventListener('pointerdown', handlePointerDown)
  document.removeEventListener('keydown', handleKeyDown)
})
</script>
