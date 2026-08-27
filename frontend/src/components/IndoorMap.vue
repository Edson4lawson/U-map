<template>
  <div class="indoor-map-container">
    <!-- Sélecteur d'étage -->
    <div class="floor-selector flex gap-2 mb-4">
      <button 
        @click="currentFloor = 'rdc'"
        :class="['floor-btn px-4 py-2 rounded-lg font-medium transition-all', currentFloor === 'rdc' ? 'bg-primary text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300']"
      >
        RDC
      </button>
      <button 
        @click="currentFloor = '1er'"
        :class="['floor-btn px-4 py-2 rounded-lg font-medium transition-all', currentFloor === '1er' ? 'bg-primary text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300']"
      >
        1er Étage
      </button>
    </div>

    <!-- Plan SVG interactif -->
    <div class="map-wrapper relative bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
      <svg 
        viewBox="0 0 800 600" 
        class="w-full h-auto"
        :class="{ 'animate-fade-in': currentFloor }"
      >
        <!-- RDC Plan -->
        <g v-if="currentFloor === 'rdc'" class="floor-plan">
          <!-- Fond du bâtiment -->
          <rect x="50" y="50" width="700" height="500" fill="#f8fafc" stroke="#334155" stroke-width="3" class="dark:fill-gray-700 dark:stroke-gray-600"/>
          
          <!-- Entrée principale -->
          <rect x="350" y="530" width="100" height="20" fill="#3b82f6" class="cursor-pointer hover:fill-blue-600 transition-colors" @click="selectArea('entrance')"/>
          <text x="400" y="545" text-anchor="middle" fill="white" font-size="12" font-weight="bold">Entrée</text>
          
          <!-- Hall d'accueil -->
          <rect x="300" y="400" width="200" height="130" fill="#e0f2fe" stroke="#0ea5e9" stroke-width="2" class="cursor-pointer hover:fill-blue-200 transition-colors dark:fill-blue-900/30 dark:stroke-blue-400" @click="selectArea('hall')"/>
          <text x="400" y="470" text-anchor="middle" fill="#0c4a6e" font-size="14" font-weight="bold" class="dark:text-blue-200">Hall d'accueil</text>
          
          <!-- Zone de réception -->
          <rect x="100" y="400" width="180" height="130" fill="#fef3c7" stroke="#f59e0b" stroke-width="2" class="cursor-pointer hover:fill-amber-200 transition-colors dark:fill-amber-900/30 dark:stroke-amber-400" @click="selectArea('reception')"/>
          <text x="190" y="470" text-anchor="middle" fill="#92400e" font-size="14" font-weight="bold" class="dark:text-amber-200">Réception</text>
          
          <!-- Salle de lecture principale -->
          <rect x="100" y="150" width="350" height="230" fill="#dbeafe" stroke="#3b82f6" stroke-width="2" class="cursor-pointer hover:fill-blue-300 transition-colors dark:fill-blue-900/30 dark:stroke-blue-400" @click="selectArea('reading_room')"/>
          <text x="275" y="270" text-anchor="middle" fill="#1e40af" font-size="16" font-weight="bold" class="dark:text-blue-200">Salle de lecture</text>
          <text x="275" y="290" text-anchor="middle" fill="#1e40af" font-size="12" class="dark:text-blue-300">150 places</text>
          
          <!-- Zone informatique -->
          <rect x="480" y="150" width="250" height="150" fill="#dcfce7" stroke="#22c55e" stroke-width="2" class="cursor-pointer hover:fill-green-200 transition-colors dark:fill-green-900/30 dark:stroke-green-400" @click="selectArea('computer_zone')"/>
          <text x="605" y="230" text-anchor="middle" fill="#166534" font-size="14" font-weight="bold" class="dark:text-green-200">Zone informatique</text>
          <text x="605" y="250" text-anchor="middle" fill="#166534" font-size="12" class="dark:text-green-300">30 postes</text>
          
          <!-- WC -->
          <rect x="480" y="320" width="80" height="60" fill="#fce7f3" stroke="#ec4899" stroke-width="2" class="cursor-pointer hover:fill-pink-200 transition-colors dark:fill-pink-900/30 dark:stroke-pink-400" @click="selectArea('wc')"/>
          <text x="520" y="355" text-anchor="middle" fill="#9d174d" font-size="12" font-weight="bold" class="dark:text-pink-200">WC</text>
          
          <!-- Escalier -->
          <rect x="580" y="320" width="150" height="60" fill="#e5e7eb" stroke="#6b7280" stroke-width="2" class="cursor-pointer hover:fill-gray-300 transition-colors dark:fill-gray-600 dark:stroke-gray-400" @click="selectArea('stairs')"/>
          <text x="655" y="355" text-anchor="middle" fill="#374151" font-size="12" font-weight="bold" class="dark:text-gray-300">Escalier</text>
          
          <!-- Bureau du conservateur -->
          <rect x="300" y="150" width="170" height="100" fill="#f3e8ff" stroke="#a855f7" stroke-width="2" class="cursor-pointer hover:fill-purple-200 transition-colors dark:fill-purple-900/30 dark:stroke-purple-400" @click="selectArea('office')"/>
          <text x="385" y="205" text-anchor="middle" fill="#6b21a8" font-size="12" font-weight="bold" class="dark:text-purple-200">Bureau</text>
        </g>

        <!-- 1er Étage Plan -->
        <g v-if="currentFloor === '1er'" class="floor-plan">
          <!-- Fond du bâtiment -->
          <rect x="50" y="50" width="700" height="500" fill="#f8fafc" stroke="#334155" stroke-width="3" class="dark:fill-gray-700 dark:stroke-gray-600"/>
          
          <!-- Escalier -->
          <rect x="580" y="320" width="150" height="60" fill="#e5e7eb" stroke="#6b7280" stroke-width="2" class="cursor-pointer hover:fill-gray-300 transition-colors dark:fill-gray-600 dark:stroke-gray-400" @click="selectArea('stairs')"/>
          <text x="655" y="355" text-anchor="middle" fill="#374151" font-size="12" font-weight="bold" class="dark:text-gray-300">Escalier</text>
          
          <!-- Salle de travail en groupe -->
          <rect x="100" y="150" width="350" height="200" fill="#dbeafe" stroke="#3b82f6" stroke-width="2" class="cursor-pointer hover:fill-blue-300 transition-colors dark:fill-blue-900/30 dark:stroke-blue-400" @click="selectArea('group_room')"/>
          <text x="275" y="255" text-anchor="middle" fill="#1e40af" font-size="14" font-weight="bold" class="dark:text-blue-200">Travail en groupe</text>
          <text x="275" y="275" text-anchor="middle" fill="#1e40af" font-size="12" class="dark:text-blue-300">8 salles</text>
          
          <!-- Section périodiques -->
          <rect x="480" y="150" width="250" height="150" fill="#fef3c7" stroke="#f59e0b" stroke-width="2" class="cursor-pointer hover:fill-amber-200 transition-colors dark:fill-amber-900/30 dark:stroke-amber-400" @click="selectArea('periodicals')"/>
          <text x="605" y="230" text-anchor="middle" fill="#92400e" font-size="14" font-weight="bold" class="dark:text-amber-200">Périodiques</text>
          
          <!-- Salle de recherche -->
          <rect x="100" y="380" width="200" height="150" fill="#dcfce7" stroke="#22c55e" stroke-width="2" class="cursor-pointer hover:fill-green-200 transition-colors dark:fill-green-900/30 dark:stroke-green-400" @click="selectArea('research')"/>
          <text x="200" y="460" text-anchor="middle" fill="#166534" font-size="14" font-weight="bold" class="dark:text-green-200">Recherche</text>
          
          <!-- Dépôt -->
          <rect x="320" y="380" width="180" height="150" fill="#e5e7eb" stroke="#6b7280" stroke-width="2" class="cursor-pointer hover:fill-gray-300 transition-colors dark:fill-gray-600 dark:stroke-gray-400" @click="selectArea('deposit')"/>
          <text x="410" y="460" text-anchor="middle" fill="#374151" font-size="14" font-weight="bold" class="dark:text-gray-300">Dépôt</text>
          
          <!-- WC -->
          <rect x="520" y="380" width="80" height="60" fill="#fce7f3" stroke="#ec4899" stroke-width="2" class="cursor-pointer hover:fill-pink-200 transition-colors dark:fill-pink-900/30 dark:stroke-pink-400" @click="selectArea('wc')"/>
          <text x="560" y="415" text-anchor="middle" fill="#9d174d" font-size="12" font-weight="bold" class="dark:text-pink-200">WC</text>
          
          <!-- Salle de conférence -->
          <rect x="620" y="380" width="110" height="150" fill="#f3e8ff" stroke="#a855f7" stroke-width="2" class="cursor-pointer hover:fill-purple-200 transition-colors dark:fill-purple-900/30 dark:stroke-purple-400" @click="selectArea('conference')"/>
          <text x="675" y="460" text-anchor="middle" fill="#6b21a8" font-size="12" font-weight="bold" class="dark:text-purple-200">Conférence</text>
        </g>
      </svg>

      <!-- Légende des zones sélectionnées -->
      <div v-if="selectedArea" class="area-info absolute bottom-4 left-4 right-4 bg-white/95 dark:bg-gray-900/95 backdrop-blur-sm rounded-xl p-4 shadow-lg border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
          <div>
            <h4 class="font-bold text-gray-900 dark:text-white">{{ areaInfo[selectedArea]?.name }}</h4>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ areaInfo[selectedArea]?.description }}</p>
          </div>
          <button @click="selectedArea = null" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
            <Icon icon="ph:x-bold" class="w-6 h-6"/>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Icon } from '@iconify/vue'

const currentFloor = ref('rdc')
const selectedArea = ref(null)

const areaInfo = {
  entrance: { name: 'Entrée principale', description: 'Accès principal de la bibliothèque' },
  hall: { name: 'Hall d\'accueil', description: 'Espace d\'accueil et d\'orientation' },
  reception: { name: 'Réception', description: 'Bureau d\'accueil et d\'information' },
  reading_room: { name: 'Salle de lecture', description: '150 places assises, calme et lumineux' },
  computer_zone: { name: 'Zone informatique', description: '30 postes informatiques avec accès internet' },
  wc: { name: 'Sanitaires', description: 'Toilettes accessibles' },
  stairs: { name: 'Escalier', description: 'Accès entre les étages' },
  office: { name: 'Bureau du conservateur', description: 'Administration de la bibliothèque' },
  group_room: { name: 'Travail en groupe', description: '8 salles de travail collaboratif' },
  periodicals: { name: 'Section périodiques', description: 'Journaux, magazines et revues' },
  research: { name: 'Salle de recherche', description: 'Espace dédié à la recherche académique' },
  deposit: { name: 'Dépôt', description: 'Stockage des ouvrages' },
  conference: { name: 'Salle de conférence', description: 'Espace pour conférences et événements' }
}

const selectArea = (area) => {
  selectedArea.value = area
}
</script>

<style scoped>
.indoor-map-container {
  animation: fadeIn 0.5s ease-in-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in {
  animation: fadeIn 0.3s ease-in-out;
}

.floor-btn {
  transition: all 0.3s ease;
}

.floor-btn:hover {
  transform: scale(1.05);
}

.floor-plan rect {
  transition: all 0.3s ease;
}

.floor-plan rect:hover {
  filter: brightness(1.1);
  transform: scale(1.02);
  transform-origin: center;
}
</style>
