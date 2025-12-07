// Service Worker for PWA
self.addEventListener('push', function(event) {
    console.log('[Service Worker] Push Received.');
    console.log(`[Service Worker] Push had this data: "${event.data ? event.data.text() : 'no data'}"`);

    let data = {};
    if (event.data) {
        try {
            data = event.data.json();
        } catch (e) {
            data = { title: 'New Notification', body: event.data.text() };
        }
    } else {
        // Fallback if no payload (if we can't implement encryption)
        // In a real app, we might fetch from API here
        data = {
            title: 'Proworker Chat',
            body: 'You have new messages.',
            icon: '/images/icons/icon-192x192.png'
        };
    }

    const title = data.title || 'New Message';
    const options = {
        body: data.body || 'You have a new message.',
        icon: data.icon || '/images/icons/icon-192x192.png',
        badge: '/images/icons/icon-96x96.png',
        data: { url: data.url || '/' }
    };

    // Play sound if provided in data (custom approach) or rely on system
    // Note: 'sound' option in Notification is deprecated/not supported in many browsers
    // We can try to open a windowclient and play sound if allowed, but background sound is restricted.

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function(event) {
    console.log('[Service Worker] Notification click Received.');
    event.notification.close();

    event.waitUntil(
        clients.matchAll({type: 'window'}).then(function(windowClients) {
            // Check if there is already a window/tab open with the target URL
            for (var i = 0; i < windowClients.length; i++) {
                var client = windowClients[i];
                // If so, just focus it.
                if (client.url === event.notification.data.url && 'focus' in client) {
                    return client.focus();
                }
            }
            // If not, then open the target URL in a new window/tab.
            if (clients.openWindow) {
                return clients.openWindow(event.notification.data.url);
            }
        })
    );
});
