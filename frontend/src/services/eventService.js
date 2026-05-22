const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

class EventService {
    constructor() {
        this.events = [];
    }

    async fetchEvents() {
        try {
            const response = await fetch(`${API_URL}/events`);
            this.events = await response.json();
            return this.events;
        } catch (e) {
            console.error('Erreur chargement événements:', e);
            return [];
        }
    }

    getEvents() {
        return this.events;
    }

    getEventsForPlace(placeId) {
        return this.events.filter(e => e.place_id?.toString() === placeId.toString());
    }

    getUpcomingEvents() {
        return this.events.filter(e => new Date(e.start_time) > new Date());
    }
}

export const eventService = new EventService();
