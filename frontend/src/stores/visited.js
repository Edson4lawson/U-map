import { defineStore } from 'pinia'
import { ref, watch } from 'vue'

export const useVisitedStore = defineStore('visited', () => {
  const raw = localStorage.getItem('visitedPlaces')
  let initial = []
  try {
    initial = raw ? JSON.parse(raw) : []
  } catch (e) {
    initial = []
  }
  
  // Ensure flat array of normalized IDs without duplicates
  const normalizeId = (id) => String(id)
  const uniqueIds = Array.isArray(initial) ? [...new Set(initial.map(normalizeId))] : []
  const visitedPlaces = ref(uniqueIds)

  const toggleVisited = (placeId) => {
    if (placeId === undefined || placeId === null) return
    const sId = normalizeId(placeId)
    if (visitedPlaces.value.some(id => normalizeId(id) === sId)) {
      visitedPlaces.value = visitedPlaces.value.filter(id => normalizeId(id) !== sId)
    } else {
      visitedPlaces.value.push(sId)
    }
  }

  const addVisit = (placeId) => {
    if (placeId === undefined || placeId === null) return
    const sId = normalizeId(placeId)
    if (!visitedPlaces.value.some(id => normalizeId(id) === sId)) {
      visitedPlaces.value.push(sId)
    }
  }

  const removeVisit = (placeId) => {
    if (placeId === undefined || placeId === null) return
    const sId = normalizeId(placeId)
    visitedPlaces.value = visitedPlaces.value.filter(id => normalizeId(id) !== sId)
  }

  const isVisited = (placeId) => {
    if (placeId === undefined || placeId === null) return false
    const sId = normalizeId(placeId)
    return visitedPlaces.value.some(id => normalizeId(id) === sId)
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

