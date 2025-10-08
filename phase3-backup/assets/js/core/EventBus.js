/**
 * EventBus - Conflict-free event system
 * 
 * Provides pub/sub pattern for component communication without DOM event conflicts.
 * Prevents handler collisions and enables clean component decoupling.
 * 
 * @class EventBus
 */
class EventBus {
    constructor() {
        this.listeners = new Map();
        this.debug = false;
        this.eventHistory = [];
        this.maxHistorySize = 100;
    }

    /**
     * Subscribe to an event
     * 
     * @param {string} event - Event name (supports namespacing with ':')
     * @param {Function} callback - Callback function to execute
     * @param {Object} context - Optional context for callback execution
     * @returns {Function} Unsubscribe function
     */
    on(event, callback, context = null) {
        this.validateEvent(event);
        this.validateCallback(callback);

        if (!this.listeners.has(event)) {
            this.listeners.set(event, []);
        }

        const listener = {
            callback,
            context,
            id: this.generateListenerId()
        };

        this.listeners.get(event).push(listener);

        if (this.debug) {
            console.log(`[EventBus] Subscribed to: ${event}`, listener.id);
        }

        // Return unsubscribe function
        return () => this.off(event, callback);
    }

    /**
     * Subscribe to an event once (auto-unsubscribe after first call)
     * 
     * @param {string} event - Event name
     * @param {Function} callback - Callback function
     * @param {Object} context - Optional context
     * @returns {Function} Unsubscribe function
     */
    once(event, callback, context = null) {
        const unsubscribe = this.on(event, (...args) => {
            unsubscribe();
            callback.apply(context, args);
        }, context);

        return unsubscribe;
    }

    /**
     * Unsubscribe from an event
     * 
     * @param {string} event - Event name
     * @param {Function} callback - Callback function to remove
     */
    off(event, callback) {
        if (!this.listeners.has(event)) {
            return;
        }

        const listeners = this.listeners.get(event);
        const index = listeners.findIndex(l => l.callback === callback);

        if (index !== -1) {
            listeners.splice(index, 1);

            if (this.debug) {
                console.log(`[EventBus] Unsubscribed from: ${event}`);
            }
        }

        // Clean up empty listener arrays to prevent memory leaks
        if (listeners.length === 0) {
            this.listeners.delete(event);
        }
    }

    /**
     * Emit an event to all subscribers
     * 
     * @param {string} event - Event name
     * @param {Object} data - Event data payload
     */
    emit(event, data = {}) {
        const eventObject = {
            type: event,
            data,
            timestamp: Date.now()
        };

        // Add to history for debugging
        this.addToHistory(eventObject);

        if (this.debug) {
            console.log(`[EventBus] Emitting: ${event}`, data);
        }

        if (!this.listeners.has(event)) {
            if (this.debug) {
                console.log(`[EventBus] No listeners for: ${event}`);
            }
            return;
        }

        const listeners = this.listeners.get(event);

        // Call all listeners with error handling
        for (const listener of listeners) {
            try {
                listener.callback.call(listener.context, eventObject);
            } catch (error) {
                console.error(`[EventBus] Error in listener for ${event}:`, error);
                
                // Emit error event (but prevent infinite loop)
                if (event !== 'eventbus:error') {
                    this.emit('eventbus:error', {
                        originalEvent: event,
                        error,
                        listener: listener.id
                    });
                }
            }
        }
    }

    /**
     * Clear all listeners for an event or all events
     * 
     * @param {string} event - Optional event name (clears all if not provided)
     */
    clear(event = null) {
        if (event) {
            this.listeners.delete(event);
            if (this.debug) {
                console.log(`[EventBus] Cleared listeners for: ${event}`);
            }
        } else {
            this.listeners.clear();
            if (this.debug) {
                console.log('[EventBus] Cleared all listeners');
            }
        }
    }

    /**
     * Get listener count for an event
     * 
     * @param {string} event - Event name
     * @returns {number} Number of listeners
     */
    getListenerCount(event) {
        return this.listeners.has(event) ? this.listeners.get(event).length : 0;
    }

    /**
     * Get all registered event names
     * 
     * @returns {Array<string>} Array of event names
     */
    getEventNames() {
        return Array.from(this.listeners.keys());
    }

    /**
     * Enable debug mode
     * 
     * @param {boolean} enabled - Whether to enable debug mode
     */
    setDebug(enabled) {
        this.debug = enabled;
        console.log(`[EventBus] Debug mode ${enabled ? 'enabled' : 'disabled'}`);
    }

    /**
     * Get event history (for debugging)
     * 
     * @returns {Array} Event history
     */
    getHistory() {
        return [...this.eventHistory];
    }

    /**
     * Clear event history
     */
    clearHistory() {
        this.eventHistory = [];
    }

    /**
     * Destroy event bus and cleanup all listeners
     */
    destroy() {
        this.listeners.clear();
        this.eventHistory = [];
        
        if (this.debug) {
            console.log('[EventBus] Destroyed');
        }
    }

    /**
     * Validate event name
     * 
     * @private
     * @param {string} event - Event name
     */
    validateEvent(event) {
        if (typeof event !== 'string' || event.trim() === '') {
            throw new Error('[EventBus] Event name must be a non-empty string');
        }
    }

    /**
     * Validate callback function
     * 
     * @private
     * @param {Function} callback - Callback function
     */
    validateCallback(callback) {
        if (typeof callback !== 'function') {
            throw new Error('[EventBus] Callback must be a function');
        }
    }

    /**
     * Generate unique listener ID
     * 
     * @private
     * @returns {string} Unique ID
     */
    generateListenerId() {
        return `listener_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    }

    /**
     * Add event to history
     * 
     * @private
     * @param {Object} eventObject - Event object
     */
    addToHistory(eventObject) {
        this.eventHistory.push(eventObject);

        // Limit history size to prevent memory leaks
        if (this.eventHistory.length > this.maxHistorySize) {
            this.eventHistory.shift();
        }
    }
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = EventBus;
}

// Make available globally for WordPress
if (typeof window !== 'undefined') {
    window.EventBus = EventBus;
}
