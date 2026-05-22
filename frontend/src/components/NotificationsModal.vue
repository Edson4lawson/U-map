<template>
  <div v-if="isOpen" class="fixed inset-0 z-[60] flex justify-end pointer-events-none">
    <!-- Backdrop -->
    <div @click="close" class="absolute inset-0 bg-black/20 backdrop-blur-sm pointer-events-auto transition-opacity" aria-hidden="true"></div>

    <!-- Panel -->
    <div class="relative w-full max-w-sm h-full bg-white dark:bg-gray-900 shadow-2xl pointer-events-auto flex flex-col transform transition-transform duration-300"
         :class="isOpen ? 'translate-x-0' : 'translate-x-full'">
       
      <!-- Header -->
      <div class="p-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-white/80 dark:bg-gray-900/80 backdrop-blur-md sticky top-0 z-10">
         <div class="flex items-center gap-2">
            <h2 class="text-lg font-bold dark:text-white">Notifications</h2>
            <span v-if="unreadCount > 0" class="bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">
              {{ unreadCount }}
            </span>
         </div>
         <button @click="close" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-full transition-colors">
            <Icon icon="ph:x-bold" class="w-5 h-5 dark:text-gray-400" />
         </button>
      </div>

      <!-- Content -->
      <div class="flex-1 overflow-y-auto p-4 space-y-3">
         <div v-if="notifications.length === 0" class="flex flex-col items-center justify-center h-64 text-center text-gray-500">
            <Icon icon="ph:bell-slash" class="w-12 h-12 mb-2 opacity-50" />
            <p>Aucune notification pour le moment</p>
         </div>

         <div v-for="notif in notifications" :key="notif.id" 
              class="relative bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border transition-all hover:bg-gray-50 dark:hover:bg-gray-750"
              :class="notif.read ? 'border-gray-100 dark:border-gray-800 opacity-75' : 'border-blue-100 dark:border-blue-900/30 ring-1 ring-blue-500/20'"
              @click="markRead(notif.id)">
            
            <div class="flex gap-3">
               <!-- Icon based on type -->
               <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center"
                    :class="{
                      'bg-blue-100 text-blue-600': notif.type === 'info',
                      'bg-yellow-100 text-yellow-600': notif.type === 'warning',
                      'bg-green-100 text-green-600': notif.type === 'location',
                      'bg-purple-100 text-purple-600': notif.type === 'event'
                    }">
                  <Icon v-if="notif.type === 'info'" icon="ph:info-bold" />
                  <Icon v-else-if="notif.type === 'warning'" icon="ph:warning-bold" />
                  <Icon v-else-if="notif.type === 'location'" icon="ph:map-pin-bold" />
                  <Icon v-else-if="notif.type === 'event'" icon="ph:calendar-bold" />
               </div>

               <div class="flex-1 min-w-0">
                  <div class="flex justify-between items-start">
                     <h3 class="font-bold text-gray-900 dark:text-gray-100 text-sm truncate pr-2">{{ notif.title }}</h3>
                     <span class="text-[10px] text-gray-400 whitespace-nowrap">{{ formatTime(notif.timestamp) }}</span>
                  </div>
                  <p class="text-sm text-gray-600 dark:text-gray-300 mt-0.5 line-clamp-2 leading-snug">{{ notif.message }}</p>
               </div>
            </div>
            
            <!-- Unread dot -->
            <div v-if="!notif.read" class="absolute top-4 right-2 w-2 h-2 bg-blue-500 rounded-full"></div>
         </div>
      </div>

      <!-- Footer -->
      <div class="p-4 border-t border-gray-100 dark:border-gray-800">
         <button @click="store.markAllAsRead()" class="w-full py-2.5 text-sm font-medium text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors">
            Tout marquer comme lu
         </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Icon } from '@iconify/vue'
import { useNotificationStore } from '../stores/notifications'
import { formatDistanceToNow } from 'date-fns'
import { fr } from 'date-fns/locale'

// Props : Contrôle si la modale est visible ou non
const props = defineProps({
  isOpen: Boolean
})

// Emits : Permet de signaler au parent (Home.vue) de fermer la modale
const emit = defineEmits(['close'])

const store = useNotificationStore()

// Données réactives provenant du store
const notifications = computed(() => store.notifications)
const unreadCount = computed(() => store.unreadCount)

// Ferme la modale
const close = () => {
  emit('close')
}

// Marque une notification comme lue au clic
const markRead = (id) => {
  store.markAsRead(id)
}

// Formate la date pour afficher "il y a X minutes" en français
const formatTime = (date) => {
  try {
    return formatDistanceToNow(new Date(date), { addSuffix: true, locale: fr })
  } catch (e) {
    return 'A l\'instant'
  }
}
</script>
