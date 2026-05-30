<template>
  <div v-if="place" class="bg-white dark:bg-gray-900 min-h-screen relative pb-24">
    <!-- Back Button -->
    <button @click="$router.back()" class="absolute top-4 left-4 z-20 p-2 bg-white/50 dark:bg-black/50 backdrop-blur-md rounded-full text-gray-900 dark:text-white hover:bg-white dark:hover:bg-black transition-colors">
      <Icon icon="ph:arrow-left-bold" class="w-6 h-6" />
    </button>

    <!-- Images Swiper -->
    <div class="h-72 w-full relative">
       <swiper
        :modules="modules"
        :pagination="{ clickable: true }"
        class="h-full w-full"
      >
        <swiper-slide v-for="(img, index) in place.images" :key="index">
           <img :src="img" class="w-full h-full object-cover" />
           <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
        </swiper-slide>
      </swiper>
    </div>

    <!-- Content -->
    <div class="-mt-6 relative z-10 bg-white dark:bg-gray-900 rounded-t-3xl px-6 py-8 shadow-inner animate-slide-up">
       <div class="flex justify-between items-start mb-4">
          <div>
            <span class="text-primary text-sm font-bold uppercase tracking-wide">{{ place.type }}</span>
            <h1 class="text-3xl font-display font-bold text-gray-900 dark:text-white mt-1">{{ place.name }}</h1>
          </div>
          <button @click="toggleVisit" class="flex flex-col items-center gap-1">
             <div 
               class="p-3 rounded-full transition-colors duration-300"
               :class="isVisited ? 'bg-secondary text-white shadow-lg shadow-green-500/30' : 'bg-gray-100 dark:bg-gray-800 text-gray-400'"
             >
                <Icon :icon="isVisited ? 'ph:check-bold' : 'ph:check'" class="w-6 h-6" />
             </div>
             <span class="text-[10px] font-medium" :class="isVisited ? 'text-secondary' : 'text-gray-400'">
               {{ isVisited ? 'Visité' : 'Visiter' }}
             </span>
          </button>
       </div>

       <div class="flex flex-wrap gap-2 mb-6">
          <span v-for="tag in place.tags" :key="tag" class="px-3 py-1 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 rounded-lg text-xs font-medium">
            #{{ tag }}
          </span>
       </div>

       <div class="space-y-6">
          <section>
             <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
               <Icon icon="ph:info" />
               Description
             </h2>
             <p class="text-gray-600 dark:text-gray-400 leading-relaxed">{{ place.description }}</p>
          </section>

          <section>
             <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
               <Icon icon="ph:clock" />
               Horaires
             </h2>
             <p class="text-gray-600 dark:text-gray-400">{{ place.openingHours }}</p>
          </section>

          <!-- Plan d'intérieur interactif (BU centrale ou grands amphis) -->
          <section v-if="hasIndoorPlan" class="border-t border-gray-100 dark:border-gray-800 pt-6">
             <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
               <Icon icon="ph:map-trifold-bold" class="text-primary" />
               Plan d'Intérieur Interactif
             </h2>
             <IndoorMap />
          </section>
       </div>

       <!-- Sticky Bottom Action -->
       <div class="fixed bottom-20 left-0 w-full px-6 z-30 pointer-events-none">
          <div class="grid grid-cols-2 gap-3 pointer-events-auto">
            <button @click="goToMap" class="btn-primary flex items-center justify-center gap-2 transform active:scale-95 bg-gray-100 !text-gray-900 dark:bg-gray-800 dark:!text-white border-none">
               <Icon icon="ph:map-pin-bold" class="w-5 h-5" />
               Carte
            </button>
            <button @click="startItinerary" class="btn-primary flex items-center justify-center gap-2 transform active:scale-95 bg-secondary">
               <Icon icon="ph:navigation-arrow-bold" class="w-5 h-5" />
               Itinéraire
            </button>
          </div>
       </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Swiper, SwiperSlide } from 'swiper/vue'
import { Pagination } from 'swiper/modules'
import { Icon } from '@iconify/vue'
import 'swiper/css'
import 'swiper/css/pagination'

import { campusService } from '../services/campusService'
import { useVisitedStore } from '../stores/visited'
import IndoorMap from '../components/IndoorMap.vue'

const route = useRoute()
const router = useRouter()
const visitedStore = useVisitedStore()
const modules = [Pagination]

const place = ref(null)

const hasIndoorPlan = computed(() => {
    return place.value && (
        place.value.name.toLowerCase().includes('bibliothèque') || 
        place.value.name.toLowerCase().includes('bu') ||
        place.value.id === 'bu_centrale' ||
        place.value.id === '1' ||
        place.value.category.toLowerCase().includes('bibliothèque')
    )
})

onMounted(async () => {
  const feature = await campusService.getPlaceById(route.params.id)
  if (feature) {
    place.value = feature.properties
  }
})

const isVisited = computed(() => place.value && visitedStore.isVisited(place.value.id))

const toggleVisit = () => {
  if (place.value) {
    visitedStore.toggleVisited(place.value.id)
  }
}

const goToMap = () => {
  router.push({ name: 'Map', query: { focus: place.value.id } })
}

const startItinerary = () => {
    router.push({ name: 'Map', query: { routeTo: place.value.id } })
}


</script>

<style scoped>
.animate-slide-up {
  animation: slideUp 0.5s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes slideUp {
  from { transform: translateY(20px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}
</style>
