<template>
  <div class="flex flex-col items-center justify-center p-8 text-center">
    <div class="mb-4">
      <component :is="iconComponent" class="w-16 h-16 mx-auto" :class="iconColor" />
    </div>
    <h2 class="text-2xl font-bold mb-2 text-gray-900 dark:text-gray-100">{{ title }}</h2>
    <p class="text-gray-600 dark:text-gray-400 mb-6">{{ message }}</p>
    <button 
      v-if="action" 
      @click="action.handler"
      class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
    >
      {{ action.label }}
    </button>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { 
  AlertCircle, 
  Lock, 
  Ban, 
  Search, 
  AlertTriangle,
  RefreshCw
} from '@lucide/vue'

interface Props {
  type: '400' | '401' | '403' | '404' | '422' | '429' | '500'
  message?: string
  action?: {
    label: string
    handler: () => void
  }
}

const props = withDefaults(defineProps<Props>(), {
  message: ''
})

const errorConfig = {
  '400': {
    title: 'Requête invalide',
    icon: AlertCircle,
    color: 'text-orange-500'
  },
  '401': {
    title: 'Non authentifié',
    icon: Lock,
    color: 'text-red-500'
  },
  '403': {
    title: 'Accès refusé',
    icon: Ban,
    color: 'text-red-500'
  },
  '404': {
    title: 'Non trouvé',
    icon: Search,
    color: 'text-gray-500'
  },
  '422': {
    title: 'Erreur de validation',
    icon: AlertTriangle,
    color: 'text-orange-500'
  },
  '429': {
    title: 'Trop de requêtes',
    icon: AlertCircle,
    color: 'text-orange-500'
  },
  '500': {
    title: 'Erreur serveur',
    icon: RefreshCw,
    color: 'text-red-500'
  }
}

const config = computed(() => errorConfig[props.type])
const iconComponent = computed(() => config.value.icon)
const iconColor = computed(() => config.value.color)
const title = computed(() => config.value.title)
</script>
