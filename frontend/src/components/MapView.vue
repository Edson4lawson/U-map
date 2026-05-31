<template>
  <div class="relative w-full h-full">
    <div id="map" class="w-full h-full z-0"></div>
    
    <!-- Map Controls Overlay -->
    <div class="absolute top-4 right-4 z-[400] flex flex-col gap-2">
       <button @click="toggleLayer" class="p-3 bg-white dark:bg-gray-800 rounded-2xl shadow-xl text-gray-700 dark:text-gray-200 hover:scale-105 transition-transform" aria-label="Toggle Layer">
         <Icon :icon="isSatellite ? 'ph:map-trifold' : 'ph:globe-hemisphere-west'" class="w-6 h-6" />
       </button>
       <button @click="locateUser" class="p-3 bg-white dark:bg-gray-800 rounded-2xl shadow-xl text-primary hover:scale-105 transition-transform" aria-label="Locate Me">
         <Icon icon="ph:navigation-arrow-fill" class="w-6 h-6" />
       </button>
       <button @click="handleAddClick" 
          class="p-3 bg-white dark:bg-gray-800 text-orange-500 rounded-2xl shadow-xl hover:scale-105 transition-transform" 
          aria-label="Add Place">
          <Icon icon="ph:plus-circle-bold" class="w-6 h-6" />
       </button>
    </div>

    <!-- Remove Instruction Overlay for Add Mode as it is now automatic -->


    <!-- Add Place Modal -->
    <div v-if="showModal" class="fixed inset-0 z-[500] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white dark:bg-gray-900 rounded-3xl p-6 w-full max-w-md shadow-2xl scale-in">
            <h2 class="text-xl font-bold mb-4 dark:text-white">Contribuer à U-Map UAC</h2>
            
            <!-- Contribution Type Selector -->
            <div class="flex gap-2 mb-4 p-1 bg-gray-100 dark:bg-gray-800 rounded-2xl">
                <button type="button" @click="contributionType = 'place'" 
                        :class="contributionType === 'place' ? 'bg-white dark:bg-gray-700 shadow-sm text-primary font-bold' : 'text-gray-500'"
                        class="flex-1 py-2 text-xs font-medium rounded-xl transition-all">
                    📍 Lieu Permanent
                </button>
                <button type="button" @click="contributionType = 'report'" 
                        :class="contributionType === 'report' ? 'bg-white dark:bg-gray-700 shadow-sm text-red-500 font-bold' : 'text-gray-500'"
                        class="flex-1 py-2 text-xs font-medium rounded-xl transition-all">
                    ⚠️ Infos en direct
                </button>
            </div>

            <form @submit.prevent="submitContribution" class="space-y-4">
                <div v-if="contributionType === 'place'" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Nom du lieu (ou pseudo)</label>
                        <input v-model="newPlace.name" type="text" required placeholder="Ex: Amphi Houdégbé (Ancien)" 
                               class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-xl p-3 focus:ring-2 focus:ring-primary dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Catégorie</label>
                        <select v-model="newPlace.category" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-xl p-3 focus:ring-2 focus:ring-primary dark:text-white">
                            <option value="Enseignements et académique">Amphi / Salle</option>
                            <option value="Vie étudiante">Détente / Resto</option>
                            <option value="Administratif">Administration</option>
                            <option value="Divers">Autre</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Description (Optionnel)</label>
                        <textarea v-model="newPlace.description" rows="2" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-xl p-3 focus:ring-2 focus:ring-primary dark:text-white"></textarea>
                    </div>
                </div>
                
                <div v-else class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Type de situation en direct</label>
                        <select v-model="newLiveReport.type" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-xl p-3 focus:ring-2 focus:ring-red-500 dark:text-white">
                            <option value="power_outage">🔌 Coupure d'électricité</option>
                            <option value="crowded">👥 Amphi / Lieu bondé</option>
                            <option value="event">🎉 Événement en cours</option>
                            <option value="other">⚠️ Autre signalement</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Description de la situation</label>
                        <textarea v-model="newLiveReport.description" required placeholder="Ex: Panne générale sur tout le secteur..." rows="2" 
                                  class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-xl p-3 focus:ring-2 focus:ring-red-500 dark:text-white"></textarea>
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="button" @click="showModal = false" class="flex-1 py-3 text-gray-500 font-bold hover:bg-gray-100 dark:hover:bg-gray-800 rounded-xl transition-colors">Annuler</button>
                    <button type="submit" :disabled="isSubmitting" 
                            :class="contributionType === 'report' ? 'bg-red-500 shadow-red-500/20 hover:bg-red-600' : 'bg-primary shadow-blue-500/20'"
                            class="flex-2 py-3 px-8 text-white font-bold rounded-xl shadow-lg disabled:opacity-50 transition-colors">
                        {{ isSubmitting ? 'Envoi...' : 'Contribuer' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'
import { Icon } from '@iconify/vue'
import { useRouter, useRoute } from 'vue-router'
import L from 'leaflet'
import { useVisitedStore } from '../stores/visited'
import { useMapManager } from '../composables/useMapManager'
import { campusService } from '../services/campusService'
import { telemetryService } from '../services/telemetryService'

const router = useRouter()
const route = useRoute()
const visitedStore = useVisitedStore()
const { map, isSatellite, init, toggleLayer, addMarker, focusOn, clear, userMarker, drawRoute, updateRouteStart } = useMapManager()
let routeWatchId = null
let userWatchId = null

// State pour l'ajout de lieux
const showModal = ref(false)
const isSubmitting = ref(false)
const contributionType = ref('place')
const newPlace = ref({
    name: '',
    type: 'Lieu suggéré',
    category: 'Divers',
    description: '',
    latitude: 0,
    longitude: 0
})
const newLiveReport = ref({
    type: 'power_outage',
    description: '',
    latitude: 0,
    longitude: 0
})
const liveReportMarkers = ref([])

const updateMarkers = async () => {
  if (!map.value) return

  // Nettoyer les anciens signalements en direct
  liveReportMarkers.value.forEach(m => map.value.removeLayer(m))
  liveReportMarkers.value = []

  const places = await campusService.getAllPlaces()
  places.forEach(feature => {
    const { id, name, type, added_by, status } = feature.properties
    const isVisited = visitedStore.isVisited(id)
    
    // Couleur différente pour les lieux en attente
    let color = isVisited ? '#10B981' : '#3B82F6'
    if (status === 'pending') color = '#F97316' // Orange pour "En attente"

    if (!feature.geometry || !feature.geometry.coordinates) return
    
    const [lng, lat] = feature.geometry.coordinates
    
    addMarker(id, lat, lng, {
      color,
      popupContent: createPopupContent(feature.properties, id, feature.geometry.coordinates)
    })
  })

  // Récupérer et afficher les signalements communautaires en direct
  try {
      const liveReports = await campusService.getLiveReports()
      liveReports.forEach(report => {
        let emoji = '⚠️';
        let label = 'Signalement';
        if (report.type === 'power_outage') { emoji = '🔌'; label = 'Coupure d\'électricité'; }
        else if (report.type === 'crowded') { emoji = '👥'; label = 'Amphi / Lieu bondé'; }
        else if (report.type === 'event') { emoji = '🎉'; label = 'Événement en cours'; }

        const liveIcon = L.divIcon({
            className: 'custom-div-icon',
            html: `<div class="pulsing-marker" style="background-color: #EF4444; width: 14px; height: 14px; border-radius: 50%; border: 2px solid white; display: flex; align-items: center; justify-content: center; font-size: 8px;"></div>`,
            iconSize: [14, 14],
            iconAnchor: [7, 7]
        });

        const marker = L.marker([report.latitude, report.longitude], { icon: liveIcon }).addTo(map.value);
        marker.bindPopup(`
          <div class="p-2 min-w-[180px] font-sans text-gray-900">
             <h3 class="font-bold text-red-600 text-sm mb-1">${emoji} ${label}</h3>
             <p class="text-xs text-gray-700 mb-2">${report.description}</p>
             <div class="text-[9px] text-gray-400">Signalé par : ${report.reporter_name}</div>
             <div class="text-[9px] text-gray-400">Il y a : ${Math.round((new Date() - new Date(report.created_at)) / 60000)} min</div>
          </div>
        `);

        liveReportMarkers.value.push(marker)
      })
  } catch (e) {
      console.warn("Could not load live reports:", e)
  }
}

const createPopupContent = (props, id, coordinates) => {
    const [lng, lat] = coordinates
    return `
      <div class="p-2 min-w-[200px] font-sans">
         <h3 class="font-bold text-gray-900 text-base mb-1">${props.name}</h3>
         <p class="text-xs text-gray-500 mb-1">${props.type}</p>
         ${props.added_by ? `<p class="text-[10px] text-orange-600 font-bold mb-2 italic">Proposé par : ${props.added_by}</p>` : ''}
         <div class="flex flex-col gap-2">
            <button onclick="window.goToDetail('${id}')" class="w-full py-2 bg-blue-600 text-white text-xs font-bold rounded-xl shadow-lg shadow-blue-500/20">Détails</button>
            <button onclick="window.startRoute(${lat}, ${lng})" class="w-full py-2 bg-emerald-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-emerald-500/20">Y aller 🚶</button>
         </div>
      </div>
    `
}

const UAC_CENTER = { lat: 6.44800, lng: 2.35200 } // Un peu plus au nord pour couvrir le coeur des amphis
const MAX_ADD_DISTANCE = 3000 // 3km

const locateUser = () => {
    telemetryService.trackEvent('user_locate_attempt')
    if (!navigator.geolocation) return
    
    // Annuler l'ancien watch général s'il existe
    if (userWatchId) {
        navigator.geolocation.clearWatch(userWatchId)
    }
    
    const updateUserMarker = (latitude, longitude) => {
        if (userMarker.value) map.value.removeLayer(userMarker.value)
        userMarker.value = L.circleMarker([latitude, longitude], {
            radius: 8,
            fillColor: '#EF4444',
            fillOpacity: 1,
            color: '#FFFFFF',
            weight: 2
        }).addTo(map.value)
    }

    // 🚀 OBTENIR IMMÉDIATEMENT LA POSITION (Au clic)
    navigator.geolocation.getCurrentPosition((position) => {
        const { latitude, longitude } = position.coords
        updateUserMarker(latitude, longitude)
        if (map.value && map.value._map) {
            try {
                map.value.setView([latitude, longitude], 17)
            } catch (e) {
                console.error('Error setting view:', e)
            }
        }
    }, (err) => {
        alert("Erreur GPS : Impossible d'obtenir une position précise. Vérifiez vos réglages.")
    }, {
        enableHighAccuracy: true, 
        timeout: 10000,
        maximumAge: 0 
    })

    // 🚶 SUIVI CONTINU GÉNÉRAL (Met à jour le point bleu/rouge en direct quand l'utilisateur marche)
    userWatchId = navigator.geolocation.watchPosition((position) => {
        const { latitude, longitude } = position.coords
        updateUserMarker(latitude, longitude)
    }, (err) => {
        console.warn("Erreur de suivi GPS général :", err)
    }, {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0
    })
}

const calculateDistance = (lat1, lon1, lat2, lon2) => {
    const R = 6371e3
    const φ1 = lat1 * Math.PI / 180
    const φ2 = lat2 * Math.PI / 180
    const Δφ = (lat2 - lat1) * Math.PI / 180
    const Δλ = (lon2 - lon1) * Math.PI / 180
    const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) + Math.cos(φ1) * Math.cos(φ2) * Math.sin(Δλ / 2) * Math.sin(Δλ / 2)
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a))
}

const handleAddClick = () => {
    if (!navigator.geolocation) {
        alert("La géolocalisation n'est pas supportée.")
        return
    }

    isSubmitting.value = true
    
    // Tentative rapide d'abord
    navigator.geolocation.getCurrentPosition(
        (position) => {
            const { latitude, longitude } = position.coords
            
            // Vérification Périmètre UAC
            const dist = calculateDistance(latitude, longitude, UAC_CENTER.lat, UAC_CENTER.lng)
            
            if (dist > MAX_ADD_DISTANCE) {
                isSubmitting.value = false
                alert("Désolé, vous devez être physiquement sur le campus de l'UAC pour ajouter un lieu.")
                return
            }

            newPlace.value.latitude = latitude
            newPlace.value.longitude = longitude
            showModal.value = true
            isSubmitting.value = false
            telemetryService.trackEvent('add_place_start', { lat: latitude, lng: longitude })
        },
        (error) => {
            isSubmitting.value = false
            alert("Erreur GPS : " + (error.code === 3 ? "Temps d'attente dépassé. Réessayez." : "Position non autorisée."))
        },
        { 
            enableHighAccuracy: true, 
            timeout: 8000, // Timeout plus court pour ne pas bloquer l'UI trop longtemps
            maximumAge: 10000 
        }
    )
}

const submitContribution = async () => {
    isSubmitting.value = true
    try {
        if (contributionType.value === 'place') {
            await campusService.createPlace(newPlace.value)
            alert("Succès ! Votre position a été enregistrée comme nouveau lieu. Un admin validera le nom bientôt.")
        } else {
            newLiveReport.value.latitude = newPlace.value.latitude
            newLiveReport.value.longitude = newPlace.value.longitude
            await campusService.createLiveReport(newLiveReport.value)
            alert("Merci ! Votre signalement en direct a bien été publié sur la carte.")
        }
        showModal.value = false
        // Réinitialiser
        newPlace.value = { name: '', type: 'Lieu suggéré', category: 'Divers', description: '', latitude: 0, longitude: 0 }
        newLiveReport.value = { type: 'power_outage', description: '', latitude: 0, longitude: 0 }
        // Rafraîchir les marqueurs
        await updateMarkers()
    } catch (e) {
        alert("Erreur lors de l'envoi. Assurez-vous d'être connecté.")
    } finally {
        isSubmitting.value = false
    }
}

// Itinéraire piéton via Leaflet Routing
const startRoute = (destLat, destLng) => {
    if (!navigator.geolocation) return
    
    // Annuler l'ancien watch s'il y en a un
    if (routeWatchId) {
        navigator.geolocation.clearWatch(routeWatchId)
    }
    
    let isFirstPoint = true

    // 🚀 OBTENIR IMMÉDIATEMENT LA POSITION (Réactivité instantanée au clic)
    navigator.geolocation.getCurrentPosition((pos) => {
        const { latitude, longitude } = pos.coords
        
        if (userMarker.value) map.value.removeLayer(userMarker.value)
        
        userMarker.value = L.circleMarker([latitude, longitude], {
            radius: 8,
            fillColor: '#EF4444',
            fillOpacity: 1,
            color: '#FFFFFF',
            weight: 2
        }).addTo(map.value)

        drawRoute(latitude, longitude, destLat, destLng)
        
        // Centrer immédiatement la carte
        if (map.value && map.value._map) {
            try {
                const bounds = L.latLngBounds([
                    [latitude, longitude],
                    [destLat, destLng]
                ])
                map.value.fitBounds(bounds, { padding: [50, 50] })
            } catch (e) {
                console.error('Error fitting bounds:', e)
            }
        }
        isFirstPoint = false
    }, (err) => {
        console.warn("Erreur GPS instantanée :", err)
    }, {
        enableHighAccuracy: true,
        timeout: 4000,
        maximumAge: 0
    })

    // 🚶 SUIVI CONTINU ET DYNAMIQUE EN TEMPS RÉEL (Au fur et à mesure des pas)
    routeWatchId = navigator.geolocation.watchPosition((pos) => {
        const { latitude, longitude } = pos.coords
        
        if (userMarker.value) map.value.removeLayer(userMarker.value)
        
        userMarker.value = L.circleMarker([latitude, longitude], {
            radius: 8,
            fillColor: '#EF4444',
            fillOpacity: 1,
            color: '#FFFFFF',
            weight: 2
        }).addTo(map.value)

        if (isFirstPoint) {
            drawRoute(latitude, longitude, destLat, destLng)
            
            if (map.value && map.value._map) {
                try {
                    const bounds = L.latLngBounds([
                        [latitude, longitude],
                        [destLat, destLng]
                    ])
                    map.value.fitBounds(bounds, { padding: [50, 50] })
                } catch (e) {
                    console.error('Error fitting bounds:', e)
                }
            }
            isFirstPoint = false
        } else {
            // Mettre à jour dynamiquement la position de départ de la route existante
            updateRouteStart(latitude, longitude)
        }

    }, (err) => {
        console.warn("Erreur GPS lors du suivi continu :", err)
    }, { 
        enableHighAccuracy: true, 
        timeout: 5000,
        maximumAge: 0 // Ne jamais utiliser de position en cache pour la navigation active
    })
}

watch(() => visitedStore.visitedPlaces, updateMarkers, { deep: true })

onMounted(() => {
  window.goToDetail = (id) => router.push(`/lieu/${id}`)
  window.startRoute = (lat, lng) => startRoute(lat, lng)

  // Centré sur le coeur de l'UAC
  init('map', [6.44800, 2.35200])
  
  updateMarkers()
  
  if (route.query.focus) {
      const place = campusService.getPlaceById(route.query.focus)
      if (place && place.geometry && place.geometry.coordinates) {
          focusOn(place.geometry.coordinates[1], place.geometry.coordinates[0])
      }
  }
})

onUnmounted(() => {
  if (routeWatchId) {
      navigator.geolocation.clearWatch(routeWatchId)
  }
  if (userWatchId) {
      navigator.geolocation.clearWatch(userWatchId)
  }
  clear()
  delete window.goToDetail
  delete window.startRoute
})
</script>

<style scoped>
.animate-bounce-slow {
    animation: bounce 3s infinite;
}

@keyframes bounce {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}

.scale-in {
    animation: scaleIn 0.3s ease-out;
}

@keyframes scaleIn {
    from { transform: scale(0.9); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

:deep(.leaflet-control-zoom) {
    margin-bottom: 75px !important;
}

/* Pulsing warning icon styling for Live Reports */
:deep(.pulsing-marker) {
    position: relative;
    border-radius: 50%;
    animation: pulse-red 2s infinite;
}

@keyframes pulse-red {
    0% {
        box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(239, 68, 68, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
    }
}
</style>
