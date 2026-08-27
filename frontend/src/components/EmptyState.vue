<template>
  <div class="flex flex-col items-center justify-center p-8 text-center">
    <div class="mb-4">
      <component :is="iconComponent" class="w-16 h-16 mx-auto text-gray-400" />
    </div>
    <h2 class="text-xl font-bold mb-2 text-gray-900 dark:text-gray-100">{{ title }}</h2>
    <p class="text-gray-600 dark:text-gray-400 mb-6">{{ message }}</p>
    <button 
      v-if="action" 
      @click="action.handler"
      class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors flex items-center gap-2"
    >
      <component :is="actionIcon" class="w-4 h-4" />
      {{ action.label }}
    </button>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { 
  Inbox, 
  Search, 
  MapPin, 
  MessageSquare,
  Plus,
  RefreshCw
} from '@lucide/vue'

interface Props {
  type: 'generic' | 'search' | 'places' | 'messages' | 'favorites'
  message?: string
  action?: {
    label: string
    handler: () => void
    icon?: 'plus' | 'refresh'
  }
}

const props = withDefaults(defineProps<Props>(), {
  message: ''
})

const emptyConfig = {
  'generic': {
    title: 'Aucune donnée',
    icon: Inbox
  },
  'search': {
    title: 'Aucun résultat',
    icon: Search
  },
  'places': {
    title: 'Aucun lieu trouvé',
    icon: MapPin
  },
  'messages': {
    title: 'Aucun message',
    icon: MessageSquare
  },
  'favorites': {
    title: 'Aucun favori',
    icon: Inbox
  }
}

const config = computed(() => emptyConfig[props.type])
const iconComponent = computed(() => config.value.icon)
const title = computed(() => config.value.title)

const actionIcon = computed(() => {
  if (!props.action?.icon) return Plus
  return props.action.icon === 'refresh' ? RefreshCw : Plus
})
</script>
