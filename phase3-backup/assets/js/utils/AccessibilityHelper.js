/**
 * Accessibility Helper Utility
 * 
 * Provides utilities for adding ARIA attributes, managing focus,
 * and ensuring accessibility compliance across components.
 * 
 * @class AccessibilityHelper
 */
class AccessibilityHelper {
    /**
     * Add ARIA label to element
     * 
     * @param {HTMLElement} element - Element to add label to
     * @param {string} label - Label text
     * @returns {HTMLElement} The element
     */
    static addLabel(element, label) {
        if (!element || !label) {
            return element;
        }
        
        element.setAttribute('aria-label', label);
        return element;
    }
    
    /**
     * Add ARIA described by relationship
     * 
     * @param {HTMLElement} element - Element to describe
     * @param {string|HTMLElement} descriptor - ID or element that describes
     * @returns {HTMLElement} The element
     */
    static addDescribedBy(element, descriptor) {
        if (!element || !descriptor) {
            return element;
        }
        
        const descriptorId = typeof descriptor === 'string' 
            ? descriptor 
            : descriptor.id;
            
        if (!descriptorId) {
            console.warn('[AccessibilityHelper] Descriptor must have an ID');
            return element;
        }
        
        const existing = element.getAttribute('aria-describedby');
        if (existing) {
            // Append to existing
            const ids = existing.split(' ');
            if (!ids.includes(descriptorId)) {
                element.setAttribute('aria-describedby', `${existing} ${descriptorId}`);
            }
        } else {
            element.setAttribute('aria-describedby', descriptorId);
        }
        
        return element;
    }
    
    /**
     * Remove ARIA described by relationship
     * 
     * @param {HTMLElement} element - Element
     * @param {string} descriptorId - ID to remove
     * @returns {HTMLElement} The element
     */
    static removeDescribedBy(element, descriptorId) {
        if (!element || !descriptorId) {
            return element;
        }
        
        const existing = element.getAttribute('aria-describedby');
        if (existing) {
            const ids = existing.split(' ').filter(id => id !== descriptorId);
            if (ids.length > 0) {
                element.setAttribute('aria-describedby', ids.join(' '));
            } else {
                element.removeAttribute('aria-describedby');
            }
        }
        
        return element;
    }
    
    /**
     * Mark element as invalid with error message
     * 
     * @param {HTMLElement} element - Form element
     * @param {string} errorMessage - Error message
     * @param {string} errorId - Optional error element ID
     * @returns {HTMLElement} The element
     */
    static markInvalid(element, errorMessage, errorId = null) {
        if (!element) {
            return element;
        }
        
        // Set aria-invalid
        element.setAttribute('aria-invalid', 'true');
        
        // Create or update error message element
        if (errorMessage) {
            const id = errorId || `${element.id || 'field'}-error`;
            let errorElement = document.getElementById(id);
            
            if (!errorElement) {
                errorElement = document.createElement('span');
                errorElement.id = id;
                errorElement.className = 'field-error';
                errorElement.setAttribute('role', 'alert');
                
                // Insert after the element
                if (element.parentNode) {
                    element.parentNode.insertBefore(errorElement, element.nextSibling);
                }
            }
            
            errorElement.textContent = errorMessage;
            errorElement.style.display = 'block';
            
            // Link error to field
            this.addDescribedBy(element, id);
        }
        
        return element;
    }
    
    /**
     * Mark element as valid (remove invalid state)
     * 
     * @param {HTMLElement} element - Form element
     * @returns {HTMLElement} The element
     */
    static markValid(element) {
        if (!element) {
            return element;
        }
        
        // Remove aria-invalid
        element.removeAttribute('aria-invalid');
        
        // Remove error message
        const describedBy = element.getAttribute('aria-describedby');
        if (describedBy) {
            const ids = describedBy.split(' ');
            ids.forEach(id => {
                const errorElement = document.getElementById(id);
                if (errorElement && errorElement.classList.contains('field-error')) {
                    errorElement.style.display = 'none';
                    this.removeDescribedBy(element, id);
                }
            });
        }
        
        return element;
    }
    
    /**
     * Create live region for dynamic content announcements
     * 
     * @param {string} id - Unique ID for the live region
     * @param {string} politeness - 'polite', 'assertive', or 'off'
     * @param {boolean} atomic - Whether to announce entire region
     * @returns {HTMLElement} Live region element
     */
    static createLiveRegion(id, politeness = 'polite', atomic = false) {
        let region = document.getElementById(id);
        
        if (!region) {
            region = document.createElement('div');
            region.id = id;
            region.className = 'sr-only'; // Screen reader only
            region.setAttribute('aria-live', politeness);
            region.setAttribute('aria-atomic', atomic ? 'true' : 'false');
            region.setAttribute('role', politeness === 'assertive' ? 'alert' : 'status');
            
            // Add to body
            document.body.appendChild(region);
        }
        
        return region;
    }
    
    /**
     * Announce message to screen readers
     * 
     * @param {string} message - Message to announce
     * @param {string} politeness - 'polite' or 'assertive'
     * @returns {void}
     */
    static announce(message, politeness = 'polite') {
        const regionId = `mas-live-region-${politeness}`;
        const region = this.createLiveRegion(regionId, politeness, true);
        
        // Clear previous message
        region.textContent = '';
        
        // Announce new message after a brief delay
        // This ensures screen readers pick up the change
        setTimeout(() => {
            region.textContent = message;
        }, 100);
        
        // Clear after announcement
        setTimeout(() => {
            region.textContent = '';
        }, 5000);
    }
    
    /**
     * Add required indicator to form field
     * 
     * @param {HTMLElement} element - Form element
     * @param {boolean} required - Whether field is required
     * @returns {HTMLElement} The element
     */
    static setRequired(element, required = true) {
        if (!element) {
            return element;
        }
        
        if (required) {
            element.setAttribute('aria-required', 'true');
            element.required = true;
            
            // Add visual indicator if label exists
            const label = this.findLabel(element);
            if (label && !label.querySelector('.required-indicator')) {
                const indicator = document.createElement('span');
                indicator.className = 'required-indicator';
                indicator.textContent = ' *';
                indicator.setAttribute('aria-hidden', 'true');
                label.appendChild(indicator);
            }
        } else {
            element.removeAttribute('aria-required');
            element.required = false;
            
            // Remove visual indicator
            const label = this.findLabel(element);
            if (label) {
                const indicator = label.querySelector('.required-indicator');
                if (indicator) {
                    indicator.remove();
                }
            }
        }
        
        return element;
    }
    
    /**
     * Find label for form element
     * 
     * @param {HTMLElement} element - Form element
     * @returns {HTMLElement|null} Label element or null
     */
    static findLabel(element) {
        if (!element) {
            return null;
        }
        
        // Try by ID
        if (element.id) {
            const label = document.querySelector(`label[for="${element.id}"]`);
            if (label) {
                return label;
            }
        }
        
        // Try parent label
        let parent = element.parentElement;
        while (parent) {
            if (parent.tagName === 'LABEL') {
                return parent;
            }
            parent = parent.parentElement;
        }
        
        return null;
    }
    
    /**
     * Setup form field with proper ARIA attributes
     * 
     * @param {HTMLElement} field - Form field element
     * @param {Object} options - Configuration options
     * @param {string} options.label - Accessible label
     * @param {string} options.description - Field description
     * @param {boolean} options.required - Whether field is required
     * @param {string} options.errorMessage - Current error message
     * @returns {HTMLElement} The field element
     */
    static setupFormField(field, options = {}) {
        if (!field) {
            return field;
        }
        
        const {
            label,
            description,
            required = false,
            errorMessage = null
        } = options;
        
        // Add label if provided and no label exists
        if (label && !this.findLabel(field) && !field.getAttribute('aria-label')) {
            this.addLabel(field, label);
        }
        
        // Add description if provided
        if (description) {
            const descId = `${field.id || 'field'}-description`;
            let descElement = document.getElementById(descId);
            
            if (!descElement) {
                descElement = document.createElement('span');
                descElement.id = descId;
                descElement.className = 'field-description';
                descElement.textContent = description;
                
                if (field.parentNode) {
                    field.parentNode.insertBefore(descElement, field.nextSibling);
                }
            }
            
            this.addDescribedBy(field, descId);
        }
        
        // Set required state
        this.setRequired(field, required);
        
        // Set error state if provided
        if (errorMessage) {
            this.markInvalid(field, errorMessage);
        }
        
        return field;
    }
    
    /**
     * Trap focus within an element (for modals, dialogs)
     * 
     * @param {HTMLElement} element - Container element
     * @returns {Function} Cleanup function to remove trap
     */
    static trapFocus(element) {
        if (!element) {
            return () => {};
        }
        
        // Get all focusable elements
        const focusableSelector = 
            'a[href], button:not([disabled]), textarea:not([disabled]), ' +
            'input:not([disabled]), select:not([disabled]), ' +
            '[tabindex]:not([tabindex="-1"])';
            
        const focusableElements = element.querySelectorAll(focusableSelector);
        const firstFocusable = focusableElements[0];
        const lastFocusable = focusableElements[focusableElements.length - 1];
        
        // Store previously focused element
        const previouslyFocused = document.activeElement;
        
        // Focus first element
        if (firstFocusable) {
            firstFocusable.focus();
        }
        
        // Handle tab key
        const handleTab = (e) => {
            if (e.key !== 'Tab') {
                return;
            }
            
            if (e.shiftKey) {
                // Shift + Tab
                if (document.activeElement === firstFocusable) {
                    e.preventDefault();
                    lastFocusable.focus();
                }
            } else {
                // Tab
                if (document.activeElement === lastFocusable) {
                    e.preventDefault();
                    firstFocusable.focus();
                }
            }
        };
        
        element.addEventListener('keydown', handleTab);
        
        // Return cleanup function
        return () => {
            element.removeEventListener('keydown', handleTab);
            
            // Restore focus
            if (previouslyFocused && previouslyFocused.focus) {
                previouslyFocused.focus();
            }
        };
    }
    
    /**
     * Add visible focus indicator to element
     * 
     * @param {HTMLElement} element - Element to add focus indicator to
     * @param {string} style - Focus style ('outline', 'ring', 'glow')
     * @returns {HTMLElement} The element
     */
    static addFocusIndicator(element, style = 'outline') {
        if (!element) {
            return element;
        }
        
        element.classList.add('has-focus-indicator');
        element.dataset.focusStyle = style;
        
        return element;
    }
    
    /**
     * Check if element is visible to screen readers
     * 
     * @param {HTMLElement} element - Element to check
     * @returns {boolean} Whether element is accessible
     */
    static isAccessible(element) {
        if (!element) {
            return false;
        }
        
        // Check if hidden
        if (element.hasAttribute('hidden') || 
            element.getAttribute('aria-hidden') === 'true') {
            return false;
        }
        
        // Check display and visibility
        const style = window.getComputedStyle(element);
        if (style.display === 'none' || style.visibility === 'hidden') {
            return false;
        }
        
        return true;
    }
    
    /**
     * Make element screen reader only (visually hidden)
     * 
     * @param {HTMLElement} element - Element to hide visually
     * @returns {HTMLElement} The element
     */
    static makeScreenReaderOnly(element) {
        if (!element) {
            return element;
        }
        
        element.classList.add('sr-only');
        return element;
    }
    
    /**
     * Hide element from screen readers
     * 
     * @param {HTMLElement} element - Element to hide
     * @returns {HTMLElement} The element
     */
    static hideFromScreenReaders(element) {
        if (!element) {
            return element;
        }
        
        element.setAttribute('aria-hidden', 'true');
        return element;
    }
    
    /**
     * Show element to screen readers
     * 
     * @param {HTMLElement} element - Element to show
     * @returns {HTMLElement} The element
     */
    static showToScreenReaders(element) {
        if (!element) {
            return element;
        }
        
        element.removeAttribute('aria-hidden');
        return element;
    }
    
    /**
     * Set element as busy/loading
     * 
     * @param {HTMLElement} element - Element
     * @param {boolean} busy - Whether element is busy
     * @param {string} message - Optional loading message
     * @returns {HTMLElement} The element
     */
    static setBusy(element, busy = true, message = null) {
        if (!element) {
            return element;
        }
        
        if (busy) {
            element.setAttribute('aria-busy', 'true');
            
            if (message) {
                this.announce(message, 'polite');
            }
        } else {
            element.removeAttribute('aria-busy');
        }
        
        return element;
    }
    
    /**
     * Create skip link for navigation
     * 
     * @param {string} targetId - ID of target element
     * @param {string} label - Link label
     * @returns {HTMLElement} Skip link element
     */
    static createSkipLink(targetId, label = 'Skip to main content') {
        const skipLink = document.createElement('a');
        skipLink.href = `#${targetId}`;
        skipLink.className = 'skip-link';
        skipLink.textContent = label;
        
        skipLink.addEventListener('click', (e) => {
            e.preventDefault();
            const target = document.getElementById(targetId);
            if (target) {
                target.focus();
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
        
        return skipLink;
    }
    
    /**
     * Ensure element has unique ID
     * 
     * @param {HTMLElement} element - Element
     * @param {string} prefix - ID prefix
     * @returns {string} Element ID
     */
    static ensureId(element, prefix = 'mas') {
        if (!element) {
            return null;
        }
        
        if (!element.id) {
            element.id = `${prefix}-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
        }
        
        return element.id;
    }
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AccessibilityHelper;
}

// Make available globally
if (typeof window !== 'undefined') {
    window.AccessibilityHelper = AccessibilityHelper;
}
