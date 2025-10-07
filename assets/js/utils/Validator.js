/**
 * Validator Utility
 * 
 * Provides validation rules and methods for form fields.
 * Supports color validation, CSS units, text fields, and custom rules.
 * 
 * @class Validator
 */
class Validator {
    constructor() {
        // Built-in validation rules
        this.rules = new Map();
        this.setupBuiltInRules();
    }

    /**
     * Setup built-in validation rules
     */
    setupBuiltInRules() {
        // Required field
        this.rules.set('required', {
            validate: (value) => {
                return value !== null && value !== undefined && value !== '';
            },
            message: 'This field is required'
        });

        // Email validation
        this.rules.set('email', {
            validate: (value) => {
                if (!value) return true; // Empty is valid unless required
                return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
            },
            message: 'Please enter a valid email address'
        });

        // URL validation
        this.rules.set('url', {
            validate: (value) => {
                if (!value) return true;
                try {
                    new URL(value);
                    return true;
                } catch {
                    return false;
                }
            },
            message: 'Please enter a valid URL'
        });

        // Hex color validation
        this.rules.set('hexColor', {
            validate: (value) => {
                if (!value) return true;
                return /^#[0-9A-F]{6}$/i.test(value);
            },
            message: 'Please enter a valid hex color (e.g., #FF0000)'
        });

        // RGB color validation
        this.rules.set('rgbColor', {
            validate: (value) => {
                if (!value) return true;
                return /^rgb\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*\)$/i.test(value);
            },
            message: 'Please enter a valid RGB color (e.g., rgb(255, 0, 0))'
        });

        // RGBA color validation
        this.rules.set('rgbaColor', {
            validate: (value) => {
                if (!value) return true;
                return /^rgba\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*,\s*[\d.]+\s*\)$/i.test(value);
            },
            message: 'Please enter a valid RGBA color (e.g., rgba(255, 0, 0, 0.5))'
        });

        // CSS unit validation (px, em, rem, %, vh, vw)
        this.rules.set('cssUnit', {
            validate: (value) => {
                if (!value) return true;
                return /^\d+(\.\d+)?(px|em|rem|%|vh|vw|pt|cm|mm|in)$/i.test(value);
            },
            message: 'Please enter a valid CSS unit (e.g., 10px, 1.5em, 50%)'
        });

        // Number validation
        this.rules.set('number', {
            validate: (value) => {
                if (!value) return true;
                return !isNaN(parseFloat(value)) && isFinite(value);
            },
            message: 'Please enter a valid number'
        });

        // Integer validation
        this.rules.set('integer', {
            validate: (value) => {
                if (!value) return true;
                return Number.isInteger(Number(value));
            },
            message: 'Please enter a valid integer'
        });

        // Min value validation
        this.rules.set('min', {
            validate: (value, params) => {
                if (!value) return true;
                const num = parseFloat(value);
                return !isNaN(num) && num >= params.min;
            },
            message: (params) => `Value must be at least ${params.min}`
        });

        // Max value validation
        this.rules.set('max', {
            validate: (value, params) => {
                if (!value) return true;
                const num = parseFloat(value);
                return !isNaN(num) && num <= params.max;
            },
            message: (params) => `Value must be at most ${params.max}`
        });

        // Min length validation
        this.rules.set('minLength', {
            validate: (value, params) => {
                if (!value) return true;
                return String(value).length >= params.minLength;
            },
            message: (params) => `Must be at least ${params.minLength} characters`
        });

        // Max length validation
        this.rules.set('maxLength', {
            validate: (value, params) => {
                if (!value) return true;
                return String(value).length <= params.maxLength;
            },
            message: (params) => `Must be at most ${params.maxLength} characters`
        });

        // Pattern validation (regex)
        this.rules.set('pattern', {
            validate: (value, params) => {
                if (!value) return true;
                const regex = new RegExp(params.pattern);
                return regex.test(value);
            },
            message: (params) => params.message || 'Invalid format'
        });

        // Alpha (letters only)
        this.rules.set('alpha', {
            validate: (value) => {
                if (!value) return true;
                return /^[a-zA-Z]+$/.test(value);
            },
            message: 'Only letters are allowed'
        });

        // Alphanumeric
        this.rules.set('alphanumeric', {
            validate: (value) => {
                if (!value) return true;
                return /^[a-zA-Z0-9]+$/.test(value);
            },
            message: 'Only letters and numbers are allowed'
        });

        // Slug (URL-friendly)
        this.rules.set('slug', {
            validate: (value) => {
                if (!value) return true;
                return /^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(value);
            },
            message: 'Only lowercase letters, numbers, and hyphens are allowed'
        });
    }

    /**
     * Add custom validation rule
     * 
     * @param {string} name - Rule name
     * @param {Function} validate - Validation function
     * @param {string|Function} message - Error message or message generator
     */
    addRule(name, validate, message) {
        this.rules.set(name, {
            validate,
            message
        });
    }

    /**
     * Validate a single value against a rule
     * 
     * @param {*} value - Value to validate
     * @param {string} ruleName - Rule name
     * @param {Object} params - Rule parameters
     * @returns {Object} Validation result { valid: boolean, message: string }
     */
    validateValue(value, ruleName, params = {}) {
        const rule = this.rules.get(ruleName);

        if (!rule) {
            console.warn(`[Validator] Rule "${ruleName}" not found`);
            return { valid: true, message: '' };
        }

        const isValid = rule.validate(value, params);

        if (isValid) {
            return { valid: true, message: '' };
        }

        // Get error message
        let message;
        if (typeof rule.message === 'function') {
            message = rule.message(params);
        } else {
            message = rule.message;
        }

        return { valid: false, message };
    }

    /**
     * Validate a field against multiple rules
     * 
     * @param {*} value - Value to validate
     * @param {Array|string} rules - Rule name(s) or array of rule objects
     * @returns {Object} Validation result { valid: boolean, errors: Array }
     */
    validate(value, rules) {
        const errors = [];

        // Convert string to array
        if (typeof rules === 'string') {
            rules = [{ rule: rules }];
        }

        // Convert simple array to rule objects
        if (Array.isArray(rules) && rules.length > 0 && typeof rules[0] === 'string') {
            rules = rules.map(rule => ({ rule }));
        }

        // Validate against each rule
        for (const ruleConfig of rules) {
            const ruleName = ruleConfig.rule;
            const params = ruleConfig.params || {};
            const customMessage = ruleConfig.message;

            const result = this.validateValue(value, ruleName, params);

            if (!result.valid) {
                errors.push(customMessage || result.message);
            }
        }

        return {
            valid: errors.length === 0,
            errors
        };
    }

    /**
     * Validate multiple fields
     * 
     * @param {Object} data - Data object with field values
     * @param {Object} schema - Validation schema
     * @returns {Object} Validation result { valid: boolean, errors: Object }
     */
    validateFields(data, schema) {
        const errors = {};
        let isValid = true;

        for (const [field, rules] of Object.entries(schema)) {
            const value = data[field];
            const result = this.validate(value, rules);

            if (!result.valid) {
                errors[field] = result.errors;
                isValid = false;
            }
        }

        return {
            valid: isValid,
            errors
        };
    }

    /**
     * Validate settings object
     * Convenience method for validating plugin settings
     * 
     * @param {Object} settings - Settings object
     * @returns {Object} Validation result { valid: boolean, errors: Array }
     */
    validateSettings(settings) {
        const schema = {
            // Color fields
            menu_background: [{ rule: 'hexColor' }],
            menu_text_color: [{ rule: 'hexColor' }],
            menu_hover_background: [{ rule: 'hexColor' }],
            menu_hover_text_color: [{ rule: 'hexColor' }],
            menu_active_background: [{ rule: 'hexColor' }],
            menu_active_text_color: [{ rule: 'hexColor' }],
            admin_bar_background: [{ rule: 'hexColor' }],
            admin_bar_text_color: [{ rule: 'hexColor' }],

            // CSS unit fields
            menu_width: [{ rule: 'cssUnit' }],
            menu_item_height: [{ rule: 'cssUnit' }],
            menu_border_radius: [{ rule: 'cssUnit' }],

            // Numeric fields
            glassmorphism_blur: [
                { rule: 'number' },
                { rule: 'min', params: { min: 0 } },
                { rule: 'max', params: { max: 50 } }
            ]
        };

        const result = this.validateFields(settings, schema);

        // Convert errors object to array format
        const errorArray = [];
        for (const [field, messages] of Object.entries(result.errors)) {
            errorArray.push({
                field,
                message: messages[0] // Use first error message
            });
        }

        return {
            valid: result.valid,
            errors: errorArray
        };
    }

    /**
     * Show validation errors in form
     * Highlights fields and displays error messages
     * 
     * @param {HTMLFormElement} form - Form element
     * @param {Array} errors - Array of error objects { field, message }
     */
    showValidationErrors(form, errors) {
        // Clear existing errors first
        this.clearValidationErrors(form);

        for (const error of errors) {
            const field = form.elements[error.field];

            if (!field) {
                continue;
            }

            // Add error class
            field.classList.add('error', 'invalid');

            // Add ARIA attributes for accessibility
            field.setAttribute('aria-invalid', 'true');

            // Create or update error message
            let errorElement = field.parentNode.querySelector('.field-error');

            if (!errorElement) {
                errorElement = document.createElement('span');
                errorElement.className = 'field-error';
                errorElement.setAttribute('role', 'alert');
                field.parentNode.appendChild(errorElement);
            }

            errorElement.textContent = error.message;
            errorElement.style.display = 'block';

            // Link error message to field for screen readers
            const errorId = `${error.field}-error`;
            errorElement.id = errorId;
            field.setAttribute('aria-describedby', errorId);

            // Remove error on field change
            const removeError = () => {
                field.classList.remove('error', 'invalid');
                field.removeAttribute('aria-invalid');
                field.removeAttribute('aria-describedby');
                
                if (errorElement) {
                    errorElement.style.display = 'none';
                }
                
                field.removeEventListener('input', removeError);
                field.removeEventListener('change', removeError);
            };

            field.addEventListener('input', removeError);
            field.addEventListener('change', removeError);
        }

        // Scroll to first error
        if (errors.length > 0) {
            const firstErrorField = form.elements[errors[0].field];
            if (firstErrorField) {
                firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstErrorField.focus();
            }
        }
    }

    /**
     * Clear all validation errors from form
     * 
     * @param {HTMLFormElement} form - Form element
     */
    clearValidationErrors(form) {
        // Remove error classes
        const errorFields = form.querySelectorAll('.error, .invalid');
        errorFields.forEach(field => {
            field.classList.remove('error', 'invalid');
            field.removeAttribute('aria-invalid');
            field.removeAttribute('aria-describedby');
        });

        // Hide error messages
        const errorMessages = form.querySelectorAll('.field-error');
        errorMessages.forEach(msg => {
            msg.style.display = 'none';
        });
    }

    /**
     * Real-time validation for a field
     * Returns a debounced validation function
     * 
     * @param {HTMLElement} field - Field element
     * @param {Array|string} rules - Validation rules
     * @param {Function} callback - Callback function with validation result
     * @param {number} delay - Debounce delay in milliseconds
     * @returns {Function} Debounced validation function
     */
    createRealtimeValidator(field, rules, callback, delay = 300) {
        let timeout;

        return (e) => {
            clearTimeout(timeout);

            timeout = setTimeout(() => {
                const value = field.type === 'checkbox' ? field.checked : field.value;
                const result = this.validate(value, rules);

                // Update field state
                if (result.valid) {
                    field.classList.remove('error', 'invalid');
                    field.classList.add('valid');
                    field.removeAttribute('aria-invalid');
                } else {
                    field.classList.remove('valid');
                    field.classList.add('error', 'invalid');
                    field.setAttribute('aria-invalid', 'true');
                }

                // Call callback with result
                if (callback) {
                    callback(result, field);
                }
            }, delay);
        };
    }

    /**
     * Attach real-time validation to a field
     * 
     * @param {HTMLElement} field - Field element
     * @param {Array|string} rules - Validation rules
     * @param {Object} options - Options
     */
    attachRealtimeValidation(field, rules, options = {}) {
        const {
            delay = 300,
            showErrors = true,
            callback = null
        } = options;

        const validator = this.createRealtimeValidator(field, rules, (result) => {
            if (showErrors && !result.valid) {
                // Show error message
                let errorElement = field.parentNode.querySelector('.field-error');

                if (!errorElement) {
                    errorElement = document.createElement('span');
                    errorElement.className = 'field-error';
                    errorElement.setAttribute('role', 'alert');
                    field.parentNode.appendChild(errorElement);
                }

                errorElement.textContent = result.errors[0];
                errorElement.style.display = 'block';

                const errorId = `${field.name}-error`;
                errorElement.id = errorId;
                field.setAttribute('aria-describedby', errorId);
            } else if (result.valid) {
                // Hide error message
                const errorElement = field.parentNode.querySelector('.field-error');
                if (errorElement) {
                    errorElement.style.display = 'none';
                }
                field.removeAttribute('aria-describedby');
            }

            if (callback) {
                callback(result, field);
            }
        }, delay);

        // Attach to input and change events
        field.addEventListener('input', validator);
        field.addEventListener('change', validator);

        // Return cleanup function
        return () => {
            field.removeEventListener('input', validator);
            field.removeEventListener('change', validator);
        };
    }
}

// Create singleton instance
const validator = new Validator();

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Validator;
    module.exports.validator = validator;
}

// Make available globally for WordPress
if (typeof window !== 'undefined') {
    window.Validator = Validator;
    window.validator = validator;
}
