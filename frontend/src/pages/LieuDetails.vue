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
        <swiper-slide v-for="(img, index) in placeImages" :key="index">
           <img :src="img" class="w-full h-full object-cover" />
           <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
        </swiper-slide>
      </swiper>
    </div>

    <!-- Content -->
    <div class="-mt-6 relative z-10 bg-white dark:bg-gray-900 rounded-t-3xl px-6 py-8 shadow-inner animate-slide-up">
       <div class="flex justify-between items-start mb-4">
          <div>
            <h1 class="text-3xl font-display font-bold text-gray-900 dark:text-white">{{ place.name }}</h1>
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
          <span v-for="tag in placeTags" :key="tag" class="px-3 py-1 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 rounded-lg text-xs font-medium">
            #{{ tag }}
          </span>
       </div>

       <div class="space-y-6">
          <section>
             <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
               <Icon icon="ph:info" />
               Description
             </h2>
             <p class="text-gray-600 dark:text-gray-400 leading-relaxed">{{ placeDescription }}</p>
          </section>

          <section>
             <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
               <Icon icon="ph:clock" />
               Horaires
             </h2>
             <p class="text-gray-600 dark:text-gray-400">{{ placeOpeningHours }}</p>
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
import { useMeta } from '../composables/useMeta'
import { useStructuredData, getPlaceSchema, getBreadcrumbSchema } from '../composables/useStructuredData'

const route = useRoute()
const router = useRouter()
const visitedStore = useVisitedStore()
const modules = [Pagination]

const place = ref(null)
const placeFeature = ref(null)

// SEO dynamique basé sur le lieu chargé
const seoTitle = computed(() => place.value ? place.value.name : 'Détails du lieu')
const seoDescription = computed(() => place.value ? `${place.value.name} — ${place.value.description} Campus UAC, Abomey-Calavi.` : "Détails d'un lieu du campus de l'UAC.")

useMeta(seoTitle, seoDescription, { canonicalPath: `/lieu/${route.params.id}` })

// JSON-LD structuré pour le lieu
useStructuredData(() => {
  const schemas = [
    getBreadcrumbSchema([
      { name: 'Accueil', url: '/' },
      { name: 'Lieux', url: '/lieux' },
      { name: place.value?.name || 'Lieu', url: `/lieu/${route.params.id}` }
    ])
  ]
  if (placeFeature.value) {
    const p = placeFeature.value
    const placeSchema = getPlaceSchema({
      id: p.properties.id,
      name: p.properties.name,
      description: p.properties.description,
      type: p.properties.type,
      openingHours: p.properties.openingHours,
      coordinates: p.geometry?.coordinates
    })
    if (placeSchema) schemas.push(placeSchema)
  }
  return schemas
})

const hasIndoorPlan = computed(() => {
    return place.value && place.value.category?.toLowerCase() === 'library'
})

// Images avec fallback basé sur la catégorie
const placeImages = computed(() => {
  const defaultImages = {
    'amphitheatre': 'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?q=80&w=1000&auto=format&fit=crop',
    'faculty': 'https://images.unsplash.com/photo-1562774053-701939374585?q=80&w=1000&auto=format&fit=crop',
    'department': 'https://images.unsplash.com/photo-1497633762265-9d179a990aa6?q=80&w=1000&auto=format&fit=crop',
    'office': 'https://images.unsplash.com/photo-1497366216548-37526070297c?q=80&w=1000&auto=format&fit=crop',
    'administration': 'https://images.unsplash.com/photo-1497366811356-6870744d04b2?q=80&w=1000&auto=format&fit=crop',
    'laboratory': 'https://images.unsplash.com/photo-1532094349884-543bc11b234d?q=80&w=1000&auto=format&fit=crop',
    'library': 'https://images.unsplash.com/photo-1521587760476-6c12a4b040da?q=80&w=1000&auto=format&fit=crop',
    'restaurant': 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?q=80&w=1000&auto=format&fit=crop',
    'cafe': 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?q=80&w=1000&auto=format&fit=crop',
    'bank': 'https://images.unsplash.com/photo-1601597111158-2fceff292cdc?q=80&w=1000&auto=format&fit=crop',
    'atm': 'https://images.unsplash.com/photo-1556742049-0cfed4f7a07d?q=80&w=1000&auto=format&fit=crop',
    'fuel': 'https://images.unsplash.com/photo-1626694549297-5d8b5e4e6b5e?q=80&w=1000&auto=format&fit=crop',
    'dormitory': 'https://images.unsplash.com/photo-1555854877-bab0e564b8d5?q=80&w=1000&auto=format&fit=crop',
    'research_center': 'https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?q=80&w=1000&auto=format&fit=crop',
    'institute': 'https://images.unsplash.com/photo-1562774053-701939374585?q=80&w=1000&auto=format&fit=crop',
    'school': 'https://images.unsplash.com/photo-1580582932707-520aed937b7b?q=80&w=1000&auto=format&fit=crop',
    'botanical_garden': 'https://images.unsplash.com/photo-1585320806297-9794b3e4eeae?q=80&w=1000&auto=format&fit=crop',
    'greenhouse': 'https://images.unsplash.com/photo-1416879595882-3373a0480b5b?q=80&w=1000&auto=format&fit=crop',
    'farm': 'https://images.unsplash.com/photo-1500382017468-9049fed747ef?q=80&w=1000&auto=format&fit=crop',
    'print_shop': 'https://images.unsplash.com/photo-1562564055-71e051d33c19?q=80&w=1000&auto=format&fit=crop',
    'academic_area': 'https://images.unsplash.com/photo-1562774053-701939374585?q=80&w=1000&auto=format&fit=crop',
    'campus': 'https://images.unsplash.com/photo-1541339907198-e08756dedf3f?q=80&w=1000&auto=format&fit=crop',
    'building': 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?q=80&w=1000&auto=format&fit=crop',
    'university': 'https://images.unsplash.com/photo-1562774053-701939374585?q=80&w=1000&auto=format&fit=crop',
    'default': 'https://images.unsplash.com/photo-1562774053-701939374585?q=80&w=1000&auto=format&fit=crop'
  }

  if (place.value?.images && Array.isArray(place.value.images) && place.value.images.length > 0) {
    return place.value.images
  }
  
  const category = place.value?.category || place.value?.type || 'default'
  return [defaultImages[category] || defaultImages.default]
})

// Tags basés sur la catégorie réelle du lieu
const placeTags = computed(() => {
  if (!place.value) return []
  
  const tags = []
  
  // Utiliser la catégorie principale
  if (place.value.category && place.value.category !== 'yes' && place.value.category !== 'other') {
    tags.push(place.value.category)
  }
  
  // Ne plus utiliser les tags OSM techniques - seulement la catégorie
  return tags.slice(0, 3) // Maximum 3 tags
})

// Description améliorée
const placeDescription = computed(() => {
  if (!place.value) return ''
  
  if (place.value.description && place.value.description !== 'University') {
    return place.value.description
  }
  
  // Générer une description basée sur le type et la catégorie
  const type = place.value.type || 'Lieu'
  const category = place.value.category || ''
  const name = place.value.name || ''
  
  if (category === 'university') {
    return `${name} est un établissement universitaire situé au sein du campus de l'Université d'Abomey-Calavi (UAC).`
  } else if (category === 'library') {
    return `${name} est une bibliothèque universitaire offrant des ressources documentaires et des espaces d'étude aux étudiants.`
  } else if (category === 'restaurant' || category === 'cafe') {
    return `${name} est un établissement de restauration situé sur le campus UAC.`
  } else if (category === 'fuel') {
    return `${name} est une station-service située sur le campus UAC.`
  } else if (category === 'bank' || category === 'atm') {
    return `${name} est un service bancaire situé sur le campus UAC.`
  }
  
  return `${name} est un ${type} situé sur le campus de l'Université d'Abomey-Calavi.`
})

// Horaires par défaut
const placeOpeningHours = computed(() => {
  if (place.value?.opening_hours) {
    return place.value.opening_hours
  }
  
  const category = place.value?.category || ''
  
  if (['faculty', 'department', 'institute', 'school', 'research_center', 'academic_area', 'university', 'library'].includes(category)) {
    return 'Lundi - Vendredi: 8h00 - 18h00'
  } else if (['restaurant', 'cafe'].includes(category)) {
    return 'Lundi - Samedi: 7h30 - 20h00'
  } else if (['bank', 'atm'].includes(category)) {
    return 'Lundi - Vendredi: 8h30 - 16h00'
  } else if (category === 'fuel') {
    return '24h/24'
  } else if (['administration', 'office'].includes(category)) {
    return 'Lundi - Vendredi: 8h00 - 17h00'
  } else if (['laboratory', 'botanical_garden', 'greenhouse', 'farm'].includes(category)) {
    return 'Lundi - Vendredi: 8h00 - 17h00'
  } else if (category === 'dormitory') {
    return '24h/24 (résidents)'
  } else if (category === 'print_shop') {
    return 'Lundi - Vendredi: 8h00 - 17h00'
  }
  
  return 'Horaires non disponibles'
})

onMounted(async () => {
  const feature = await campusService.getPlaceById(route.params.id)
  if (feature) {
    placeFeature.value = feature
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
  const identifier = place.value?.slug || place.value?.id
  router.push({ name: 'Map', query: { focus: identifier } })
}

const startItinerary = () => {
  const identifier = place.value?.slug || place.value?.id
  router.push({ name: 'Map', query: { focus: identifier, routeTo: identifier } })
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
