/**
 * Service Worker Registration Script
 * BOFETODA TODA Management System
 * 
 * This script registers the service worker and handles subscription to push notifications
 */

(function() {
    'use strict';
    
    // Check if service workers are supported
    if (!('serviceWorker' in navigator)) {
        console.warn('Service Workers not supported in this browser');
        return;
    }
    
    // Check if Push API is supported
    if (!('PushManager' in window)) {
        console.warn('Push notifications not supported in this browser');
        return;
    }
    
    // Configuration
    const SW_PATH = '/sw.js';
    const VAPID_PUBLIC_KEY = document.querySelector('meta[name="vapid-public-key"]')?.content;
    
    if (!VAPID_PUBLIC_KEY) {
        console.error('VAPID public key not found in page meta tags');
        return;
    }
    
    /**
     * Convert base64 VAPID key to Uint8Array
     */
    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');
        
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }
    
    /**
     * Register the service worker
     */
    async function registerServiceWorker() {
        try {
            const registration = await navigator.serviceWorker.register(SW_PATH, {
                scope: '/'
            });
            
            console.log('[SW Registration] Success:', registration.scope);
            
            // Wait for service worker to be ready
            await navigator.serviceWorker.ready;
            console.log('[SW Registration] Service worker ready');
            
            // Check if already subscribed
            const existingSubscription = await registration.pushManager.getSubscription();
            
            if (existingSubscription) {
                console.log('[SW Registration] Already subscribed to push notifications');
                window.pushSubscription = existingSubscription;
            } else {
                console.log('[SW Registration] Not yet subscribed to push notifications');
            }
            
            return registration;
            
        } catch (error) {
            console.error('[SW Registration] Failed:', error);
            throw error;
        }
    }
    
    /**
     * Subscribe to push notifications
     */
    async function subscribeToPush(registration) {
        try {
            // Request permission if not already granted
            let permission = Notification.permission;
            
            if (permission === 'default') {
                console.log('[Push Subscribe] Requesting permission...');
                permission = await Notification.requestPermission();
            }
            
            if (permission !== 'granted') {
                console.log('[Push Subscribe] Permission denied:', permission);
                return null;
            }
            
            console.log('[Push Subscribe] Permission granted');
            
            // Subscribe to push notifications
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY)
            });
            
            console.log('[Push Subscribe] Subscribed:', subscription.endpoint);
            
            // Send subscription to server
            const response = await fetch('/subscribe-push.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    subscription: subscription.toJSON()
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                console.log('[Push Subscribe] Subscription saved to server');
                window.pushSubscription = subscription;
                
                // Store subscription status in localStorage
                localStorage.setItem('pushSubscribed', 'true');
                localStorage.setItem('pushSubscribedAt', new Date().toISOString());
                
                // Dispatch event for app to handle
                window.dispatchEvent(new CustomEvent('pushSubscribed', {
                    detail: { subscription }
                }));
                
                return subscription;
            } else {
                console.error('[Push Subscribe] Server error:', result.error);
                return null;
            }
            
        } catch (error) {
            console.error('[Push Subscribe] Error:', error);
            
            // Store denial in localStorage to avoid repeated prompts
            if (error.name === 'NotAllowedError') {
                localStorage.setItem('pushDenied', 'true');
            }
            
            return null;
        }
    }
    
    /**
     * Unsubscribe from push notifications
     */
    async function unsubscribeFromPush() {
        try {
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.getSubscription();
            
            if (!subscription) {
                console.log('[Push Unsubscribe] No active subscription');
                return true;
            }
            
            // Unsubscribe from push service
            const success = await subscription.unsubscribe();
            
            if (success) {
                console.log('[Push Unsubscribe] Unsubscribed from push service');
                
                // Notify server
                await fetch('/unsubscribe-push.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        endpoint: subscription.endpoint
                    })
                });
                
                // Clear localStorage
                localStorage.removeItem('pushSubscribed');
                localStorage.removeItem('pushSubscribedAt');
                
                window.pushSubscription = null;
                
                // Dispatch event
                window.dispatchEvent(new Event('pushUnsubscribed'));
                
                return true;
            }
            
            return false;
            
        } catch (error) {
            console.error('[Push Unsubscribe] Error:', error);
            return false;
        }
    }
    
    /**
     * Check if user should be prompted for push permission
     */
    function shouldPromptForPush() {
        console.log('[shouldPromptForPush] Checking conditions...');
        
        // Don't prompt if already subscribed
        if (localStorage.getItem('pushSubscribed') === 'true') {
            console.log('[shouldPromptForPush] FALSE - Already subscribed');
            return false;
        }
        
        // Don't prompt if user denied permission
        if (localStorage.getItem('pushDenied') === 'true') {
            console.log('[shouldPromptForPush] FALSE - User previously denied');
            return false;
        }
        
        // Don't prompt if notification permission already denied
        if (Notification.permission === 'denied') {
            console.log('[shouldPromptForPush] FALSE - Browser permission denied');
            return false;
        }
        
        console.log('[shouldPromptForPush] TRUE - All checks passed');
        return true;
    }
    
    /**
     * Initialize push notifications
     */
    async function initializePushNotifications() {
        try {
            // Register service worker
            const registration = await registerServiceWorker();
            
            // Expose functions globally
            window.subscribeToPush = () => subscribeToPush(registration);
            window.unsubscribeFromPush = unsubscribeFromPush;
            window.shouldPromptForPush = shouldPromptForPush;
            
            console.log('[Push Init] Initialization complete');
            
            // Dispatch ready event
            window.dispatchEvent(new Event('pushNotificationsReady'));
            
            // Auto-prompt for subscription if appropriate
            if (shouldPromptForPush()) {
                console.log('[Push Init] Auto-prompting for push subscription');
                
                // Delay slightly to let page load complete
                setTimeout(async () => {
                    try {
                        const subscription = await subscribeToPush(registration);
                        
                        if (subscription) {
                            console.log('[Push Init] Auto-subscription successful');
                            
                            // Show success toast if available
                            if (window.Toast) {
                                Toast.success(
                                    'Notifications Enabled',
                                    'You will receive important updates and reminders.',
                                    3000
                                );
                            }
                        }
                    } catch (error) {
                        console.log('[Push Init] Auto-subscription declined or failed:', error.message);
                        
                        // If user denied, don't show error toast (they made a choice)
                        if (error.name !== 'NotAllowedError') {
                            if (window.Toast) {
                                Toast.info(
                                    'Notifications',
                                    'You can enable notifications anytime from your profile settings.',
                                    4000
                                );
                            }
                        }
                    }
                }, 1000); // 1 second delay
            }
            
        } catch (error) {
            console.error('[Push Init] Initialization failed:', error);
        }
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializePushNotifications);
    } else {
        initializePushNotifications();
    }
    
})();
