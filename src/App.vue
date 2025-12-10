<template>
  <div class="flex flex-col h-full overflow-hidden text-gray-900 dark:text-gray-100 font-sans">
    <VideoBackground />
    
    <!-- ERROR BOUNDARY -->
    <div v-if="error" class="fixed inset-0 z-[100] bg-red-900/90 backdrop-blur text-white p-8 flex flex-col items-center justify-center pointer-events-auto">
      <div class="bg-gray-900 p-6 rounded-xl border border-red-500 shadow-2xl max-w-2xl w-full">
        <h2 class="text-2xl font-bold text-red-500 mb-4 flex items-center gap-2">
          <span class="text-3xl">⚠️</span> Application Error
        </h2>
        <pre class="bg-black/50 p-4 rounded-lg overflow-x-auto text-sm font-mono text-red-200 whitespace-pre-wrap">{{ error }}</pre>
        <button @click="reloadApp" class="mt-6 w-full py-3 bg-red-600 hover:bg-red-700 rounded-lg font-semibold transition-colors">
          Reload Application
        </button>
      </div>
    </div>

    <!-- Main Content Area -->
    <main class="flex-1 overflow-y-auto w-full pb-24 relative z-0 scroll-smooth">
      <router-view v-slot="{ Component }">
        <transition name="fade" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </main>

    <!-- Floating Actions -->
    <FloatingButtons />

    <!-- Bottom Navigation -->
    <Navbar />
  </div>
</template>

<script setup>
import { ref, onErrorCaptured, onMounted } from 'vue'
import VideoBackground from './components/VideoBackground.vue'
import Navbar from './components/Navbar.vue'
import FloatingButtons from './components/FloatingButtons.vue'
import { useProximity } from './composables/useProximity'
import { useNotificationStore } from './stores/notifications'

const error = ref(null)
const { startWatching } = useProximity()
const notificationStore = useNotificationStore()

const reloadApp = () => {
  window.location.reload()
}

onErrorCaptured((err) => {
  error.value = err.toString()
  return false // Prevent propogation
})

onMounted(() => {
  // Lancer la surveillance de proximité (GPS) pour les notifications
  startWatching()

  // Injection de fausses notifications pour la démo (Simule un backend)
  setTimeout(() => {
    if (notificationStore.notifications.length === 0) {
       notificationStore.addNotification({
         title: 'Bienvenue sur U-map !',
         message: 'Découvrez le campus de l\'UAC comme jamais auparavant. Ajoutez vos lieux favoris !',
         type: 'info'
       })
       
       notificationStore.addNotification({
         title: 'Examen à venir',
         message: 'N\'oubliez pas de consulter vos salles de composition pour la session de Janvier.',
         type: 'warning'
       })

       notificationStore.addNotification({
         title: 'Concert Campus',
         message: 'Grand concert ce vendredi soir à l\'amphi Idriss Deby. Venez nombreux !',
         type: 'event'
       })
    }
  }, 1000)
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
</style>
