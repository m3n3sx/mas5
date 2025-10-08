/**
 * Base Component Class
 * 
 * All UI components extend this base class for consistent lifecycle management.
 * Provides common functionality for initialization, rendering, event handling, and cleanup.
 * 
 * @class Component
 */
class Component {
    /**
     * Create a component
     * 
     * @param {HTMLElement} element - DOM element for this component
     * @param {APIClient} apiClient - API client instance
     * @param {StateManager} stateManager - State manager instance
     * @param {EventBus} eventBus - Event bus instance
     */
    constructor(element, apiClient, stateManager, eventBus) {
        this.element = element;
        this.api = apiClient;
        this.state = stateManager;
        this.events = eventBus;

        // DOM optimizer for performance
        this.domOptimizer = typeof domOptimizer !== 'undefined' ? domOptimizer : null;

        // Component-specific local state
        this.localState = {};

        // Event unsubscribe functions for cleanup
        this.unsubscribers = [];

        // Bound methods cache for performance
        this.boundMethods = new Map();

        // Component lifecycle state
        this.isInitialized = false;
        this.isDestroyed = false;

        // Component name (override in subclasses)
        this.name = this.constructor.name;

        // Initialize component
        this.init();
    }

    /**
     * Initialize component
     * Override in subclasses to add custom initialization logic
     * 
     * @returns {void}
     */
    init() {
        if (this.isInitialized) {
            console.warn(`[${this.name}] Already initialized`);
            return;
        }

        this.log('Initializing...');

        // Render initial UI
        this.render();

        // Bind event listeners
        this.bindEvents();

        this.isInitialized = true;
        this.log('Initialized');
    }

    /**
     * Render component UI
     * Override in subclasses to implement rendering logic
     * 
     * @returns {void}
     */
    render() {
        // To be implemented by subclasses
        this.log('Render called (no implementation)');
    }

    /**
     * Bind event listeners
     * Override in subclasses to attach event listeners
     * 
     * @returns {void}
     */
    bindEvents() {
        // To be implemented by subclasses
        this.log('BindEvents called (no implementation)');
    }

    /**
     * Update local component state and trigger re-render
     * 
     * @param {Object} updates - State updates to merge
     * @returns {void}
     */
    setState(updates) {
        this.localState = { ...this.localState, ...updates };
        this.log('State updated:', updates);
        
        // Trigger re-render
        this.render();
    }

    /**
     * Get local component state
     * 
     * @returns {Object} Current local state
     */
    getState() {
        return { ...this.localState };
    }

    /**
     * Get bound method (cached for performance)
     * Ensures 'this' context is preserved and method is only bound once
     * 
     * @param {string} methodName - Name of method to bind
     * @returns {Function} Bound method
     */
    getBoundMethod(methodName) {
        if (!this.boundMethods.has(methodName)) {
            if (typeof this[methodName] !== 'function') {
                throw new Error(`[${this.name}] Method ${methodName} does not exist`);
            }
            this.boundMethods.set(methodName, this[methodName].bind(this));
        }
        return this.boundMethods.get(methodName);
    }

    /**
     * Subscribe to event bus event with automatic cleanup
     * 
     * @param {string} event - Event name
     * @param {Function} callback - Callback function
     * @returns {Function} Unsubscribe function
     */
    subscribe(event, callback) {
        const unsubscribe = this.events.on(event, callback, this);
        this.unsubscribers.push(unsubscribe);
        this.log(`Subscribed to event: ${event}`);
        return unsubscribe;
    }

    /**
     * Subscribe to event once with automatic cleanup
     * 
     * @param {string} event - Event name
     * @param {Function} callback - Callback function
     * @returns {Function} Unsubscribe function
     */
    subscribeOnce(event, callback) {
        const unsubscribe = this.events.once(event, callback, this);
        this.unsubscribers.push(unsubscribe);
        this.log(`Subscribed once to event: ${event}`);
        return unsubscribe;
    }

    /**
     * Emit event through event bus
     * 
     * @param {string} event - Event name
     * @param {Object} data - Event data
     * @returns {void}
     */
    emit(event, data = {}) {
        this.events.emit(event, data);
        this.log(`Emitted event: ${event}`, data);
    }

    /**
     * Add DOM event listener with automatic cleanup
     * 
     * @param {HTMLElement} element - Element to attach listener to
     * @param {string} event - Event type
     * @param {Function} handler - Event handler
     * @param {Object} options - Event listener options
     * @returns {void}
     */
    addEventListener(element, event, handler, options = {}) {
        if (!element) {
            console.warn(`[${this.name}] Cannot add event listener: element is null`);
            return;
        }

        element.addEventListener(event, handler, options);

        // Store cleanup function
        this.unsubscribers.push(() => {
            element.removeEventListener(event, handler, options);
        });

        this.log(`Added DOM event listener: ${event}`);
    }

    /**
     * Query selector within component element
     * 
     * @param {string} selector - CSS selector
     * @returns {HTMLElement|null} Found element or null
     */
    $(selector) {
        return this.element ? this.element.querySelector(selector) : null;
    }

    /**
     * Query selector all within component element
     * 
     * @param {string} selector - CSS selector
     * @returns {NodeList} Found elements
     */
    $$(selector) {
        return this.element ? this.element.querySelectorAll(selector) : [];
    }

    /**
     * Show component element
     * 
     * @returns {void}
     */
    show() {
        if (this.element) {
            this.element.style.display = '';
            this.element.classList.remove('hidden');
            this.log('Component shown');
        }
    }

    /**
     * Hide component element
     * 
     * @returns {void}
     */
    hide() {
        if (this.element) {
            this.element.style.display = 'none';
            this.element.classList.add('hidden');
            this.log('Component hidden');
        }
    }

    /**
     * Enable component
     * 
     * @returns {void}
     */
    enable() {
        if (this.element) {
            this.element.classList.remove('disabled');
            const inputs = this.element.querySelectorAll('input, button, select, textarea');
            inputs.forEach(input => input.disabled = false);
            this.log('Component enabled');
        }
    }

    /**
     * Disable component
     * 
     * @returns {void}
     */
    disable() {
        if (this.element) {
            this.element.classList.add('disabled');
            const inputs = this.element.querySelectorAll('input, button, select, textarea');
            inputs.forEach(input => input.disabled = true);
            this.log('Component disabled');
        }
    }

    /**
     * Schedule DOM update using requestAnimationFrame
     * 
     * @param {string} key - Update key
     * @param {Function} callback - Update callback
     */
    scheduleUpdate(key, callback) {
        if (this.domOptimizer) {
            this.domOptimizer.scheduleFrame(`${this.name}-${key}`, callback);
        } else {
            requestAnimationFrame(callback);
        }
    }

    /**
     * Batch multiple DOM updates
     * 
     * @param {Function} updateFn - Update function
     */
    batchUpdate(updateFn) {
        if (this.domOptimizer) {
            this.domOptimizer.batchUpdate(updateFn);
        } else {
            requestAnimationFrame(updateFn);
        }
    }

    /**
     * Delegate event handler for better performance
     * 
     * @param {string} eventType - Event type
     * @param {string} selector - CSS selector
     * @param {Function} handler - Event handler
     * @returns {Function} Cleanup function
     */
    delegateEvent(eventType, selector, handler) {
        if (this.domOptimizer && this.element) {
            const cleanup = this.domOptimizer.delegate(
                this.element,
                eventType,
                selector,
                handler.bind(this)
            );
            this.unsubscribers.push(cleanup);
            return cleanup;
        } else {
            // Fallback to regular event listener
            const boundHandler = handler.bind(this);
            this.element?.addEventListener(eventType, (e) => {
                if (e.target.matches(selector)) {
                    boundHandler(e, e.target);
                }
            });
            return () => {};
        }
    }

    /**
     * Update element classes optimally
     * 
     * @param {HTMLElement} element - Target element
     * @param {Object} changes - Class changes
     */
    updateClasses(element, changes) {
        if (this.domOptimizer) {
            this.domOptimizer.updateClasses(element, changes);
        } else {
            if (changes.add) element.classList.add(...changes.add);
            if (changes.remove) element.classList.remove(...changes.remove);
            if (changes.toggle) changes.toggle.forEach(c => element.classList.toggle(c));
        }
    }

    /**
     * Update element styles optimally
     * 
     * @param {HTMLElement} element - Target element
     * @param {Object} styles - Style properties
     */
    updateStyles(element, styles) {
        if (this.domOptimizer) {
            this.domOptimizer.updateStyles(element, styles);
        } else {
            Object.assign(element.style, styles);
        }
    }

    /**
     * Debounce a function
     * 
     * @param {Function} func - Function to debounce
     * @param {number} wait - Wait time in milliseconds
     * @returns {Function} Debounced function
     */
    debounce(func, wait) {
        if (this.domOptimizer) {
            return this.domOptimizer.debounce(func, wait);
        }
        
        let timeout;
        return (...args) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    /**
     * Throttle a function
     * 
     * @param {Function} func - Function to throttle
     * @param {number} limit - Limit time in milliseconds
     * @returns {Function} Throttled function
     */
    throttle(func, limit) {
        let inThrottle;
        return (...args) => {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }

    /**
     * Handle error
     * 
     * @param {string} context - Error context
     * @param {Error} error - Error object
     * @returns {void}
     */
    handleError(context, error) {
        console.error(`[${this.name}] ${context}:`, error);

        // Emit error event
        this.emit('component:error', {
            component: this.name,
            context,
            error,
            timestamp: Date.now()
        });
    }

    /**
     * Log message (if debug mode enabled)
     * 
     * @param {...*} args - Arguments to log
     * @returns {void}
     */
    log(...args) {
        if (this.state?.config?.debug || window.masAdminConfig?.debug) {
            console.log(`[${this.name}]`, ...args);
        }
    }

    /**
     * Destroy component and cleanup all resources
     * 
     * @returns {void}
     */
    destroy() {
        if (this.isDestroyed) {
            console.warn(`[${this.name}] Already destroyed`);
            return;
        }

        this.log('Destroying...');

        // Unsubscribe from all events
        for (const unsubscribe of this.unsubscribers) {
            try {
                unsubscribe();
            } catch (error) {
                console.error(`[${this.name}] Error unsubscribing:`, error);
            }
        }
        this.unsubscribers = [];

        // Clear bound methods cache
        this.boundMethods.clear();

        // Clear local state
        this.localState = {};

        // Mark as destroyed
        this.isDestroyed = true;
        this.isInitialized = false;

        this.log('Destroyed');
    }

    /**
     * Refresh component (destroy and re-initialize)
     * 
     * @returns {void}
     */
    refresh() {
        this.log('Refreshing...');
        this.destroy();
        this.isDestroyed = false;
        this.init();
    }
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Component;
}

// Make available globally
if (typeof window !== 'undefined') {
    window.Component = Component;
}
