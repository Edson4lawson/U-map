import { ref, onUnmounted } from 'vue'
import { useVisitedStore } from '../stores/visited'
import { useNotificationStore } from '../stores/notifications'
import { campusService } from '../services/campusService'

/**
 * Formule de Haversine pour calculer la distance entre deux points GPS en mètres.
 * @returns {number} Distance en mètres
 */
function getDistanceInMeters(lat1, lon1, lat2, lon2) {
  const R = 6371e3; // Rayon de la Terre en mètres
  const φ1 = lat1 * Math.PI / 180;
  const φ2 = lat2 * Math.PI / 180;
  const Δφ = (lat2 - lat1) * Math.PI / 180;
  const Δλ = (lon2 - lon1) * Math.PI / 180;

  const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
            Math.cos(φ1) * Math.cos(φ2) *
            Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

  return R * c;
}

/**
 * Composable pour gérer la détection de proximité.
 * Observe la position de l'utilisateur et envoie une notif s'il est proche d'un lieu.
 */
export function useProximity() {
  const visitedStore = useVisitedStore()
  const notificationStore = useNotificationStore()
  
  const watchId = ref(null)
  
  // Pour éviter de spammer l'utilisateur, on stocke la dernière notif par lieu
  // Map : ID du lieu -> Timestamp de la dernière notif
  const recentNotifications = ref(new Map())
  const NOTIFICATION_COOLDOWN = 30 * 60 * 1000 // 30 minutes d'attente avant de re-notifier pour le même lieu
  const DISTANCE_THRESHOLD = 150 // Distance de déclenchement en mètres

  /**
   * Commence à surveiller la position GPS en temps réel.
   */
  const startWatching = () => {
    if (!navigator.geolocation) return

    watchId.value = navigator.geolocation.watchPosition(
      (position) => {
        const { latitude, longitude } = position.coords
        checkProximity(latitude, longitude)
      },
      (err) => {
        // Log discret pour ne pas polluer la console
        if (err.code === 3) console.debug('Proximité: Refresh position skip (timeout)')
      },
      {
        enableHighAccuracy: false, // Basse précision suffisante pour la proximité (150m)
        timeout: 10000,
        maximumAge: 60000 // On accepte une position vieille d'une minute
      }
    )
  }

  /**
   * Arrête la surveillance GPS.
   */
  const stopWatching = () => {
    if (watchId.value !== null) {
      navigator.geolocation.clearWatch(watchId.value)
      watchId.value = null
    }
  }

  /**
   * Vérifie la distance avec tous les lieux stockés.
   */
  const checkProximity = async (userLat, userLon) => {
    const places = await campusService.getAllPlaces()
    
    places.forEach(feature => {
      if (!feature.geometry || !feature.geometry.coordinates) return
      
      const { id, name } = feature.properties
      // GeoJSON structure: [longitude, latitude]
      const [placeLon, placeLat] = feature.geometry.coordinates
      const distance = getDistanceInMeters(userLat, userLon, placeLat, placeLon)

      if (distance < DISTANCE_THRESHOLD) {
        // Vérifie si on a déjà notifié récemment (Cooldown)
        const lastNotified = recentNotifications.value.get(id)
        const now = Date.now()

        if (!lastNotified || (now - lastNotified > NOTIFICATION_COOLDOWN)) {
            // Déclenche la notification
            notificationStore.addNotification({
                title: 'À proximité !',
                message: `Vous êtes proche de : ${name}`,
                type: 'location'
            })
            recentNotifications.value.set(id, now)
        }
      }
    })
  }

  // Nettoyage automatique si le composant est détruit
  onUnmounted(() => {
    stopWatching()
  })

  return {
    startWatching,
    stopWatching
  }
}
