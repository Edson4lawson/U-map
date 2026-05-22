import { watchEffect } from 'vue'

/**
 * Composable pour gérer dynamiquement les meta-données de la page.
 * Utile pour le SEO et l'expérience utilisateur (titre de l'onglet).
 */
export function useMeta(title, description) {
  watchEffect(() => {
    if (title) {
      document.title = `${title} | U-map UAC`
    }
    
    if (description) {
      const metaDescription = document.querySelector('meta[name="description"]')
      if (metaDescription) {
        metaDescription.setAttribute('content', description)
      } else {
        const meta = document.createElement('meta')
        meta.name = 'description'
        meta.content = description
        document.head.appendChild(meta)
      }
    }
  })
}
