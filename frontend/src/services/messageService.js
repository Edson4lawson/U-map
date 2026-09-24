import { authService } from './authService';

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';
const CONVERSATIONS_CACHE_KEY = 'umap_conversations_cache';
const MESSAGES_CACHE_PREFIX = 'umap_messages_cache_';
const STUDENTS_CACHE_KEY = 'umap_students_cache';

class MessageService {
    constructor() {
        this.conversationsCache = null;
        this.messagesCache = new Map();
        this.studentsCache = null;
        this.loadInitialCache();
    }

    loadInitialCache() {
        try {
            const rawConvs = localStorage.getItem(CONVERSATIONS_CACHE_KEY);
            if (rawConvs) {
                this.conversationsCache = JSON.parse(rawConvs);
            }
            const rawStudents = localStorage.getItem(STUDENTS_CACHE_KEY);
            if (rawStudents) {
                this.studentsCache = JSON.parse(rawStudents);
            }
        } catch (e) {
            console.warn('Error reading message cache from storage:', e);
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

        const response = await fetch(url, { ...options, headers });

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
     * Récupère les messages d'une conversation avec un utilisateur (avec cache instantané).
     * Retourne { data: Message[], meta: {...} }
     */
    async getMessages(receiverId, page = 1, perPage = 50, forceRefresh = false) {
        const cacheKey = String(receiverId);

        // Si page 1 et cache présent, renvoyer immédiatement si pas de forceRefresh
        if (page === 1 && !forceRefresh) {
            let cached = this.messagesCache.get(cacheKey);
            if (!cached) {
                try {
                    const raw = localStorage.getItem(`${MESSAGES_CACHE_PREFIX}${cacheKey}`);
                    if (raw) {
                        cached = JSON.parse(raw);
                        this.messagesCache.set(cacheKey, cached);
                    }
                } catch {}
            }

            if (cached && Array.isArray(cached) && cached.length > 0) {
                // Revalider en arrière-plan sans bloquer
                this.revalidateMessages(receiverId, page, perPage);
                return { data: cached, fromCache: true };
            }
        }

        const res = await this.#apiCall(
            `${API_URL}/messages/${receiverId}?page=${page}&per_page=${perPage}`
        );

        const list = Array.isArray(res.data) ? res.data : (Array.isArray(res) ? res : []);
        if (page === 1 && list.length > 0) {
            this.messagesCache.set(cacheKey, list);
            try {
                localStorage.setItem(`${MESSAGES_CACHE_PREFIX}${cacheKey}`, JSON.stringify(list));
            } catch {}
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
                const cacheKey = String(receiverId);
                this.messagesCache.set(cacheKey, list);
                try {
                    localStorage.setItem(`${MESSAGES_CACHE_PREFIX}${cacheKey}`, JSON.stringify(list));
                } catch {}
            }
        } catch {}
    }

    /**
     * Récupère la liste des conversations actives (avec cache instantané).
     * Retourne { data: ConversationUser[], meta: {...} }
     */
    async getConversations(forceRefresh = false) {
        if (!forceRefresh && this.conversationsCache && Array.isArray(this.conversationsCache) && this.conversationsCache.length > 0) {
            this.revalidateConversations();
            return { data: this.conversationsCache, fromCache: true };
        }

        const res = await this.#apiCall(`${API_URL}/conversations`);
        const list = Array.isArray(res.data) ? res.data : (Array.isArray(res) ? res : []);
        this.conversationsCache = list;
        try {
            localStorage.setItem(CONVERSATIONS_CACHE_KEY, JSON.stringify(list));
        } catch {}
        return res;
    }

    async revalidateConversations() {
        try {
            const res = await this.#apiCall(`${API_URL}/conversations`);
            const list = Array.isArray(res.data) ? res.data : (Array.isArray(res) ? res : []);
            this.conversationsCache = list;
            try {
                localStorage.setItem(CONVERSATIONS_CACHE_KEY, JSON.stringify(list));
            } catch {}
        } catch {}
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
     * Met à jour le cache localement.
     */
    async sendMessage(receiverId, content) {
        const data = await this.#apiCall(`${API_URL}/messages`, {
            method: 'POST',
            body: JSON.stringify({ receiver_id: receiverId, content }),
        });
        const newMsg = data.message || data;

        // Mettre à jour le cache des messages
        const cacheKey = String(receiverId);
        const existing = this.messagesCache.get(cacheKey) || [];
        if (!existing.some(m => m.id === newMsg.id)) {
            const updated = [...existing, newMsg];
            this.messagesCache.set(cacheKey, updated);
            try {
                localStorage.setItem(`${MESSAGES_CACHE_PREFIX}${cacheKey}`, JSON.stringify(updated));
            } catch {}
        }

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
     * @param {number} messageId - ID du message
     * @param {string} targetLang - 'fr' ou 'en'
     * @returns {Promise<string>} - Texte traduit
     */
    async translateMessage(messageId, targetLang = 'fr') {
        const data = await this.#apiCall(`${API_URL}/messages/${messageId}/translate`, {
            method: 'POST',
            body: JSON.stringify({ target_lang: targetLang }),
        });
        return data.translated_text;
    }
}

export const messageService = new MessageService();

