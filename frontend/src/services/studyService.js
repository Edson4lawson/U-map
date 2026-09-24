import { authService } from './authService';

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

class StudyService {
    /**
     * Met à jour le statut et le lieu d'étude de l'étudiant connecté.
     */
    async updateStudyStatus(studyStatus, studyLocation) {
        const token = authService.getToken();
        if (!token) return null;

        const response = await fetch(`${API_URL}/users/study-status`, {
            method: 'PUT',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                study_status: studyStatus,
                study_location: studyLocation
            })
        });

        if (!response.ok) {
            if (response.status === 401) {
                authService.logout();
                window.dispatchEvent(new CustomEvent('auth:expired'));
            }
            let errorMessage = 'Impossible de mettre à jour votre statut. Veuillez réessayer.';
            try {
                const error = await response.json();
                errorMessage = error.message || errorMessage;
            } catch {}
            throw new Error(errorMessage);
        }

        return await response.json();
    }

    /**
     * Récupère tous les étudiants en train d'étudier actuellement.
     */
    async getStudyBuddies() {
        const token = authService.getToken();
        if (!token) return [];

        try {
            const response = await fetch(`${API_URL}/study-buddies`, {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                }
            });

            if (response.status === 401) {
                authService.logout();
                window.dispatchEvent(new CustomEvent('auth:expired'));
                return [];
            }

            if (!response.ok) return [];

            return await response.json();
        } catch (e) {
            return [];
        }
    }
}

export const studyService = new StudyService();
