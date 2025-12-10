<template>
  <div class="min-h-screen relative">

    
    <div class="pt-4 pb-24 px-4 max-w-xl md:max-w-full mx-auto relative z-10">
      <div class="sticky top-0 w-full z-30 bg-transparent rounded-lg backdrop-blur-md py-4">
        <h1 class="text-3xl md:text-4xl font-display font-bold text-white mb-4 px-2">Lieux</h1>
        <SearchBar v-model="search" />
      </div>

      <div class="space-y-4 mt-2 max-w-full grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mx-auto">
        <transition-group name="list">
          <div 
            v-for="place in filteredPlaces" 
            :key="place.properties.id"
            class="card-glass p-3 flex gap-4 cursor-pointer hover:bg-white dark:hover:bg-gray-800 transition-all active:scale-[0.98]"
            @click="goToPlace(place.properties.id)"
          >
            <div class="w-24 h-24 md:w-32 md:h-32 rounded-xl overflow-hidden flex-shrink-0">
              <img :src="place.properties.images[0]" :alt="place.properties.name" class="w-full h-full object-cover">
            </div>
            
            <div class="flex-1 flex flex-col justify-center">
              <div class="flex justify-between items-start">
                 <h3 class="font-bold text-gray-900 dark:text-white text-lg md:text-xl">{{ place.properties.name }}</h3>
                 <Icon v-if="visitedStore.isVisited(place.properties.id)" icon="ph:check-circle-fill" class="text-secondary w-5 h-5" />
              </div>
              
              <p class="text-primary text-sm font-medium">{{ place.properties.type }}</p>
              
              <div class="flex gap-2 mt-2">
                <span v-for="tag in place.properties.tags.slice(0, 2)" :key="tag" class="text-[10px] px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded-full text-gray-500 dark:text-gray-400">
                  {{ tag }}
                </span>
              </div>
            </div>
          </div>
        </transition-group>

        <div v-if="filteredPlaces.length === 0" class="text-center py-20 text-gray-300">
          <Icon icon="ph:warning-circle" class="w-12 h-12 mx-auto mb-2 opacity-50" />
          <p>Aucun lieu trouvé.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { Icon } from '@iconify/vue'
import SearchBar from '../components/SearchBar.vue'
import campusData from '../data/campus.json'
import { useVisitedStore } from '../stores/visited'

const router = useRouter()
const visitedStore = useVisitedStore()
const search = ref('')

const filteredPlaces = computed(() => {
  if (!search.value) return campusData.features
  const q = search.value.toLowerCase()
  return campusData.features.filter(p => 
    p.properties.name.toLowerCase().includes(q) || 
    p.properties.type.toLowerCase().includes(q) ||
    p.properties.tags.some(t => t.toLowerCase().includes(q))
  )
})

const goToPlace = (id) => {
  router.push(`/lieu/${id}`)
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
