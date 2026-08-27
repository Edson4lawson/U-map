import customPlaces from '../data/custom-places.json';

/**
 * Service pour la gestion des données du campus.
 * Centralise les accès aux lieux, catégories et filtrage.
 */
const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

class CampusService {
    constructor() {
        this.useApi = true; // API activée !
    }

    /**
     * Récupère tous les lieux (Features du GeoJSON).
     * Utilise l'API backend pour les données de la base de données.
     */
    async getAllPlaces() {
        // Récupérer les lieux depuis l'API backend (base de données)
        const dbPlaces = await this.getDBPlaces();
        
        return dbPlaces;
    }

    /**
     * Récupère un lieu par son ID ou slug — cherche dans custom-places puis API.
     */
    async getPlaceById(idOrSlug) {
        // Chercher d'abord dans les lieux personnalisés
        const customPlace = customPlaces.features.find(f => f.properties.id.toString() === idOrSlug.toString() || f.properties.slug === idOrSlug);
        if (customPlace) {
            return customPlace;
        }
        
        // Sinon chercher via API
        if (this.useApi) {
            try {
                const response = await fetch(`${API_URL}/places/${idOrSlug}`);
                if (response.ok) {
                    return await response.json();
                }
            } catch (e) {
                // Silently fall back to null - no user-facing error needed for this
            }
        }
        
        return null;
    }

    /**
     * Récupère les lieux par catégorie.
     */
    async getPlacesByCategory(category) {
        const places = await this.getAllPlaces();
        return places.filter(f => f.properties.category === category);
    }

    /**
     * Liste toutes les catégories uniques disponibles.
     */
    async getCategories() {
        const places = await this.getAllPlaces();
        const categories = places.map(f => f.properties.category);
        return [...new Set(categories)];
    }

    /**
     * Recherche textuelle dans les lieux.
     */
    async searchPlaces(query) {
        if (!query) return [];
        const places = await this.getAllPlaces();
        const q = query.toLowerCase();
        return places.filter(f => 
            f.properties.name.toLowerCase().includes(q) || 
            f.properties.type.toLowerCase().includes(q) ||
            (f.properties.tags && f.properties.tags.some(t => t.toLowerCase().includes(q)))
        );
    }

    /**
     * Crée un nouveau lieu via l'API.
     */
    async createPlace(placeData) {
        const token = localStorage.getItem('u_map_token');
        const response = await fetch(`${API_URL}/places`, {
            method: 'POST',
            headers: { 
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json' 
            },
            body: JSON.stringify(placeData)
        });

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Impossible d\'ajouter ce lieu. Veuillez vérifier votre connexion et réessayer.');
        }

        return await response.json();
    }

    /**
     * Récupère tous les signalements en temps réel.
     */
    async getLiveReports() {
        try {
            const response = await fetch(`${API_URL}/live-reports`);
            return await response.json();
        } catch (e) {
            // Silently return empty array - no user-facing error needed for background data
            return [];
        }
    }

    /**
     * Crée un signalement en direct.
     */
    async createLiveReport(reportData) {
        const token = localStorage.getItem('u_map_token');
        const response = await fetch(`${API_URL}/live-reports`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(reportData)
        });

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Impossible d\'envoyer ce signalement. Veuillez vérifier votre connexion et réessayer.');
        }

        return await response.json();
    }

    /**
     * Récupère les lieux depuis la base de données via API backend.
     * Retourne les lieux sous format GeoJSON compatible.
     */
    async getDBPlaces() {
        try {
            const token = localStorage.getItem('u_map_token');
            const headers = {};
            if (token) {
                headers['Authorization'] = `Bearer ${token}`;
            }
            const response = await fetch(`${API_URL}/places`, { headers });
            const data = await response.json();
            
            if (data && data.features && data.features.length > 0) {
                return data.features;
            }
            
            return [];
        } catch (e) {
            // Silently return empty array - no user-facing error needed for background data
            return [];
        }
    }

    /**
     * Récupère les POI OpenStreetMap autour de l'UAC via API backend avec cache.
     * Retourne les lieux sous format GeoJSON compatible.
     */
    async getOSMPlaces(lat = 6.44100, lng = 2.35200, radius = 2000) {
        try {
            // Utiliser l'API backend avec cache au lieu d'appeler Overpass directement
            const response = await fetch(`${API_URL}/places/osm?lat=${lat}&lng=${lng}&radius=${radius}`);
            const data = await response.json();
            
            if (data && data.features && data.features.length > 0) {
                return data.features;
            }
            
            return [];
        } catch (e) {
            // Silently return empty array - no user-facing error needed for background data
            return [];
        }
    }

    /**
     * Catégorise un type OSM en catégorie U-Map
     */
    categorizeOSMType(tags) {
        const amenity = tags?.amenity || '';
        const building = tags?.building || '';

        if (['university', 'college', 'school'].includes(amenity) || 
            ['university', 'school'].includes(building)) {
            return 'faculty';
        }

        if (amenity === 'library') {
            return 'library';
        }

        if (['cafe', 'restaurant', 'fast_food', 'bar'].includes(amenity)) {
            return amenity === 'cafe' ? 'cafe' : 'restaurant';
        }

        if (amenity === 'bank') {
            return 'bank';
        }

        if (amenity === 'atm') {
            return 'atm';
        }

        if (amenity === 'fuel') {
            return 'fuel';
        }

        if (['hospital', 'pharmacy', 'clinic'].includes(amenity)) {
            return 'Santé';
        }

        if (amenity === 'parking') {
            return 'Parking';
        }

        if (building === 'dormitory') {
            return 'dormitory';
        }

        if (building === 'greenhouse') {
            return 'greenhouse';
        }

        return 'building';
    }
}

export const campusService = new CampusService();
