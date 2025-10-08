/**
 * Notification System Component
 * 
 * Toast notification system for user feedback with different types,
 * animations, action buttons, and accessibility features.
 * 
 * @class NotificationSystem
 */
class NotificationSystem {
    /**
     * Create notification system
     * 
     * @param {EventBus} eventBus - Event bus for communication
     */
    constructor(eventBus) {
        this.events = eventBus;
        this.notifications = new Map();
        this.nextId = 1;
        this.container = null;
        
        // Configuration
        this.config = {
            maxNotifications: 5,
            defaultDuration: 5000,
            animationDuration: 300,
            position: 'top-right' // top-right, top-left, bottom-right, bottom-left
        };
        
        this.init();
    }
    
    /**
     * Initialize notification system
     */
    init() {
        this.createContainer();
        this.bindEvents();
    }
    
    /**
     * Create notification container in DOM with proper ARIA attributes
     */
    createContainer() {
        // Check if container already exists
        this.container = document.getElementById('mas-notification-container');
        
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.id = 'mas-notification-container';
            this.container.className = `mas-notification-container mas-notification-${this.config.position}`;
            this.container.setAttribute('aria-live', 'polite');
            this.container.setAttribute('aria-atomic', 'false');
            this.container.setAttribute('aria-relevant', 'additions text');
            this.container.setAttribute('role', 'region');
            this.container.setAttribute('aria-label', 'Notifications');
            
            document.body.appendChild(this.container);
        }
    }
    
    /**
     * Bind event listeners
     */
    bindEvents() {
        // Listen for notification requests
        this.events.on('notification:show', (event) => {
            this.show(event.data);
        });
        
        // Listen for hide requests
        this.events.on('notification:hide', (event) => {
            if (event.data.id) {
                this.hide(event.data.id);
            }
        });
        
        // Listen for hide all requests
        this.events.on('notification:hideAll', () => {
            this.hideAll();
        });
        
        // Global keyboard handler for Escape key
        if (typeof KeyboardNavigationHelper !== 'undefined') {
            this.escapeCleanup = KeyboardNavigationHelper.addEscapeHandler(
                document.body,
                () => this.hideAll(),
                false // Don't stop propagation to allow other handlers
            );
        } else {
            // Fallback
            this.escapeHandler = (e) => {
                if (e.key === 'Escape') {
                    this.hideAll();
                }
            };
            document.addEventListener('keydown', this.escapeHandler);
        }
    }
    
    /**
     * Show notification
     * 
     * @param {Object} options - Notification options
     * @param {string} options.type - Notification type (success, error, warning, info)
     * @param {string} options.message - Notification message
     * @param {number} [options.duration] - Duration in ms (0 for persistent)
     * @param {Array} [options.actions] - Action buttons
     * @param {string} [options.title] - Optional title
     * @returns {number} Notification ID
     */
    show(options) {
        const {
            type = 'info',
            message,
            duration = this.config.defaultDuration,
            actions = [],
            title = null
        } = options;
        
        // Validate required fields
        if (!message) {
            console.error('[NotificationSystem] Message is required');
            return null;
        }
        
        // Generate unique ID
        const id = this.nextId++;
        
        // Create notification element
        const notification = this.createNotificationElement(id, type, message, title, actions);
        
        // Store notification data
        this.notifications.set(id, {
            id,
            element: notification,
            type,
            message,
            duration,
            timer: null
        });
        
        // Add to container
        this.container.appendChild(notification);
        
        // Trigger slide-in animation
        requestAnimationFrame(() => {
            notification.classList.add('mas-notification-show');
        });
        
        // Auto-dismiss if duration is set
        if (duration > 0) {
            const timer = setTimeout(() => {
                this.hide(id);
            }, duration);
            
            this.notifications.get(id).timer = timer;
        }
        
        // Limit number of notifications
        this.enforceMaxNotifications();
        
        // Emit shown event
        this.events.emit('notification:shown', { id, type, message });
        
        return id;
    }
    
    /**
     * Create notification DOM element
     * 
     * @param {number} id - Notification ID
     * @param {string} type - Notification type
     * @param {string} message - Message text
     * @param {string} title - Optional title
     * @param {Array} actions - Action buttons
     * @returns {HTMLElement} Notification element
     */
    createNotificationElement(id, type, message, title, actions) {
        const notification = document.createElement('div');
        notification.className = `mas-notification mas-notification-${type}`;
        notification.setAttribute('data-notification-id', id);
        notification.setAttribute('role', 'alert');
        notification.setAttribute('aria-live', 'assertive');
        
        // Icon
        const icon = this.getIconForType(type);
        
        // Build HTML
        let html = `
            <div class="mas-notification-icon">
                ${icon}
            </div>
            <div class="mas-notification-content">
        `;
        
        if (title) {
            html += `<div class="mas-notification-title">${this.escapeHtml(title)}</div>`;
        }
        
        html += `
                <div class="mas-notification-message">${this.escapeHtml(message)}</div>
        `;
        
        // Add actions if provided
        if (actions && actions.length > 0) {
            html += '<div class="mas-notification-actions">';
            
            for (const action of actions) {
                html += `
                    <button 
                        class="mas-notification-action" 
                        data-action="${this.escapeHtml(action.id)}"
                        type="button"
                    >
                        ${this.escapeHtml(action.label)}
                    </button>
                `;
            }
            
            html += '</div>';
        }
        
        html += `
            </div>
            <button 
                class="mas-notification-close" 
                type="button"
                aria-label="Dismiss notification"
            >
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                </svg>
            </button>
        `;
        
        notification.innerHTML = html;
        
        // Bind close button
        const closeButton = notification.querySelector('.mas-notification-close');
        closeButton.addEventListener('click', () => {
            this.hide(id);
        });
        
        // Bind action buttons
        if (actions && actions.length > 0) {
            const actionButtons = notification.querySelectorAll('.mas-notification-action');
            actionButtons.forEach((button, index) => {
                button.addEventListener('click', () => {
                    const action = actions[index];
                    if (action.callback) {
                        action.callback();
                    }
                    
                    // Auto-dismiss after action unless specified
                    if (action.dismissAfter !== false) {
                        this.hide(id);
                    }
                });
            });
        }
        
        return notification;
    }
    
    /**
     * Get icon SVG for notification type
     * 
     * @param {string} type - Notification type
     * @returns {string} SVG icon HTML
     */
    getIconForType(type) {
        const icons = {
            success: `
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
                </svg>
            `,
            error: `
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                </svg>
            `,
            warning: `
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/>
                </svg>
            `,
            info: `
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>
                </svg>
            `
        };
        
        return icons[type] || icons.info;
    }
    
    /**
     * Hide notification with animation
     * 
     * @param {number} id - Notification ID
     */
    hide(id) {
        const notification = this.notifications.get(id);
        
        if (!notification) {
            return;
        }
        
        // Clear auto-dismiss timer
        if (notification.timer) {
            clearTimeout(notification.timer);
        }
        
        // Add fade-out animation
        notification.element.classList.remove('mas-notification-show');
        notification.element.classList.add('mas-notification-hide');
        
        // Remove from DOM after animation
        setTimeout(() => {
            if (notification.element.parentNode) {
                notification.element.parentNode.removeChild(notification.element);
            }
            this.notifications.delete(id);
            
            // Emit hidden event
            this.events.emit('notification:hidden', { id });
        }, this.config.animationDuration);
    }
    
    /**
     * Hide all notifications
     */
    hideAll() {
        const ids = Array.from(this.notifications.keys());
        for (const id of ids) {
            this.hide(id);
        }
    }
    
    /**
     * Enforce maximum number of notifications
     */
    enforceMaxNotifications() {
        if (this.notifications.size > this.config.maxNotifications) {
            // Remove oldest notification
            const oldestId = this.notifications.keys().next().value;
            this.hide(oldestId);
        }
    }
    
    /**
     * Escape HTML to prevent XSS
     * 
     * @param {string} text - Text to escape
     * @returns {string} Escaped text
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    /**
     * Show success notification (convenience method)
     * 
     * @param {string} message - Success message
     * @param {number} duration - Duration in ms
     * @returns {number} Notification ID
     */
    success(message, duration = 3000) {
        return this.show({
            type: 'success',
            message,
            duration
        });
    }
    
    /**
     * Show error notification (convenience method)
     * 
     * @param {string} message - Error message
     * @param {Object} options - Additional options
     * @returns {number} Notification ID
     */
    error(message, options = {}) {
        return this.show({
            type: 'error',
            message,
            duration: options.duration || 0, // Persistent by default
            actions: options.actions || []
        });
    }
    
    /**
     * Show warning notification (convenience method)
     * 
     * @param {string} message - Warning message
     * @param {number} duration - Duration in ms
     * @returns {number} Notification ID
     */
    warning(message, duration = 5000) {
        return this.show({
            type: 'warning',
            message,
            duration
        });
    }
    
    /**
     * Show info notification (convenience method)
     * 
     * @param {string} message - Info message
     * @param {number} duration - Duration in ms
     * @returns {number} Notification ID
     */
    info(message, duration = 5000) {
        return this.show({
            type: 'info',
            message,
            duration
        });
    }
    
    /**
     * Show error notification with retry action
     * 
     * @param {string} message - Error message
     * @param {Function} retryCallback - Function to call on retry
     * @returns {number} Notification ID
     */
    errorWithRetry(message, retryCallback) {
        return this.show({
            type: 'error',
            message,
            duration: 0, // Persistent
            actions: [
                {
                    id: 'retry',
                    label: 'Retry',
                    callback: retryCallback,
                    dismissAfter: true
                },
                {
                    id: 'dismiss',
                    label: 'Dismiss',
                    callback: null,
                    dismissAfter: true
                }
            ]
        });
    }
    
    /**
     * Show error notification with report action
     * 
     * @param {string} message - Error message
     * @param {Object} errorDetails - Error details for reporting
     * @returns {number} Notification ID
     */
    errorWithReport(message, errorDetails = {}) {
        return this.show({
            type: 'error',
            message,
            duration: 0, // Persistent
            actions: [
                {
                    id: 'report',
                    label: 'Report Issue',
                    callback: () => {
                        this.reportError(errorDetails);
                    },
                    dismissAfter: true
                },
                {
                    id: 'dismiss',
                    label: 'Dismiss',
                    callback: null,
                    dismissAfter: true
                }
            ]
        });
    }
    
    /**
     * Report error to console or external service
     * 
     * @param {Object} errorDetails - Error details
     */
    reportError(errorDetails) {
        console.group('Error Report');
        console.error('Error Details:', errorDetails);
        console.error('Timestamp:', new Date().toISOString());
        console.error('User Agent:', navigator.userAgent);
        console.error('URL:', window.location.href);
        console.groupEnd();
        
        // Emit event for external error tracking
        this.events.emit('error:reported', {
            details: errorDetails,
            timestamp: Date.now(),
            userAgent: navigator.userAgent,
            url: window.location.href
        });
        
        // Show confirmation
        this.success('Error report logged to console', 2000);
    }
    
    /**
     * Show network error with retry
     * 
     * @param {Function} retryCallback - Function to call on retry
     * @returns {number} Notification ID
     */
    networkError(retryCallback) {
        return this.errorWithRetry(
            'Network error occurred. Please check your connection and try again.',
            retryCallback
        );
    }
    
    /**
     * Show unexpected error with report option
     * 
     * @param {Error} error - Error object
     * @returns {number} Notification ID
     */
    unexpectedError(error) {
        return this.errorWithReport(
            'An unexpected error occurred. You can report this issue to help us fix it.',
            {
                message: error.message,
                stack: error.stack,
                name: error.name
            }
        );
    }
    
    /**
     * Destroy notification system
     */
    destroy() {
        // Hide all notifications
        this.hideAll();
        
        // Remove escape handler
        if (this.escapeCleanup) {
            this.escapeCleanup();
        } else if (this.escapeHandler) {
            document.removeEventListener('keydown', this.escapeHandler);
        }
        
        // Remove container
        if (this.container && this.container.parentNode) {
            this.container.parentNode.removeChild(this.container);
        }
        
        this.container = null;
        this.notifications.clear();
    }
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = NotificationSystem;
}
