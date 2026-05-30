import { watchEffect, onMounted, onUnmounted } from 'vue'

/**
 * Composable pour gérer dynamiquement les meta-données de la page.
 * Utile pour le SEO et l'expérience utilisateur (titre de l'onglet).
 * @param {Ref<string>|ComputedRef<string>|string} title - Le titre de la page (réactif ou statique)
 * @param {Ref<string>|ComputedRef<string>|string} description - La description meta (réactive ou statique)
 * @returns {Function} Fonction de cleanup pour arrêter le watchEffect
 */
export function useMeta(title, description) {
  let cleanup

  onMounted(() => {
    cleanup = watchEffect(() => {
      const titleValue = typeof title === 'function' ? title.value : title
      const descriptionValue = typeof description === 'function' ? description.value : description

      if (titleValue) {
        document.title = `${titleValue} | U-map UAC`
      }
      
      if (descriptionValue) {
        const metaDescription = document.querySelector('meta[name="description"]')
        if (metaDescription) {
          metaDescription.setAttribute('content', descriptionValue)
        } else {
          const meta = document.createElement('meta')
          meta.name = 'description'
          meta.content = descriptionValue
          document.head.appendChild(meta)
        }
      }
    })
  })

  onUnmounted(() => {
    if (cleanup) {
      cleanup()
    }
  })

  return () => cleanup && cleanup()
}
