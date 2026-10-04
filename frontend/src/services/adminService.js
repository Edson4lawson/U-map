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
            'Accept': 'application/json',
            'Authorization': `Bearer ${this.getToken()}`
        };
    }

    async login(password) {
        const res = await fetch(`${API_URL}/admin/login`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ password })
        });
        if (!res.ok) {
            const err = await res.json().catch(() => ({}));
            throw new Error(err.message || 'Mot de passe incorrect. Veuillez réessayer.');
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
        if (!res.ok) throw new Error('Impossible de charger les statistiques. Veuillez vérifier votre connexion.');
        return res.json();
    }

    async getAnalytics(period = '30d') {
        const res = await fetch(`${API_URL}/admin/analytics?period=${encodeURIComponent(period)}`, { headers: this.headers() });
        if (!res.ok) throw new Error('Impossible de charger les données analytiques.');
        return res.json();
    }

    async getUsers(search = '') {
        const url = search ? `${API_URL}/admin/users?search=${encodeURIComponent(search)}` : `${API_URL}/admin/users`;
        const res = await fetch(url, { headers: this.headers() });
        if (!res.ok) throw new Error('Impossible de charger les utilisateurs. Veuillez réessayer.');
        return res.json();
    }

    async deleteUser(id) {
        const res = await fetch(`${API_URL}/admin/users/${id}`, {
            method: 'DELETE',
            headers: this.headers()
        });
        if (!res.ok) throw new Error('Impossible de supprimer cet utilisateur. Veuillez réessayer.');
        return res.json();
    }

    async toggleRestrictUser(id) {
        const res = await fetch(`${API_URL}/admin/users/${id}/restrict`, {
            method: 'PUT',
            headers: this.headers()
        });
        if (!res.ok) throw new Error('Impossible de modifier les restrictions. Veuillez réessayer.');
        return res.json();
    }

    async unbanUser(id) {
        const res = await fetch(`${API_URL}/admin/users/${id}/unban`, {
            method: 'POST',
            headers: this.headers()
        });
        if (!res.ok) throw new Error('Impossible de lever les sanctions. Veuillez réessayer.');
        return res.json();
    }

    async getUserSanctions(id) {
        const res = await fetch(`${API_URL}/admin/users/${id}/sanctions`, { headers: this.headers() });
        if (!res.ok) throw new Error('Impossible de charger la fiche utilisateur.');
        return res.json();
    }

    async getUserDossier(id) {
        return this.getUserSanctions(id);
    }

    async updateUserRole(id, role) {
        const res = await fetch(`${API_URL}/admin/users/${id}/role`, {
            method: 'PUT',
            headers: this.headers(),
            body: JSON.stringify({ role })
        });
        if (!res.ok) {
            const err = await res.json().catch(() => ({}));
            throw new Error(err.message || 'Impossible de mettre à jour le rôle.');
        }
        return res.json();
    }

    async forceLogoutUser(id) {
        const res = await fetch(`${API_URL}/admin/users/${id}/force-logout`, {
            method: 'POST',
            headers: this.headers()
        });
        if (!res.ok) {
            const err = await res.json().catch(() => ({}));
            throw new Error(err.message || 'Impossible de déconnecter l\'utilisateur.');
        }
        return res.json();
    }

    async resetUserPassword(id, new_password = null) {
        const res = await fetch(`${API_URL}/admin/users/${id}/reset-password`, {
            method: 'POST',
            headers: this.headers(),
            body: JSON.stringify({ new_password })
        });
        if (!res.ok) {
            const err = await res.json().catch(() => ({}));
            throw new Error(err.message || 'Impossible de réinitialiser le mot de passe.');
        }
        return res.json();
    }

    async updateUserNote(id, admin_note) {
        const res = await fetch(`${API_URL}/admin/users/${id}/note`, {
            method: 'POST',
            headers: this.headers(),
            body: JSON.stringify({ admin_note })
        });
        if (!res.ok) throw new Error('Impossible d\'enregistrer la note interne.');
        return res.json();
    }

    // ── Signalements & Modération ────────────────────────────────
    async getReports(params = {}) {
        const query = new URLSearchParams(params).toString();
        const url = query ? `${API_URL}/admin/reports?${query}` : `${API_URL}/admin/reports`;
        const res = await fetch(url, { headers: this.headers() });
        if (!res.ok) throw new Error('Impossible de charger les signalements. Veuillez réessayer.');
        return res.json();
    }

    async getReportContext(id) {
        const res = await fetch(`${API_URL}/admin/reports/${id}/context`, { headers: this.headers() });
        if (!res.ok) throw new Error('Impossible de charger le contexte du message.');
        return res.json();
    }

    async updateReportStatus(id, data) {
        const res = await fetch(`${API_URL}/admin/reports/${id}/status`, {
            method: 'PUT',
            headers: this.headers(),
            body: JSON.stringify(data)
        });
        if (!res.ok) throw new Error('Impossible de mettre à jour le signalement.');
        return res.json();
    }

    async resolveReport(id) {
        return this.updateReportStatus(id, { status: 'resolved' });
    }

    // ── Actions graduées ────────────────────────────────────────
    async warnUser({ user_id, reason, report_id }) {
        const res = await fetch(`${API_URL}/admin/moderation/warn`, {
            method: 'POST',
            headers: this.headers(),
            body: JSON.stringify({ user_id, reason, report_id })
        });
        if (!res.ok) throw new Error('Échec de l\'envoi de l\'avertissement.');
        return res.json();
    }

    async muteUser({ user_id, reason, duration_hours, report_id }) {
        const res = await fetch(`${API_URL}/admin/moderation/mute`, {
            method: 'POST',
            headers: this.headers(),
            body: JSON.stringify({ user_id, reason, duration_hours, report_id })
        });
        if (!res.ok) throw new Error('Échec de la mise en sourdine.');
        return res.json();
    }

    async suspendUser({ user_id, reason, duration_hours, report_id }) {
        const res = await fetch(`${API_URL}/admin/moderation/suspend`, {
            method: 'POST',
            headers: this.headers(),
            body: JSON.stringify({ user_id, reason, duration_hours, report_id })
        });
        if (!res.ok) throw new Error('Échec de la suspension du compte.');
        return res.json();
    }

    async banUser({ user_id, reason, report_id }) {
        const res = await fetch(`${API_URL}/admin/moderation/ban`, {
            method: 'POST',
            headers: this.headers(),
            body: JSON.stringify({ user_id, reason, report_id })
        });
        if (!res.ok) throw new Error('Échec du bannissement.');
        return res.json();
    }

    async deleteMessage(id) {
        const res = await fetch(`${API_URL}/admin/messages/${id}`, {
            method: 'DELETE',
            headers: this.headers()
        });
        if (!res.ok) throw new Error('Impossible de supprimer le message.');
        return res.json();
    }

    // ── Journal d'Audit ──────────────────────────────────────────
    async getAuditLogs(page = 1) {
        const res = await fetch(`${API_URL}/admin/audit-logs?page=${page}`, { headers: this.headers() });
        if (!res.ok) throw new Error('Impossible de charger le journal d\'audit.');
        return res.json();
    }

    // ── Lieux (Phase 2) ─────────────────────────────────────────
    async getPlaces(params = {}) {
        const query = new URLSearchParams(params).toString();
        const url = query ? `${API_URL}/admin/places?${query}` : `${API_URL}/admin/places`;
        const res = await fetch(url, { headers: this.headers() });
        if (!res.ok) throw new Error('Impossible de charger les lieux. Veuillez réessayer.');
        return res.json();
    }

    async updatePlace(id, placeData) {
        const res = await fetch(`${API_URL}/admin/places/${id}`, {
            method: 'PUT',
            headers: this.headers(),
            body: JSON.stringify(placeData)
        });
        if (!res.ok) {
            const data = await res.json().catch(() => ({}));
            throw new Error(data.message || 'Impossible de mettre à jour le lieu.');
        }
        return res.json();
    }

    async updatePlaceVisibility(id, status) {
        const res = await fetch(`${API_URL}/admin/places/${id}/visibility`, {
            method: 'PUT',
            headers: this.headers(),
            body: JSON.stringify({ status })
        });
        if (!res.ok) throw new Error('Impossible de changer la visibilité du lieu.');
        return res.json();
    }

    async getDuplicatePlaces() {
        const res = await fetch(`${API_URL}/admin/places/duplicates`, { headers: this.headers() });
        if (!res.ok) throw new Error('Impossible de charger la liste des doublons.');
        return res.json();
    }

    async regeneratePlaceDescription(id, autoSave = false) {
        const res = await fetch(`${API_URL}/admin/places/${id}/regenerate-ai`, {
            method: 'POST',
            headers: this.headers(),
            body: JSON.stringify({ auto_save: autoSave })
        });
        if (!res.ok) throw new Error('Impossible de générer la description par l\'IA.');
        return res.json();
    }

    async approvePlace(id) {
        const res = await fetch(`${API_URL}/admin/places/${id}/approve`, {
            method: 'PUT',
            headers: this.headers()
        });
        if (!res.ok) throw new Error('Impossible d\'approuver ce lieu. Veuillez réessayer.');
        return res.json();
    }

    async deletePlace(id) {
        const res = await fetch(`${API_URL}/admin/places/${id}`, {
            method: 'DELETE',
            headers: this.headers()
        });
        if (!res.ok) throw new Error('Impossible de supprimer ce lieu. Veuillez réessayer.');
        return res.json();
    }

    async getMessages() {
        const res = await fetch(`${API_URL}/admin/messages`, { headers: this.headers() });
        if (!res.ok) throw new Error('Impossible de charger les messages. Veuillez réessayer.');
        return res.json();
    }

    // ── Supervision & Système (Phase 5) ───────────────────────────
    async getSystemHealth() {
        const res = await fetch(`${API_URL}/admin/system/health`, { headers: this.headers() });
        if (!res.ok) throw new Error('Impossible de charger l\'état du système.');
        return res.json();
    }

    async getSystemLogs(params = {}) {
        const qs = new URLSearchParams(params).toString();
        const url = `${API_URL}/admin/system/logs${qs ? '?' + qs : ''}`;
        const res = await fetch(url, { headers: this.headers() });
        if (!res.ok) throw new Error('Impossible de charger les logs applicatifs.');
        return res.json();
    }

    async clearSystemLogs() {
        const res = await fetch(`${API_URL}/admin/system/logs`, {
            method: 'DELETE',
            headers: this.headers()
        });
        if (!res.ok) throw new Error('Impossible de vider les logs système.');
        return res.json();
    }

    async clearSystemCache() {
        const res = await fetch(`${API_URL}/admin/system/cache-clear`, {
            method: 'POST',
            headers: this.headers()
        });
        if (!res.ok) throw new Error('Impossible de vider le cache système.');
        return res.json();
    }

    async optimizeSystem() {
        const res = await fetch(`${API_URL}/admin/system/optimize`, {
            method: 'POST',
            headers: this.headers()
        });
        if (!res.ok) throw new Error('Impossible d\'optimiser le système.');
        return res.json();
    }

    // ── Assistant & Audit Campus IA (Phase 6) ─────────────────────
    async getCampusAudit() {
        const res = await fetch(`${API_URL}/admin/ai/campus-audit`, { headers: this.headers() });
        if (!res.ok) throw new Error('Impossible d\'exécuter l\'audit campus.');
        return res.json();
    }

    async generateCampusDigest() {
        const res = await fetch(`${API_URL}/admin/ai/campus-digest`, {
            method: 'POST',
            headers: this.headers()
        });
        if (!res.ok) throw new Error('Impossible de générer le bulletin campus IA.');
        return res.json();
    }
}

export const adminService = new AdminService();
