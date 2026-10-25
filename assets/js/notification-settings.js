/**
 * Notification Settings UI Component
 * BOFETODA TODA Management System
 * 
 * Provides UI for managing push notification preferences
 */

(function() {
    'use strict';
    
    /**
     * Create notification settings UI
     */
    function createNotificationSettingsUI() {
        // Wait for push notifications to be ready
        window.addEventListener('pushNotificationsReady', function() {
            updateNotificationStatus();
        });
        
        // Check initial status
        setTimeout(updateNotificationStatus, 500);
    }
    
    /**
     * Update notification status display
     */
    async function updateNotificationStatus() {
        const statusElements = document.querySelectorAll('[data-notification-status]');
        const toggleButtons = document.querySelectorAll('[data-notification-toggle]');
        
        if (statusElements.length === 0 && toggleButtons.length === 0) {
            return; // No UI elements to update
        }
        
        // Check if notifications are supported
        if (!('Notification' in window) || !('serviceWorker' in navigator)) {
            statusElements.forEach(el => {
                el.textContent = 'Not supported in this browser';
                el.className = 'notification-status notification-status-unsupported';
            });
            
            toggleButtons.forEach(btn => {
                btn.disabled = true;
                btn.textContent = 'Notifications Not Supported';
            });
            
            return;
        }
        
        // Check permission status
        const permission = Notification.permission;
        const isSubscribed = localStorage.getItem('pushSubscribed') === 'true';
        
        // Update status display
        statusElements.forEach(el => {
            if (permission === 'granted' && isSubscribed) {
                el.textContent = 'Enabled';
                el.className = 'notification-status notification-status-enabled';
            } else if (permission === 'denied') {
                el.textContent = 'Blocked';
                el.className = 'notification-status notification-status-blocked';
            } else {
                el.textContent = 'Disabled';
                el.className = 'notification-status notification-status-disabled';
            }
        });
        
        // Update toggle buttons
        toggleButtons.forEach(btn => {
            if (permission === 'denied') {
                btn.disabled = true;
                btn.textContent = 'Blocked (Check Browser Settings)';
                btn.className = 'btn-notification-toggle btn-blocked';
            } else if (permission === 'granted' && isSubscribed) {
                btn.disabled = false;
                btn.textContent = '🔔 Notifications Enabled';
                btn.className = 'btn-notification-toggle btn-enabled';
                btn.setAttribute('data-action', 'disable');
            } else {
                btn.disabled = false;
                btn.textContent = '🔕 Enable Notifications';
                btn.className = 'btn-notification-toggle btn-disabled';
                btn.setAttribute('data-action', 'enable');
            }
        });
    }
    
    /**
     * Handle toggle button click
     */
    async function handleToggle(button) {
        const action = button.getAttribute('data-action');
        
        button.disabled = true;
        button.textContent = 'Processing...';
        
        try {
            if (action === 'enable') {
                // Request permission and subscribe
                if (typeof window.subscribeToPush === 'function') {
                    const subscription = await window.subscribeToPush();
                    
                    if (subscription) {
                        if (window.Toast) {
                            Toast.success(
                                'Notifications Enabled',
                                'You will now receive important updates and reminders.'
                            );
                        }
                    } else {
                        throw new Error('Subscription failed');
                    }
                } else {
                    throw new Error('Push notifications not initialized');
                }
                
            } else if (action === 'disable') {
                // Unsubscribe
                if (typeof window.unsubscribeFromPush === 'function') {
                    const success = await window.unsubscribeFromPush();
                    
                    if (success) {
                        if (window.Toast) {
                            Toast.info(
                                'Notifications Disabled',
                                'You will no longer receive push notifications.'
                            );
                        }
                    } else {
                        throw new Error('Unsubscription failed');
                    }
                } else {
                    throw new Error('Push notifications not initialized');
                }
            }
            
            // Update UI
            setTimeout(updateNotificationStatus, 500);
            
        } catch (error) {
            console.error('Notification toggle error:', error);
            
            if (window.Toast) {
                Toast.error(
                    'Error',
                    'Failed to update notification settings. Please try again.'
                );
            }
            
            button.disabled = false;
        }
    }
    
    /**
     * Get notification info text
     */
    function getNotificationInfoText() {
        const permission = Notification.permission;
        
        if (permission === 'granted') {
            return 'You will receive notifications for application updates, message replies, and deadline reminders.';
        } else if (permission === 'denied') {
            return 'Notifications are blocked. To enable them, please update your browser settings.';
        } else {
            return 'Enable notifications to receive important updates, message replies, and deadline reminders instantly.';
        }
    }
    
    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            createNotificationSettingsUI();
            
            // Set up toggle button listeners
            document.addEventListener('click', function(e) {
                const toggleBtn = e.target.closest('[data-notification-toggle]');
                if (toggleBtn) {
                    e.preventDefault();
                    handleToggle(toggleBtn);
                }
            });
            
            // Update info text elements
            const infoElements = document.querySelectorAll('[data-notification-info]');
            infoElements.forEach(el => {
                el.textContent = getNotificationInfoText();
            });
        });
    } else {
        createNotificationSettingsUI();
        
        // Set up toggle button listeners
        document.addEventListener('click', function(e) {
            const toggleBtn = e.target.closest('[data-notification-toggle]');
            if (toggleBtn) {
                e.preventDefault();
                handleToggle(toggleBtn);
            }
        });
        
        // Update info text elements
        const infoElements = document.querySelectorAll('[data-notification-info]');
        infoElements.forEach(el => {
            el.textContent = getNotificationInfoText();
        });
    }
    
    // Expose utility function
    window.updateNotificationStatus = updateNotificationStatus;
    
    console.log('[Notification Settings] UI component initialized');
    
})();
