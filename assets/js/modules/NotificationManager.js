/**
 * Modern Admin Styler V2 - Notification Manager Module
 * Centralny moduł zarządzania powiadomieniami
 */

class NotificationManager {
    constructor() {
        this.activeNotifications = new Map();
    }

    init() {
        console.log('🔔 NotificationManager initialized');
    }

    show(message, type = 'info', duration = 3000, position = 'top-right') {
        const existing = document.querySelector(`.mas-notification-${position}`);
        if (existing) {
            existing.remove();
        }

        const notification = document.createElement('div');
        notification.className = `mas-notification mas-notification-${type} mas-notification-${position}`;
        
        const icons = {
            success: '✅',
            info: 'ℹ️',
            warning: '⚠️',
            error: '❌',
            theme: '🎨',
            preview: '👁️'
        };

        let icon = icons[type] || icons.info;

        notification.innerHTML = `
            <div class="mas-notification-content">
                <span class="mas-notification-icon">${icon}</span>
                <span class="mas-notification-message">${message}</span>
            </div>
        `;

        // Modern glassmorphism styling
        Object.assign(notification.style, {
            position: 'fixed',
            padding: '16px 24px',
            borderRadius: '12px',
            backdropFilter: 'blur(16px) saturate(1.1)',
            border: '1px solid rgba(255,255,255,0.2)',
            boxShadow: '0 8px 32px rgba(0,0,0,0.15), 0 4px 16px rgba(0,0,0,0.1)',
            zIndex: '999999',
            opacity: '0',
            transition: 'all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94)',
            maxWidth: '350px',
            minWidth: '200px',
            color: 'white',
            backgroundColor: this._getBackgroundColor(type),
            fontFamily: 'system-ui, -apple-system, sans-serif',
            fontSize: '14px',
            fontWeight: '500'
        });

        // Position logic with smooth entrance animations
        switch (position) {
            case 'top-right':
                Object.assign(notification.style, { 
                    top: '20px', 
                    right: '20px', 
                    transform: 'translateX(100px) scale(0.9)' 
                });
                break;
            case 'bottom-right':
                Object.assign(notification.style, { 
                    bottom: '20px', 
                    right: '20px', 
                    transform: 'translateY(100px) scale(0.9)' 
                });
                break;
            case 'top-left':
                Object.assign(notification.style, { 
                    top: '20px', 
                    left: '20px', 
                    transform: 'translateX(-100px) scale(0.9)' 
                });
                break;
            case 'bottom-left':
                Object.assign(notification.style, { 
                    bottom: '20px', 
                    left: '20px', 
                    transform: 'translateY(100px) scale(0.9)' 
                });
                break;
        }
        
        document.body.appendChild(notification);

        // Smooth entrance animation
        requestAnimationFrame(() => {
            notification.style.opacity = '1';
            notification.style.transform = 'translate(0,0) scale(1)';
        });

        // Auto-dismiss with smooth exit
        const timeoutId = setTimeout(() => {
            notification.style.opacity = '0';
            
            // Reverse transform for exit animation
            switch (position) {
                case 'top-right': 
                    notification.style.transform = 'translateX(50px) scale(0.95)'; 
                    break;
                case 'bottom-right': 
                    notification.style.transform = 'translateY(50px) scale(0.95)'; 
                    break;
                case 'top-left': 
                    notification.style.transform = 'translateX(-50px) scale(0.95)'; 
                    break;
                case 'bottom-left': 
                    notification.style.transform = 'translateY(50px) scale(0.95)'; 
                    break;
            }
            
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 400);
            this.activeNotifications.delete(timeoutId);
        }, duration);

        this.activeNotifications.set(timeoutId, notification);
        
        // Dispatch event for other modules
        this.dispatchNotificationEvent('shown', { message, type, duration, position });
        
        return timeoutId;
    }

    _getBackgroundColor(type) {
        const colors = {
            success: 'linear-gradient(135deg, #4CAF50, #45a049)',
            info: 'linear-gradient(135deg, #2196F3, #1976D2)', 
            warning: 'linear-gradient(135deg, #FF9800, #F57C00)',
            error: 'linear-gradient(135deg, #f44336, #D32F2F)',
            theme: 'linear-gradient(135deg, #9C27B0, #7B1FA2)',
            preview: 'linear-gradient(135deg, #00BCD4, #0097A7)'
        };
        return colors[type] || colors.info;
    }

    dismiss(notificationId) {
        const notification = this.activeNotifications.get(notificationId);
        if (notification) {
            clearTimeout(notificationId);
            notification.style.opacity = '0';
            notification.style.transform = 'scale(0.9)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 300);
            this.activeNotifications.delete(notificationId);
            this.dispatchNotificationEvent('dismissed', { notificationId });
        }
    }

    dismissAll() {
        this.activeNotifications.forEach((notification, id) => this.dismiss(id));
        this.dispatchNotificationEvent('dismissedAll', {});
    }

    // Enhanced notification with custom styling
    showAdvanced(config) {
        const {
            message,
            type = 'info',
            duration = 3000,
            position = 'top-right',
            icon = null,
            actions = [],
            persistent = false
        } = config;

        const notificationId = this.show(message, type, persistent ? 0 : duration, position);
        
        if (actions.length > 0) {
            const notification = this.activeNotifications.get(notificationId);
            if (notification) {
                // Add action buttons
                const actionsContainer = document.createElement('div');
                actionsContainer.style.cssText = `
                    margin-top: 12px;
                    display: flex;
                    gap: 8px;
                `;
                
                actions.forEach(action => {
                    const button = document.createElement('button');
                    button.textContent = action.label;
                    button.style.cssText = `
                        background: rgba(255,255,255,0.2);
                        border: 1px solid rgba(255,255,255,0.3);
                        color: white;
                        padding: 6px 12px;
                        border-radius: 6px;
                        cursor: pointer;
                        font-size: 12px;
                        transition: all 0.2s ease;
                    `;
                    
                    button.addEventListener('click', () => {
                        action.callback();
                        if (action.dismiss !== false) {
                            this.dismiss(notificationId);
                        }
                    });
                    
                    button.addEventListener('mouseenter', () => {
                        button.style.background = 'rgba(255,255,255,0.3)';
                    });
                    
                    button.addEventListener('mouseleave', () => {
                        button.style.background = 'rgba(255,255,255,0.2)';
                    });
                    
                    actionsContainer.appendChild(button);
                });
                
                notification.querySelector('.mas-notification-content').appendChild(actionsContainer);
            }
        }

        return notificationId;
    }

    // Quick helper methods
    success(message, duration = 3000) {
        return this.show(message, 'success', duration, 'bottom-right');
    }

    error(message, duration = 5000) {
        return this.show(message, 'error', duration, 'bottom-right');
    }

    info(message, duration = 3000) {
        return this.show(message, 'info', duration, 'top-right');
    }

    warning(message, duration = 4000) {
        return this.show(message, 'warning', duration, 'top-right');
    }

    theme(message, duration = 3000) {
        return this.show(message, 'theme', duration, 'top-right');
    }

    preview(message, duration = 2000) {
        return this.show(message, 'preview', duration, 'top-left');
    }

    dispatchNotificationEvent(action, data) {
        const event = new CustomEvent('mas-notification-' + action, {
            detail: {
                action,
                timestamp: Date.now(),
                ...data
            }
        });
        document.dispatchEvent(event);
    }

    // Get stats for debugging
    getStats() {
        return {
            activeCount: this.activeNotifications.size,
            activeNotifications: Array.from(this.activeNotifications.keys())
        };
    }

    // Debug method
    logStats() {
        console.log('📊 NotificationManager Stats:', this.getStats());
    }
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = NotificationManager;
} else {
    window.NotificationManager = NotificationManager;
}