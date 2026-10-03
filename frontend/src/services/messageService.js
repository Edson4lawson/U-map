import { authService } from './authService';
import { chatDatabase } from '../db/chatDatabase';

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';
const STUDENTS_CACHE_KEY = 'umap_students_cache';

class MessageService {
    constructor() {
        this.studentsCache = null;
        this.loadInitialCache();
    }

    loadInitialCache() {
        try {
            const rawStudents = localStorage.getItem(STUDENTS_CACHE_KEY);
            if (rawStudents) {
                this.studentsCache = JSON.parse(rawStudents);
            }
        } catch (e) {
            console.warn('Error reading student cache from storage:', e);
        }
    }

    /**
     * Ensure IndexedDB is scoped strictly to the authenticated user.
     */
    async ensureUserScope() {
        const user = authService.getCurrentUser();
        if (user && user.id) {
            await chatDatabase.ensureUserIsolation(user.id);
        }
    }

    /**
     * Centralized fetch with auth and proper error handling.
     */
    async #apiCall(url, options = {}) {
        const token = authService.getToken();
        if (!token) {
            throw new Error('Non authentifié');
        }

        const headers = {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            ...(options.headers || {}),
        };

        let response;
        try {
            response = await fetch(url, { ...options, headers });
        } catch (netErr) {
            if (!navigator.onLine || netErr.message?.toLowerCase().includes('failed to fetch') || netErr.name === 'TypeError') {
                throw new Error('Pas de connexion internet. Vérifiez votre réseau.');
            }
            throw netErr;
        }

        if (!response.ok) {
            if (response.status === 401) {
                authService.logout();
                window.dispatchEvent(new CustomEvent('auth:expired'));
            }

            let errorMessage = `Erreur réseau (${response.status})`;
            try {
                const errData = await response.json();
                errorMessage = errData.message || errData.error || errorMessage;
            } catch {
                // Could not parse JSON error
            }
            throw new Error(errorMessage);
        }

        return response.json();
    }

    /**
     * Instant local retrieval of cached messages from IndexedDB.
     */
    async getLocalCachedMessages(receiverId, limit = 100) {
        await this.ensureUserScope();
        return await chatDatabase.getCachedMessages(receiverId, limit);
    }

    /**
     * Instant local retrieval of cached conversations from IndexedDB.
     */
    async getLocalCachedConversations() {
        await this.ensureUserScope();
        return await chatDatabase.getCachedConversations();
    }

    /**
     * Récupère les messages d'une conversation avec un utilisateur.
     * Pour la page 1 : retourne immédiatement les données IndexedDB si disponibles (0ms),
     * puis met à jour IndexedDB dès la réponse réseau.
     * Pour la page > 1 : bascule directement sur l'API réseau pour charger l'historique complet.
     */
    async getMessages(receiverId, page = 1, perPage = 50, forceRefresh = false) {
        await this.ensureUserScope();

        // 1. Page 1 + pas de forceRefresh : tentative de lecture instantanée IndexedDB
        if (page === 1 && !forceRefresh) {
            const cached = await chatDatabase.getCachedMessages(receiverId, 100);
            if (cached && cached.length > 0) {
                // Revalidation en arrière-plan sans bloquer
                this.revalidateMessages(receiverId, page, perPage);
                return { data: cached, fromCache: true };
            }
        }

        // 2. Appel réseau (Page > 1 ou forceRefresh ou cache vide)
        const res = await this.#apiCall(
            `${API_URL}/messages/${receiverId}?page=${page}&per_page=${perPage}`
        );

        const list = Array.isArray(res.data) ? res.data : (Array.isArray(res) ? res : []);
        if (list.length > 0) {
            // Upsert merge dans IndexedDB (ne remplace pas aveuglément)
            await chatDatabase.saveMessages(receiverId, list, 100);
        }

        return res;
    }

    async revalidateMessages(receiverId, page = 1, perPage = 50) {
        try {
            const res = await this.#apiCall(
                `${API_URL}/messages/${receiverId}?page=${page}&per_page=${perPage}`
            );
            const list = Array.isArray(res.data) ? res.data : (Array.isArray(res) ? res : []);
            if (list.length > 0) {
                await chatDatabase.saveMessages(receiverId, list, 100);
            }
        } catch (e) {
            // Revalidation silencieuse en tâche de fond
        }
    }

    /**
     * Récupère la liste des conversations actives.
     * Affiche immédiatement ce qui est en IndexedDB (0ms), puis synchronise avec le réseau.
     */
    async getConversations(forceRefresh = false) {
        await this.ensureUserScope();

        if (!forceRefresh) {
            const cached = await chatDatabase.getCachedConversations();
            if (cached && cached.length > 0) {
                this.revalidateConversations();
                return { data: cached, fromCache: true };
            }
        }

        const res = await this.#apiCall(`${API_URL}/conversations`);
        const list = Array.isArray(res.data) ? res.data : (Array.isArray(res) ? res : []);
        if (list.length > 0) {
            await chatDatabase.saveConversations(list);
        }
        return res;
    }

    async revalidateConversations() {
        try {
            const res = await this.#apiCall(`${API_URL}/conversations`);
            const list = Array.isArray(res.data) ? res.data : (Array.isArray(res) ? res : []);
            if (list.length > 0) {
                await chatDatabase.saveConversations(list);
            }
        } catch (e) {
            // Revalidation silencieuse
        }
    }

    /**
     * Récupère le nombre de messages non lus.
     */
    async getUnreadCount() {
        const token = authService.getToken();
        if (!token) return { count: 0 };
        
        try {
            const response = await fetch(`${API_URL}/messages/unread-count`, {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json',
                },
            });
            
            if (response.status === 401) {
                return { count: 0 };
            }
            
            if (!response.ok) {
                return { count: 0 };
            }
            
            return await response.json();
        } catch {
            return { count: 0 };
        }
    }

    /**
     * Envoie un message à un utilisateur.
     * Met à jour IndexedDB localement immédiatement (upsert merge).
     */
    async sendMessage(receiverId, content) {
        const data = await this.#apiCall(`${API_URL}/messages`, {
            method: 'POST',
            body: JSON.stringify({ receiver_id: receiverId, content }),
        });
        const newMsg = data.message || data;

        // Persistance immédiate dans Dexie
        await chatDatabase.saveSingleMessage(receiverId, newMsg, 100);

        return newMsg;
    }

    /**
     * Récupère la liste des étudiants pour le modal "Nouvelle discussion".
     */
    async getStudents(page = 1, perPage = 100, forceRefresh = false) {
        if (!forceRefresh && this.studentsCache && this.studentsCache.length > 0) {
            this.revalidateStudents(page, perPage);
            return this.studentsCache;
        }

        const data = await this.#apiCall(
            `${API_URL}/students?page=${page}&per_page=${perPage}`
        );
        const list = data.data || data || [];
        this.studentsCache = list;
        try {
            localStorage.setItem(STUDENTS_CACHE_KEY, JSON.stringify(list));
        } catch {}
        return list;
    }

    async revalidateStudents(page = 1, perPage = 100) {
        try {
            const data = await this.#apiCall(
                `${API_URL}/students?page=${page}&per_page=${perPage}`
            );
            const list = data.data || data || [];
            this.studentsCache = list;
            try {
                localStorage.setItem(STUDENTS_CACHE_KEY, JSON.stringify(list));
            } catch {}
        } catch {}
    }

    /**
     * Traduit un message via le backend (DeepL/MyMemory avec cache BDD).
     */
    async translateMessage(messageId, targetLang = 'fr') {
        const data = await this.#apiCall(`${API_URL}/messages/${messageId}/translate`, {
            method: 'POST',
            body: JSON.stringify({ target_lang: targetLang }),
        });
        return data.translated_text;
    }

    /**
     * Clear all local chat cache on logout.
     */
    async clearLocalCache() {
        await chatDatabase.clearAllChatData();
        this.studentsCache = null;
        localStorage.removeItem(STUDENTS_CACHE_KEY);
    }
}

export const messageService = new MessageService();
