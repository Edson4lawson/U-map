<template>
  <div class="flex flex-col h-full overflow-hidden text-gray-900 dark:text-gray-100 font-sans transition-colors duration-300" :class="isHomeRoute ? '' : 'bg-slate-50 dark:bg-slate-950'">
    <VideoBackground v-if="isHomeRoute" />
    
    <!-- PREMIUM ERROR BOUNDARY -->
    <Transition name="error-overlay">
      <div v-if="error" class="fixed inset-0 z-[100] flex items-center justify-center p-4 pointer-events-auto"
           style="background: radial-gradient(ellipse at center, rgba(15,23,42,0.95) 0%, rgba(7,10,20,0.98) 100%);">
        
        <!-- Premium animated glow accents -->
        <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-gradient-to-br from-red-500/20 via-orange-500/10 to-yellow-500/20 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute bottom-1/4 right-1/4 w-72 h-72 bg-gradient-to-br from-purple-500/20 via-pink-500/10 to-red-500/20 rounded-full blur-3xl animate-pulse" style="animation-delay: 1.5s;"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-64 h-64 bg-gradient-to-br from-blue-500/15 via-cyan-500/10 to-teal-500/15 rounded-full blur-3xl animate-pulse" style="animation-delay: 0.75s;"></div>

        <div class="relative z-10 w-full max-w-lg">
          <!-- Premium glassmorphism card -->
          <div class="bg-gradient-to-br from-white/10 via-white/5 to-white/10 backdrop-blur-3xl border border-white/20 rounded-3xl p-8 shadow-2xl ring-1 ring-white/10">
            
            <!-- Animated error icon with glow -->
            <div class="flex justify-center mb-6">
              <div class="relative">
                <div class="absolute inset-0 bg-gradient-to-br from-red-500 to-orange-600 rounded-2xl blur-xl opacity-50 animate-pulse"></div>
                <div class="relative w-20 h-20 rounded-2xl bg-gradient-to-br from-red-500 via-orange-500 to-yellow-500 flex items-center justify-center shadow-2xl shadow-red-500/40 animate-bounce" style="animation-duration: 2s;">
                  <Icon icon="ph:warning-diamond-bold" class="w-10 h-10 text-white" />
                </div>
              </div>
            </div>

            <h2 class="text-2xl font-bold text-white text-center mb-3 tracking-tight bg-gradient-to-r from-white via-gray-100 to-gray-200 bg-clip-text text-transparent">Une erreur est survenue</h2>
            <p class="text-slate-300 text-sm text-center mb-6 leading-relaxed">
              L'application a rencontré un problème inattendu. Veuillez recharger la page pour continuer.
            </p>



            <!-- Action buttons -->
            <div class="flex flex-col gap-3">
              <button @click="reloadApp" 
                      class="w-full py-3.5 bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-400 hover:to-indigo-500 text-white rounded-2xl font-semibold text-sm shadow-lg shadow-blue-500/20 active:scale-[0.98] transition-all flex items-center justify-center gap-2">
                <Icon icon="ph:arrow-clockwise-bold" class="w-4.5 h-4.5" />
                Recharger l'application
              </button>
              <button @click="dismissError" 
                      class="w-full py-3 bg-white/5 hover:bg-white/10 border border-white/10 text-slate-300 rounded-2xl font-medium text-sm transition-all flex items-center justify-center gap-2">
                <Icon icon="ph:x-bold" class="w-4 h-4" />
                Ignorer et continuer
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Main Content Area -->
    <main class="flex-1 w-full relative z-0 scroll-smooth" :class="[route.path === '/chat' ? 'overflow-hidden' : 'overflow-y-auto', {'pb-24': !hideNavigation}]">
      <router-view v-slot="{ Component }">
        <transition name="fade" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </main>

    <!-- Toast Notifications -->
    <Toast :toasts="toastState.toasts.value" @remove="toastState.removeToast" />

    <!-- Floating Actions -->
    <FloatingButtons v-if="!hideNavigation" />

    <!-- Bottom Navigation -->
    <Navbar v-if="!hideNavigation" />
  </div>
</template>

<script setup>
import { ref, onErrorCaptured, onMounted, onUnmounted, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import { Icon } from '@iconify/vue'
import VideoBackground from './components/VideoBackground.vue'
import Navbar from './components/Navbar.vue'
import FloatingButtons from './components/FloatingButtons.vue'
import Toast from './components/Toast.vue'
import { useProximity } from './composables/useProximity'
import { useNotificationStore } from './stores/notifications'
import { useToast } from './composables/useToast'
import { messageService } from './services/messageService'
import { authService } from './services/authService'
import echo from './services/echo'

const route = useRoute()
const isAdminRoute = computed(() => route.path.startsWith('/admin'))
const isChatActive = computed(() => route.path === '/chat' && !!route.query.chat)
const hideNavigation = computed(() => isAdminRoute.value || isChatActive.value)
const isHomeRoute = computed(() => route.path === '/')

const error = ref(null)
const showErrorDetails = ref(false)
const { startWatching } = useProximity()
const notificationStore = useNotificationStore()
const toastState = useToast()
let echoUserChannel = null

const reloadApp = () => {
  window.location.reload()
}

const dismissError = () => {
  error.value = null
  showErrorDetails.value = false
}

// Fetch unread count once on mount (and when visibility changes)
const fetchUnreadCountOnce = async () => {
  if (document.hidden) return
  if (authService.getToken()) {
      try {
          const { count } = await messageService.getUnreadCount()
          if (count > 0) {
              notificationStore.addNotification({
                  id: 'new_msg',
                  title: 'Nouveau message !',
                  message: `Vous avez ${count} message(s) non lu(s).`,
                  type: 'info'
              })
          }
      } catch (e) { /* Ignore background errors */ }
  }
}

// Subscribe to real-time user notifications channel via Echo
const subscribeToUserChannel = () => {
  const user = authService.getCurrentUser()
  if (!user || !echo) return

  // Leave any previous channel
  if (echoUserChannel) {
    echo.leaveChannel(`private-user.${echoUserChannel}`)
  }

  echoUserChannel = user.id
  echo.private(`user.${user.id}`)
    .listen('.message.sent', (data) => {
      // Show a toast notification for incoming messages
      toastState.info(`💬 ${data.sender?.name || 'Quelqu\'un'} vous a envoyé un message`)
      
      // Update the notification store
      notificationStore.addNotification({
        id: `msg_${data.id}`,
        title: 'Nouveau message !',
        message: `${data.sender?.name || 'Utilisateur'}: ${data.content?.substring(0, 50)}...`,
        type: 'info'
      })
    })
}

onErrorCaptured((err) => {
  error.value = err.toString()
  return false // Prevent propagation
})

onMounted(() => {
  // Capture unhandled promise rejections — except silent network errors
  window.addEventListener('unhandledrejection', (event) => {
    const reason = event.reason
    // Ignore network / fetch errors (WebSocket reconnections, etc.)
    if (reason instanceof TypeError && reason.message?.includes('fetch')) return
    if (reason?.name === 'NetworkError') return
    if (reason?.message?.toLowerCase().includes('network') || reason?.message?.toLowerCase().includes('failed to fetch')) return
    // Ignore WebSocket connection errors silently
    if (reason?.message?.toLowerCase().includes('websocket') || reason?.message?.toLowerCase().includes('pusher')) return
    error.value = reason?.message || "Erreur asynchrone inconnue"
  })

  // Start GPS proximity watching for notifications
  startWatching()

  // Initial unread count fetch
  fetchUnreadCountOnce()

  // Subscribe to real-time notifications via Echo (replaces 30s polling)
  if (authService.isAuthenticated()) {
    subscribeToUserChannel()
  }

  // Listen for visibility changes to re-fetch once
  document.addEventListener('visibilitychange', fetchUnreadCountOnce)
})

onUnmounted(() => {
  if (echoUserChannel) {
    echo.leaveChannel(`private-user.${echoUserChannel}`)
  }
  document.removeEventListener('visibilitychange', fetchUnreadCountOnce)
})
</script>

<style>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.error-overlay-enter-active {
  transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.error-overlay-leave-active {
  transition: all 0.3s ease;
}
.error-overlay-enter-from {
  opacity: 0;
  transform: scale(0.95);
}
.error-overlay-leave-to {
  opacity: 0;
  transform: scale(0.97);
}

.accordion-enter-active {
  transition: all 0.3s ease;
}
.accordion-leave-active {
  transition: all 0.2s ease;
}
.accordion-enter-from,
.accordion-leave-to {
  opacity: 0;
  max-height: 0;
  transform: translateY(-4px);
}
.accordion-enter-to,
.accordion-leave-from {
  max-height: 200px;
}
</style>
