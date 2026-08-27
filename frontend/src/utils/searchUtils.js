/**
 * Utilitaires de recherche avancée avec normalisation et scoring
 */

/**
 * Normalise une chaîne de caractères pour la recherche floue
 * - Convertit en minuscules
 * - Supprime les accents (é → e, à → a, etc.)
 * - Supprime les espaces, tirets, underscores et ponctuation
 * - Réduit les espaces multiples
 */
export function normalizeText(text) {
  if (!text) return ''
  
  return text
    .toLowerCase()
    .normalize('NFD') // Décompose les caractères accentués
    .replace(/[\u0300-\u036f]/g, '') // Supprime les diacritiques
    .replace(/[\s\-_.,;:!?'"()]/g, '') // Supprime espaces, tirets, underscores, ponctuation
    .replace(/\s+/g, '') // Réduit les espaces multiples
}

/**
 * Calcule un score de pertinence pour une correspondance de recherche
 * Plus le score est élevé, plus la correspondance est pertinente
 */
export function calculateRelevanceScore(place, normalizedQuery, originalQuery) {
  let score = 0
  
  const props = place.properties || place
  const name = props.name || ''
  const description = props.description || ''
  const category = props.category || ''
  const type = props.type || ''
  const tags = Array.isArray(props.tags) ? props.tags : []
  
  const normalizedName = normalizeText(name)
  const normalizedCategory = normalizeText(category)
  const normalizedType = normalizeText(type)
  const normalizedTags = tags.map(t => normalizeText(String(t || ''))).join(' ')
  const normalizedDescription = normalizeText(description)
  
  const q = normalizedQuery
  const qOriginal = originalQuery.toLowerCase()
  
  // 1. Correspondance exacte du nom (score maximal)
  if (name.toLowerCase() === qOriginal) {
    score += 100
  }
  
  // 2. Correspondance exacte après normalisation du nom
  if (normalizedName === q) {
    score += 90
  }
  
  // 3. Le nom commence par la requête (après normalisation)
  if (normalizedName.startsWith(q)) {
    score += 80
  }
  
  // 4. La requête est contenue dans le nom (après normalisation)
  if (normalizedName.includes(q)) {
    score += 70
  }
  
  // 5. Correspondance dans la catégorie
  if (normalizedCategory === q) {
    score += 60
  } else if (normalizedCategory.includes(q)) {
    score += 50
  }
  
  // 6. Correspondance dans le type
  if (normalizedType === q) {
    score += 55
  } else if (normalizedType.includes(q)) {
    score += 45
  }
  
  // 7. Correspondance dans les tags
  if (normalizedTags.includes(q)) {
    score += 40
  }
  
  // 8. Correspondance dans la description
  if (normalizedDescription.includes(q)) {
    score += 30
  }
  
  // 9. Correspondance partielle dans le nom (mots séparés)
  const queryWords = q.split('').filter(c => c.trim())
  if (queryWords.length > 1) {
    let wordMatchCount = 0
    queryWords.forEach(word => {
      if (normalizedName.includes(word)) wordMatchCount++
    })
    if (wordMatchCount > 0) {
      score += 20 * (wordMatchCount / queryWords.length)
    }
  }
  
  return score
}

/**
 * Pré-calcule les versions normalisées des lieux pour optimiser la recherche
 */
export function preprocessPlaces(places) {
  return places.map(place => {
    const props = place.properties || place
    return {
      ...place,
      _searchIndex: {
        name: normalizeText(props.name || ''),
        description: normalizeText(props.description || ''),
        category: normalizeText(props.category || ''),
        type: normalizeText(props.type || ''),
        tags: Array.isArray(props.tags) 
          ? props.tags.map(t => normalizeText(String(t || ''))).join(' ')
          : ''
      }
    }
  })
}

/**
 * Recherche avancée avec normalisation et scoring
 */
export function searchPlaces(places, query) {
  if (!query || query.trim() === '') {
    return places
  }
  
  const normalizedQuery = normalizeText(query)
  const originalQuery = query.trim()
  
  // Calculer le score pour chaque lieu
  const scoredPlaces = places.map(place => {
    const score = calculateRelevanceScore(place, normalizedQuery, originalQuery)
    return { place, score }
  })
  
  // Filtrer les lieux avec un score > 0 et trier par score décroissant
  return scoredPlaces
    .filter(item => item.score > 0)
    .sort((a, b) => b.score - a.score)
    .map(item => item.place)
}
