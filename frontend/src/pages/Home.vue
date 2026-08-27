<template>
  <div class="min-h-screen relative">
    <div class="pt-6 pb-24 px-4 max-w-xl md:max-w-full mx-auto relative z-10">
      <!-- Custom Header -->
      <header class="flex items-center justify-between mb-8 relative z-50">
        <div class="flex items-center gap-2">
          <div class="w-10 h-10 bg-primary rounded-xl flex items-center justify-center transform -rotate-6 shadow-lg overflow-hidden">
            <img src="/pwa-192.png" alt="U-map Logo" class="w-full h-full object-cover" />
          </div>
          <h1 class="text-2xl md:text-4xl font-fun text-white">U-map</h1>
        </div>
        <div class="flex items-center gap-3">
            <button @click="showNotifications = true"
              class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center relative hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
              <Icon icon="ph:bell-bold" class="text-gray-500 w-5 h-5" />
              <span v-if="unreadNotifications > 0"
                class="absolute top-2 right-2 w-2.5 h-2.5 bg-red-500 border-2 border-white dark:border-gray-900 rounded-full"></span>
            </button>
        </div>

        <!-- Notifications Modal -->
        <NotificationsModal :is-open="showNotifications" @close="showNotifications = false" />
      </header>

      <!-- Hero Section -->
      <div class="text-center mt-4 mb-12" data-aos="fade-down">
        <h2 class="text-3xl md:text-5xl font-display font-bold text-white mb-2">
          {{ $t('home.hero_title', { brand: 'U-map' }) }}
        </h2>
        <p class="text-gray-200 text-lg md:text-xl">
          {{ $t('home.hero_subtitle') }}
        </p>

        <div class="mt-8 flex justify-center gap-4">
          <router-link to="/map" class="btn-primary flex items-center gap-2 shadow-xl shadow-blue-500/20">
            <span>{{ $t('common.explore') }}</span>
            <Icon icon="ph:arrow-right-bold" />
          </router-link>
        </div>
      </div>

      <!-- Stats / Quick Info -->
      <div class="grid grid-cols-2 gap-4 mb-12">
        <div class="card-glass p-4 text-center">
          <Icon icon="ph:buildings-bold" class="w-8 h-8 md:w-12 md:h-12 mx-auto text-secondary mb-2" />
          <span class="block text-2xl md:text-3xl font-bold dark:text-white">{{ allPlaces.length }}</span>
          <span class="text-xs text-gray-500 uppercase tracking-wider">{{ $t('home.stats.places') }}</span>
        </div>
        <div class="card-glass p-4 text-center">
          <Icon icon="ph:check-circle-bold" class="w-8 h-8 md:w-12 md:h-12 mx-auto text-primary mb-2" />
          <span class="block text-2xl md:text-3xl font-bold dark:text-white">{{ visitedCount }}</span>
          <span class="text-xs text-gray-500 uppercase tracking-wider">{{ $t('home.stats.visited') }}</span>
        </div>
      </div>

      <!-- Popular Places Carousel -->
      <section data-aos="fade-up" data-aos-delay="200">
        <div class="flex justify-between items-center mb-4 px-2">
          <h2 class="text-xl font-bold dark:text-white">{{ $t('home.popular_places') }}</h2>
          <router-link to="/lieux" class="text-sm text-primary font-medium hover:underline">{{ $t('home.view_all') }}</router-link>
        </div>

        <swiper :modules="modules" 
                :slides-per-view="1.2" 
                :breakpoints="{
                  '768': { slidesPerView: 2.2 },
                  '1024': { slidesPerView: 3.2 }
                }"
                :space-between="20" 
                :centered-slides="false"
                :pagination="{ clickable: false }" 
                :autoplay="{ delay: 5000 }" 
                class="pb-10">
          <swiper-slide v-for="place in featuredPlaces" :key="place.id" class="h-64 md:h-72">
            <div class="relative w-full h-full rounded-2xl overflow-hidden shadow-lg group cursor-pointer"
              @click="goToPlace(place)">
              <img :src="getImage(place)" :alt="place.name"
                class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" />
              <div
                class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent p-4 flex flex-col justify-end">
                <span class="px-2 py-1 bg-primary/90 text-white text-xs rounded-md w-fit mb-2 backdrop-blur-sm">{{
                  place.type }}</span>
                <h3 class="text-white font-bold text-lg leading-tight">{{ place.name }}</h3>
                <div class="flex items-center text-gray-300 text-xs mt-1">
                  <Icon icon="ph:clock" class="mr-1" />
                  {{ place.openingHours }}
                </div>
              </div>
            </div>
          </swiper-slide>
        </swiper>
      </section>

      <!-- Events Section -->
      <section class="mt-8 px-2">
        <h2 class="text-xl font-bold dark:text-white mb-4 flex items-center gap-2">
           <Icon icon="ph:calendar-star-bold" class="text-secondary" />
           Événements à venir
        </h2>
        <div class="space-y-3">
           <div v-for="event in dynamicEvents" :key="event.id" class="card-glass p-4 border-l-4 border-secondary">
              <div class="flex justify-between items-start">
                 <h3 class="font-bold text-gray-900 dark:text-white">{{ event.title }}</h3>
                 <span class="text-[10px] bg-secondary/20 text-secondary px-2 py-0.5 rounded-full uppercase font-bold">{{ event.type }}</span>
              </div>
              <p class="text-xs text-gray-500 mt-1 line-clamp-2">{{ event.description }}</p>
              <div class="flex items-center gap-3 mt-3 text-[10px] text-gray-400 font-medium">
                 <div class="flex items-center gap-1">
                    <Icon icon="ph:clock" />
                    {{ new Date(event.start_time).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) }}
                 </div>
              </div>
           </div>
           <div v-if="dynamicEvents.length === 0" class="text-center py-4 text-gray-400 text-sm">
              Aucun événement prévu prochainement.
           </div>
        </div>
      </section>

      <!-- Quick Actions Grid -->
      <section class="mt-8">
        <h2 class="text-xl font-bold dark:text-white mb-4 px-2">{{ $t('home.quick_access') }}</h2>
        <div class="grid grid-cols-3 gap-3">
          <button
            class="card-glass p-4 flex flex-col items-center justify-center gap-2 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
            @click="$router.push('/lieux?filter=studies')">
            <div
              class="w-10 h-10 md:w-16 md:h-16 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center">
              <Icon icon="ph:graduation-cap" class="w-5 h-5 md:w-8 md:h-8" />
            </div>
            <span class="text-xs font-medium dark:text-gray-300">{{ $t('home.actions.studies') }}</span>
          </button>
          <button
            class="card-glass p-4 flex flex-col items-center justify-center gap-2 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
            @click="$router.push('/lieux?filter=food')">
            <div
              class="w-10 h-10 md:w-16 md:h-16 rounded-full bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 flex items-center justify-center">
              <Icon icon="ph:fork-knife" class="w-5 h-5 md:w-8 md:h-8" />
            </div>
            <span class="text-xs font-medium dark:text-gray-300">{{ $t('home.actions.food') }}</span>
          </button>
          <button
            class="card-glass p-4 flex flex-col items-center justify-center gap-2 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
            @click="$router.push('/lieux?filter=visited')">
            <div
              class="w-10 h-10 md:w-16 md:h-16 rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 flex items-center justify-center">
              <Icon icon="ph:star" class="w-5 h-5 md:w-8 md:h-8" />
            </div>
            <span class="text-xs font-medium dark:text-gray-300">{{ $t('home.actions.favorites') }}</span>
          </button>
        </div>
      </section>

      <!-- Custom Footer -->

      <footer class="mt-12 py-8 border-t border-gray-200 dark:border-gray-800 text-center">
        <p class="text-gray-400 text-sm font-fun">Made with <Icon icon="ph:heart-fill" class="inline text-red-500 w-4 h-4 align-middle" /> for UAC</p> <br>
        <a href="https://Edson-lawson.vercel.app" target="_blank" rel="noopener"
          class="sweep-link text-gray-400 text-sm font-gothic hover:text-primary transition-colors duration-300 relative">
          Développé par Edson Lawson
        </a>
        <div class="flex justify-center gap-4 mt-4">
          <a href="#" class="text-gray-400 hover:text-primary transition-colors">
            <Icon icon="ph:instagram-logo" class="w-6 h-6" />
          </a>
          <a href="#" class="text-gray-400 hover:text-primary transition-colors">
            <Icon icon="ph:facebook-logo" class="w-6 h-6" />
          </a>
          <a href="#" class="text-gray-400 hover:text-primary transition-colors">
            <Icon icon="ph:whatsapp-logo" class="w-6 h-6" />
          </a>
        </div>
        <div class="flex justify-center gap-6 mt-4 text-xs">
          <router-link to="/privacy-policy" class="text-gray-500 hover:text-primary transition-colors">
            Politique de confidentialité
          </router-link>
          <router-link to="/terms-of-service" class="text-gray-500 hover:text-primary transition-colors">
            Conditions d'utilisation
          </router-link>
        </div>
        <p class="text-[10px] text-gray-500 mt-4">&copy; 2025 U-map App. Tous droits réservés.</p>
      </footer>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useMeta } from '../composables/useMeta'
import { useStructuredData, getHomeSchema } from '../composables/useStructuredData'

useMeta('Accueil', "Votre guide interactif et communautaire du campus de l'UAC (Abomey-Calavi). Carte interactive, navigation GPS, lieux et badges d'exploration.", { canonicalPath: '/' })

useStructuredData(getHomeSchema())

import { Icon } from '@iconify/vue'
import { Swiper, SwiperSlide } from 'swiper/vue'
import { Pagination, Autoplay } from 'swiper/modules'
import 'swiper/css'
import 'swiper/css/pagination'

import { campusService } from '../services/campusService'
import { eventService } from '../services/eventService'
import { useVisitedStore } from '../stores/visited'
import { useNotificationStore } from '../stores/notifications'
import NotificationsModal from '../components/NotificationsModal.vue'

const router = useRouter()
const visitedStore = useVisitedStore()
const notificationStore = useNotificationStore()
const modules = [Pagination, Autoplay]

// État pour afficher/masquer la modale de notifications
const showNotifications = ref(false)

const allPlaces = ref([])
const visitedCount = computed(() => visitedStore.visitedPlaces.length)
const featuredPlaces = computed(() => allPlaces.value.slice(0, 5))
const dynamicEvents = ref([])

onMounted(async () => {
    // Paralléliser les appels pour gagner du temps
    const [places, events] = await Promise.all([
        campusService.getAllPlaces(),
        eventService.fetchEvents()
    ])
    
    allPlaces.value = places.map(f => f.properties)
    dynamicEvents.value = eventService.getUpcomingEvents()

    // Générer des notifications dynamiques basées sur les événements
    dynamicEvents.value.forEach(event => {
        const eventDate = new Date(event.start_time)
        const isSoon = (eventDate - new Date()) < 86400000 // Moins de 24h
        
        if (isSoon) {
            notificationStore.addNotification({
                id: `evt_${event.id}`, // Éviter les doublons
                title: 'Événement imminent !',
                message: `${event.title} commence bientôt. Ne le ratez pas !`,
                type: 'event'
            })
        }
    })
})


// Compteur pour le badge rouge sur la cloche
const unreadNotifications = computed(() => notificationStore.unreadCount)

const goToPlace = (place) => {
  const identifier = place.slug || place.id
  router.push(`/lieu/${identifier}`)
}

const getImage = (place) => {
  // Default images based on category
  const defaultImages = {
    'university': 'https://images.unsplash.com/photo-1562774053-701939374585?q=80&w=1000&auto=format&fit=crop',
    'library': 'https://images.unsplash.com/photo-1521587760476-6c12a4b040da?q=80&w=1000&auto=format&fit=crop',
    'building': 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?q=80&w=1000&auto=format&fit=crop',
    'restaurant': 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?q=80&w=1000&auto=format&fit=crop',
    'fuel': 'https://images.unsplash.com/photo-1626694549297-5d8b5e4e6b5e?q=80&w=1000&auto=format&fit=crop',
    'cafe': 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?q=80&w=1000&auto=format&fit=crop',
    'atm': 'https://images.unsplash.com/photo-1556742049-0cfed4f7a07d?q=80&w=1000&auto=format&fit=crop',
    'bank': 'https://images.unsplash.com/photo-1601597111158-2fceff292cdc?q=80&w=1000&auto=format&fit=crop',
    'default': 'https://images.unsplash.com/photo-1562774053-701939374585?q=80&w=1000&auto=format&fit=crop'
  }

  const images = place?.images
  
  if (images && Array.isArray(images) && images.length > 0 && images[0]) {
    return images[0]
  }
  
  // Use category-based default image
  const category = place?.category || place?.type || 'default'
  return defaultImages[category] || defaultImages.default
}
</script>

<style>
.swiper-pagination-bullet-active {
  background-color: theme('colors.primary') !important;
}
</style>
