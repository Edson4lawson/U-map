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
       <button @click="toggleAddMode"
          :class="addMode ? 'bg-orange-500 text-white' : 'bg-white dark:bg-gray-800 text-orange-500'"
          class="p-3 rounded-2xl shadow-xl hover:scale-105 transition-transform"
          aria-label="Add Place">
          <Icon icon="ph:plus-circle-bold" class="w-6 h-6" />
       </button>
    </div>

    <!-- Add Mode Indicator -->
    <div v-if="addMode" class="absolute top-4 left-4 z-[400] bg-orange-500 text-white px-3 py-2 rounded-2xl shadow-xl flex items-center gap-2">
       <Icon icon="ph:cursor-click-bold" class="w-4 h-4 flex-shrink-0" />
       <span class="text-xs font-bold">Cliquez sur la carte ou utilisez votre GPS</span>
       <button @click="useGPSForPlace" :disabled="gpsLoading" class="flex items-center gap-1 ml-1 bg-white/20 hover:bg-white/30 rounded-xl px-2 py-1 text-xs font-bold transition-all disabled:opacity-60">
          <Icon :icon="gpsLoading ? 'ph:spinner-gap-bold' : 'ph:crosshair-bold'" :class="gpsLoading ? 'animate-spin' : ''" class="w-3.5 h-3.5" />
          <span>Ma position</span>
       </button>
       <button @click="disableAddMode" class="ml-1 hover:bg-orange-600 rounded-full p-1">
          <Icon icon="ph:x-bold" class="w-4 h-4" />
       </button>
    </div>


    <!-- Add Place Modal -->
    <div v-if="showModal" class="fixed inset-0 z-[500] flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/60 backdrop-blur-sm pb-16 sm:pb-0">
        <div class="bg-white dark:bg-gray-900 rounded-t-3xl sm:rounded-3xl p-5 sm:p-6 w-full max-w-md shadow-2xl scale-in max-h-[80vh] overflow-y-auto pb-8">

            <!-- Header -->
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-extrabold dark:text-white">Contribuer à U-Map UAC</h2>
                    <p class="text-[11px] text-gray-400 mt-0.5">Votre contribution sera validée par un admin</p>
                </div>
                <button type="button" @click="showModal = false" class="w-9 h-9 rounded-full bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 text-gray-500 dark:text-slate-400 flex items-center justify-center transition-all">
                    <Icon icon="ph:x-bold" class="w-5 h-5" />
                </button>
            </div>

            <!-- Contribution Type Selector -->
            <div class="flex gap-2 mb-4 p-1 bg-gray-100 dark:bg-gray-800 rounded-2xl">
                <button type="button" @click="contributionType = 'place'" 
                        :class="contributionType === 'place' ? 'bg-white dark:bg-gray-700 shadow-sm text-blue-600 dark:text-blue-400 font-bold' : 'text-gray-500'"
                        class="flex-1 py-2 text-xs font-medium rounded-xl transition-all">
                    📍 Lieu Permanent
                </button>
                <button type="button" @click="contributionType = 'report'" 
                        :class="contributionType === 'report' ? 'bg-white dark:bg-gray-700 shadow-sm text-red-500 font-bold' : 'text-gray-500'"
                        class="flex-1 py-2 text-xs font-medium rounded-xl transition-all">
                    ⚠️ Infos en direct
                </button>
            </div>

            <!-- Position Source Selector -->
            <div class="mb-4 p-3 rounded-2xl border" :class="positionSource === 'gps' ? 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-800' : 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800'">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[11px] font-extrabold uppercase tracking-wider" :class="positionSource === 'gps' ? 'text-emerald-600 dark:text-emerald-400' : 'text-blue-600 dark:text-blue-400'">
                        {{ positionSource === 'gps' ? '📍 Position GPS' : '🗺️ Position sur la carte' }}
                    </span>
                    <button type="button" @click="useGPSForPlace" :disabled="gpsLoading"
                            class="flex items-center gap-1 text-xs font-bold px-2.5 py-1 rounded-xl transition-all"
                            :class="positionSource === 'gps' ? 'bg-emerald-500 text-white hover:bg-emerald-600' : 'bg-blue-500 text-white hover:bg-blue-600'">
                        <Icon :icon="gpsLoading ? 'ph:spinner-gap-bold' : 'ph:crosshair-bold'" :class="gpsLoading ? 'animate-spin' : ''" class="w-3.5 h-3.5" />
                        <span>{{ gpsLoading ? 'Localisation...' : 'Utiliser ma position' }}</span>
                    </button>
                </div>
                <div v-if="pendingLat !== 0" class="flex gap-2 text-[10px] font-mono">
                    <span class="bg-white/60 dark:bg-black/30 px-2 py-0.5 rounded-lg" :class="positionSource === 'gps' ? 'text-emerald-700 dark:text-emerald-300' : 'text-blue-700 dark:text-blue-300'">Lat : {{ pendingLat.toFixed(6) }}</span>
                    <span class="bg-white/60 dark:bg-black/30 px-2 py-0.5 rounded-lg" :class="positionSource === 'gps' ? 'text-emerald-700 dark:text-emerald-300' : 'text-blue-700 dark:text-blue-300'">Lng : {{ pendingLng.toFixed(6) }}</span>
                </div>
                <p v-else class="text-[10px] text-gray-400 italic">Cliquez sur la carte ou utilisez le bouton GPS ci-dessus</p>
            </div>

            <form @submit.prevent="submitContribution" class="space-y-4">
                <div v-if="contributionType === 'place'" class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Nom du lieu *</label>
                        <input v-model="newPlace.name" type="text" required placeholder="Ex: Amphi Houdégbé, Bibliothèque..." 
                               class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-xl p-3 text-sm focus:ring-2 focus:ring-blue-500 dark:text-white transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Catégorie</label>
                        <select v-model="newPlace.category" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-xl p-3 text-sm focus:ring-2 focus:ring-blue-500 dark:text-white">
                            <option value="Enseignements et académique">🎓 Amphi / Salle de cours</option>
                            <option value="Vie étudiante">🍽️ Détente / Restauration</option>
                            <option value="Administratif">🏛️ Administration</option>
                            <option value="Services">🏥 Services (Santé, Banque...)</option>
                            <option value="Sport">⚽ Sport & Loisirs</option>
                            <option value="Divers">📌 Autre</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Description (Optionnel)</label>
                        <textarea v-model="newPlace.description" rows="2" placeholder="Décrivez ce lieu..." class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-xl p-3 text-sm focus:ring-2 focus:ring-blue-500 dark:text-white resize-none"></textarea>
                    </div>
                </div>
                
                <div v-else class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Type de situation en direct</label>
                        <select v-model="newLiveReport.type" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-xl p-3 text-sm focus:ring-2 focus:ring-red-500 dark:text-white">
                            <option value="power_outage">🔌 Coupure d'électricité</option>
                            <option value="crowded">👥 Amphi / Lieu bondé</option>
                            <option value="event">🎉 Événement en cours</option>
                            <option value="other">⚠️ Autre signalement</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Description (Optionnel)</label>
                        <textarea v-model="newLiveReport.description" placeholder="Ex: Panne sur tout le secteur..." rows="2" 
                                  class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-xl p-3 text-sm focus:ring-2 focus:ring-red-500 dark:text-white resize-none"></textarea>
                    </div>
                </div>

                <div class="flex gap-3 pt-1">
                    <button type="button" @click="showModal = false" class="flex-1 py-3 text-gray-500 font-bold hover:bg-gray-100 dark:hover:bg-gray-800 rounded-xl transition-colors text-sm">Annuler</button>
                    <button type="submit" :disabled="isSubmitting" 
                            :class="contributionType === 'report' ? 'bg-red-500 hover:bg-red-600' : 'bg-blue-600 hover:bg-blue-700'"
                            class="flex-1 py-3 text-white font-bold rounded-xl shadow-lg disabled:opacity-50 transition-all text-sm flex items-center justify-center gap-2">
                        <Icon v-if="isSubmitting" icon="ph:spinner-gap-bold" class="animate-spin w-4 h-4" />
                        {{ isSubmitting ? 'Envoi...' : 'Contribuer' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { Icon } from '@iconify/vue'
import { useRouter, useRoute } from 'vue-router'
import L from 'leaflet'
import { useVisitedStore } from '../stores/visited'
import { useMapManager } from '../composables/useMapManager'
import { campusService } from '../services/campusService'
import { telemetryService } from '../services/telemetryService'
import errorHandler from '../services/errorHandler'

const router = useRouter()
const route = useRoute()
const visitedStore = useVisitedStore()
const { map, isSatellite, init, toggleLayer, addMarker, addPolygon, focusOn, clear, userMarker, drawRoute, updateRouteStart, setClickHandler, removeClickHandler } = useMapManager()
let routeWatchId = null
let userWatchId = null

// ─── État ajout de lieu ───────────────────────────────────────────────────────
const showModal      = ref(false)
const isSubmitting   = ref(false)
const contributionType = ref('place')
const addMode        = ref(false)
const gpsLoading     = ref(false)
const pendingLat     = ref(0)   // Coordonnée active pour la contribution (clic OU GPS)
const pendingLng     = ref(0)
const positionSource = ref('map') // 'map' | 'gps'

const newPlace = ref({
    name: '',
    type: 'Lieu suggéré',
    category: 'Divers',
    description: '',
})
const newLiveReport = ref({
    type: 'power_outage',
    description: '',
})
const liveReportMarkers = ref([])

// Délimitation du campus UAC (coordonnées précises du campus principal de Zogbadjè)
const UAC_BOUNDARIES = [
  [6.4475, 2.3444], // Coin Nord-Est (Limite vers le quartier Zogbadjè)
  [6.4478, 2.3496], // Coin Nord-Ouest (Bordure de la route RNIE 2)
  [6.4357, 2.3512], // Coin Sud-Est (Entrée principale / Échangeur)
  [6.4353, 2.3448], // Coin Sud-Ouest (Limite Ouest du campus / Clôture arrière)
  [6.4475, 2.3444]  // Fermeture
]
let campusPolygon = null

const updateMarkers = async () => {
  if (!map.value) return

  // Nettoyer les anciens signalements en direct
  liveReportMarkers.value.forEach(m => map.value.removeLayer(m))
  liveReportMarkers.value = []

  // Récupérer tous les lieux depuis la base de données
  const allPlaces = await campusService.getAllPlaces()

  allPlaces.forEach(feature => {
    const { id, name, type, source, added_by, status } = feature.properties
    const isVisited = visitedStore.isVisited(id)

    // Couleur différente selon la source et le statut
    let color = isVisited ? '#10B981' : '#3B82F6'
    if (source === 'osm') color = '#8B5CF6' // Violet pour les lieux OSM
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
    const identifier = props.slug || id
    return `
      <div class="p-2 min-w-[200px] font-sans">
         <h3 class="font-bold text-gray-900 text-base mb-1">${props.name}</h3>
         <p class="text-xs text-gray-500 mb-1">${props.type}</p>
         ${props.added_by ? `<p class="text-[10px] text-orange-600 font-bold mb-2 italic">Proposé par : ${props.added_by}</p>` : ''}
         <div class="flex flex-col gap-2">
            <button onclick="window.goToDetail('${identifier}')" class="w-full py-2 bg-blue-600 text-white text-xs font-bold rounded-xl shadow-lg shadow-blue-500/20">Détails</button>
            <button onclick="window.startRoute(${lat}, ${lng})" class="w-full py-2 bg-emerald-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-emerald-500/20">Y aller 🚶</button>
         </div>
      </div>
    `
}

const UAC_CENTER = { lat: 6.414989, lng: 2.343469 } // Centre du campus (Rectorat UAC)
const MAX_ADD_DISTANCE = 1000 // 1km - distance maximale pour ajouter un lieu sur le campus

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
        if (map.value) {
            try {
                map.value.setView([latitude, longitude], 17)
            } catch (e) {
                console.error('Error setting view:', e)
            }
        }
    }, (err) => {
        errorHandler.error("Impossible d'obtenir une position précise. Vérifiez vos réglages GPS.")
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

const toggleAddMode = () => {
    const token = localStorage.getItem('u_map_token')
    if (!token) {
        errorHandler.error("Vous devez être connecté pour ajouter un lieu. Connectez-vous d'abord.")
        router.push('/login')
        return
    }

    addMode.value = !addMode.value
    if (addMode.value) {
        // Enregistrer le handler de clic sur la carte (sans réinitialiser la carte !)
        setClickHandler((lat, lng) => handleMapClick(lat, lng))
        telemetryService.trackEvent('add_mode_activated')
    } else {
        removeClickHandler()
        telemetryService.trackEvent('add_mode_deactivated')
    }
}

const disableAddMode = () => {
    addMode.value = false
    removeClickHandler()
}

const handleMapClick = (lat, lng) => {
    if (!addMode.value) return

    // Vérification Périmètre UAC
    const dist = calculateDistance(lat, lng, UAC_CENTER.lat, UAC_CENTER.lng)
    if (dist > MAX_ADD_DISTANCE) {
        errorHandler.error("Désolé, ce lieu est trop loin du campus de l'UAC.")
        return
    }

    pendingLat.value = lat
    pendingLng.value = lng
    positionSource.value = 'map'
    showModal.value = true
    disableAddMode()
    telemetryService.trackEvent('add_place_click', { lat, lng })
}

// Obtenir la position GPS de l'utilisateur (utilisable depuis la bannière OU depuis le modal)
const useGPSForPlace = () => {
    if (!navigator.geolocation) {
        errorHandler.error("La géolocalisation n'est pas supportée par votre navigateur.")
        return
    }
    const token = localStorage.getItem('u_map_token')
    if (!token) {
        errorHandler.error("Vous devez être connecté pour ajouter un lieu.")
        router.push('/login')
        return
    }

    gpsLoading.value = true

    navigator.geolocation.getCurrentPosition(
        (position) => {
            const { latitude, longitude } = position.coords
            gpsLoading.value = false

            // Vérification Périmètre UAC
            const dist = calculateDistance(latitude, longitude, UAC_CENTER.lat, UAC_CENTER.lng)
            if (dist > MAX_ADD_DISTANCE) {
                errorHandler.error("Désolé, vous devez être physiquement sur le campus de l'UAC pour ajouter un lieu.")
                return
            }

            pendingLat.value = latitude
            pendingLng.value = longitude
            positionSource.value = 'gps'

            // Si le mode ajout est actif mais pas le modal, ouvrir le modal
            if (!showModal.value) {
                showModal.value = true
                disableAddMode()
            }
            telemetryService.trackEvent('add_place_gps', { lat: latitude, lng: longitude })
        },
        (error) => {
            gpsLoading.value = false
            if (error.code === 1) {
                errorHandler.error("Accès GPS refusé. Autorisez la localisation dans les paramètres de votre navigateur.")
            } else if (error.code === 3) {
                errorHandler.error("Temps d'attente GPS dépassé. Réessayez dans un endroit dégagé.")
            } else {
                errorHandler.error("Impossible d'obtenir votre position GPS.")
            }
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    )
}

const handleAddClick = () => {
    if (!navigator.geolocation) {
        errorHandler.error("La géolocalisation n'est pas supportée par votre navigateur.")
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
                errorHandler.error("Désolé, vous devez être physiquement sur le campus de l'UAC pour ajouter un lieu.")
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
            if (error.code === 3) {
                errorHandler.error("Temps d'attente GPS dépassé. Réessayez.")
            } else {
                errorHandler.error("Position GPS non autorisée. Vérifiez vos paramètres.")
            }
        },
        {
            enableHighAccuracy: true,
            timeout: 8000, // Timeout plus court pour ne pas bloquer l'UI trop longtemps
            maximumAge: 10000
        }
    )
}

const submitContribution = async () => {
    // Si aucune position n'a été spécifiée explicitement, utiliser par défaut la position du centre UAC
    if (pendingLat.value === 0 || pendingLng.value === 0) {
        pendingLat.value = UAC_CENTER.lat
        pendingLng.value = UAC_CENTER.lng
    }

    const token = localStorage.getItem('u_map_token')
    if (!token) {
        errorHandler.error("Vous devez être connecté pour contribuer.")
        router.push('/login')
        return
    }

    isSubmitting.value = true
    try {
        if (contributionType.value === 'place') {
            await campusService.createPlace({
                ...newPlace.value,
                latitude: pendingLat.value,
                longitude: pendingLng.value,
            })
            errorHandler.success("✅ Votre lieu a été soumis avec succès ! Un administrateur le validera prochainement.")
        } else {
            await campusService.createLiveReport({
                ...newLiveReport.value,
                latitude: pendingLat.value,
                longitude: pendingLng.value,
            })
            errorHandler.success("✅ Votre signalement en direct a bien été publié sur la carte.")
        }
        showModal.value = false
        // Réinitialiser l'état
        newPlace.value    = { name: '', type: 'Lieu suggéré', category: 'Divers', description: '' }
        newLiveReport.value = { type: 'power_outage', description: '' }
        pendingLat.value   = 0
        pendingLng.value   = 0
        positionSource.value = 'map'
        // Rafraîchir les marqueurs
        await updateMarkers()
    } catch (e) {
        const msg = e?.message || ''
        if (msg.includes('403') || msg.includes('Unauthorized') || msg.toLowerCase().includes('unauthenticated')) {
            errorHandler.error("Votre session a expiré. Reconnectez-vous pour contribuer.")
            router.push('/login')
        } else {
            errorHandler.error("Erreur lors de l'envoi. Vérifiez votre connexion et réessayez.")
        }
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
        if (map.value) {
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
            
            if (map.value) {
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

onMounted(async () => {
  window.goToDetail = (id) => router.push(`/lieu/${id}`)
  window.startRoute = (lat, lng) => startRoute(lat, lng)

  await nextTick()

  // Centré sur le coeur de l'UAC avec vérification du DOM
  setTimeout(() => {
    if (document.getElementById('map')) {
      init('map', [UAC_CENTER.lat, UAC_CENTER.lng])
      updateMarkers()
    }
  }, 50)
  
  if (route.query.focus) {
      const place = await campusService.getPlaceById(route.query.focus)
      if (place && place.geometry && place.geometry.coordinates) {
          focusOn(place.geometry.coordinates[1], place.geometry.coordinates[0])
      }
  }

  if (route.query.place) {
      const place = await campusService.getPlaceById(route.query.place)
      if (place && place.geometry && place.geometry.coordinates) {
          const [lng, lat] = place.geometry.coordinates

          // If route=true, generate route from current position
          if (route.query.route === 'true') {
              startRoute(lat, lng)
          } else {
              focusOn(lat, lng)
              // Open popup for the place
              const markers = document.querySelectorAll('.leaflet-marker-icon')
              markers.forEach(marker => {
                  if (marker._latlng && marker._latlng.lat === lat && marker._latlng.lng === lng) {
                      marker.openPopup()
                  }
              })
          }
      }
  }

  if (route.query.routeTo) {
      const place = await campusService.getPlaceById(route.query.routeTo)
      if (place && place.geometry && place.geometry.coordinates) {
          const [lng, lat] = place.geometry.coordinates
          startRoute(lat, lng)
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
