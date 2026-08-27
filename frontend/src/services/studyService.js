const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

class StudyService {
    /**
     * Met à jour le statut et le lieu d'étude de l'étudiant connecté.
     */
    async updateStudyStatus(studyStatus, studyLocation) {
        const token = localStorage.getItem('u_map_token');
        const response = await fetch(`${API_URL}/users/study-status`, {
            method: 'PUT',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                study_status: studyStatus,
                study_location: studyLocation
            })
        });

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Impossible de mettre à jour votre statut. Veuillez réessayer.');
        }

        return await response.json();
    }

    /**
     * Récupère tous les étudiants en train d'étudier actuellement.
     */
    async getStudyBuddies() {
        const token = localStorage.getItem('u_map_token');
        try {
            const response = await fetch(`${API_URL}/study-buddies`, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            return await response.json();
        } catch (e) {
            // Silently return empty array - no user-facing error needed for background data
            return [];
        }
    }
}

export const studyService = new StudyService();
