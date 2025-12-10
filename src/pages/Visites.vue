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
      
      <!-- Visited Places List -->
      <div v-if="allPlacesList.length > 0" class="space-y-4">
         <div 
            v-for="place in allPlacesList" 
            :key="place.id"
            class="card-glass p-3 flex gap-4 cursor-pointer hover:bg-white dark:hover:bg-gray-800 transition-all border-l-4"
            :class="place.isCustom ? 'border-l-purple-500' : 'border-l-secondary'"
            @click="goToPlace(place.id)"
          >
            <div class="w-24 h-24 md:w-32 md:h-32 rounded-xl overflow-hidden flex-shrink-0 relative bg-gray-200 dark:bg-gray-700">
              <img v-if="place.images && place.images[0]" :src="place.images[0]" :alt="place.name" class="w-full h-full object-cover">
              <div v-else class="w-full h-full flex items-center justify-center">
                 <Icon icon="ph:map-pin" class="w-8 h-8 text-gray-400" />
              </div>
              <div class="absolute inset-0 bg-black/20 grid place-items-center">
                 <Icon v-if="!place.isCustom" icon="ph:check-bold" class="text-white w-8 h-8" />
                 <Icon v-else icon="ph:user-bold" class="text-white w-8 h-8" />
              </div>
            </div>
            
            <div class="flex-1 flex flex-col justify-center">
               <h3 class="font-bold text-gray-900 dark:text-white text-lg md:text-xl">{{ place.name }}</h3>
               <p class="text-gray-500 text-sm">{{ place.type }}</p>
               <div class="flex items-center justify-between mt-2">
                  <p class="text-xs font-medium" :class="place.isCustom ? 'text-purple-500' : 'text-secondary'">
                     {{ place.isCustom ? 'Lieu ajouté' : 'Visité' }}
                  </p>
                  <button v-if="place.isCustom" @click.stop="deleteCustomPlace(place.id)" class="text-red-500 hover:text-red-700 p-1">
                     <Icon icon="ph:trash" />
                  </button>
               </div>
            </div>
         </div>
      </div>

      <div v-else class="flex flex-col items-center justify-center pt-20 text-center">
         <div class="w-20 h-20 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center mb-4">
            <Icon icon="ph:map-pin-dotted" class="w-10 h-10 text-gray-400" />
         </div>
         <h2 class="text-xl font-bold text-white">Aucun lieu visité ou ajouté</h2>
         <p class="text-gray-300 mt-2 max-w-xs">Explorez le campus ou ajoutez vos propres lieux.</p>
         <router-link to="/map" class="btn-primary mt-6">Explorer la carte</router-link>
      </div>

      <!-- Add Place Modal -->
      <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white dark:bg-gray-900 rounded-2xl w-full max-w-md p-6 shadow-xl border border-gray-200 dark:border-gray-800">
          <div class="flex justify-between items-center mb-4">
             <h3 class="text-xl font-bold dark:text-white">Ajouter un lieu</h3>
             <button @click="closeModal" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
               <Icon icon="ph:x-bold" class="w-6 h-6" />
             </button>
          </div>

          <div class="space-y-4">
             <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom du lieu / Pseudo</label>
                <input v-model="newPlace.name" type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 focus:ring-primary focus:border-primary" placeholder="Ex: Mon coin détente">
             </div>
             
             <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type de lieu</label>
                <select v-model="newPlace.type" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 focus:ring-primary focus:border-primary">
                   <option value="Personnel">Personnel</option>
                   <option value="Étude">Zone d'étude</option>
                   <option value="Détente">Détente</option>
                   <option value="Restauration">Restauration</option>
                   <option value="Autre">Autre</option>
                </select>
             </div>

             <div class="bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg flex items-start gap-3 text-sm text-blue-700 dark:text-blue-300">
                <Icon icon="ph:info" class="w-5 h-5 flex-shrink-0 mt-0.5" />
                <p>Votre position actuelle sera utilisée pour localiser ce lieu.</p>
             </div>
             
             <button @click="savePlace" :disabled="!newPlace.name || loading" class="w-full btn-primary flex justify-center items-center gap-2">
                <Icon v-if="loading" icon="eos-icons:loading" class="animate-spin" />
                <span v-else>Enregistrer le lieu</span>
             </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { Icon } from '@iconify/vue'
import campusData from '../data/campus.json'
import { useVisitedStore } from '../stores/visited'
import { useCustomPlacesStore } from '../stores/customPlaces'

const router = useRouter()
const visitedStore = useVisitedStore()
const customPlacesStore = useCustomPlacesStore()

const showAddModal = ref(false)
const loading = ref(false)
const newPlace = ref({
  name: '',
  type: 'Personnel'
})

const allPlacesList = computed(() => {
  const visited = campusData.features
    .filter(p => visitedStore.isVisited(p.properties.id))
    .map(p => ({ ...p.properties, isCustom: false }))
  
  const custom = customPlacesStore.customPlaces.map(p => ({ ...p, isCustom: true }))
  
  return [...custom, ...visited]
})

const closeModal = () => {
  showAddModal.value = false
  newPlace.value = { name: '', type: 'Personnel' }
  loading.value = false
}

const savePlace = () => {
  loading.value = true
  if ("geolocation" in navigator) {
    navigator.geolocation.getCurrentPosition((position) => {
       const customPlace = {
         name: newPlace.value.name,
         type: newPlace.value.type,
         geometry: {
            type: 'Point',
            coordinates: [position.coords.longitude, position.coords.latitude]
         }
       }
       customPlacesStore.addPlace(customPlace)
       closeModal()
    }, (error) => {
       console.error("Geolocation error:", error)
       alert("Impossible de récupérer votre position.")
       loading.value = false
    })
  } else {
     alert("La géolocalisation n'est pas supportée par votre navigateur.")
     loading.value = false
  }
}

const deleteCustomPlace = (id) => {
  if(confirm('Voulez-vous vraiment supprimer ce lieu ?')) {
     customPlacesStore.removePlace(id)
  }
}

const goToPlace = (id) => {
  // Logic to navigate or show on map
  // If custom, we might not have a details page yet, so maybe just show on map?
  // Or reuse details page if we adapt it.
  // For now, let's redirect to map focused on it if it's custom
  if (String(id).startsWith('custom_')) {
      // Pass coord or id to map
      router.push({ path: '/map', query: { focusCustom: id } })
  } else {
      router.push(`/lieu/${id}`)
  }
}
</script>
