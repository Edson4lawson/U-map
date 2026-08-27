<template>
  <div class="h-screen w-full relative">
    <MapView />
  </div>
</template>

<script setup>
import MapView from '../components/MapView.vue'
import { useMeta } from '../composables/useMeta'
import { useStructuredData, getBreadcrumbSchema } from '../composables/useStructuredData'

useMeta('Carte Interactive du Campus', "Naviguez sur le campus de l'Université d'Abomey-Calavi avec la carte interactive U-map. GPS, itinéraires, et localisation en temps réel.", { canonicalPath: '/map' })

useStructuredData(getBreadcrumbSchema([
  { name: 'Accueil', url: '/' },
  { name: 'Carte Interactive', url: '/map' }
]))
</script>
