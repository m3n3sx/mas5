/**
 * DOM Optimizer Utility
 * 
 * Provides utilities for optimizing DOM updates and rendering.
 * Includes requestAnimationFrame helpers, event delegation,
 * and batch update utilities.
 * 
 * @class DOMOptimizer
 */
class DOMOptimizer {
    constructor() {
        // Track pending animation frames
        this.pendingFrames = new Map();
        
        // Event delegation handlers
        this.delegatedHandlers = new Map();
        
        // Batch update queue
        this.batchQueue = [];
        this.batchScheduled = false;
    }

    /**
     * Schedule a function to run on next animation frame
     * Automatically cancels previous frame if called multiple times
     * 
     * @param {string} key - Unique key for this animation
     * @param {Function} callback - Function to execute
     * @returns {number} Animation frame ID
     */
    scheduleFrame(key, callback) {
        // Cancel existing frame for this key
        if (this.pendingFrames.has(key)) {
            cancelAnimationFrame(this.pendingFrames.get(key));
        }

        // Schedule new frame
        const frameId = requestAnimationFrame(() => {
            callback();
            this.pendingFrames.delete(key);
        });

        this.pendingFrames.set(key, frameId);
        return frameId;
    }

    /**
     * Cancel a scheduled animation frame
     * 
     * @param {string} key - Animation key
     */
    cancelFrame(key) {
        if (this.pendingFrames.has(key)) {
            cancelAnimationFrame(this.pendingFrames.get(key));
            this.pendingFrames.delete(key);
        }
    }

    /**
     * Cancel all pending animation frames
     */
    cancelAllFrames() {
        for (const frameId of this.pendingFrames.values()) {
            cancelAnimationFrame(frameId);
        }
        this.pendingFrames.clear();
    }

    /**
     * Batch multiple DOM updates together
     * Uses requestAnimationFrame to batch updates
     * 
     * @param {Function} updateFn - Function that performs DOM updates
     */
    batchUpdate(updateFn) {
        this.batchQueue.push(updateFn);

        if (!this.batchScheduled) {
            this.batchScheduled = true;
            requestAnimationFrame(() => {
                this.flushBatchQueue();
            });
        }
    }

    /**
     * Flush the batch update queue
     */
    flushBatchQueue() {
        // Execute all queued updates
        while (this.batchQueue.length > 0) {
            const updateFn = this.batchQueue.shift();
            try {
                updateFn();
            } catch (error) {
                console.error('Error in batch update:', error);
            }
        }

        this.batchScheduled = false;
    }

    /**
     * Create a DocumentFragment for batch DOM insertions
     * More efficient than inserting elements one by one
     * 
     * @param {Array<HTMLElement>} elements - Elements to add to fragment
     * @returns {DocumentFragment} Document fragment
     */
    createFragment(elements) {
        const fragment = document.createDocumentFragment();
        
        for (const element of elements) {
            if (element instanceof HTMLElement) {
                fragment.appendChild(element);
            }
        }

        return fragment;
    }

    /**
     * Batch insert multiple elements using DocumentFragment
     * 
     * @param {HTMLElement} container - Container element
     * @param {Array<HTMLElement>} elements - Elements to insert
     * @param {string} position - Insert position ('append', 'prepend', 'replace')
     */
    batchInsert(container, elements, position = 'append') {
        if (!container || !elements || elements.length === 0) {
            return;
        }

        const fragment = this.createFragment(elements);

        this.scheduleFrame(`batch-insert-${container.id || 'unknown'}`, () => {
            switch (position) {
                case 'prepend':
                    container.insertBefore(fragment, container.firstChild);
                    break;
                case 'replace':
                    container.innerHTML = '';
                    container.appendChild(fragment);
                    break;
                case 'append':
                default:
                    container.appendChild(fragment);
                    break;
            }
        });
    }

    /**
     * Setup event delegation for better performance
     * Instead of attaching handlers to many elements, attach one to parent
     * 
     * @param {HTMLElement} container - Container element
     * @param {string} eventType - Event type (e.g., 'click')
     * @param {string} selector - CSS selector for target elements
     * @param {Function} handler - Event handler function
     * @returns {Function} Cleanup function
     */
    delegate(container, eventType, selector, handler) {
        if (!container) {
            throw new Error('Container element is required');
        }

        const delegateKey = `${eventType}-${selector}`;
        
        // Create wrapper handler
        const wrapperHandler = (event) => {
            const target = event.target.closest(selector);
            if (target && container.contains(target)) {
                handler.call(target, event, target);
            }
        };

        // Store handler for cleanup
        if (!this.delegatedHandlers.has(container)) {
            this.delegatedHandlers.set(container, new Map());
        }
        this.delegatedHandlers.get(container).set(delegateKey, wrapperHandler);

        // Attach event listener
        container.addEventListener(eventType, wrapperHandler);

        // Return cleanup function
        return () => {
            container.removeEventListener(eventType, wrapperHandler);
            const handlers = this.delegatedHandlers.get(container);
            if (handlers) {
                handlers.delete(delegateKey);
                if (handlers.size === 0) {
                    this.delegatedHandlers.delete(container);
                }
            }
        };
    }

    /**
     * Remove all delegated event handlers for a container
     * 
     * @param {HTMLElement} container - Container element
     */
    removeDelegated(container) {
        const handlers = this.delegatedHandlers.get(container);
        if (!handlers) {
            return;
        }

        // Remove all handlers
        for (const [key, handler] of handlers) {
            const [eventType] = key.split('-');
            container.removeEventListener(eventType, handler);
        }

        this.delegatedHandlers.delete(container);
    }

    /**
     * Minimize reflows by reading all layout properties first,
     * then making all changes
     * 
     * @param {Array<Object>} operations - Array of {element, read, write} objects
     */
    batchReadWrite(operations) {
        // Read phase - get all layout properties
        const readResults = operations.map(op => {
            if (op.read) {
                return op.read(op.element);
            }
            return null;
        });

        // Write phase - make all DOM changes
        this.scheduleFrame('batch-read-write', () => {
            operations.forEach((op, index) => {
                if (op.write) {
                    op.write(op.element, readResults[index]);
                }
            });
        });
    }

    /**
     * Optimize class list changes
     * Batch multiple class changes together
     * 
     * @param {HTMLElement} element - Target element
     * @param {Object} changes - Object with add/remove/toggle arrays
     */
    updateClasses(element, changes) {
        if (!element) {
            return;
        }

        this.scheduleFrame(`classes-${element.id || 'unknown'}`, () => {
            if (changes.add) {
                element.classList.add(...changes.add);
            }
            if (changes.remove) {
                element.classList.remove(...changes.remove);
            }
            if (changes.toggle) {
                changes.toggle.forEach(className => {
                    element.classList.toggle(className);
                });
            }
        });
    }

    /**
     * Optimize style changes
     * Batch multiple style changes together
     * 
     * @param {HTMLElement} element - Target element
     * @param {Object} styles - Object with style properties
     */
    updateStyles(element, styles) {
        if (!element || !styles) {
            return;
        }

        this.scheduleFrame(`styles-${element.id || 'unknown'}`, () => {
            Object.assign(element.style, styles);
        });
    }

    /**
     * Optimize attribute changes
     * Batch multiple attribute changes together
     * 
     * @param {HTMLElement} element - Target element
     * @param {Object} attributes - Object with attribute key-value pairs
     */
    updateAttributes(element, attributes) {
        if (!element || !attributes) {
            return;
        }

        this.scheduleFrame(`attrs-${element.id || 'unknown'}`, () => {
            for (const [key, value] of Object.entries(attributes)) {
                if (value === null || value === undefined) {
                    element.removeAttribute(key);
                } else {
                    element.setAttribute(key, value);
                }
            }
        });
    }

    /**
     * Measure element dimensions without causing reflow
     * Uses ResizeObserver if available
     * 
     * @param {HTMLElement} element - Element to measure
     * @param {Function} callback - Callback with dimensions
     * @returns {Function} Cleanup function
     */
    observeSize(element, callback) {
        if (!element) {
            return () => {};
        }

        if ('ResizeObserver' in window) {
            const observer = new ResizeObserver((entries) => {
                for (const entry of entries) {
                    callback({
                        width: entry.contentRect.width,
                        height: entry.contentRect.height,
                        element: entry.target
                    });
                }
            });

            observer.observe(element);

            return () => observer.disconnect();
        } else {
            // Fallback: use requestAnimationFrame
            let lastWidth = element.offsetWidth;
            let lastHeight = element.offsetHeight;

            const check = () => {
                const width = element.offsetWidth;
                const height = element.offsetHeight;

                if (width !== lastWidth || height !== lastHeight) {
                    lastWidth = width;
                    lastHeight = height;
                    callback({ width, height, element });
                }

                this.scheduleFrame('size-observer', check);
            };

            check();

            return () => this.cancelFrame('size-observer');
        }
    }

    /**
     * Debounce a function
     * 
     * @param {Function} func - Function to debounce
     * @param {number} delay - Delay in milliseconds
     * @returns {Function} Debounced function
     */
    debounce(func, delay) {
        let timeoutId = null;

        return function (...args) {
            if (timeoutId) {
                clearTimeout(timeoutId);
            }

            timeoutId = setTimeout(() => {
                func.apply(this, args);
                timeoutId = null;
            }, delay);
        };
    }

    /**
     * Throttle a function
     * 
     * @param {Function} func - Function to throttle
     * @param {number} delay - Delay in milliseconds
     * @returns {Function} Throttled function
     */
    throttle(func, delay) {
        let lastCall = 0;
        let timeoutId = null;

        return function (...args) {
            const now = Date.now();
            const timeSinceLastCall = now - lastCall;

            if (timeSinceLastCall >= delay) {
                lastCall = now;
                func.apply(this, args);
            } else {
                if (timeoutId) {
                    clearTimeout(timeoutId);
                }
                timeoutId = setTimeout(() => {
                    lastCall = Date.now();
                    func.apply(this, args);
                }, delay - timeSinceLastCall);
            }
        };
    }

    /**
     * Check if element is in viewport
     * 
     * @param {HTMLElement} element - Element to check
     * @param {number} threshold - Threshold percentage (0-1)
     * @returns {boolean} Whether element is in viewport
     */
    isInViewport(element, threshold = 0) {
        if (!element) {
            return false;
        }

        const rect = element.getBoundingClientRect();
        const windowHeight = window.innerHeight || document.documentElement.clientHeight;
        const windowWidth = window.innerWidth || document.documentElement.clientWidth;

        const vertInView = (rect.top <= windowHeight) && ((rect.top + rect.height) >= 0);
        const horInView = (rect.left <= windowWidth) && ((rect.left + rect.width) >= 0);

        return vertInView && horInView;
    }

    /**
     * Observe element visibility
     * Uses IntersectionObserver if available
     * 
     * @param {HTMLElement} element - Element to observe
     * @param {Function} callback - Callback when visibility changes
     * @param {Object} options - IntersectionObserver options
     * @returns {Function} Cleanup function
     */
    observeVisibility(element, callback, options = {}) {
        if (!element) {
            return () => {};
        }

        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    callback({
                        isVisible: entry.isIntersecting,
                        ratio: entry.intersectionRatio,
                        element: entry.target
                    });
                });
            }, {
                threshold: options.threshold || 0,
                rootMargin: options.rootMargin || '0px'
            });

            observer.observe(element);

            return () => observer.disconnect();
        } else {
            // Fallback: use scroll event
            const checkVisibility = this.throttle(() => {
                const isVisible = this.isInViewport(element);
                callback({ isVisible, ratio: isVisible ? 1 : 0, element });
            }, 100);

            window.addEventListener('scroll', checkVisibility, { passive: true });
            checkVisibility(); // Initial check

            return () => window.removeEventListener('scroll', checkVisibility);
        }
    }

    /**
     * Clean up all resources
     */
    destroy() {
        // Cancel all pending frames
        this.cancelAllFrames();

        // Clear batch queue
        this.batchQueue = [];
        this.batchScheduled = false;

        // Remove all delegated handlers
        for (const container of this.delegatedHandlers.keys()) {
            this.removeDelegated(container);
        }
    }

    /**
     * Get statistics
     * 
     * @returns {Object} Statistics
     */
    getStats() {
        return {
            pendingFrames: this.pendingFrames.size,
            batchQueueSize: this.batchQueue.length,
            delegatedContainers: this.delegatedHandlers.size
        };
    }
}

// Create singleton instance
const domOptimizer = new DOMOptimizer();

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DOMOptimizer;
    module.exports.domOptimizer = domOptimizer;
}

// Make available globally
if (typeof window !== 'undefined') {
    window.DOMOptimizer = DOMOptimizer;
    window.domOptimizer = domOptimizer;
}
