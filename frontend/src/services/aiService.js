/**
 * Service pour l'IA prédictive et d'assistance.
 */
class AIService {
    async askCampusAI(question) {
        try {
            const response = await fetch('http://localhost:8000/api/ai/ask', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ question })
            });
            const data = await response.json();
            return data.answer;
        } catch (e) {
            console.error("AI API Error:", e);
            return "Désolé, je n'arrive pas à contacter mon cerveau serveur.";
        }
    }

    /**
     * Suggère des lieux basés sur l'heure et le profil
     */
    getSmartSuggestions() {
        const hour = new Date().getHours();
        if (hour >= 11 && hour <= 14) {
            return "C'est l'heure du déjeuner ! Pourquoi ne pas faire un tour au Resto U ?";
        }
        if (hour >= 18) {
            return "Le campus s'anime le soir. Les jardins de l'UAC sont agréables pour se détendre.";
        }
        return "Bonne journée d'étude ! La BU est ouverte jusqu'à 20h.";
    }
}

export const aiService = new AIService();
