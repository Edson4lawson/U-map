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
                if (data && data.features && data.features.length > 0) {
                    return data.features;
                }
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
            throw new Error(error.message || 'Erreur lors de la création du lieu');
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
            console.error("Error fetching live reports:", e);
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
            throw new Error(error.message || 'Erreur lors de la création du signalement');
        }

        return await response.json();
    }
}

export const campusService = new CampusService();
