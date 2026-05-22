/**
 * Service de télémétrie ultra-léger.
 * Permet de suivre l'utilisation de l'app sans compromettre la vie privée.
 * En production, enverrait les données vers une API (ex: Plausible, Mixpanel).
 */
class TelemetryService {
    trackEvent(name, properties = {}) {
        const payload = {
            event: name,
            properties,
            timestamp: new Date().toISOString(),
            url: window.location.href
        };

        // En développement, on log dans la console
        if (import.meta.env.DEV) {
            console.log(`[Telemetry] ${name}:`, properties);
        } else {
            // En production : fetch('/api/analytics', { method: 'POST', body: JSON.stringify(payload) })
        }
    }

    trackPageView(page) {
        this.trackEvent('page_view', { page });
    }
}

export const telemetryService = new TelemetryService();
