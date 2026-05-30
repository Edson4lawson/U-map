import { defineStore } from 'pinia'
import { useVisitedStore } from './visited'
import { campusService } from '../services/campusService'

export const useBadgeStore = defineStore('badges', {
  state: () => ({
    allBadges: [
      {
        id: 'pionnier',
        name: 'Pionnier UAC 📍',
        description: 'Débloqué en visitant votre premier amphi ou lieu sur le campus.',
        condition: (visitedIds, places) => visitedIds.length >= 1,
        icon: 'ph:map-pin-bold',
        color: 'from-blue-500 to-cyan-500'
      },
      {
        id: 'bibliothecaire',
        name: 'Rat de Bibliothèque 📚',
        description: 'Débloqué en explorant la Bibliothèque Universitaire Centrale (BU).',
        condition: (visitedIds, places) => {
            const buIds = places.filter(p => p.properties.id === 'bu_centrale' || p.properties.name.toLowerCase().includes('bibliothèque') || p.properties.name.toLowerCase().includes('bu')).map(p => p.properties.id.toString())
            return visitedIds.some(id => buIds.includes(id.toString()))
        },
        icon: 'ph:books-bold',
        color: 'from-purple-500 to-indigo-500'
      },
      {
        id: 'gourmet',
        name: 'Gourmet Campus 🍔',
        description: 'Débloqué en visitant un restaurant universitaire (Resto U) ou café.',
        condition: (visitedIds, places) => {
            const restoIds = places.filter(p => p.properties.category.toLowerCase().includes('resto') || p.properties.category.toLowerCase().includes('vie étudiante') || p.properties.name.toLowerCase().includes('resto')).map(p => p.properties.id.toString())
            return visitedIds.some(id => restoIds.includes(id.toString()))
        },
        icon: 'ph:hamburger-bold',
        color: 'from-amber-500 to-orange-500'
      },
      {
        id: 'grand_voyageur',
        name: 'Grand Voyageur 🧭',
        description: 'Débloqué en explorant plus de 5 lieux différents sur le campus de l\'UAC.',
        condition: (visitedIds, places) => visitedIds.length >= 5,
        icon: 'ph:compass-bold',
        color: 'from-emerald-500 to-teal-500'
      }
    ]
  }),
  getters: {
    unlockedBadges: (state) => {
      const visitedStore = useVisitedStore()
      const visitedIds = visitedStore.visitedPlaces
      // Pour une vérification simple en direct, on utilise les places du store local ou passées en paramètre
      // Dans le cas de getters Pinia réactifs, nous retournons une fonction ou calculons en direct
      return (places) => {
          if (!places || places.length === 0) return []
          return state.allBadges.filter(badge => badge.condition(visitedIds, places))
      }
    },
    lockedBadges: (state) => {
      const visitedStore = useVisitedStore()
      const visitedIds = visitedStore.visitedPlaces
      return (places) => {
          if (!places || places.length === 0) return state.allBadges
          return state.allBadges.filter(badge => !badge.condition(visitedIds, places))
      }
    }
  }
})
