<template>
  <div class="min-h-screen relative">
    <div class="pt-6 pb-24 px-4 max-w-xl md:max-w-full mx-auto relative z-10">
      <!-- Custom Header -->
      <header class="flex items-center justify-between mb-8 relative z-50">
        <div class="flex items-center gap-2">
          <div class="w-10 h-10 bg-primary rounded-xl flex items-center justify-center transform -rotate-6 shadow-lg">
            <Icon icon="ph:map-trifold-bold" class="text-white w-6 h-6" />
          </div>
          <h1 class="text-2xl md:text-4xl font-fun text-white">U-map</h1>
        </div>
        <button @click="showNotifications = true"
          class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center relative hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
          <Icon icon="ph:bell-bold" class="text-gray-500 w-5 h-5" />
          <span v-if="unreadNotifications > 0"
            class="absolute top-2 right-2 w-2.5 h-2.5 bg-red-500 border-2 border-white dark:border-gray-900 rounded-full"></span>
        </button>

        <!-- Notifications Modal -->
        <NotificationsModal :is-open="showNotifications" @close="showNotifications = false" />
      </header>

      <!-- Hero Section -->
      <div class="text-center mt-4 mb-12" data-aos="fade-down">
        <h2 class="text-3xl md:text-5xl font-display font-bold text-white mb-2">
          Bienvenue sur <span class="text-primary font-mono">U-map</span>
        </h2>
        <p class="text-gray-200 text-lg md:text-xl">
          Votre guide interactif du campus d'Abomey-Calavi.
        </p>

        <div class="mt-8 flex justify-center gap-4">
          <router-link to="/map" class="btn-primary flex items-center gap-2 shadow-xl shadow-blue-500/20">
            <span>Explorer</span>
            <Icon icon="ph:arrow-right-bold" />
          </router-link>
        </div>
      </div>

      <!-- Stats / Quick Info -->
      <div class="grid grid-cols-2 gap-4 mb-12" data-aos="fade-up" data-aos-delay="100">
        <div class="card-glass p-4 text-center">
          <Icon icon="ph:buildings-bold" class="w-8 h-8 md:w-12 md:h-12 mx-auto text-secondary mb-2" />
          <span class="block text-2xl md:text-3xl font-bold dark:text-white">{{ places.length }}</span>
          <span class="text-xs text-gray-500 uppercase tracking-wider">Lieux</span>
        </div>
        <div class="card-glass p-4 text-center">
          <Icon icon="ph:check-circle-bold" class="w-8 h-8 md:w-12 md:h-12 mx-auto text-primary mb-2" />
          <span class="block text-2xl md:text-3xl font-bold dark:text-white">{{ visitedCount }}</span>
          <span class="text-xs text-gray-500 uppercase tracking-wider">Visités</span>
        </div>
      </div>

      <!-- Popular Places Carousel -->
      <section data-aos="fade-up" data-aos-delay="200">
        <div class="flex justify-between items-center mb-4 px-2">
          <h2 class="text-xl font-bold dark:text-white">Lieux Populaires</h2>
          <router-link to="/lieux" class="text-sm text-primary font-medium hover:underline">Voir tout</router-link>
        </div>

        <swiper :modules="modules" :slides-per-view="1.2" :space-between="20" :centered-slides="true"
          :pagination="{ clickable: false }" :autoplay="{ delay: 5000 }" class="pb-10">
          <swiper-slide v-for="place in featuredPlaces" :key="place.id" class="h-64 md:h-96">
            <div class="relative w-full h-full rounded-2xl overflow-hidden shadow-lg group cursor-pointer"
              @click="goToPlace(place.id)">
              <img :src="place.images[0]" :alt="place.name"
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

      <!-- Quick Actions Grid -->
      <section class="mt-8" data-aos="fade-up" data-aos-delay="300">
        <h2 class="text-xl font-bold dark:text-white mb-4 px-2">Accès Rapide</h2>
        <div class="grid grid-cols-3 gap-3">
          <button
            class="card-glass p-4 flex flex-col items-center justify-center gap-2 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
            @click="$router.push('/lieux')">
            <div
              class="w-10 h-10 md:w-16 md:h-16 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center">
              <Icon icon="ph:books" class="w-5 h-5 md:w-8 md:h-8" />
            </div>
            <span class="text-xs font-medium dark:text-gray-300">Études</span>
          </button>
          <button
            class="card-glass p-4 flex flex-col items-center justify-center gap-2 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
            @click="$router.push('/lieux')">
            <div
              class="w-10 h-10 md:w-16 md:h-16 rounded-full bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 flex items-center justify-center">
              <Icon icon="ph:coffee" class="w-5 h-5 md:w-8 md:h-8" />
            </div>
            <span class="text-xs font-medium dark:text-gray-300">Resto</span>
          </button>
          <button
            class="card-glass p-4 flex flex-col items-center justify-center gap-2 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
            @click="$router.push('/visites')">
            <div
              class="w-10 h-10 md:w-16 md:h-16 rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 flex items-center justify-center">
              <Icon icon="ph:star" class="w-5 h-5 md:w-8 md:h-8" />
            </div>
            <span class="text-xs font-medium dark:text-gray-300">Favoris</span>
          </button>
        </div>
      </section>

      <!-- Custom Footer -->
      <footer class="mt-12 py-8 border-t border-gray-200 dark:border-gray-800 text-center">
        <p class="text-gray-400 text-sm font-fun">Made with ❤️ for UAC</p> <br>
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
            <Icon icon="ph:twitter-logo" class="w-6 h-6" />
          </a>
        </div>
        <p class="text-[10px] text-gray-500 mt-4">&copy; 2025 U-map App. Tous droits réservés.</p>
      </footer>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { Icon } from '@iconify/vue'
import { Swiper, SwiperSlide } from 'swiper/vue'
import { Pagination, Autoplay } from 'swiper/modules'
import 'swiper/css'
import 'swiper/css/pagination'

import campusData from '../data/campus.json'
import { useVisitedStore } from '../stores/visited'
import { useNotificationStore } from '../stores/notifications'
import NotificationsModal from '../components/NotificationsModal.vue'

const router = useRouter()
const visitedStore = useVisitedStore()
const notificationStore = useNotificationStore()
const modules = [Pagination, Autoplay]

// État pour afficher/masquer la modale de notifications
const showNotifications = ref(false)

const places = campusData.features.map(feature => ({
  ...feature.properties,
  coordinates: feature.geometry.coordinates
}))
const visitedCount = computed(() => visitedStore.visitedPlaces.length)
const featuredPlaces = computed(() => places.slice(0, 3))
// Compteur pour le badge rouge sur la cloche
const unreadNotifications = computed(() => notificationStore.unreadCount)

const goToPlace = (id) => {
  router.push(`/lieu/${id}`)
}
</script>

<style>
.swiper-pagination-bullet-active {
  background-color: theme('colors.primary') !important;
}
</style>
