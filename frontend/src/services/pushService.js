import { authService } from './authService';
import errorHandler from './errorHandler';

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';
const PROMPT_STORAGE_KEY = 'umap_push_prompted_timestamp';

class PushService {
    constructor() {
        this.vapidPublicKey = import.meta.env.VITE_VAPID_PUBLIC_KEY || null;
    }

    /**
     * Check if Web Push is supported by the current browser and platform.
     */
    isSupported() {
        return 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;
    }

    /**
     * Check if the device is running iOS.
     */
    isIOS() {
        return /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    }

    /**
     * Check if the web application is running in standalone mode (installed as PWA).
     */
    isStandalone() {
        return window.navigator.standalone === true || window.matchMedia('(display-mode: standalone)').matches;
    }

    /**
     * Convert VAPID base64 string to Uint8Array for PushManager subscription.
     */
    urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
        const base64 = (base64String + padding)
            .replace(/-/g, '+')
            .replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    /**
     * Fetch VAPID public key from backend if not defined in frontend env.
     */
    async getVapidPublicKey() {
        if (this.vapidPublicKey) {
            return this.vapidPublicKey;
        }

        try {
            const response = await fetch(`${API_URL}/vapid-public-key`);
            if (response.ok) {
                const data = await response.json();
                this.vapidPublicKey = data.publicKey;
                return this.vapidPublicKey;
            }
        } catch (e) {
            console.warn('Could not fetch VAPID public key from server:', e);
        }
        return null;
    }

    /**
     * Request Push Permission and subscribe if granted.
     * Triggered on meaningful user action (e.g. sending a message or opening a chat).
     * @param {boolean} silent - If true, avoids displaying errors or prompts when already denied.
     */
    async subscribeUser(silent = false) {
        if (!authService.isAuthenticated()) {
            return false;
        }

        // 1. Check iOS Safari constraint
        if (this.isIOS() && !this.isStandalone()) {
            if (!silent) {
                errorHandler.info(
                    "Sur iPhone/iPad, pour recevoir des notifications en arrière-plan, appuyez sur Partager ⎋ puis 'Sur l'écran d'accueil'.",
                    "Notifications iOS"
                );
            }
            return false;
        }

        // 2. Check general Push support
        if (!this.isSupported()) {
            if (!silent) {
                errorHandler.warning("Votre navigateur ne supporte pas les notifications Web Push.", "Non supporté");
            }
            return false;
        }

        // 3. If already denied, don't nag the user repeatedly
        if (Notification.permission === 'denied') {
            return false;
        }

        try {
            // 4. Request permission
            const permission = await Notification.requestPermission();
            localStorage.setItem(PROMPT_STORAGE_KEY, Date.now().toString());

            if (permission !== 'granted') {
                return false;
            }

            // 5. Get VAPID key
            const publicKey = await this.getVapidPublicKey();
            if (!publicKey) {
                console.warn('No VAPID public key available.');
                return false;
            }

            // 6. Get Service Worker registration
            const registration = await navigator.serviceWorker.ready;
            let subscription = await registration.pushManager.getSubscription();

            if (!subscription) {
                const convertedKey = this.urlBase64ToUint8Array(publicKey);
                subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: convertedKey,
                });
            }

            // 7. Send subscription to Laravel backend
            const token = authService.getToken();
            if (!token) return false;

            const subData = subscription.toJSON();
            const response = await fetch(`${API_URL}/push-subscriptions`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    endpoint: subscription.endpoint,
                    keys: {
                        p256dh: subData.keys?.p256dh,
                        auth: subData.keys?.auth,
                    },
                    contentEncoding: (PushManager.supportedContentEncodings || ['aesgcm'])[0],
                }),
            });

            if (response.ok) {
                return true;
            }
        } catch (e) {
            console.error('Error during WebPush subscription:', e);
        }

        return false;
    }

    /**
     * Prompt for push notifications on a meaningful user action (ex: after sending a message).
     * Only prompts once every 7 days if ignored.
     */
    async promptOnMeaningfulAction() {
        if (!authService.isAuthenticated()) return;
        if (!this.isSupported()) return;
        if (Notification.permission === 'denied' || Notification.permission === 'granted') {
            if (Notification.permission === 'granted') {
                await this.subscribeUser(true);
            }
            return;
        }

        // Check if recently prompted
        const lastPrompt = localStorage.getItem(PROMPT_STORAGE_KEY);
        if (lastPrompt) {
            const daysSince = (Date.now() - parseInt(lastPrompt, 10)) / (1000 * 60 * 60 * 24);
            if (daysSince < 7) {
                return;
            }
        }

        // Trigger subscription
        await this.subscribeUser(true);
    }

    /**
     * Unsubscribe current device from push notifications (e.g. on logout).
     */
    async unsubscribeUser() {
        if (!this.isSupported()) return;

        try {
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.getSubscription();
            if (subscription) {
                const endpoint = subscription.endpoint;
                const token = authService.getToken();

                if (token) {
                    await fetch(`${API_URL}/push-subscriptions`, {
                        method: 'DELETE',
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ endpoint }),
                    }).catch(() => {});
                }

                await subscription.unsubscribe();
            }
        } catch (e) {
            console.warn('Error unsubscribing push:', e);
        }
    }
}

export const pushService = new PushService();
