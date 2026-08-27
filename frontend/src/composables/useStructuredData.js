import { onMounted, onUnmounted } from 'vue'

const BASE_URL = 'https://umap-ten.vercel.app'

/**
 * Composable pour injecter des données structurées JSON-LD dans le <head>.
 * Utilisé pour les rich snippets Google (lieu, organisation, breadcrumbs, etc.).
 *
 * @param {Object|Function} schemaData - Un objet schema.org ou une fonction qui retourne l'objet.
 *   Peut être un tableau pour injecter plusieurs blocs JSON-LD.
 */
export function useStructuredData(schemaData) {
  const scriptElements = []

  function injectSchema(data) {
    const script = document.createElement('script')
    script.type = 'application/ld+json'
    script.textContent = JSON.stringify(data)
    document.head.appendChild(script)
    scriptElements.push(script)
  }

  onMounted(() => {
    const data = typeof schemaData === 'function' ? schemaData() : schemaData
    if (Array.isArray(data)) {
      data.forEach(item => injectSchema(item))
    } else {
      injectSchema(data)
    }
  })

  onUnmounted(() => {
    scriptElements.forEach(el => {
      if (el.parentNode) el.parentNode.removeChild(el)
    })
    scriptElements.length = 0
  })
}

/**
 * Schéma pour la page d'accueil — WebSite + EducationalOrganization.
 */
export function getHomeSchema() {
  return [
    {
      '@context': 'https://schema.org',
      '@type': 'WebSite',
      name: 'U-map',
      alternateName: 'U-map UAC',
      url: BASE_URL,
      description: "Guide intelligent interactif du campus de l'Université d'Abomey-Calavi (UAC). Navigation GPS, plans d'intérieur et exploration gamifiée.",
      inLanguage: ['fr', 'en'],
      potentialAction: {
        '@type': 'SearchAction',
        target: {
          '@type': 'EntryPoint',
          urlTemplate: `${BASE_URL}/lieux?q={search_term_string}`
        },
        'query-input': 'required name=search_term_string'
      }
    },
    {
      '@context': 'https://schema.org',
      '@type': 'EducationalOrganization',
      name: "Université d'Abomey-Calavi",
      alternateName: 'UAC',
      url: 'https://www.uac.bj',
      logo: `${BASE_URL}/og-image.png`,
      address: {
        '@type': 'PostalAddress',
        streetAddress: 'Campus Universitaire',
        addressLocality: 'Abomey-Calavi',
        addressCountry: 'BJ'
      },
      geo: {
        '@type': 'GeoCoordinates',
        latitude: 6.4180,
        longitude: 2.3450
      },
      sameAs: [
        'https://www.uac.bj'
      ]
    }
  ]
}

/**
 * Schéma pour un lieu spécifique du campus.
 * @param {Object} place - Données du lieu (name, description, type, coordinates, openingHours)
 */
export function getPlaceSchema(place) {
  if (!place) return null
  return {
    '@context': 'https://schema.org',
    '@type': 'Place',
    name: place.name,
    description: place.description,
    url: `${BASE_URL}/lieu/${place.id}`,
    geo: {
      '@type': 'GeoCoordinates',
      latitude: place.coordinates?.[1] || 6.4180,
      longitude: place.coordinates?.[0] || 2.3450
    },
    address: {
      '@type': 'PostalAddress',
      streetAddress: `Campus UAC — ${place.name}`,
      addressLocality: 'Abomey-Calavi',
      addressCountry: 'BJ'
    },
    openingHoursSpecification: place.openingHours ? {
      '@type': 'OpeningHoursSpecification',
      description: place.openingHours
    } : undefined,
    isPartOf: {
      '@type': 'EducationalOrganization',
      name: "Université d'Abomey-Calavi"
    },
    additionalType: place.type
  }
}

/**
 * Schéma BreadcrumbList pour la navigation.
 * @param {Array<{name: string, url: string}>} items
 */
export function getBreadcrumbSchema(items) {
  return {
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    itemListElement: items.map((item, index) => ({
      '@type': 'ListItem',
      position: index + 1,
      name: item.name,
      item: item.url ? `${BASE_URL}${item.url}` : undefined
    }))
  }
}
