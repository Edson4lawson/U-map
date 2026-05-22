import { defineStore } from 'pinia'
import { ref, watch } from 'vue'

export const useVisitedStore = defineStore('visited', () => {
  const visitedPlaces = ref(JSON.parse(localStorage.getItem('visitedPlaces')) || [])

  const toggleVisited = (placeId) => {
    if (visitedPlaces.value.includes(placeId)) {
      visitedPlaces.value = visitedPlaces.value.filter(id => id !== placeId)
    } else {
      visitedPlaces.value.push(placeId)
    }
  }

  const addVisit = (placeId) => {
    if (!visitedPlaces.value.includes(placeId)) {
      visitedPlaces.value.push(placeId)
    }
  }

  const removeVisit = (placeId) => {
    visitedPlaces.value = visitedPlaces.value.filter(id => id !== placeId)
  }

  const isVisited = (placeId) => {
    return visitedPlaces.value.includes(placeId)
  }

  watch(visitedPlaces, (newVal) => {
    localStorage.setItem('visitedPlaces', JSON.stringify(newVal))
  }, { deep: true })

  return {
    visitedPlaces,
    toggleVisited,
    addVisit,
    removeVisit,
    isVisited
  }
})
