import { defineStore } from 'pinia'
import { ref, watch } from 'vue'

export const useCustomPlacesStore = defineStore('customPlaces', () => {
  const customPlaces = ref(JSON.parse(localStorage.getItem('customPlaces')) || [])

  const addPlace = (place) => {
    // Generate a unique ID (simple timestamp based)
    const newPlace = {
      ...place,
      id: `custom_${Date.now()}`,
      isCustom: true
    }
    customPlaces.value.push(newPlace)
  }

  const removePlace = (placeId) => {
    customPlaces.value = customPlaces.value.filter(p => p.id !== placeId)
  }

  watch(customPlaces, (newVal) => {
    localStorage.setItem('customPlaces', JSON.stringify(newVal))
  }, { deep: true })

  return {
    customPlaces,
    addPlace,
    removePlace
  }
})
