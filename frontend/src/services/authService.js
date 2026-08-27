/**
 * Service pour la gestion de l'authentification.
 * Utilise localStorage pour stocker le token et Laravel Sanctum pour le backend.
 */
const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

class AuthService {
    /**
     * Méthode centralisée pour tous les appels API.
     * Gère uniformément les erreurs JSON/HTML et les statuts d'erreur.
     */
    async #apiCall(url, options = {}) {
        const response = await fetch(url, options);

        if (!response.ok) {
            // Token expiré → nettoyer la session
            if (response.status === 401 && options._skipAutoLogout !== true) {
                this.#clearSession();
                window.dispatchEvent(new CustomEvent('auth:expired'));
            }

            const contentType = response.headers.get('content-type');
            let errorMessage = `Erreur serveur (${response.status}). Veuillez réessayer.`;
            let extra = {};

            if (contentType?.includes('application/json')) {
                try {
                    const errData = await response.json();
                    errorMessage = errData.message || errorMessage;
                    extra = errData; // Garder captcha_required, etc.
                } catch {
                    // JSON parsing failed, keep default message
                }
            }

            const err = new Error(errorMessage);
            Object.assign(err, extra);
            throw err;
        }

        return response.json();
    }

    #clearSession() {
        localStorage.removeItem('u_map_token');
        localStorage.removeItem('u_map_user');
    }

    #authHeaders() {
        return {
            'Authorization': `Bearer ${this.getToken()}`,
            'Content-Type': 'application/json',
        };
    }

    // ── Login / Register ─────────────────────────────────────────

    async login(identifier, password, remember = false, captchaToken = null, captchaAnswer = null) {
        const body = { identifier, password, remember };
        if (captchaToken) {
            body.captcha_token = captchaToken;
            body.captcha_answer = captchaAnswer;
        }

        const data = await this.#apiCall(`${API_URL}/login`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body),
            _skipAutoLogout: true,
        });

        if (data.two_factor_required) return data;

        localStorage.setItem('u_map_token', data.token);
        localStorage.setItem('u_map_user', JSON.stringify(data.user));
        return data.user;
    }

    async register(userData) {
        const data = await this.#apiCall(`${API_URL}/register`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(userData),
            _skipAutoLogout: true,
        });
        localStorage.setItem('u_map_token', data.token);
        localStorage.setItem('u_map_user', JSON.stringify(data.user));
        return data.user;
    }

    logout() {
        const token = localStorage.getItem('u_map_token');
        if (token) {
            fetch(`${API_URL}/logout`, {
                method: 'POST',
                headers: this.#authHeaders(),
            }).catch(e => {
                // Silently ignore logout errors - user is logging out anyway
            });
        }
        this.#clearSession();
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

    updateCurrentUser(userData) {
        localStorage.setItem('u_map_user', JSON.stringify(userData));
    }

    // ── CAPTCHA ──────────────────────────────────────────────────

    async getCaptcha() {
        return this.#apiCall(`${API_URL}/captcha`);
    }

    // ── 2FA ─────────────────────────────────────────────────────

    async verify2fa(tempToken, code) {
        const data = await this.#apiCall(`${API_URL}/2fa/verify`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ temp_token: tempToken, code }),
            _skipAutoLogout: true,
        });
        localStorage.setItem('u_map_token', data.token);
        localStorage.setItem('u_map_user', JSON.stringify(data.user));
        return data.user;
    }

    async enable2fa() {
        return this.#apiCall(`${API_URL}/2fa/enable`, {
            method: 'POST',
            headers: this.#authHeaders(),
        });
    }

    async confirm2fa(code) {
        return this.#apiCall(`${API_URL}/2fa/confirm`, {
            method: 'POST',
            headers: this.#authHeaders(),
            body: JSON.stringify({ code }),
        });
    }

    async disable2fa(password) {
        return this.#apiCall(`${API_URL}/2fa/disable`, {
            method: 'POST',
            headers: this.#authHeaders(),
            body: JSON.stringify({ password }),
        });
    }

    // ── Magic Link ───────────────────────────────────────────────

    async sendMagicLink(email) {
        return this.#apiCall(`${API_URL}/magic-link`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email }),
        });
    }

    async loginWithMagicLink(token) {
        const data = await this.#apiCall(`${API_URL}/magic-link/login`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ token }),
            _skipAutoLogout: true,
        });
        localStorage.setItem('u_map_token', data.token);
        localStorage.setItem('u_map_user', JSON.stringify(data.user));
        return data.user;
    }

    // ── Social Login ─────────────────────────────────────────────

    async socialLogin(provider, credential, email = null, name = null) {
        const data = await this.#apiCall(`${API_URL}/social-login`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ provider, token: credential, email, name }),
            _skipAutoLogout: true,
        });
        localStorage.setItem('u_map_token', data.token);
        localStorage.setItem('u_map_user', JSON.stringify(data.user));
        return data.user;
    }

    // ── Device Sessions ──────────────────────────────────────────

    async getDevices() {
        return this.#apiCall(`${API_URL}/devices`, {
            headers: this.#authHeaders(),
        });
    }

    async revokeDevice(id) {
        return this.#apiCall(`${API_URL}/devices/${id}`, {
            method: 'DELETE',
            headers: this.#authHeaders(),
        });
    }

    // ── Profile ──────────────────────────────────────────────────

    async updateProfile(data) {
        const result = await this.#apiCall(`${API_URL}/profile`, {
            method: 'PUT',
            headers: this.#authHeaders(),
            body: JSON.stringify(data),
        });
        if (result.user) {
            this.updateCurrentUser(result.user);
        }
        return result;
    }

    async changePassword(currentPassword, password, passwordConfirmation) {
        return this.#apiCall(`${API_URL}/profile/password`, {
            method: 'PUT',
            headers: this.#authHeaders(),
            body: JSON.stringify({
                current_password: currentPassword,
                password,
                password_confirmation: passwordConfirmation,
            }),
        });
    }

    async deleteAccount(password) {
        return this.#apiCall(`${API_URL}/profile`, {
            method: 'DELETE',
            headers: this.#authHeaders(),
            body: JSON.stringify({ password }),
        });
    }

    // ── Password Reset ───────────────────────────────────────────

    async forgotPassword(email) {
        return this.#apiCall(`${API_URL}/forgot-password`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email }),
        });
    }

    async resetPassword(token, email, password, passwordConfirmation) {
        return this.#apiCall(`${API_URL}/reset-password`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ token, email, password, password_confirmation: passwordConfirmation }),
        });
    }
}

export const authService = new AuthService();
