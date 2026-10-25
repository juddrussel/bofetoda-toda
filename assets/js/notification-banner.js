/**
 * Notification Banner Component
 * Shows a prominent banner encouraging users to enable push notifications
 */

(function() {
    'use strict';
    
    // Wait for push notifications to be ready
    window.addEventListener('pushNotificationsReady', function() {
        console.log('[Banner] Push notifications ready event fired');
        
        // Check if we should show the banner
        const shouldPrompt = window.shouldPromptForPush ? window.shouldPromptForPush() : false;
        console.log('[Banner] shouldPromptForPush():', shouldPrompt);
        
        if (!shouldPrompt) {
            console.log('[Banner] Not showing banner - shouldPromptForPush returned false');
            return;
        }
        
        console.log('[Banner] Creating notification banner');
        
        // Create banner HTML
        const banner = document.createElement('div');
        banner.id = 'notification-banner';
        banner.innerHTML = `
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 16px 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.15); position: sticky; top: 54px; z-index: 19; font-size: 14px;">
                <div style="max-width: 720px; margin: 0 auto; display: flex; align-items: center; gap: 12px;">
                    <div style="flex: 1;">
                        <div style="font-weight: 600; margin-bottom: 4px;">🔔 Stay Updated!</div>
                        <div style="font-size: 13px; opacity: 0.95;">Enable notifications to receive important updates about your application and deadlines.</div>
                    </div>
                    <button id="enable-notifications-btn" style="background: white; color: #667eea; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; font-size: 13px; cursor: pointer; white-space: nowrap; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        Enable
                    </button>
                    <button id="dismiss-notifications-btn" style="background: transparent; color: white; border: 1px solid rgba(255,255,255,0.4); padding: 10px 16px; border-radius: 6px; font-weight: 500; font-size: 13px; cursor: pointer; white-space: nowrap;">
                        Later
                    </button>
                </div>
            </div>
        `;
        
        // Insert at top of page
        const appShell = document.querySelector('.app-shell');
        if (appShell && appShell.firstChild) {
            appShell.insertBefore(banner, appShell.firstChild.nextSibling);
        }
        
        // Handle enable button click
        document.getElementById('enable-notifications-btn').addEventListener('click', async function() {
            this.disabled = true;
            this.textContent = 'Enabling...';
            
            try {
                if (window.subscribeToPush) {
                    const subscription = await window.subscribeToPush();
                    
                    if (subscription) {
                        // Success - remove banner
                        banner.remove();
                        
                        if (window.Toast) {
                            Toast.success(
                                'Notifications Enabled! 🎉',
                                'You will now receive important updates and reminders.',
                                4000
                            );
                        }
                    } else {
                        // Failed or denied
                        this.disabled = false;
                        this.textContent = 'Enable';
                        
                        if (window.Toast) {
                            Toast.warning(
                                'Notifications Blocked',
                                'Please enable notifications in your browser settings.',
                                5000
                            );
                        }
                    }
                }
            } catch (error) {
                console.error('Failed to enable notifications:', error);
                this.disabled = false;
                this.textContent = 'Enable';
                
                if (window.Toast) {
                    Toast.error(
                        'Error',
                        'Failed to enable notifications. Please try again.',
                        4000
                    );
                }
            }
        });
        
        // Handle dismiss button click
        document.getElementById('dismiss-notifications-btn').addEventListener('click', function() {
            banner.remove();
            
            // Don't ask again this session
            sessionStorage.setItem('notificationBannerDismissed', 'true');
            
            if (window.Toast) {
                Toast.info(
                    'Reminder',
                    'You can enable notifications anytime from your profile settings.',
                    4000
                );
            }
        });
    });
})();
