import { ref } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import 'leaflet-routing-machine'
import 'leaflet-routing-machine/dist/leaflet-routing-machine.css'
import { campusService } from '../services/campusService'

/**
 * Composable pour gérer la logique de la carte avec Leaflet (OpenStreetMap).
 */
export function useMapManager() {
  const map = ref(null)
  const markers = ref({})
  const userMarker = ref(null)
  const isSatellite = ref(false)
  const routingControl = ref(null)

  const init = (containerId, initialView = [6.44100, 2.35200], options = {}) => {
    try {
      // Initialisation de la carte Leaflet
      map.value = L.map(containerId, {
          zoomControl: false
      }).setView(initialView, 16); // Zoom 16 est parfait pour voir les bâtiments de l'UAC

      // Ajout de la couche OpenStreetMap par défaut
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
      }).addTo(map.value);

      // Ajout d'un contrôle de zoom personnalisé en bas à droite
      L.control.zoom({
          position: 'bottomright'
      }).addTo(map.value);

      // Gestion du clic sur la carte
      map.value.on('click', (e) => {
          if (options.onClick) {
              options.onClick(e.latlng.lat, e.latlng.lng)
          }
      });
    } catch (e) {
      console.error('Error initializing map:', e)
    }
  }

  const toggleLayer = () => {
    if (!map.value) return
    isSatellite.value = !isSatellite.value
    const mapEl = document.getElementById('map')
    if (isSatellite.value) {
        if (mapEl) mapEl.classList.add('map-satellite')
        // Couche Satellite (Esri par exemple)
        try {
          L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
              attribution: 'Tiles &copy; Esri'
          }).addTo(map.value);
        } catch (e) {
          console.error('Error adding satellite layer:', e)
        }
    } else {
        if (mapEl) mapEl.classList.remove('map-satellite')
        // Retour à OSM
        try {
          L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
              attribution: '&copy; OpenStreetMap'
          }).addTo(map.value);
        } catch (e) {
          console.error('Error adding OSM layer:', e)
        }
    }
  }

  const addMarker = (id, lat, lng, options = {}) => {
    if (!map.value) return null
    const { color = '#3B82F6', popupContent = '' } = options

    try {
      if (markers.value[id]) {
        map.value.removeLayer(markers.value[id])
      }

      // Custom marker avec CSS simple
      const customIcon = L.divIcon({
          className: 'custom-div-icon',
          html: `<div style="background-color: ${color}; width: 12px; height: 12px; border-radius: 50%; border: 2px solid white;"></div>`,
          iconSize: [12, 12],
          iconAnchor: [6, 6]
      });

      const marker = L.marker([lat, lng], { icon: customIcon }).addTo(map.value);

      if (popupContent) {
          marker.bindPopup(popupContent);
      }

      markers.value[id] = marker
      return marker
    } catch (e) {
      console.error('Error adding marker:', e)
      return null
    }
  }

  const focusOn = (lat, lng, zoom = 18) => {
    if (map.value && map.value._map) {
      try {
        map.value.setView([lat, lng], zoom)
      } catch (e) {
        console.error('Error focusing on location:', e)
      }
    }
  }

  const clear = () => {
    Object.values(markers.value).forEach(m => map.value.removeLayer(m))
    markers.value = {}
    if (routingControl.value) {
        map.value.removeControl(routingControl.value)
        routingControl.value = null
    }
  }

  const drawRoute = (startLat, startLng, destLat, destLng) => {
    if (!map.value) return
    
    if (routingControl.value) {
        map.value.removeControl(routingControl.value)
    }

    routingControl.value = L.Routing.control({
        waypoints: [
            L.latLng(startLat, startLng),
            L.latLng(destLat, destLng)
        ],
        routeWhileDragging: false,
        addWaypoints: false,
        show: false, // hide instructions panel
        lineOptions: {
            styles: [{color: '#3B82F6', weight: 6, opacity: 0.8}]
        },
        createMarker: function() { return null; } // do not create extra markers for route
    }).addTo(map.value)
  }

  const updateRouteStart = (startLat, startLng) => {
    if (routingControl.value) {
        routingControl.value.spliceWaypoints(0, 1, L.latLng(startLat, startLng))
    }
  }

  return {
    map,
    isSatellite,
    init,
    toggleLayer,
    addMarker,
    focusOn,
    clear,
    drawRoute,
    updateRouteStart,
    userMarker
  }
}
