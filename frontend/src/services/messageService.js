import { authService } from './authService';

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

class MessageService {
    /**
     * Centralized fetch with auth and proper error handling.
     */
    async #apiCall(url, options = {}) {
        const token = authService.getToken();
        const headers = {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json',
            ...(options.headers || {}),
        };

        const response = await fetch(url, { ...options, headers });

        if (!response.ok) {
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
     * Récupère les messages d'une conversation avec un utilisateur.
     * Retourne { data: Message[], meta: {...} }
     */
    async getMessages(receiverId, page = 1, perPage = 50) {
        return this.#apiCall(
            `${API_URL}/messages/${receiverId}?page=${page}&per_page=${perPage}`
        );
    }

    /**
     * Récupère la liste des conversations actives.
     * Retourne { data: ConversationUser[], meta: {...} }
     */
    async getConversations() {
        return this.#apiCall(`${API_URL}/conversations`);
    }

    /**
     * Récupère le nombre de messages non lus.
     */
    async getUnreadCount() {
        if (!authService.getToken()) return { count: 0 };
        try {
            return await this.#apiCall(`${API_URL}/messages/unread-count`);
        } catch {
            return { count: 0 };
        }
    }

    /**
     * Envoie un message à un utilisateur.
     * Retourne l'objet Message créé (avec content déchiffré).
     */
    async sendMessage(receiverId, content) {
        const data = await this.#apiCall(`${API_URL}/messages`, {
            method: 'POST',
            body: JSON.stringify({ receiver_id: receiverId, content }),
        });
        // Backend wraps it in { message: {...} }
        return data.message || data;
    }

    /**
     * Récupère la liste des étudiants pour le modal "Nouvelle discussion".
     */
    async getStudents(page = 1, perPage = 100) {
        const data = await this.#apiCall(
            `${API_URL}/students?page=${page}&per_page=${perPage}`
        );
        return data.data || data;
    }
}

export const messageService = new MessageService();
