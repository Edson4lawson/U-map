<template>
  <div class="min-h-screen relative">
    <div class="pt-4 pb-24 px-4 max-w-xl md:max-w-full mx-auto relative z-10">
      <div class="sticky top-0 w-full z-30 bg-transparent rounded-lg backdrop-blur-md py-4">
        <h1 class="text-3xl md:text-4xl font-display font-bold text-gray-900 dark:text-white mb-4 px-2">Lieux</h1>
        <SearchBar v-model="search" />

        <!-- Filter Chips Row -->
        <div class="flex gap-2 overflow-x-auto py-3 px-2 no-scrollbar mt-2">
          <button v-for="filter in filters" :key="filter.id" @click="selectedFilter = filter.id" :class="[
            selectedFilter === filter.id
              ? 'bg-primary text-white shadow-lg shadow-blue-500/20 font-bold'
              : 'bg-gray-200 dark:bg-gray-800/60 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-700/60'
          ]"
            class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium whitespace-nowrap transition-all">
            <Icon :icon="filter.icon" class="w-4 h-4" />
            <span>{{ filter.label }}</span>
          </button>
        </div>
      </div>

      <div class="space-y-4 mt-2 max-w-full grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mx-auto">
        <transition-group name="list">
          <div v-for="place in filteredPlaces" :key="place.properties.id"
            class="card-glass p-3 flex gap-4 cursor-pointer hover:bg-white dark:hover:bg-gray-800 transition-all active:scale-[0.98]"
            @click="goToPlace(place)">
            <div class="w-24 h-24 md:w-32 md:h-32 rounded-xl overflow-hidden flex-shrink-0">
              <img :src="getImage(place)" :alt="place.properties.name" class="w-full h-full object-cover">
            </div>

            <div class="flex-1 flex flex-col justify-center">
              <div class="flex justify-between items-start">
                <h3 class="font-bold text-gray-900 dark:text-white text-lg md:text-xl">{{ place.properties.name }}</h3>
                <Icon v-if="visitedStore.isVisited(place.properties.id)" icon="ph:check-circle-fill"
                  class="text-secondary w-5 h-5" />
              </div>

              <p class="text-primary text-sm font-medium">{{ place.properties.type }}</p>

              <div class="flex gap-2 mt-2">
                <span v-for="tag in getDisplayTags(place.properties.tags, place.properties.category)" :key="tag"
                  class="text-[10px] px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded-full text-gray-500 dark:text-gray-400">
                  {{ tag }}
                </span>
              </div>
            </div>
          </div>
        </transition-group>

        <div v-if="filteredPlaces.length === 0" class="text-center py-20 text-gray-500 dark:text-gray-300">
          <Icon icon="ph:warning-circle" class="w-12 h-12 mx-auto mb-2 opacity-50" />
          <p>Aucun lieu trouvé.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { Icon } from '@iconify/vue'
import SearchBar from '../components/SearchBar.vue'
import { campusService } from '../services/campusService'
import { useVisitedStore } from '../stores/visited'
import { useMeta } from '../composables/useMeta'
import { useStructuredData, getBreadcrumbSchema } from '../composables/useStructuredData'
import { preprocessPlaces, searchPlaces } from '../utils/searchUtils'

useMeta('Lieux du Campus UAC', "Explorez tous les bâtiments, amphithéâtres, bibliothèques, laboratoires, restaurants et services du campus de l'Université d'Abomey-Calavi.", { canonicalPath: '/lieux' })

useStructuredData(getBreadcrumbSchema([
  { name: 'Accueil', url: '/' },
  { name: 'Lieux du Campus', url: '/lieux' }
]))

const router = useRouter()
const route = useRoute()
const visitedStore = useVisitedStore()
const search = ref('')
const selectedFilter = ref('all')
const allPlaces = ref([])
const preprocessedPlaces = ref([])

const filters = [
  { id: 'all', label: 'Tous', icon: 'ph:squares-four' },
  { id: 'amphi', label: 'Amphithéâtres', icon: 'ph:chalkboard-teacher' },
  { id: 'studies', label: 'Études', icon: 'ph:graduation-cap' },
  { id: 'library', label: 'Bibliothèques', icon: 'ph:books' },
  { id: 'building', label: 'Bâtiments', icon: 'ph:buildings' },
  { id: 'food', label: 'Restauration', icon: 'ph:coffee' },
  { id: 'fuel', label: 'Stations-service', icon: 'ph:gas-pump' },
  { id: 'visited', label: 'Visités', icon: 'ph:star' },
]

onMounted(async () => {
  allPlaces.value = await campusService.getAllPlaces()
  // Pré-calculer les versions normalisées pour optimiser la recherche
  preprocessedPlaces.value = preprocessPlaces(allPlaces.value)

  // Check for filter in URL query
  if (route.query.filter) {
    const filterExists = filters.find(f => f.id === route.query.filter)
    if (filterExists) {
      selectedFilter.value = route.query.filter
    }
  }
})

const filteredPlaces = computed(() => {
  if (!preprocessedPlaces.value) return []

  let places = preprocessedPlaces.value

  // Apply category filter
  if (selectedFilter.value !== 'all') {
    if (selectedFilter.value === 'visited') {
      // Filter by visited places
      places = places.filter(p => visitedStore.isVisited(p.properties.id))
    } else if (selectedFilter.value === 'amphi') {
      // Filter by amphithéâtres
      places = places.filter(p => {
        const name = (p.properties?.name || '').toLowerCase()
        const category = (p.properties?.category || '').toLowerCase()
        return name.includes('amphi') || category === 'amphitheatre'
      })
    } else if (selectedFilter.value === 'studies') {
      // Filter by study-related categories (faculty, department, library, institute, school, research_center, etc.)
      const studyCategories = ['faculty', 'department', 'library', 'institute', 'school', 'research_center', 'academic_area', 'university', 'college']
      places = places.filter(p => {
        const category = (p.properties.category || '').toLowerCase()
        return studyCategories.includes(category)
      })
    } else if (selectedFilter.value === 'food') {
      // Filter by food-related categories (fast_food, restaurant, cafe, etc.)
      const foodCategories = ['fast_food', 'restaurant', 'cafe', 'bar']
      places = places.filter(p => {
        const category = (p.properties.category || '').toLowerCase()
        return foodCategories.includes(category)
      })
    } else {
      // Filter by category (legacy filter for single category)
      places = places.filter(p => {
        const category = (p.properties?.category || '').toLowerCase()
        const filter = selectedFilter.value.toLowerCase()
        return category.includes(filter)
      })
    }
  }

  // Apply advanced search filter with normalization and scoring
  if (search.value) {
    places = searchPlaces(places, search.value)
  }

  return places
})

const goToPlace = (place) => {
  const identifier = place.properties?.slug || place.properties?.id
  router.push(`/lieu/${identifier}`)
}

const getImage = (place) => {
  // Default images based on category
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

  const props = place?.properties || place
  const images = props?.images

  if (images && Array.isArray(images) && images.length > 0 && images[0]) {
    return images[0]
  }

  // Use category-based default image
  const category = props?.category || props?.type || 'default'
  return defaultImages[category] || defaultImages.default
}

const getDisplayTags = (tags, category) => {
  if (!category) return []
  
  const displayTags = []
  
  // Utiliser uniquement la catégorie principale
  if (category && category !== 'yes' && category !== 'other') {
    displayTags.push(category)
  }
  
  // Ne plus utiliser les tags OSM techniques - seulement la catégorie
  return displayTags.slice(0, 2) // Maximum 2 tags
}
</script>

<style scoped>
.list-enter-active,
.list-leave-active {
  transition: all 0.3s ease;
}

.list-enter-from,
.list-leave-to {
  opacity: 0;
  transform: translateY(20px);
}
</style>
