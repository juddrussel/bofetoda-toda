/**
 * Service Worker for Web Push Notifications
 * BOFETODA TODA Management System
 * 
 * This service worker handles push notifications and manages notification clicks
 */

// Service worker version - increment when updating to force re-registration
const SW_VERSION = '1.0.0';
const CACHE_NAME = 'bofetoda-notifications-v1';

// Install event - fired when service worker is first registered
self.addEventListener('install', (event) => {
    console.log('[Service Worker] Installing version:', SW_VERSION);
    // Skip waiting to activate immediately
    self.skipWaiting();
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
    console.log('[Service Worker] Activated version:', SW_VERSION);
    event.waitUntil(
        clients.claim() // Take control of all pages immediately
    );
});

// Push event - handle incoming push notifications
self.addEventListener('push', (event) => {
    console.log('[Service Worker] Push received');
    
    if (!event.data) {
        console.warn('[Service Worker] Push event has no data');
        return;
    }
    
    try {
        const data = event.data.json();
        console.log('[Service Worker] Push data:', data);
        
        const title = data.title || 'BOFETODA Notification';
        const options = {
            body: data.body || '',
            icon: data.icon || '/Admin_BOFETODA/LOGO_TODA.png',
            badge: data.badge || '/Admin_BOFETODA/LOGO_BOCAUE.png',
            image: data.image || null,
            data: {
                url: data.url || '/',
                notificationId: data.notificationId || null,
                timestamp: Date.now()
            },
            tag: data.tag || 'bofetoda-notification',
            requireInteraction: data.requireInteraction || false,
            vibrate: data.vibrate || [200, 100, 200],
            actions: data.actions || []
        };
        
        event.waitUntil(
            self.registration.showNotification(title, options)
        );
        
    } catch (error) {
        console.error('[Service Worker] Error parsing push data:', error);
        
        // Fallback notification
        event.waitUntil(
            self.registration.showNotification('BOFETODA Notification', {
                body: 'You have a new notification',
                icon: '/Admin_BOFETODA/LOGO_TODA.png'
            })
        );
    }
});

// Notification click event - handle user clicking on notification
self.addEventListener('notificationclick', (event) => {
    console.log('[Service Worker] Notification clicked:', event.notification.tag);
    
    event.notification.close(); // Close the notification
    
    const urlToOpen = event.notification.data?.url || '/';
    
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((clientList) => {
                // Check if there's already a window/tab open
                for (const client of clientList) {
                    if (client.url.includes(self.location.origin) && 'focus' in client) {
                        // Focus existing window and navigate to URL
                        return client.focus().then(() => {
                            if ('navigate' in client) {
                                return client.navigate(urlToOpen);
                            }
                        });
                    }
                }
                
                // No existing window, open a new one
                if (clients.openWindow) {
                    return clients.openWindow(urlToOpen);
                }
            })
    );
});

// Notification close event - track when user dismisses notification
self.addEventListener('notificationclose', (event) => {
    console.log('[Service Worker] Notification closed:', event.notification.tag);
    
    // Optional: Send analytics or update server about dismissed notification
    // This can be used to track notification engagement
});

// Message event - handle messages from the main application
self.addEventListener('message', (event) => {
    console.log('[Service Worker] Message received:', event.data);
    
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
    
    if (event.data && event.data.type === 'GET_VERSION') {
        event.ports[0].postMessage({ version: SW_VERSION });
    }
});

// Background sync event (future enhancement)
// Can be used to queue notifications when offline
self.addEventListener('sync', (event) => {
    console.log('[Service Worker] Background sync:', event.tag);
    
    if (event.tag === 'sync-notifications') {
        event.waitUntil(
            // Implement sync logic here if needed
            Promise.resolve()
        );
    }
});

console.log('[Service Worker] Loaded version:', SW_VERSION);
