/**
 * Debouncer Utility
 * 
 * Provides debouncing and throttling utilities for performance optimization.
 * Useful for handling frequent events like input changes, scroll, and resize.
 * 
 * @class Debouncer
 */
class Debouncer {
    constructor() {
        // Store active timers for cleanup
        this.timers = new Map();
        this.throttleTimers = new Map();
    }

    /**
     * Debounce a function
     * Delays execution until after wait time has elapsed since last call
     * 
     * @param {Function} func - Function to debounce
     * @param {number} wait - Wait time in milliseconds
     * @param {Object} options - Options
     * @param {boolean} options.leading - Execute on leading edge
     * @param {boolean} options.trailing - Execute on trailing edge (default: true)
     * @param {number} options.maxWait - Maximum time to wait before forcing execution
     * @returns {Function} Debounced function
     */
    debounce(func, wait, options = {}) {
        const {
            leading = false,
            trailing = true,
            maxWait = null
        } = options;

        let timeout;
        let maxTimeout;
        let lastCallTime;
        let lastInvokeTime = 0;
        let lastArgs;
        let lastThis;
        let result;

        const invokeFunc = (time) => {
            const args = lastArgs;
            const thisArg = lastThis;

            lastArgs = lastThis = undefined;
            lastInvokeTime = time;
            result = func.apply(thisArg, args);
            return result;
        };

        const leadingEdge = (time) => {
            lastInvokeTime = time;
            
            // Start timer for trailing edge
            timeout = setTimeout(timerExpired, wait);
            
            // Execute on leading edge
            return leading ? invokeFunc(time) : result;
        };

        const remainingWait = (time) => {
            const timeSinceLastCall = time - lastCallTime;
            const timeSinceLastInvoke = time - lastInvokeTime;
            const timeWaiting = wait - timeSinceLastCall;

            return maxWait !== null
                ? Math.min(timeWaiting, maxWait - timeSinceLastInvoke)
                : timeWaiting;
        };

        const shouldInvoke = (time) => {
            const timeSinceLastCall = time - lastCallTime;
            const timeSinceLastInvoke = time - lastInvokeTime;

            return (
                lastCallTime === undefined ||
                timeSinceLastCall >= wait ||
                timeSinceLastCall < 0 ||
                (maxWait !== null && timeSinceLastInvoke >= maxWait)
            );
        };

        const timerExpired = () => {
            const time = Date.now();
            
            if (shouldInvoke(time)) {
                return trailingEdge(time);
            }
            
            // Restart timer
            timeout = setTimeout(timerExpired, remainingWait(time));
        };

        const trailingEdge = (time) => {
            timeout = undefined;

            // Execute on trailing edge only if function was called
            if (trailing && lastArgs) {
                return invokeFunc(time);
            }
            
            lastArgs = lastThis = undefined;
            return result;
        };

        const cancel = () => {
            if (timeout !== undefined) {
                clearTimeout(timeout);
            }
            if (maxTimeout !== undefined) {
                clearTimeout(maxTimeout);
            }
            lastInvokeTime = 0;
            lastArgs = lastCallTime = lastThis = timeout = maxTimeout = undefined;
        };

        const flush = () => {
            return timeout === undefined ? result : trailingEdge(Date.now());
        };

        const pending = () => {
            return timeout !== undefined;
        };

        const debounced = function(...args) {
            const time = Date.now();
            const isInvoking = shouldInvoke(time);

            lastArgs = args;
            lastThis = this;
            lastCallTime = time;

            if (isInvoking) {
                if (timeout === undefined) {
                    return leadingEdge(lastCallTime);
                }
                if (maxWait !== null) {
                    // Handle invocations in a tight loop
                    timeout = setTimeout(timerExpired, wait);
                    return invokeFunc(lastCallTime);
                }
            }
            
            if (timeout === undefined) {
                timeout = setTimeout(timerExpired, wait);
            }
            
            return result;
        };

        debounced.cancel = cancel;
        debounced.flush = flush;
        debounced.pending = pending;

        return debounced;
    }

    /**
     * Throttle a function
     * Ensures function is called at most once per specified time period
     * 
     * @param {Function} func - Function to throttle
     * @param {number} wait - Wait time in milliseconds
     * @param {Object} options - Options
     * @param {boolean} options.leading - Execute on leading edge (default: true)
     * @param {boolean} options.trailing - Execute on trailing edge (default: true)
     * @returns {Function} Throttled function
     */
    throttle(func, wait, options = {}) {
        const {
            leading = true,
            trailing = true
        } = options;

        return this.debounce(func, wait, {
            leading,
            trailing,
            maxWait: wait
        });
    }

    /**
     * Create a debounced function with ID for tracking
     * Useful for managing multiple debounced functions
     * 
     * @param {string} id - Unique identifier
     * @param {Function} func - Function to debounce
     * @param {number} wait - Wait time in milliseconds
     * @param {Object} options - Options
     * @returns {Function} Debounced function
     */
    createDebounced(id, func, wait, options = {}) {
        // Cancel existing debounced function with same ID
        if (this.timers.has(id)) {
            const existing = this.timers.get(id);
            if (existing.cancel) {
                existing.cancel();
            }
        }

        const debounced = this.debounce(func, wait, options);
        this.timers.set(id, debounced);

        return debounced;
    }

    /**
     * Create a throttled function with ID for tracking
     * 
     * @param {string} id - Unique identifier
     * @param {Function} func - Function to throttle
     * @param {number} wait - Wait time in milliseconds
     * @param {Object} options - Options
     * @returns {Function} Throttled function
     */
    createThrottled(id, func, wait, options = {}) {
        // Cancel existing throttled function with same ID
        if (this.throttleTimers.has(id)) {
            const existing = this.throttleTimers.get(id);
            if (existing.cancel) {
                existing.cancel();
            }
        }

        const throttled = this.throttle(func, wait, options);
        this.throttleTimers.set(id, throttled);

        return throttled;
    }

    /**
     * Cancel a debounced function by ID
     * 
     * @param {string} id - Function identifier
     */
    cancel(id) {
        if (this.timers.has(id)) {
            const debounced = this.timers.get(id);
            if (debounced.cancel) {
                debounced.cancel();
            }
            this.timers.delete(id);
        }

        if (this.throttleTimers.has(id)) {
            const throttled = this.throttleTimers.get(id);
            if (throttled.cancel) {
                throttled.cancel();
            }
            this.throttleTimers.delete(id);
        }
    }

    /**
     * Flush a debounced function by ID
     * Immediately executes pending invocation
     * 
     * @param {string} id - Function identifier
     */
    flush(id) {
        if (this.timers.has(id)) {
            const debounced = this.timers.get(id);
            if (debounced.flush) {
                return debounced.flush();
            }
        }

        if (this.throttleTimers.has(id)) {
            const throttled = this.throttleTimers.get(id);
            if (throttled.flush) {
                return throttled.flush();
            }
        }
    }

    /**
     * Cancel all tracked debounced/throttled functions
     */
    cancelAll() {
        for (const [id, debounced] of this.timers) {
            if (debounced.cancel) {
                debounced.cancel();
            }
        }
        this.timers.clear();

        for (const [id, throttled] of this.throttleTimers) {
            if (throttled.cancel) {
                throttled.cancel();
            }
        }
        this.throttleTimers.clear();
    }

    /**
     * Check if a debounced function is pending
     * 
     * @param {string} id - Function identifier
     * @returns {boolean} Whether function is pending
     */
    isPending(id) {
        if (this.timers.has(id)) {
            const debounced = this.timers.get(id);
            if (debounced.pending) {
                return debounced.pending();
            }
        }

        if (this.throttleTimers.has(id)) {
            const throttled = this.throttleTimers.get(id);
            if (throttled.pending) {
                return throttled.pending();
            }
        }

        return false;
    }

    /**
     * Request animation frame wrapper
     * Ensures function is called on next animation frame
     * 
     * @param {Function} func - Function to call
     * @returns {number} Request ID
     */
    raf(func) {
        if (typeof requestAnimationFrame !== 'undefined') {
            return requestAnimationFrame(func);
        } else {
            // Fallback for environments without requestAnimationFrame
            return setTimeout(func, 16); // ~60fps
        }
    }

    /**
     * Cancel animation frame
     * 
     * @param {number} id - Request ID
     */
    cancelRaf(id) {
        if (typeof cancelAnimationFrame !== 'undefined') {
            cancelAnimationFrame(id);
        } else {
            clearTimeout(id);
        }
    }

    /**
     * Debounce with requestAnimationFrame
     * Useful for DOM updates and animations
     * 
     * @param {Function} func - Function to debounce
     * @returns {Function} Debounced function
     */
    debounceRaf(func) {
        let rafId;
        let pending = false;

        const debounced = (...args) => {
            if (pending) {
                return;
            }

            pending = true;
            rafId = this.raf(() => {
                func(...args);
                pending = false;
            });
        };

        debounced.cancel = () => {
            if (rafId) {
                this.cancelRaf(rafId);
                pending = false;
            }
        };

        return debounced;
    }

    /**
     * Create a rate limiter
     * Limits function calls to a maximum rate
     * 
     * @param {Function} func - Function to rate limit
     * @param {number} maxCalls - Maximum calls
     * @param {number} timeWindow - Time window in milliseconds
     * @returns {Function} Rate limited function
     */
    rateLimit(func, maxCalls, timeWindow) {
        const calls = [];

        return (...args) => {
            const now = Date.now();
            
            // Remove calls outside time window
            while (calls.length > 0 && calls[0] < now - timeWindow) {
                calls.shift();
            }

            // Check if rate limit exceeded
            if (calls.length >= maxCalls) {
                console.warn('[Debouncer] Rate limit exceeded');
                return;
            }

            // Record call and execute
            calls.push(now);
            return func(...args);
        };
    }
}

// Create singleton instance
const debouncer = new Debouncer();

// Export standalone functions for convenience
const debounce = (func, wait, options) => debouncer.debounce(func, wait, options);
const throttle = (func, wait, options) => debouncer.throttle(func, wait, options);

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Debouncer;
    module.exports.debouncer = debouncer;
    module.exports.debounce = debounce;
    module.exports.throttle = throttle;
}

// Make available globally for WordPress
if (typeof window !== 'undefined') {
    window.Debouncer = Debouncer;
    window.debouncer = debouncer;
    window.debounce = debounce;
    window.throttle = throttle;
}
