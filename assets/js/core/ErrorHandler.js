/**
 * Error Handler - Comprehensive error handling with recovery strategies
 * 
 * Provides custom error classes, retry mechanisms, and user-friendly error messages.
 * 
 * @class ErrorHandler
 */

/**
 * Base MAS Error class
 */
class MASError extends Error {
    constructor(message, code = 'mas_error', context = {}) {
        super(message);
        this.name = 'MASError';
        this.code = code;
        this.context = context;
        this.timestamp = Date.now();
    }

    toJSON() {
        return {
            name: this.name,
            message: this.message,
            code: this.code,
            context: this.context,
            timestamp: this.timestamp,
            stack: this.stack
        };
    }
}

/**
 * API Error class
 */
class MASAPIError extends MASError {
    constructor(message, code = 'api_error', status = 0, response = null) {
        super(message, code, { status, response });
        this.name = 'MASAPIError';
        this.status = status;
        this.response = response;
    }

    isNetworkError() {
        return this.status === 0 || this.code === 'network_error';
    }

    isAuthError() {
        return this.status === 401 || this.status === 403;
    }

    isValidationError() {
        return this.status === 400 && this.code === 'validation_failed';
    }

    isServerError() {
        return this.status >= 500;
    }

    isRetryable() {
        return this.isNetworkError() || this.isServerError();
    }
}

/**
 * Validation Error class
 */
class MASValidationError extends MASError {
    constructor(message, errors = {}) {
        super(message, 'validation_error', { errors });
        this.name = 'MASValidationError';
        this.errors = errors;
    }

    getFieldErrors() {
        return this.errors;
    }

    hasFieldError(field) {
        return this.errors.hasOwnProperty(field);
    }

    getFieldError(field) {
        return this.errors[field] || null;
    }
}

/**
 * Error Handler class
 */
class ErrorHandler {
    constructor(config = {}) {
        this.config = {
            maxRetries: config.maxRetries || 3,
            retryDelay: config.retryDelay || 1000,
            showNotifications: config.showNotifications !== false,
            logErrors: config.logErrors !== false,
            debug: config.debug || false
        };

        // Error history for debugging
        this.errorHistory = [];
        this.maxHistorySize = 50;

        // Retry queue
        this.retryQueue = new Map();
    }

    /**
     * Handle error with recovery strategies
     * 
     * @param {Error} error - Error to handle
     * @param {Object} options - Handling options
     * @returns {Object} Recovery result
     */
    handle(error, options = {}) {
        // Normalize error
        const normalizedError = this.normalizeError(error);

        // Add to history
        this.addToHistory(normalizedError);

        // Log error
        if (this.config.logErrors) {
            this.logError(normalizedError, options.context);
        }

        // Determine recovery strategy
        const strategy = this.determineRecoveryStrategy(normalizedError);

        // Execute recovery
        const result = {
            error: normalizedError,
            strategy,
            recovered: false,
            userMessage: this.getUserMessage(normalizedError),
            actions: this.getRecoveryActions(normalizedError)
        };

        if (this.config.debug) {
            console.log('[ErrorHandler] Handling error:', result);
        }

        return result;
    }

    /**
     * Normalize error to MASError
     * 
     * @param {Error|Object} error - Error to normalize
     * @returns {MASError} Normalized error
     */
    normalizeError(error) {
        // Already a MAS error
        if (error instanceof MASError) {
            return error;
        }

        // API error
        if (error.status !== undefined) {
            return new MASAPIError(
                error.message || 'API request failed',
                error.code || 'api_error',
                error.status,
                error.response
            );
        }

        // Validation error
        if (error.errors && typeof error.errors === 'object') {
            return new MASValidationError(
                error.message || 'Validation failed',
                error.errors
            );
        }

        // Generic error
        return new MASError(
            error.message || 'An error occurred',
            error.code || 'unknown_error',
            { originalError: error }
        );
    }

    /**
     * Determine recovery strategy
     * 
     * @param {MASError} error - Error to analyze
     * @returns {string} Recovery strategy
     */
    determineRecoveryStrategy(error) {
        if (error instanceof MASAPIError) {
            if (error.isNetworkError()) {
                return 'retry';
            }
            if (error.isAuthError()) {
                return 'reauth';
            }
            if (error.isValidationError()) {
                return 'validate';
            }
            if (error.isServerError()) {
                return 'retry';
            }
        }

        if (error instanceof MASValidationError) {
            return 'validate';
        }

        return 'notify';
    }

    /**
     * Get user-friendly error message
     * 
     * @param {MASError} error - Error object
     * @returns {string} User-friendly message
     */
    getUserMessage(error) {
        const messages = {
            'network_error': 'Network error. Please check your connection and try again.',
            'timeout': 'Request timed out. Please try again.',
            'rest_forbidden': 'You do not have permission to perform this action.',
            'rest_cookie_invalid_nonce': 'Your session has expired. Please refresh the page.',
            'validation_failed': 'Please check your input and try again.',
            'validation_error': 'Please correct the errors in the form.',
            'database_error': 'A database error occurred. Please try again.',
            'api_error': 'An error occurred while communicating with the server.',
            'unknown_error': 'An unexpected error occurred. Please try again.'
        };

        return messages[error.code] || error.message || messages['unknown_error'];
    }

    /**
     * Get recovery actions for error
     * 
     * @param {MASError} error - Error object
     * @returns {Array} Array of recovery actions
     */
    getRecoveryActions(error) {
        const actions = [];

        if (error instanceof MASAPIError) {
            if (error.isRetryable()) {
                actions.push({
                    label: 'Retry',
                    action: 'retry',
                    primary: true
                });
            }

            if (error.isAuthError()) {
                actions.push({
                    label: 'Refresh Page',
                    action: 'refresh',
                    primary: true
                });
            }
        }

        // Always offer dismiss
        actions.push({
            label: 'Dismiss',
            action: 'dismiss',
            primary: false
        });

        // Offer report for unexpected errors
        if (error.code === 'unknown_error' || error instanceof MASError && !error.code) {
            actions.push({
                label: 'Report Issue',
                action: 'report',
                primary: false
            });
        }

        return actions;
    }

    /**
     * Retry operation with exponential backoff
     * 
     * @param {Function} operation - Operation to retry
     * @param {Object} options - Retry options
     * @returns {Promise<*>} Operation result
     */
    async retry(operation, options = {}) {
        const maxRetries = options.maxRetries || this.config.maxRetries;
        const retryDelay = options.retryDelay || this.config.retryDelay;
        const operationId = options.id || this.generateOperationId();

        let lastError;

        for (let attempt = 0; attempt <= maxRetries; attempt++) {
            try {
                if (this.config.debug) {
                    console.log(`[ErrorHandler] Retry attempt ${attempt + 1}/${maxRetries + 1}`);
                }

                const result = await operation();
                
                // Success - remove from retry queue
                this.retryQueue.delete(operationId);
                
                return result;

            } catch (error) {
                lastError = error;

                if (this.config.debug) {
                    console.log(`[ErrorHandler] Attempt ${attempt + 1} failed:`, error);
                }

                // Don't retry on certain errors
                const normalizedError = this.normalizeError(error);
                if (normalizedError instanceof MASAPIError && !normalizedError.isRetryable()) {
                    break;
                }

                // Wait before retry with exponential backoff
                if (attempt < maxRetries) {
                    const delay = retryDelay * Math.pow(2, attempt);
                    await this.sleep(delay);
                }
            }
        }

        // All retries failed
        this.retryQueue.delete(operationId);
        throw lastError;
    }

    /**
     * Wrap operation with error handling
     * 
     * @param {Function} operation - Operation to wrap
     * @param {Object} options - Handling options
     * @returns {Promise<*>} Operation result
     */
    async wrap(operation, options = {}) {
        try {
            return await operation();
        } catch (error) {
            const result = this.handle(error, options);

            // Auto-retry if strategy is retry
            if (result.strategy === 'retry' && options.autoRetry !== false) {
                return await this.retry(operation, options);
            }

            throw result.error;
        }
    }

    /**
     * Log error
     * 
     * @param {MASError} error - Error to log
     * @param {string} context - Error context
     */
    logError(error, context = '') {
        const logData = {
            timestamp: new Date().toISOString(),
            context,
            error: error.toJSON ? error.toJSON() : error
        };

        console.error('[ErrorHandler]', context, error);

        // Could send to server logging endpoint here
        if (this.config.debug) {
            console.log('[ErrorHandler] Error logged:', logData);
        }
    }

    /**
     * Add error to history
     * 
     * @param {MASError} error - Error to add
     */
    addToHistory(error) {
        this.errorHistory.push({
            error,
            timestamp: Date.now()
        });

        // Limit history size
        if (this.errorHistory.length > this.maxHistorySize) {
            this.errorHistory.shift();
        }
    }

    /**
     * Get error history
     * 
     * @returns {Array} Error history
     */
    getHistory() {
        return [...this.errorHistory];
    }

    /**
     * Clear error history
     */
    clearHistory() {
        this.errorHistory = [];
    }

    /**
     * Generate operation ID
     * 
     * @returns {string} Unique operation ID
     */
    generateOperationId() {
        return `op_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    }

    /**
     * Sleep utility
     * 
     * @param {number} ms - Milliseconds to sleep
     * @returns {Promise} Sleep promise
     */
    sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    /**
     * Create error from validation errors
     * 
     * @param {Object} errors - Validation errors
     * @param {string} message - Error message
     * @returns {MASValidationError} Validation error
     */
    static createValidationError(errors, message = 'Validation failed') {
        return new MASValidationError(message, errors);
    }

    /**
     * Create API error
     * 
     * @param {string} message - Error message
     * @param {string} code - Error code
     * @param {number} status - HTTP status
     * @param {Object} response - Response data
     * @returns {MASAPIError} API error
     */
    static createAPIError(message, code, status, response = null) {
        return new MASAPIError(message, code, status, response);
    }

    /**
     * Check if error is retryable
     * 
     * @param {Error} error - Error to check
     * @returns {boolean} Whether error is retryable
     */
    static isRetryable(error) {
        const normalized = new ErrorHandler().normalizeError(error);
        return normalized instanceof MASAPIError && normalized.isRetryable();
    }
}

// Export classes
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        ErrorHandler,
        MASError,
        MASAPIError,
        MASValidationError
    };
}

// Make available globally for WordPress
if (typeof window !== 'undefined') {
    window.ErrorHandler = ErrorHandler;
    window.MASError = MASError;
    window.MASAPIError = MASAPIError;
    window.MASValidationError = MASValidationError;
}
