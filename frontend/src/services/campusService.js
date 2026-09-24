import customPlaces from '../data/custom-places.json';

/**
 * Service pour la gestion des données du campus avec Cache Multi-Niveaux (Mémoire + LocalStorage + SWR).
 * Garantit un affichage instantané (0ms) lors de la navigation entre les pages.
 */
const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';
const CACHE_KEY = 'umap_places_cache_v2';
const CACHE_TTL_MS = 15 * 60 * 1000; // 15 minutes de validité pour le cache local

class CampusService {
    constructor() {
        this.useApi = true;
        this.placesCache = null; // Cache en mémoire vive
        this.pendingFetchPromise = null; // Déduplication des requêtes simultanées
        this.loadFromLocalStorage();
    }

    /**
     * Charge le cache depuis le localStorage au démarrage.
     */
    loadFromLocalStorage() {
        try {
            const raw = localStorage.getItem(CACHE_KEY);
            if (raw) {
                const parsed = JSON.parse(raw);
                if (Array.isArray(parsed.data) && parsed.data.length > 0) {
                    this.placesCache = parsed.data;
                }
            }
        } catch (e) {
            console.warn('Erreur lecture cache localStorage lieux:', e);
        }
    }

    /**
     * Sauvegarde les lieux dans le localStorage avec horodatage.
     */
    saveToLocalStorage(data) {
        try {
            if (Array.isArray(data) && data.length > 0) {
                this.placesCache = data;
                localStorage.setItem(CACHE_KEY, JSON.stringify({
                    data,
                    timestamp: Date.now()
                }));
            }
        } catch (e) {
            console.warn('Erreur écriture cache localStorage lieux:', e);
        }
    }

    /**
     * Vérifie si le cache localStorage est encore frais.
     */
    isCacheFresh() {
        try {
            const raw = localStorage.getItem(CACHE_KEY);
            if (!raw) return false;
            const parsed = JSON.parse(raw);
            return (Date.now() - (parsed.timestamp || 0)) < CACHE_TTL_MS;
        } catch {
            return false;
        }
    }

    /**
     * Récupère tous les lieux avec stratégie Stale-While-Revalidate (SWR).
     * - Si les lieux sont en cache (mémoire ou localStorage) : retourne IMMÉDIATEMENT (0ms) !
     * - Met à jour les données en arrière-plan sans bloquer l'interface.
     */
    async getAllPlaces(forceRefresh = false) {
        // 1. Si cache disponible et pas de forceRefresh, retourner instantanément
        if (!forceRefresh && this.placesCache && this.placesCache.length > 0) {
            // Si le cache est périmé, revalider en arrière-plan
            if (!this.isCacheFresh()) {
                this.revalidateInBackground();
            }
            return this.placesCache;
        }

        // 2. Si une requête réseau est déjà en cours, réutiliser sa promesse
        if (this.pendingFetchPromise) {
            return this.pendingFetchPromise;
        }

        // 3. Charger depuis le réseau
        this.pendingFetchPromise = this.getDBPlaces()
            .then(places => {
                if (places && places.length > 0) {
                    this.saveToLocalStorage(places);
                    return places;
                }
                // Fallback sur le cache existant ou customPlaces
                return this.placesCache || customPlaces.features || [];
            })
            .catch(err => {
                console.warn('Erreur réseau récupération lieux, utilisation du cache:', err);
                return this.placesCache || customPlaces.features || [];
            })
            .finally(() => {
                this.pendingFetchPromise = null;
            });

        return this.pendingFetchPromise;
    }

    /**
     * Revalidation silencieuse en arrière-plan (SWR).
     */
    async revalidateInBackground() {
        try {
            const fresh = await this.getDBPlaces();
            if (fresh && fresh.length > 0) {
                this.saveToLocalStorage(fresh);
            }
        } catch (e) {
            // Ignorer silencieusement les erreurs de fond
        }
    }

    /**
     * Invalide le cache après création ou modification d'un lieu.
     */
    invalidateCache() {
        this.placesCache = null;
        try {
            localStorage.removeItem(CACHE_KEY);
        } catch {}
    }

    /**
     * Récupère un lieu par son ID ou slug — cherche d'abord dans le cache en mémoire (0ms).
     */
    async getPlaceById(idOrSlug) {
        if (!idOrSlug) return null;
        const strId = idOrSlug.toString();

        // 1. Chercher dans le cache existant (Instantané)
        if (this.placesCache && this.placesCache.length > 0) {
            const found = this.placesCache.find(f => 
                (f.properties?.id && f.properties.id.toString() === strId) ||
                (f.id && f.id.toString() === strId) ||
                (f.properties?.slug && f.properties.slug === idOrSlug)
            );
            if (found) return found;
        }

        // 2. Chercher dans custom-places local
        const customPlace = customPlaces.features.find(f => 
            f.properties?.id?.toString() === strId || 
            f.properties?.slug === idOrSlug
        );
        if (customPlace) return customPlace;

        // 3. Fallback réseau ciblé si non trouvé en cache
        if (this.useApi) {
            try {
                const response = await fetch(`${API_URL}/places/${idOrSlug}`);
                if (response.ok) {
                    return await response.json();
                }
            } catch (e) {
                // silencieux
            }
        }

        return null;
    }

    /**
     * Récupère les lieux par catégorie (instantané grâce au cache).
     */
    async getPlacesByCategory(category) {
        const places = await this.getAllPlaces();
        return places.filter(f => f.properties?.category === category);
    }

    /**
     * Liste toutes les catégories uniques disponibles.
     */
    async getCategories() {
        const places = await this.getAllPlaces();
        const categories = places.map(f => f.properties?.category).filter(Boolean);
        return [...new Set(categories)];
    }

    /**
     * Recherche textuelle instantanée dans les lieux (en mémoire).
     */
    async searchPlaces(query) {
        if (!query) return [];
        const places = await this.getAllPlaces();
        const q = query.toLowerCase();
        return places.filter(f => 
            (f.properties?.name && f.properties.name.toLowerCase().includes(q)) || 
            (f.properties?.type && f.properties.type.toLowerCase().includes(q)) ||
            (f.properties?.tags && Array.isArray(f.properties.tags) && f.properties.tags.some(t => String(t).toLowerCase().includes(q)))
        );
    }

    /**
     * Crée un nouveau lieu via l'API et met à jour le cache.
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
            throw new Error(error.message || 'Impossible d\'ajouter ce lieu.');
        }

        const newPlace = await response.json();
        this.invalidateCache();
        return newPlace;
    }

    /**
     * Récupère tous les signalements en temps réel.
     */
    async getLiveReports() {
        try {
            const response = await fetch(`${API_URL}/live-reports`);
            return await response.json();
        } catch (e) {
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
            throw new Error(error.message || 'Impossible d\'envoyer ce signalement.');
        }

        return await response.json();
    }

    /**
     * Récupère les lieux depuis la base de données via API backend.
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
            return [];
        }
    }

    /**
     * Récupère les POI OpenStreetMap autour de l'UAC via API backend avec cache.
     */
    async getOSMPlaces(lat = 6.44100, lng = 2.35200, radius = 2000) {
        try {
            const response = await fetch(`${API_URL}/places/osm?lat=${lat}&lng=${lng}&radius=${radius}`);
            const data = await response.json();
            
            if (data && data.features && data.features.length > 0) {
                return data.features;
            }
            
            return [];
        } catch (e) {
            return [];
        }
    }
}

export const campusService = new CampusService();
