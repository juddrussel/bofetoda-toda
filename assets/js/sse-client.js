/**
 * Server-Sent Events (SSE) Client for Real-Time Notifications
 * BOFETODA TODA Management System
 */

(function() {
    'use strict';
    
    // Configuration
    const SSE_CONFIG = {
        reconnectDelay: 3000,        // 3 seconds
        maxReconnectDelay: 30000,    // 30 seconds max
        reconnectBackoff: 1.5,       // Exponential backoff multiplier
        heartbeatTimeout: 60000      // 60 seconds
    };
    
    class SSENotificationClient {
        constructor(endpoint) {
            this.endpoint = endpoint;
            this.eventSource = null;
            this.lastEventId = 0;
            this.reconnectDelay = SSE_CONFIG.reconnectDelay;
            this.reconnectTimer = null;
            this.heartbeatTimer = null;
            this.isManualDisconnect = false;
            this.eventHandlers = {};
            
            // Auto-start connection
            this.connect();
            
            // Handle page visibility changes (pause when hidden, resume when visible)
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    console.log('[SSE] Page hidden, pausing connection');
                    this.pause();
                } else {
                    console.log('[SSE] Page visible, resuming connection');
                    this.resume();
                }
            });
        }
        
        /**
         * Connect to SSE endpoint
         */
        connect() {
            if (this.eventSource) {
                this.eventSource.close();
            }
            
            this.isManualDisconnect = false;
            
            try {
                const url = this.lastEventId > 0 
                    ? `${this.endpoint}?lastId=${this.lastEventId}`
                    : this.endpoint;
                
                console.log('[SSE] Connecting to:', url);
                this.eventSource = new EventSource(url);
                
                // Connection opened
                this.eventSource.addEventListener('open', (e) => {
                    console.log('[SSE] Connection established');
                    this.reconnectDelay = SSE_CONFIG.reconnectDelay; // Reset backoff
                    this.startHeartbeatMonitor();
                    this.trigger('connected');
                });
                
                // Handle notification events
                this.eventSource.addEventListener('notification', (e) => {
                    this.resetHeartbeatMonitor();
                    const data = JSON.parse(e.data);
                    console.log('[SSE] Notification received:', data);
                    
                    if (e.lastEventId) {
                        this.lastEventId = parseInt(e.lastEventId);
                    }
                    
                    this.trigger('notification', data);
                    this.showNotificationToast(data);
                });
                
                // Handle status change events
                this.eventSource.addEventListener('statusChange', (e) => {
                    this.resetHeartbeatMonitor();
                    const data = JSON.parse(e.data);
                    console.log('[SSE] Status change:', data);
                    this.trigger('statusChange', data);
                    this.handleStatusChange(data);
                });
                
                // Handle new application events (for officers)
                this.eventSource.addEventListener('newApplication', (e) => {
                    this.resetHeartbeatMonitor();
                    const data = JSON.parse(e.data);
                    console.log('[SSE] New application:', data);
                    this.trigger('newApplication', data);
                    this.showApplicationToast(data);
                });
                
                // Handle heartbeat
                this.eventSource.addEventListener('heartbeat', (e) => {
                    this.resetHeartbeatMonitor();
                    console.log('[SSE] Heartbeat received');
                });
                
                // Handle reconnect message from server
                this.eventSource.addEventListener('reconnect', (e) => {
                    console.log('[SSE] Server requested reconnect');
                    this.reconnect();
                });
                
                // Handle errors
                this.eventSource.addEventListener('error', (e) => {
                    console.error('[SSE] Connection error:', e);
                    this.stopHeartbeatMonitor();
                    
                    if (this.eventSource.readyState === EventSource.CLOSED) {
                        console.log('[SSE] Connection closed, will attempt reconnect');
                        this.trigger('disconnected');
                        if (!this.isManualDisconnect) {
                            this.scheduleReconnect();
                        }
                    }
                });
                
            } catch (error) {
                console.error('[SSE] Failed to create EventSource:', error);
                this.scheduleReconnect();
            }
        }
        
        /**
         * Disconnect from SSE endpoint
         */
        disconnect() {
            console.log('[SSE] Disconnecting');
            this.isManualDisconnect = true;
            this.stopHeartbeatMonitor();
            
            if (this.reconnectTimer) {
                clearTimeout(this.reconnectTimer);
                this.reconnectTimer = null;
            }
            
            if (this.eventSource) {
                this.eventSource.close();
                this.eventSource = null;
            }
            
            this.trigger('disconnected');
        }
        
        /**
         * Reconnect to SSE endpoint
         */
        reconnect() {
            console.log('[SSE] Reconnecting...');
            this.disconnect();
            this.isManualDisconnect = false;
            this.connect();
        }
        
        /**
         * Schedule reconnection with exponential backoff
         */
        scheduleReconnect() {
            if (this.reconnectTimer) {
                clearTimeout(this.reconnectTimer);
            }
            
            console.log(`[SSE] Scheduling reconnect in ${this.reconnectDelay}ms`);
            
            this.reconnectTimer = setTimeout(() => {
                this.connect();
            }, this.reconnectDelay);
            
            // Increase delay for next reconnect (exponential backoff)
            this.reconnectDelay = Math.min(
                this.reconnectDelay * SSE_CONFIG.reconnectBackoff,
                SSE_CONFIG.maxReconnectDelay
            );
        }
        
        /**
         * Start heartbeat monitor
         */
        startHeartbeatMonitor() {
            this.stopHeartbeatMonitor();
            this.heartbeatTimer = setTimeout(() => {
                console.warn('[SSE] Heartbeat timeout, reconnecting...');
                this.reconnect();
            }, SSE_CONFIG.heartbeatTimeout);
        }
        
        /**
         * Reset heartbeat monitor
         */
        resetHeartbeatMonitor() {
            if (this.heartbeatTimer) {
                clearTimeout(this.heartbeatTimer);
                this.startHeartbeatMonitor();
            }
        }
        
        /**
         * Stop heartbeat monitor
         */
        stopHeartbeatMonitor() {
            if (this.heartbeatTimer) {
                clearTimeout(this.heartbeatTimer);
                this.heartbeatTimer = null;
            }
        }
        
        /**
         * Pause connection (page hidden)
         */
        pause() {
            this.stopHeartbeatMonitor();
        }
        
        /**
         * Resume connection (page visible)
         */
        resume() {
            if (this.eventSource && this.eventSource.readyState === EventSource.OPEN) {
                this.startHeartbeatMonitor();
            } else {
                this.reconnect();
            }
        }
        
        /**
         * Register event handler
         */
        on(event, handler) {
            if (!this.eventHandlers[event]) {
                this.eventHandlers[event] = [];
            }
            this.eventHandlers[event].push(handler);
        }
        
        /**
         * Trigger event
         */
        trigger(event, data) {
            if (this.eventHandlers[event]) {
                this.eventHandlers[event].forEach(handler => {
                    try {
                        handler(data);
                    } catch (error) {
                        console.error(`[SSE] Error in ${event} handler:`, error);
                    }
                });
            }
        }
        
        /**
         * Show notification toast
         */
        showNotificationToast(data) {
            if (window.Toast) {
                Toast.info(data.title, data.message, 5000);
            }
            
            // Update notification badge if it exists
            const badge = document.querySelector('.notification-badge');
            if (badge) {
                const currentCount = parseInt(badge.textContent) || 0;
                badge.textContent = currentCount + 1;
                badge.style.display = 'inline-block';
            }
        }
        
        /**
         * Handle status change
         */
        handleStatusChange(data) {
            const statusMessages = {
                'Approved': '🎉 Your application has been approved!',
                'Rejected': '⚠️ Your application needs attention',
                'For Approval': '✓ Your documents have been validated',
                'Validated': '✓ Your documents have been verified'
            };
            
            const message = statusMessages[data.status] || 'Your application status has been updated';
            
            if (window.Toast) {
                if (data.status === 'Approved') {
                    Toast.success('Application Approved', message, 10000);
                } else if (data.status === 'Rejected') {
                    Toast.error('Action Required', message, 10000);
                } else {
                    Toast.info('Status Update', message, 5000);
                }
            }
            
            // Reload page after short delay to show updated status
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        }
        
        /**
         * Show new application toast (for officers)
         */
        showApplicationToast(data) {
            if (window.Toast) {
                Toast.info('New Application', data.message, 5000);
            }
            
            // Play notification sound if available
            if (window.playNotificationSound) {
                window.playNotificationSound();
            }
            
            // Update dashboard counters
            this.updateDashboardCounters();
        }
        
        /**
         * Update dashboard counters
         */
        updateDashboardCounters() {
            // Refresh page or update counters via AJAX
            if (window.location.pathname.includes('dashboard')) {
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            }
        }
    }
    
    // Auto-initialize SSE client when page loads
    window.addEventListener('DOMContentLoaded', function() {
        // Determine endpoint based on user type
        const isDriver = window.location.pathname.includes('Driver_BOFETODA');
        const isAdmin = window.location.pathname.includes('Admin_BOFETODA');
        
        let endpoint = null;
        
        if (isDriver) {
            endpoint = '/Driver_BOFETODA/sse-notifications.php';
        } else if (isAdmin) {
            endpoint = '/Admin_BOFETODA/sse-notifications.php';
        }
        
        if (endpoint) {
            console.log('[SSE] Initializing real-time notifications');
            window.sseClient = new SSENotificationClient(endpoint);
            
            // Expose for debugging
            window.SSE = {
                disconnect: () => window.sseClient.disconnect(),
                reconnect: () => window.sseClient.reconnect(),
                getStatus: () => {
                    if (!window.sseClient.eventSource) return 'disconnected';
                    const states = ['CONNECTING', 'OPEN', 'CLOSED'];
                    return states[window.sseClient.eventSource.readyState] || 'unknown';
                }
            };
        }
    });
    
})();
