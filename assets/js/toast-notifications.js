/**
 * Toast Notification System
 * BOFETODA TODA Management System
 * 
 * Displays in-app toast notifications with auto-dismiss and queue management
 */

(function() {
    'use strict';
    
    // Toast configuration
    const TOAST_CONFIG = {
        duration: 5000,        // Auto-dismiss after 5 seconds
        maxVisible: 3,         // Maximum toasts visible at once
        position: 'top-right', // Position on screen
        animationDuration: 300 // Animation duration in ms
    };
    
    // Toast queue
    let toastQueue = [];
    let visibleToasts = [];
    let toastIdCounter = 0;
    
    /**
     * Initialize toast container
     */
    function initToastContainer() {
        let container = document.getElementById('toast-container');
        
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'toast-container toast-' + TOAST_CONFIG.position;
            document.body.appendChild(container);
        }
        
        return container;
    }
    
    /**
     * Create toast element
     */
    function createToastElement(toast) {
        const toastEl = document.createElement('div');
        toastEl.className = 'toast toast-' + toast.type;
        toastEl.setAttribute('data-toast-id', toast.id);
        toastEl.setAttribute('role', 'alert');
        toastEl.setAttribute('aria-live', 'polite');
        
        // Toast icon
        const icon = document.createElement('div');
        icon.className = 'toast-icon';
        icon.innerHTML = getToastIcon(toast.type);
        
        // Toast content
        const content = document.createElement('div');
        content.className = 'toast-content';
        
        if (toast.title) {
            const title = document.createElement('div');
            title.className = 'toast-title';
            title.textContent = toast.title;
            content.appendChild(title);
        }
        
        const message = document.createElement('div');
        message.className = 'toast-message';
        message.textContent = toast.message;
        content.appendChild(message);
        
        // Close button
        const closeBtn = document.createElement('button');
        closeBtn.className = 'toast-close';
        closeBtn.setAttribute('aria-label', 'Close notification');
        closeBtn.innerHTML = '&times;';
        closeBtn.addEventListener('click', () => dismissToast(toast.id));
        
        // Progress bar
        const progressBar = document.createElement('div');
        progressBar.className = 'toast-progress';
        const progressFill = document.createElement('div');
        progressFill.className = 'toast-progress-fill';
        progressBar.appendChild(progressFill);
        
        // Assemble toast
        toastEl.appendChild(icon);
        toastEl.appendChild(content);
        toastEl.appendChild(closeBtn);
        toastEl.appendChild(progressBar);
        
        return toastEl;
    }
    
    /**
     * Get icon for toast type
     */
    function getToastIcon(type) {
        const icons = {
            success: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
            error: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
            warning: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
            info: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>'
        };
        
        return icons[type] || icons.info;
    }
    
    /**
     * Show toast notification
     */
    function showToast(title, message, type = 'info', duration = null) {
        const toast = {
            id: ++toastIdCounter,
            title: title,
            message: message,
            type: type,
            duration: duration !== null ? duration : TOAST_CONFIG.duration,
            timestamp: Date.now()
        };
        
        // Add to queue
        toastQueue.push(toast);
        
        // Process queue
        processQueue();
        
        return toast.id;
    }
    
    /**
     * Process toast queue
     */
    function processQueue() {
        // Show toasts up to max visible
        while (toastQueue.length > 0 && visibleToasts.length < TOAST_CONFIG.maxVisible) {
            const toast = toastQueue.shift();
            displayToast(toast);
        }
    }
    
    /**
     * Display a toast
     */
    function displayToast(toast) {
        const container = initToastContainer();
        const toastEl = createToastElement(toast);
        
        // Add to DOM
        container.appendChild(toastEl);
        
        // Track visible toast
        visibleToasts.push({
            id: toast.id,
            element: toastEl,
            timer: null
        });
        
        // Trigger animation
        setTimeout(() => {
            toastEl.classList.add('toast-show');
        }, 10);
        
        // Start progress animation
        const progressFill = toastEl.querySelector('.toast-progress-fill');
        if (progressFill && toast.duration > 0) {
            progressFill.style.transition = `width ${toast.duration}ms linear`;
            setTimeout(() => {
                progressFill.style.width = '0%';
            }, 10);
        }
        
        // Auto-dismiss
        if (toast.duration > 0) {
            const visibleToast = visibleToasts.find(t => t.id === toast.id);
            if (visibleToast) {
                visibleToast.timer = setTimeout(() => {
                    dismissToast(toast.id);
                }, toast.duration);
            }
        }
    }
    
    /**
     * Dismiss a toast
     */
    function dismissToast(toastId) {
        const index = visibleToasts.findIndex(t => t.id === toastId);
        
        if (index === -1) return;
        
        const toast = visibleToasts[index];
        
        // Clear timer
        if (toast.timer) {
            clearTimeout(toast.timer);
        }
        
        // Animate out
        toast.element.classList.remove('toast-show');
        toast.element.classList.add('toast-hide');
        
        // Remove from DOM after animation
        setTimeout(() => {
            if (toast.element.parentNode) {
                toast.element.parentNode.removeChild(toast.element);
            }
            
            // Remove from visible toasts
            visibleToasts.splice(index, 1);
            
            // Process queue for next toast
            processQueue();
            
        }, TOAST_CONFIG.animationDuration);
    }
    
    /**
     * Dismiss all toasts
     */
    function dismissAll() {
        visibleToasts.forEach(toast => {
            dismissToast(toast.id);
        });
        toastQueue = [];
    }
    
    // Expose global API
    window.Toast = {
        show: showToast,
        success: (title, message, duration) => showToast(title, message, 'success', duration),
        error: (title, message, duration) => showToast(title, message, 'error', duration),
        warning: (title, message, duration) => showToast(title, message, 'warning', duration),
        info: (title, message, duration) => showToast(title, message, 'info', duration),
        dismiss: dismissToast,
        dismissAll: dismissAll
    };
    
    // Convenience function matching task requirements
    window.showToast = showToast;
    
    // Auto-initialize
    initToastContainer();
    
    console.log('[Toast] Notification system initialized');
    
})();
