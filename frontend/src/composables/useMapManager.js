import { ref } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import 'leaflet-routing-machine'
import 'leaflet-routing-machine/dist/leaflet-routing-machine.css'

/**
 * Composable pour gérer la logique de la carte avec Leaflet (OpenStreetMap).
 * Fix: Les références de couches sont stockées pour éviter l'accumulation.
 */
export function useMapManager() {
  const map = ref(null)
  const markers = ref({})
  const userMarker = ref(null)
  const isSatellite = ref(false)
  const routingControl = ref(null)

  // Références des couches pour éviter l'accumulation au toggle
  const osmLayer = ref(null)
  const satelliteLayer = ref(null)

  let _clickHandler = null

  const init = (containerId, initialView = [6.414989, 2.343469], options = {}) => {
    try {
      map.value = L.map(containerId, {
          zoomControl: false
      }).setView(initialView, 16);

      // Couche OSM par défaut — stocker la référence
      osmLayer.value = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
          maxZoom: 19,
      }).addTo(map.value);

      L.control.zoom({ position: 'bottomright' }).addTo(map.value);

      map.value.on('click', (e) => {
          if (_clickHandler) {
              _clickHandler(e.latlng.lat, e.latlng.lng)
          }
      });
    } catch (e) {
      console.error('Error initializing map:', e)
    }
  }

  const setClickHandler = (fn) => {
    _clickHandler = fn
  }

  const removeClickHandler = () => {
    _clickHandler = null
  }

  const toggleLayer = () => {
    if (!map.value) return

    if (!isSatellite.value) {
      // Passer en satellite : retirer OSM, ajouter satellite
      if (osmLayer.value) {
        map.value.removeLayer(osmLayer.value)
        osmLayer.value = null
      }
      try {
        satelliteLayer.value = L.tileLayer(
          'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
          { attribution: 'Tiles &copy; Esri', maxZoom: 19 }
        ).addTo(map.value)
        isSatellite.value = true
      } catch (e) {
        console.error('Error adding satellite layer:', e)
      }
    } else {
      // Revenir en OSM : retirer satellite, ajouter OSM
      if (satelliteLayer.value) {
        map.value.removeLayer(satelliteLayer.value)
        satelliteLayer.value = null
      }
      try {
        osmLayer.value = L.tileLayer(
          'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
          { attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors', maxZoom: 19 }
        ).addTo(map.value)
        isSatellite.value = false
      } catch (e) {
        console.error('Error adding OSM layer:', e)
      }
    }
  }

  const addMarker = (id, lat, lng, options = {}) => {
    if (!map.value) return null
    const { color = '#3B82F6', popupContent = '', icon = null } = options

    try {
      if (markers.value[id]) {
        map.value.removeLayer(markers.value[id])
      }

      const leafletIcon = icon || L.divIcon({
          className: 'custom-div-icon',
          html: `<div style="background-color:${color};width:14px;height:14px;border-radius:50%;border:2px solid white;box-shadow:0 2px 6px rgba(0,0,0,0.35);"></div>`,
          iconSize: [14, 14],
          iconAnchor: [7, 7]
      });

      const marker = L.marker([lat, lng], { icon: leafletIcon }).addTo(map.value);

      if (popupContent) {
          marker.bindPopup(popupContent, {
            closeButton: true,
            maxWidth: 280,
            className: 'umap-popup'
          });
      }

      markers.value[id] = marker
      return marker
    } catch (e) {
      console.error('Error adding marker:', e)
      return null
    }
  }

  const removeMarker = (id) => {
    if (markers.value[id] && map.value) {
      map.value.removeLayer(markers.value[id])
      delete markers.value[id]
    }
  }

  const addPolygon = (coordinates, options = {}) => {
    if (!map.value) return null
    const { color = '#EF4444', fillColor = '#EF4444', fillOpacity = 0.1, weight = 3 } = options

    try {
      return L.polygon(coordinates, { color, fillColor, fillOpacity, weight }).addTo(map.value)
    } catch (e) {
      console.error('Error adding polygon:', e)
      return null
    }
  }

  const focusOn = (lat, lng, zoom = 18) => {
    if (map.value && map.value._mapPane) {
      try {
        map.value.flyTo([lat, lng], zoom, { duration: 0.8 })
      } catch (e) {
        console.error('Error focusing on location:', e)
      }
    }
  }

  const clear = () => {
    Object.values(markers.value).forEach(m => { if (map.value) map.value.removeLayer(m) })
    markers.value = {}
    if (routingControl.value && map.value) {
        map.value.removeControl(routingControl.value)
        routingControl.value = null
    }
  }

  const destroy = () => {
    clear()
    if (osmLayer.value && map.value) map.value.removeLayer(osmLayer.value)
    if (satelliteLayer.value && map.value) map.value.removeLayer(satelliteLayer.value)
    if (map.value) {
      map.value.remove()
      map.value = null
    }
    osmLayer.value = null
    satelliteLayer.value = null
  }

  const drawRoute = (startLat, startLng, destLat, destLng) => {
    if (!map.value) return
    if (routingControl.value) {
        map.value.removeControl(routingControl.value)
    }
    routingControl.value = L.Routing.control({
        waypoints: [L.latLng(startLat, startLng), L.latLng(destLat, destLng)],
        routeWhileDragging: false,
        addWaypoints: false,
        show: false,
        lineOptions: { styles: [{color: '#3B82F6', weight: 6, opacity: 0.8}] },
        createMarker: () => null
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
    osmLayer,
    satelliteLayer,
    markers,
    userMarker,
    init,
    toggleLayer,
    addMarker,
    removeMarker,
    addPolygon,
    focusOn,
    clear,
    destroy,
    drawRoute,
    updateRouteStart,
    setClickHandler,
    removeClickHandler,
  }
}
