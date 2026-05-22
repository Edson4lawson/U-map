import campusData from '../data/campus.json';

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
     */
    async getAllPlaces() {
        if (this.useApi) {
            try {
                const response = await fetch(`${API_URL}/places`);
                const data = await response.json();
                return data.features;
            } catch (e) {
                console.warn("API Error, falling back to local data");
            }
        }
        return campusData.features;
    }

    /**
     * Récupère un lieu par son ID — API en priorité, JSON local en fallback.
     */
    async getPlaceById(id) {
        if (this.useApi) {
            try {
                const response = await fetch(`${API_URL}/places`);
                const data = await response.json();
                const found = data.features?.find(f => f.properties.id?.toString() === id.toString());
                if (found) return found;
            } catch (e) {
                console.warn('getPlaceById: API Error, falling back to local data');
            }
        }
        // Fallback JSON local
        return campusData.features.find(f => f.properties.id.toString() === id.toString());
    }

    /**
     * Récupère les lieux par catégorie.
     */
    getPlacesByCategory(category) {
        return campusData.features.filter(f => f.properties.category === category);
    }

    /**
     * Liste toutes les catégories uniques disponibles.
     */
    getCategories() {
        const categories = campusData.features.map(f => f.properties.category);
        return [...new Set(categories)];
    }

    /**
     * Recherche textuelle dans les lieux.
     */
    searchPlaces(query) {
        if (!query) return [];
        const q = query.toLowerCase();
        return campusData.features.filter(f => 
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
            throw new Error(error.message || 'Erreur lors de la création du lieu');
        }

        return await response.json();
    }
}

export const campusService = new CampusService();
