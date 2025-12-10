<template>
  <div id="map" class="w-full h-full z-0"></div>
  
  <!-- Map Controls Overlay -->
  <div class="absolute top-4 right-4 z-[400] flex flex-col gap-2">
     <button @click="toggleLayer" class="p-3 bg-white dark:bg-gray-800 rounded-lg shadow-md text-gray-700 dark:text-gray-200 hover:bg-gray-50 focus:outline-none" aria-label="Toggle Layer">
       <Icon :icon="isSatellite ? 'ph:map-trifold' : 'ph:globe-hemisphere-west'" class="w-6 h-6" />
     </button>
     <button @click="locateUser" class="p-3 bg-white dark:bg-gray-800 rounded-lg shadow-md text-primary hover:bg-gray-50 focus:outline-none" aria-label="Locate Me">
       <Icon icon="ph:navigation-arrow-fill" class="w-6 h-6" />
     </button>
     <button v-if="currentRoute" @click="clearRoute" class="p-3 bg-red-500 text-white rounded-lg shadow-md hover:bg-red-600 focus:outline-none" aria-label="Clear Route">
        <Icon icon="ph:x-bold" class="w-6 h-6" />
     </button>
  </div>
</template>

<script setup>
import { onMounted, onUnmounted, ref, watch } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import 'leaflet-routing-machine'
import 'leaflet-routing-machine/dist/leaflet-routing-machine.css'
import { Icon } from '@iconify/vue'
import { useRouter, useRoute } from 'vue-router'
import campusData from '../data/campus.json'
import { useVisitedStore } from '../stores/visited'
import { useCustomPlacesStore } from '../stores/customPlaces'

// Fix for default marker icons (sometimes needed with bundlers)
import iconRetinaUrl from 'leaflet/dist/images/marker-icon-2x.png'
import iconUrl from 'leaflet/dist/images/marker-icon.png'
import shadowUrl from 'leaflet/dist/images/marker-shadow.png'

delete L.Icon.Default.prototype._getIconUrl
L.Icon.Default.mergeOptions({
  iconRetinaUrl,
  iconUrl,
  shadowUrl
})

const router = useRouter()
const route = useRoute()
const visitedStore = useVisitedStore()
const customPlacesStore = useCustomPlacesStore()

let map = null
let markers = {} // Keyed by location ID
let userMarker = null
let routingControl = null
const isSatellite = ref(false)
const currentRoute = ref(false)

// Constants
const TILE_LAYERS = {
  OSM: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
  SAT: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}'
}

const ATTRIBUTIONS = {
  OSM: '&copy; OpenStreetMap contributors',
  SAT: 'Tiles &copy; Esri'
}

// Layers
const osmLayer = L.tileLayer(TILE_LAYERS.OSM, { attribution: ATTRIBUTIONS.OSM })
const satLayer = L.tileLayer(TILE_LAYERS.SAT, { attribution: ATTRIBUTIONS.SAT })

// Custom Pulse Icon
const createCustomIcon = (color, isCustom = false) => {
  return L.divIcon({
    className: 'custom-marker',
    html: `<div class="marker-pin" style="background-color: ${color};"></div><div class="marker-pulse" style="border-color: ${color};"></div>${isCustom ? '<span style="position: absolute; top: -25px; left: 50%; transform: translateX(-50%); font-size: 10px; font-weight: bold; background: white; padding: 2px 4px; border-radius: 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.2);">Perso</span>' : ''}`,
    iconSize: [30, 30],
    iconAnchor: [15, 30],
    popupAnchor: [0, -30]
  })
}

const initMap = () => {
  // Center roughly on UAC
  map = L.map('map', {
    zoomControl: false,
    attributionControl: false
  }).setView([6.43500, 2.34500], 15)

  osmLayer.addTo(map)
  L.control.zoom({ position: 'bottomright' }).addTo(map)

  updateMarkers()
  
  // Check if focus query param exists
  if (route.query.focus) {
    requestAnimationFrame(() => focusOnPlace(parseInt(route.query.focus)))
  } else if (route.query.focusCustom) {
    requestAnimationFrame(() => focusOnPlace(route.query.focusCustom, true))
  }
}

const updateMarkers = () => {
  // Clear existing markers if needed or update them
  // For simplicity, we'll clear and redraw if the count changes drastically, but updating is better
  // Let's just iterate and add/update
  
  // 1. Standard Campus Data
  campusData.features.forEach(feature => {
    if (!feature.geometry || !feature.geometry.coordinates) return;

    const props = feature.properties
    const id = props.id
    const isVisited = visitedStore.isVisited(id)
    const color = isVisited ? '#10B981' : '#3B82F6' // Green or Blue
    
    addOrUpdateMarker(id, feature.geometry.coordinates, color, props, false)
  })

  // 2. Custom Places
  customPlacesStore.customPlaces.forEach(place => {
      if (!place.geometry || !place.geometry.coordinates) return;
      const color = '#A855F7' // Purple
      addOrUpdateMarker(place.id, place.geometry.coordinates, color, place, true)
  })
}

const addOrUpdateMarker = (id, coordinates, color, props, isCustom) => {
    const [lng, lat] = coordinates
    
    if (markers[id]) {
       const existingMarker = markers[id]
       const newIcon = createCustomIcon(color, isCustom)
       existingMarker.setIcon(newIcon)
       // Update popup content just in case
       existingMarker.setPopupContent(createPopupContent(props, id, isCustom, coordinates))
    } else {
        const icon = createCustomIcon(color, isCustom)
        const marker = L.marker([lat, lng], { icon })
          .bindPopup(createPopupContent(props, id, isCustom, coordinates))
          .addTo(map)
        markers[id] = marker
    }
}

const createPopupContent = (props, id, isCustom, coordinates) => {
    const detailBtn = !isCustom ? 
        `<button onclick="window.location.hash='#/lieu/${id}'" class="mt-2 px-3 py-1 bg-blue-500 text-white text-xs rounded-full hover:bg-blue-600 transition w-full">Voir détails</button>` : ''
    
    // We attach an event listener via global window function for routing
    window.startRoute = (destLat, destLng) => {
        calculateRoute(destLat, destLng)
    }

    const [lng, lat] = coordinates
    const routeBtn = `<button onclick="window.startRoute(${lat}, ${lng})" class="mt-2 px-3 py-1 bg-green-500 text-white text-xs rounded-full hover:bg-green-600 transition w-full flex items-center justify-center gap-1">
        <span>Itinéraire</span>
    </button>`

    // Share logic
    window.sharePlace = (placeId) => {
        const url = `${window.location.origin}/#/map?focus=${placeId}`
        navigator.clipboard.writeText(url).then(() => {
            alert('Lien copié !')
        }).catch(err => {
            console.error('Erreur copie:', err)
            prompt('Copiez ce lien:', url)
        })
    }

    const shareBtn = `<button onclick="window.sharePlace(${id})" class="mt-2 px-3 py-1 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-xs rounded-full hover:bg-gray-300 dark:hover:bg-gray-600 transition w-full flex items-center justify-center gap-1">
        <span>Partager</span>
    </button>`

    return `
      <div class="text-center min-w-[150px]">
         <h3 class="font-bold text-lg mb-1">${props.name}</h3>
         <p class="text-xs text-gray-500 mb-2">${props.type}</p>
         ${detailBtn}
         ${routeBtn}
         ${shareBtn}
      </div>
    `
}

const toggleLayer = () => {
  isSatellite.value = !isSatellite.value
  if (isSatellite.value) {
    map.removeLayer(osmLayer)
    map.addLayer(satLayer)
  } else {
    map.removeLayer(satLayer)
    map.addLayer(osmLayer)
  }
}

const locateUser = () => {
  return new Promise((resolve, reject) => {
    if ("geolocation" in navigator) {
        navigator.geolocation.getCurrentPosition((position) => {
        const { latitude, longitude } = position.coords
        
        if (userMarker) map.removeLayer(userMarker)
        
        userMarker = L.marker([latitude, longitude], {
            icon: L.divIcon({
                className: 'user-marker',
                html: '<div class="w-4 h-4 bg-red-500 rounded-full border-2 border-white shadow-lg pulse-ring"></div>',
                iconSize: [20, 20]
            })
        }).addTo(map)

        map.flyTo([latitude, longitude], 16)
        resolve([latitude, longitude])
        }, (error) => {
            console.warn("Geolocation denied or error:", error)
            reject(error)
        })
    } else {
        console.warn("Geolocation not supported")
        reject("Not supported")
    }
  })
}

const calculateRoute = async (destLat, destLng) => {
    try {
        const userLoc = await locateUser() // Ensures we have user location
        const [userLat, userLng] = userLoc

        if (routingControl) {
            map.removeControl(routingControl)
        }

        routingControl = L.Routing.control({
            waypoints: [
                L.latLng(userLat, userLng),
                L.latLng(destLat, destLng)
            ],
            router: L.Routing.osrmv1({
                serviceUrl: 'https://router.project-osrm.org/route/v1',
                profile: 'foot' // Mode piéton pour le campus
            }),
            lineOptions: {
                styles: [{ color: '#10B981', opacity: 0.8, weight: 6 }] // Green for eco/walking
            },
            show: true, // Show instructions
            addWaypoints: false,
            draggableWaypoints: false,
            fitSelectedRoutes: true,
            showAlternatives: false,
            language: 'fr' // Instructions en français
        }).addTo(map)
        
        currentRoute.value = true

        // Hide the container if we want minimal UI, but user asked for "clear itinerary"
        // Let's keep it default for now, allows seeing steps.

    } catch (e) {
        alert("Impossible de localiser votre position pour l'itinéraire.")
    }
}

const clearRoute = () => {
    if (routingControl) {
        map.removeControl(routingControl)
        routingControl = null
        currentRoute.value = false
    }
}

const focusOnPlace = (id, isCustom = false) => {
  let feature;
  if (isCustom) {
      feature = customPlacesStore.customPlaces.find(p => p.id === id)
  } else {
      feature = campusData.features.find(f => f.properties.id === id)
  }
  
  if (feature && map) {
    const coords = isCustom ? feature.geometry.coordinates : feature.geometry.coordinates
    const [lng, lat] = coords
    map.flyTo([lat, lng], 17)
    
    if (markers[id]) {
        markers[id].openPopup()
    }
  }
}

watch(() => visitedStore.visitedPlaces, () => {
  updateMarkers()
}, { deep: true })

watch(() => customPlacesStore.customPlaces, () => {
    updateMarkers()
}, { deep: true })

onMounted(() => {
  initMap()
})

onUnmounted(() => {
  if (map) {
      map.remove()
      map = null
  }
  markers = {}
  window.startRoute = null
  window.sharePlace = null
})
</script>

<style>
/* Leaflet Custom Marker Styles */
.marker-pin {
  width: 14px;
  height: 14px;
  border-radius: 50% 50% 50% 0;
  background: #3B82F6;
  position: absolute;
  transform: rotate(-45deg);
  left: 50%;
  top: 50%;
  margin: -15px 0 0 -15px;
  box-shadow: 0 3px 5px rgba(0,0,0,0.3);
}

.marker-pin::after {
    content: '';
    width: 6px;
    height: 6px;
    margin: 4px 0 0 4px;
    background: #fff;
    position: absolute;
    border-radius: 50%;
}

.marker-pulse {
    position: absolute;
    margin-top: -15px;
    margin-left: -15px;
    transform: rotateX(55deg);
    width: 30px;
    height: 30px;
    border-radius: 50%;
    box-shadow: 0 0 0 2px #3B82F6;
    animation: pulse-marker 1.5s infinite;
    top: 15px;
    left: 15px;
    border: 1px solid transparent;
}

@keyframes pulse-marker {
    0% { transform: scale(0.5); opacity: 1; }
    100% { transform: scale(1.5); opacity: 0; }
}

/* User Marker */
.user-marker .pulse-ring {
    animation: pulse-ring 1.5s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
}

@keyframes pulse-ring {
    0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
    100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
}

/* Fix popup styles */
.leaflet-popup-content-wrapper {
    border-radius: 0.75rem;
    box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
    border: none;
}
.leaflet-popup-content {
    margin: 10px;
}
/* Leaflet Routing Machine overrides */
.leaflet-routing-container {
    background-color: white;
    padding: 10px;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    max-height: 200px; /* Limit height to save screen space on mobile */
    overflow-y: auto;
    font-size: 12px;
}
.dark .leaflet-routing-container {
    background-color: #1f2937;
    color: white;
}
</style>
