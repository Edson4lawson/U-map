/**
 * U-map Web Push Notification Handler
 * Imported into the Service Worker via importScripts
 */

self.addEventListener('push', (event) => {
    if (!event.data) {
        return;
    }

    let payload = {};
    try {
        payload = event.data.json();
    } catch (e) {
        payload = {
            title: '💬 Nouveau message U-map',
            body: event.data.text() || 'Vous avez reçu un nouveau message.'
        };
    }

    const title = payload.title || '💬 Nouveau message';
    const options = {
        body: payload.body || 'Vous avez reçu un nouveau message.',
        icon: payload.icon || '/pwa-192.png',
        badge: payload.badge || '/pwa-192.png',
        tag: payload.tag || 'umap-message',
        renotify: true,
        vibrate: [100, 50, 100],
        data: payload.data || { url: '/chat' },
        actions: [
            { action: 'open_chat', title: 'Ouvrir la discussion' }
        ]
    };

    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const targetUrl = (event.notification.data && event.notification.data.url) 
        ? event.notification.data.url 
        : '/chat';

    const fullUrl = new URL(targetUrl, self.location.origin).href;

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            // If an app tab is already open, focus it and navigate
            for (const client of clientList) {
                if (client.url.startsWith(self.location.origin) && 'focus' in client) {
                    if (client.url !== fullUrl && 'navigate' in client) {
                        client.navigate(fullUrl);
                    }
                    return client.focus();
                }
            }
            // Otherwise, open a new window
            if (clients.openWindow) {
                return clients.openWindow(fullUrl);
            }
        })
    );
});
