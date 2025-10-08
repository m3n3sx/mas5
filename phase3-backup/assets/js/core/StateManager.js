/**
 * StateManager - Centralized state management
 * 
 * Provides reactive state management with history tracking for undo/redo.
 * Similar to Redux but simpler and tailored for WordPress admin needs.
 * 
 * @class StateManager
 */
class StateManager {
    constructor(eventBus) {
        this.eventBus = eventBus;
        
        // Initial state structure
        this.state = {
            settings: {},
            themes: [],
            backups: [],
            ui: {
                loading: false,
                saving: false,
                activeTab: 'general',
                hasUnsavedChanges: false
            },
            preview: {
                active: false,
                settings: null
            }
        };

        // State history for undo/redo
        this.history = [];
        this.historyIndex = -1;
        this.maxHistory = 50;

        // Subscribers for reactive updates
        this.subscribers = new Set();

        // Debug mode
        this.debug = false;
    }

    /**
     * Get current state (read-only deep clone)
     * 
     * @returns {Object} Current state
     */
    getState() {
        return this.deepClone(this.state);
    }

    /**
     * Get specific state path using dot notation
     * 
     * @param {string} path - Path to state value (e.g., 'ui.loading')
     * @returns {*} State value at path
     */
    get(path) {
        const keys = path.split('.');
        let value = this.state;

        for (const key of keys) {
            if (value === undefined || value === null) {
                return undefined;
            }
            value = value[key];
        }

        return value;
    }

    /**
     * Update state with new values
     * 
     * @param {Object} updates - State updates to merge
     * @param {boolean} addToHistory - Whether to add to history for undo/redo
     */
    setState(updates, addToHistory = true) {
        // Save previous state for history
        const previousState = this.deepClone(this.state);

        // Merge updates into state
        this.state = this.deepMerge(this.state, updates);

        // Add to history
        if (addToHistory) {
            this.addToHistory(previousState);
        }

        if (this.debug) {
            console.log('[StateManager] State updated:', updates);
            console.log('[StateManager] New state:', this.getState());
        }

        // Notify subscribers
        this.notifySubscribers();

        // Emit state change event
        this.eventBus.emit('state:changed', {
            state: this.getState(),
            updates,
            previousState
        });
    }

    /**
     * Set specific state path using dot notation
     * 
     * @param {string} path - Path to set (e.g., 'ui.loading')
     * @param {*} value - Value to set
     * @param {boolean} addToHistory - Whether to add to history
     */
    set(path, value, addToHistory = true) {
        const keys = path.split('.');
        const updates = {};
        let current = updates;

        // Build nested update object
        for (let i = 0; i < keys.length - 1; i++) {
            current[keys[i]] = {};
            current = current[keys[i]];
        }
        current[keys[keys.length - 1]] = value;

        this.setState(updates, addToHistory);
    }

    /**
     * Subscribe to state changes
     * 
     * @param {Function} callback - Callback function to execute on state change
     * @returns {Function} Unsubscribe function
     */
    subscribe(callback) {
        if (typeof callback !== 'function') {
            throw new Error('[StateManager] Subscriber must be a function');
        }

        this.subscribers.add(callback);

        if (this.debug) {
            console.log('[StateManager] New subscriber added');
        }

        // Return unsubscribe function
        return () => {
            this.subscribers.delete(callback);
            if (this.debug) {
                console.log('[StateManager] Subscriber removed');
            }
        };
    }

    /**
     * Notify all subscribers of state change
     * 
     * @private
     */
    notifySubscribers() {
        const state = this.getState();

        for (const callback of this.subscribers) {
            try {
                callback(state);
            } catch (error) {
                console.error('[StateManager] Error in subscriber:', error);
            }
        }
    }

    /**
     * Add state to history
     * 
     * @private
     * @param {Object} state - State to add to history
     */
    addToHistory(state) {
        // Remove any history after current index (for redo)
        this.history = this.history.slice(0, this.historyIndex + 1);

        // Add new state
        this.history.push(state);

        // Limit history size to prevent memory leaks
        if (this.history.length > this.maxHistory) {
            this.history.shift();
        } else {
            this.historyIndex++;
        }

        if (this.debug) {
            console.log(`[StateManager] History updated (${this.history.length} states)`);
        }
    }

    /**
     * Undo last state change
     * 
     * @returns {boolean} Whether undo was successful
     */
    undo() {
        if (!this.canUndo()) {
            if (this.debug) {
                console.log('[StateManager] Cannot undo - at beginning of history');
            }
            return false;
        }

        this.historyIndex--;
        this.state = this.deepClone(this.history[this.historyIndex]);
        
        this.notifySubscribers();
        this.eventBus.emit('state:undo', { state: this.getState() });

        if (this.debug) {
            console.log('[StateManager] Undo performed');
        }

        return true;
    }

    /**
     * Redo state change
     * 
     * @returns {boolean} Whether redo was successful
     */
    redo() {
        if (!this.canRedo()) {
            if (this.debug) {
                console.log('[StateManager] Cannot redo - at end of history');
            }
            return false;
        }

        this.historyIndex++;
        this.state = this.deepClone(this.history[this.historyIndex]);
        
        this.notifySubscribers();
        this.eventBus.emit('state:redo', { state: this.getState() });

        if (this.debug) {
            console.log('[StateManager] Redo performed');
        }

        return true;
    }

    /**
     * Check if undo is available
     * 
     * @returns {boolean} Whether undo is available
     */
    canUndo() {
        return this.historyIndex > 0;
    }

    /**
     * Check if redo is available
     * 
     * @returns {boolean} Whether redo is available
     */
    canRedo() {
        return this.historyIndex < this.history.length - 1;
    }

    /**
     * Clear history
     */
    clearHistory() {
        this.history = [];
        this.historyIndex = -1;

        if (this.debug) {
            console.log('[StateManager] History cleared');
        }
    }

    /**
     * Reset state to initial values
     * 
     * @param {boolean} addToHistory - Whether to add to history
     */
    reset(addToHistory = true) {
        const previousState = this.deepClone(this.state);

        this.state = {
            settings: {},
            themes: [],
            backups: [],
            ui: {
                loading: false,
                saving: false,
                activeTab: 'general',
                hasUnsavedChanges: false
            },
            preview: {
                active: false,
                settings: null
            }
        };

        if (addToHistory) {
            this.addToHistory(previousState);
        }

        this.notifySubscribers();
        this.eventBus.emit('state:reset', { state: this.getState() });

        if (this.debug) {
            console.log('[StateManager] State reset');
        }
    }

    /**
     * Enable debug mode
     * 
     * @param {boolean} enabled - Whether to enable debug mode
     */
    setDebug(enabled) {
        this.debug = enabled;
        console.log(`[StateManager] Debug mode ${enabled ? 'enabled' : 'disabled'}`);
    }

    /**
     * Get history information
     * 
     * @returns {Object} History info
     */
    getHistoryInfo() {
        return {
            length: this.history.length,
            index: this.historyIndex,
            canUndo: this.canUndo(),
            canRedo: this.canRedo()
        };
    }

    /**
     * Deep merge objects
     * 
     * @private
     * @param {Object} target - Target object
     * @param {Object} source - Source object
     * @returns {Object} Merged object
     */
    deepMerge(target, source) {
        const output = { ...target };

        for (const key in source) {
            if (source[key] instanceof Object && !Array.isArray(source[key]) && key in target) {
                output[key] = this.deepMerge(target[key], source[key]);
            } else {
                output[key] = source[key];
            }
        }

        return output;
    }

    /**
     * Deep clone object
     * 
     * @private
     * @param {*} obj - Object to clone
     * @returns {*} Cloned object
     */
    deepClone(obj) {
        if (obj === null || typeof obj !== 'object') {
            return obj;
        }

        if (obj instanceof Date) {
            return new Date(obj.getTime());
        }

        if (obj instanceof Array) {
            return obj.map(item => this.deepClone(item));
        }

        if (obj instanceof Object) {
            const cloned = {};
            for (const key in obj) {
                if (obj.hasOwnProperty(key)) {
                    cloned[key] = this.deepClone(obj[key]);
                }
            }
            return cloned;
        }

        return obj;
    }

    /**
     * Destroy state manager and cleanup
     */
    destroy() {
        this.subscribers.clear();
        this.history = [];
        this.historyIndex = -1;

        if (this.debug) {
            console.log('[StateManager] Destroyed');
        }
    }
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = StateManager;
}

// Make available globally for WordPress
if (typeof window !== 'undefined') {
    window.StateManager = StateManager;
}
