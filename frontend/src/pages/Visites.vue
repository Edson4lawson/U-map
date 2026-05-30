<template>
  <div class="min-h-screen relative">
    <div class="pt-8 pb-24 px-4 max-w-xl md:max-w-full mx-auto relative z-10">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl md:text-4xl font-display font-bold text-white">Lieux Visités</h1>
        <button @click="showAddModal = true" class="btn-primary text-sm px-4 py-2 flex items-center gap-2">
          <Icon icon="ph:plus-bold" />
          Ajouter
        </button>
      </div>
      
      <!-- Mes Badges de Réussite Carousel -->
      <section class="mb-8">
        <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
           <span>🏆</span> Mes Badges d'Exploration
        </h2>
        <div class="flex gap-4 overflow-x-auto pb-3 custom-scrollbar">
           <div v-for="badge in allBadges" :key="badge.id"
                :class="[isUnlocked(badge) ? 'bg-gradient-to-tr text-white border-none' : 'bg-slate-900/40 text-slate-500 border border-slate-800/40']"
                class="flex-shrink-0 w-44 p-4 rounded-2xl flex flex-col items-center justify-between text-center relative overflow-hidden transition-all duration-300">
             
             <!-- Unlocked lighting background effect -->
             <div v-if="isUnlocked(badge)" :class="badge.color" class="absolute inset-0 opacity-85 z-0"></div>
             
             <div class="relative z-10 flex flex-col items-center">
                <div :class="[isUnlocked(badge) ? 'bg-white/20 text-white' : 'bg-slate-850 text-slate-600']" 
                     class="w-12 h-12 rounded-full flex items-center justify-center mb-3">
                   <Icon :icon="badge.icon" class="w-6 h-6" />
                </div>
                <h3 class="font-black text-[11px] tracking-wide uppercase leading-tight">{{ badge.name }}</h3>
                <p class="text-[9px] mt-1.5 line-clamp-2 leading-tight" :class="isUnlocked(badge) ? 'text-white/80' : 'text-slate-500'">
                   {{ badge.description }}
                </p>
             </div>
             
             <!-- Unlock status indicator -->
             <span class="relative z-10 text-[8px] font-extrabold px-2 py-0.5 rounded-full mt-3 uppercase tracking-widest"
                   :class="isUnlocked(badge) ? 'bg-white/30 text-white' : 'bg-slate-850 text-slate-500'">
                {{ isUnlocked(badge) ? 'Débloqué' : 'Verrouillé' }}
             </span>
           </div>
        </div>
      </section>

      <!-- Visited Places List -->
      <div v-if="visitedPlacesList.length > 0" class="space-y-4">
         <div 
            v-for="place in visitedPlacesList" 
            :key="place.id"
            class="card-glass p-3 flex gap-4 cursor-pointer hover:bg-white dark:hover:bg-gray-800 transition-all border-l-4 border-l-secondary"
            @click="goToPlace(place.id)"
          >
            <div class="w-24 h-24 md:w-32 md:h-32 rounded-xl overflow-hidden flex-shrink-0 relative bg-gray-200 dark:bg-gray-700">
              <img v-if="place.images && place.images[0]" :src="place.images[0]" :alt="place.name" class="w-full h-full object-cover">
              <div v-else class="w-full h-full flex items-center justify-center">
                 <Icon icon="ph:map-pin" class="w-8 h-8 text-gray-400" />
              </div>
              <div class="absolute inset-0 bg-black/20 grid place-items-center">
                 <Icon icon="ph:check-bold" class="text-white w-8 h-8" />
              </div>
            </div>
            
            <div class="flex-1 flex flex-col justify-center">
               <h3 class="font-bold text-gray-900 dark:text-white text-lg md:text-xl">{{ place.name }}</h3>
               <p class="text-gray-500 text-sm">{{ place.type }}</p>
               <div class="flex items-center justify-between mt-2">
                  <p class="text-xs font-medium text-secondary">Visité ✓</p>
                  <button @click.stop="removeVisit(place.id)" class="text-red-500 hover:text-red-700 p-1">
                     <Icon icon="ph:x-circle" />
                  </button>
               </div>
            </div>
         </div>
      </div>

      <div v-else class="flex flex-col items-center justify-center pt-20 text-center">
         <div class="w-20 h-20 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center mb-4">
            <Icon icon="ph:map-pin-dotted" class="w-10 h-10 text-gray-400" />
         </div>
         <h2 class="text-xl font-bold text-white">Aucun lieu visité</h2>
         <p class="text-gray-300 mt-2 max-w-xs">Cliquez sur "Ajouter" pour marquer un lieu du campus comme visité.</p>
         <router-link to="/map" class="btn-primary mt-6">Explorer la carte</router-link>
      </div>

      <!-- Modal: Choisir un lieu à marquer comme visité -->
      <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white dark:bg-gray-900 rounded-2xl w-full max-w-md shadow-xl border border-gray-200 dark:border-gray-800 max-h-[80vh] flex flex-col">
          <div class="flex justify-between items-center p-6 pb-3">
             <h3 class="text-xl font-bold dark:text-white">Marquer un lieu comme visité</h3>
             <button @click="showAddModal = false" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
               <Icon icon="ph:x-bold" class="w-6 h-6" />
             </button>
          </div>

          <!-- Search -->
          <div class="px-6 pb-3">
             <input v-model="searchQuery" type="text" placeholder="Rechercher un lieu..." 
                    class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-xl p-3 focus:ring-2 focus:ring-primary dark:text-white text-sm">
          </div>

          <!-- List of places -->
          <div class="px-6 pb-6 overflow-y-auto flex-1 space-y-2">
             <div v-for="place in filteredPlaces" :key="place.id"
                  @click="markAsVisited(place.id)"
                  class="flex items-center gap-3 p-3 rounded-xl cursor-pointer transition-all"
                  :class="visitedStore.isVisited(place.id) 
                    ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' 
                    : 'hover:bg-gray-50 dark:hover:bg-gray-800 border border-transparent'">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0"
                     :class="visitedStore.isVisited(place.id) ? 'bg-green-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-500'">
                   <Icon :icon="visitedStore.isVisited(place.id) ? 'ph:check-bold' : 'ph:map-pin'" class="w-5 h-5" />
                </div>
                <div class="flex-1 min-w-0">
                   <p class="font-bold text-sm dark:text-white truncate">{{ place.name }}</p>
                   <p class="text-xs text-gray-500">{{ place.type }}</p>
                </div>
             </div>
             <p v-if="filteredPlaces.length === 0" class="text-center text-gray-400 py-4 text-sm">Aucun lieu trouvé.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { Icon } from '@iconify/vue'
import { campusService } from '../services/campusService'
import { useVisitedStore } from '../stores/visited'
import { useBadgeStore } from '../stores/badges'

const router = useRouter()
const visitedStore = useVisitedStore()
const badgeStore = useBadgeStore()

const allBadges = computed(() => badgeStore.allBadges)

const isUnlocked = (badge) => {
    return badge.condition(visitedStore.visitedPlaces, allPlaces.value)
}

const showAddModal = ref(false)
const searchQuery = ref('')
const allPlaces = ref([])

onMounted(async () => {
    allPlaces.value = await campusService.getAllPlaces()
})

// Liste des lieux déjà visités avec leurs propriétés complètes
const visitedPlacesList = computed(() => {
  if (!allPlaces.value) return []
  return allPlaces.value
    .filter(p => visitedStore.isVisited(p.properties.id))
    .map(p => ({ ...p.properties }))
})

// Liste filtrée pour la modale de recherche
const filteredPlaces = computed(() => {
  if (!allPlaces.value) return []
  let places = allPlaces.value.map(p => ({ ...p.properties }))
  
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    places = places.filter(p => 
      p.name.toLowerCase().includes(q) || 
      p.type.toLowerCase().includes(q)
    )
  }

  // Trier : non-visités en premier
  return places.sort((a, b) => {
    const aVisited = visitedStore.isVisited(a.id) ? 1 : 0
    const bVisited = visitedStore.isVisited(b.id) ? 1 : 0
    return aVisited - bVisited
  })
})

const markAsVisited = (id) => {
  if (visitedStore.isVisited(id)) {
    visitedStore.removeVisit(id)
  } else {
    visitedStore.addVisit(id)
  }
}

const removeVisit = (id) => {
  visitedStore.removeVisit(id)
}

const goToPlace = (id) => {
  router.push(`/lieu/${id}`)
}
</script>
