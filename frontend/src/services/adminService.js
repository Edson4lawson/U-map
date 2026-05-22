/**
 * Service pour l'administration U-Map.
 * Gère l'authentification admin et les appels API du dashboard.
 */
const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

class AdminService {
    getToken() {
        return localStorage.getItem('u_map_admin_token');
    }

    setToken(token) {
        localStorage.setItem('u_map_admin_token', token);
    }

    isAuthenticated() {
        return !!this.getToken();
    }

    logout() {
        localStorage.removeItem('u_map_admin_token');
    }

    headers() {
        return {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${this.getToken()}`
        };
    }

    async login(password) {
        const res = await fetch(`${API_URL}/admin/login`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ password })
        });
        if (!res.ok) {
            const err = await res.json();
            throw new Error(err.message || 'Mot de passe incorrect.');
        }
        const data = await res.json();
        this.setToken(data.token);
        return data;
    }

    async verify() {
        const res = await fetch(`${API_URL}/admin/verify`, { headers: this.headers() });
        return res.ok;
    }

    async getStats() {
        const res = await fetch(`${API_URL}/admin/stats`, { headers: this.headers() });
        if (!res.ok) throw new Error('Impossible de charger les statistiques.');
        return res.json();
    }

    async getUsers() {
        const res = await fetch(`${API_URL}/admin/users`, { headers: this.headers() });
        if (!res.ok) throw new Error('Impossible de charger les utilisateurs.');
        return res.json();
    }

    async deleteUser(id) {
        const res = await fetch(`${API_URL}/admin/users/${id}`, {
            method: 'DELETE',
            headers: this.headers()
        });
        if (!res.ok) throw new Error('Erreur lors de la suppression.');
        return res.json();
    }

    async getPlaces() {
        const res = await fetch(`${API_URL}/admin/places`, { headers: this.headers() });
        if (!res.ok) throw new Error('Impossible de charger les lieux.');
        return res.json();
    }

    async approvePlace(id) {
        const res = await fetch(`${API_URL}/admin/places/${id}/approve`, {
            method: 'PUT',
            headers: this.headers()
        });
        if (!res.ok) throw new Error('Erreur lors de l\'approbation.');
        return res.json();
    }

    async deletePlace(id) {
        const res = await fetch(`${API_URL}/admin/places/${id}`, {
            method: 'DELETE',
            headers: this.headers()
        });
        if (!res.ok) throw new Error('Erreur lors de la suppression.');
        return res.json();
    }

    async getMessages() {
        const res = await fetch(`${API_URL}/admin/messages`, { headers: this.headers() });
        if (!res.ok) throw new Error('Impossible de charger les messages.');
        return res.json();
    }
}

export const adminService = new AdminService();
