import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

/**
 * Store Pinia pour la gestion des notifications.
 * Permet d'ajouter, de lire et de supprimer des notifications dans toute l'application.
 */
export const useNotificationStore = defineStore('notifications', () => {
  // État : Liste des notifications
  const notifications = ref([
    // Structure exemple :
    // { id: 1, title: 'Bienvenue', message: '...', type: 'info', read: false, timestamp: new Date() }
  ])

  // Getter : Compte le nombre de notifications non lues
  const unreadCount = computed(() => notifications.value.filter(n => !n.read).length)

  /**
   * Ajoute une nouvelle notification au début de la liste.
   * @param {Object} notification - L'objet notification à ajouter
   */
  function addNotification(notification) {
    notifications.value.unshift({
      id: Date.now() + Math.random(), // ID unique simple
      timestamp: new Date(),
      read: false,
      ...notification
    })
  }

  /**
   * Marque une notification spécifique comme lue.
   * @param {number} id - L'ID de la notification
   */
  function markAsRead(id) {
    const notification = notifications.value.find(n => n.id === id)
    if (notification) {
      notification.read = true
    }
  }

  /**
   * Marque toutes les notifications comme lues.
   * Utile pour le bouton "Tout marquer comme lu".
   */
  function markAllAsRead() {
    notifications.value.forEach(n => n.read = true)
  }

  /**
   * Supprime une notification de la liste.
   * @param {number} id - L'ID de la notification à supprimer
   */
  function removeNotification(id) {
    notifications.value = notifications.value.filter(n => n.id !== id)
  }

  return {
    notifications,
    unreadCount,
    addNotification,
    markAsRead,
    markAllAsRead,
    removeNotification
  }
})
