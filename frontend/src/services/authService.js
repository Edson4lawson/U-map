/**
 * Service pour la gestion de l'authentification.
 * Utilise localStorage pour stocker le token et Laravel Sanctum pour le backend.
 */
const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

class AuthService {
    async login(email, password) {
        const response = await fetch(`${API_URL}/login`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password })
        });
        
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Erreur de connexion');
        }

        const data = await response.json();
        localStorage.setItem('u_map_token', data.token);
        localStorage.setItem('u_map_user', JSON.stringify(data.user));
        return data.user;
    }

    async register(userData) {
        const response = await fetch(`${API_URL}/register`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(userData)
        });

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Erreur d\'inscription');
        }

        const data = await response.json();
        localStorage.setItem('u_map_token', data.token);
        localStorage.setItem('u_map_user', JSON.stringify(data.user));
        return data.user;
    }

    logout() {
        const token = localStorage.getItem('u_map_token');
        if (token) {
            fetch(`${API_URL}/logout`, {
                method: 'POST',
                headers: { 
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json' 
                }
            }).catch(e => console.error("Logout error:", e));
        }
        localStorage.removeItem('u_map_token');
        localStorage.removeItem('u_map_user');
    }

    isAuthenticated() {
        return !!localStorage.getItem('u_map_token');
    }

    getCurrentUser() {
        const user = localStorage.getItem('u_map_user');
        return user ? JSON.parse(user) : null;
    }

    getToken() {
        return localStorage.getItem('u_map_token');
    }
}

export const authService = new AuthService();
