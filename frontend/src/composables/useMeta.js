import { watchEffect, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'

const BASE_URL = 'https://umap-ten.vercel.app'
const SITE_NAME = 'U-map UAC'
const DEFAULT_OG_IMAGE = `${BASE_URL}/og-image.png`
const DEFAULT_GEO = { lat: '6.4180', lng: '2.3450', placename: 'Abomey-Calavi, Bénin' }

/**
 * Composable pour gérer dynamiquement les meta-données SEO & GEO de chaque page.
 *
 * @param {string|Ref<string>} title - Le titre de la page
 * @param {string|Ref<string>} description - La meta description
 * @param {Object} [options] - Options supplémentaires
 * @param {string} [options.ogImage] - Image Open Graph personnalisée
 * @param {string} [options.ogType] - Type OG (default: 'website')
 * @param {Object} [options.geo] - { lat, lng, placename } pour le GEO SEO
 * @param {string} [options.canonicalPath] - Chemin canonical custom (sinon auto depuis route)
 */
export function useMeta(title, description, options = {}) {
  const route = useRoute()
  let cleanup
  const injectedElements = []

  /**
   * Crée ou met à jour une balise <meta> dans le <head>.
   */
  function setMeta(attribute, key, content) {
    if (!content) return
    const selector = `meta[${attribute}="${key}"]`
    let el = document.querySelector(selector)
    if (el) {
      el.setAttribute('content', content)
    } else {
      el = document.createElement('meta')
      el.setAttribute(attribute, key)
      el.setAttribute('content', content)
      document.head.appendChild(el)
      injectedElements.push(el)
    }
  }

  /**
   * Crée ou met à jour une balise <link> dans le <head>.
   */
  function setLink(rel, href, attrs = {}) {
    if (!href) return
    const extraSelector = Object.entries(attrs).map(([k, v]) => `[${k}="${v}"]`).join('')
    const selector = `link[rel="${rel}"]${extraSelector}`
    let el = document.querySelector(selector)
    if (el) {
      el.setAttribute('href', href)
    } else {
      el = document.createElement('link')
      el.setAttribute('rel', rel)
      el.setAttribute('href', href)
      Object.entries(attrs).forEach(([k, v]) => el.setAttribute(k, v))
      document.head.appendChild(el)
      injectedElements.push(el)
    }
  }

  onMounted(() => {
    cleanup = watchEffect(() => {
      const titleValue = typeof title === 'object' && title?.value !== undefined ? title.value : title
      const descValue = typeof description === 'object' && description?.value !== undefined ? description.value : description
      const canonicalPath = options.canonicalPath || route.path
      const canonicalUrl = `${BASE_URL}${canonicalPath}`
      const fullTitle = titleValue ? `${titleValue} | ${SITE_NAME}` : SITE_NAME
      const ogImage = options.ogImage || DEFAULT_OG_IMAGE
      const ogType = options.ogType || 'website'
      const geo = options.geo || DEFAULT_GEO

      // --- Titre ---
      document.title = fullTitle

      // --- Meta Description ---
      setMeta('name', 'description', descValue)

      // --- Canonical URL ---
      setLink('canonical', canonicalUrl)

      // --- Open Graph ---
      setMeta('property', 'og:title', fullTitle)
      setMeta('property', 'og:description', descValue)
      setMeta('property', 'og:url', canonicalUrl)
      setMeta('property', 'og:image', ogImage)
      setMeta('property', 'og:type', ogType)
      setMeta('property', 'og:site_name', SITE_NAME)
      setMeta('property', 'og:locale', 'fr_FR')

      // --- Twitter Card ---
      setMeta('property', 'twitter:card', 'summary_large_image')
      setMeta('property', 'twitter:title', fullTitle)
      setMeta('property', 'twitter:description', descValue)
      setMeta('property', 'twitter:url', canonicalUrl)
      setMeta('property', 'twitter:image', ogImage)

      // --- GEO SEO ---
      setMeta('name', 'geo.position', `${geo.lat};${geo.lng}`)
      setMeta('name', 'geo.placename', geo.placename)
      setMeta('name', 'geo.region', 'BJ')
      setMeta('name', 'ICBM', `${geo.lat}, ${geo.lng}`)

      // --- hreflang (fr + en) ---
      setLink('alternate', `${canonicalUrl}`, { hreflang: 'fr' })
      setLink('alternate', `${canonicalUrl}`, { hreflang: 'en' })
      setLink('alternate', `${canonicalUrl}`, { hreflang: 'x-default' })
    })
  })

  onUnmounted(() => {
    if (cleanup) cleanup()
    // Nettoyer les éléments injectés dynamiquement
    injectedElements.forEach(el => {
      if (el.parentNode) el.parentNode.removeChild(el)
    })
    injectedElements.length = 0
  })

  return () => cleanup && cleanup()
}
