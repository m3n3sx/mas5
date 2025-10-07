/**
 * Keyboard Navigation Helper
 * 
 * Provides utilities for implementing keyboard navigation patterns
 * including tab navigation, modal/dialog handling, and custom key bindings.
 * 
 * @class KeyboardNavigationHelper
 */
class KeyboardNavigationHelper {
    /**
     * Key codes for common keys
     */
    static Keys = {
        ENTER: 'Enter',
        SPACE: ' ',
        ESCAPE: 'Escape',
        TAB: 'Tab',
        ARROW_UP: 'ArrowUp',
        ARROW_DOWN: 'ArrowDown',
        ARROW_LEFT: 'ArrowLeft',
        ARROW_RIGHT: 'ArrowRight',
        HOME: 'Home',
        END: 'End',
        PAGE_UP: 'PageUp',
        PAGE_DOWN: 'PageDown'
    };

    /**
     * Add Enter/Space key activation to element
     * Makes non-button elements behave like buttons
     * 
     * @param {HTMLElement} element - Element to add activation to
     * @param {Function} callback - Callback function
     * @returns {Function} Cleanup function
     */
    static addActivation(element, callback) {
        if (!element || !callback) {
            return () => {};
        }

        const handler = (e) => {
            if (e.key === this.Keys.ENTER || e.key === this.Keys.SPACE) {
                e.preventDefault();
                callback(e);
            }
        };

        element.addEventListener('keydown', handler);

        // Ensure element is focusable
        if (!element.hasAttribute('tabindex')) {
            element.setAttribute('tabindex', '0');
        }

        // Add role if not present
        if (!element.hasAttribute('role')) {
            element.setAttribute('role', 'button');
        }

        return () => {
            element.removeEventListener('keydown', handler);
        };
    }

    /**
     * Add Escape key handler to element
     * Commonly used for closing modals, dialogs, and notifications
     * 
     * @param {HTMLElement} element - Element to add handler to
     * @param {Function} callback - Callback function
     * @param {boolean} stopPropagation - Whether to stop event propagation
     * @returns {Function} Cleanup function
     */
    static addEscapeHandler(element, callback, stopPropagation = true) {
        if (!element || !callback) {
            return () => {};
        }

        const handler = (e) => {
            if (e.key === this.Keys.ESCAPE) {
                if (stopPropagation) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                callback(e);
            }
        };

        element.addEventListener('keydown', handler);

        return () => {
            element.removeEventListener('keydown', handler);
        };
    }

    /**
     * Trap focus within an element (for modals, dialogs)
     * 
     * @param {HTMLElement} element - Container element
     * @param {Object} options - Configuration options
     * @param {boolean} options.returnFocus - Whether to return focus on cleanup
     * @param {HTMLElement} options.initialFocus - Element to focus initially
     * @returns {Function} Cleanup function
     */
    static trapFocus(element, options = {}) {
        if (!element) {
            return () => {};
        }

        const {
            returnFocus = true,
            initialFocus = null
        } = options;

        // Get all focusable elements
        const focusableSelector = 
            'a[href]:not([disabled]), ' +
            'button:not([disabled]), ' +
            'textarea:not([disabled]), ' +
            'input:not([disabled]), ' +
            'select:not([disabled]), ' +
            '[tabindex]:not([tabindex="-1"]):not([disabled])';

        // Store previously focused element
        const previouslyFocused = document.activeElement;

        // Focus initial element or first focusable
        const focusFirst = () => {
            const focusableElements = element.querySelectorAll(focusableSelector);
            const firstFocusable = initialFocus || focusableElements[0];
            
            if (firstFocusable) {
                // Use setTimeout to ensure element is ready
                setTimeout(() => {
                    firstFocusable.focus();
                }, 10);
            }
        };

        focusFirst();

        // Handle tab key
        const handleTab = (e) => {
            if (e.key !== this.Keys.TAB) {
                return;
            }

            const focusableElements = Array.from(element.querySelectorAll(focusableSelector));
            const firstFocusable = focusableElements[0];
            const lastFocusable = focusableElements[focusableElements.length - 1];

            if (focusableElements.length === 0) {
                e.preventDefault();
                return;
            }

            if (e.shiftKey) {
                // Shift + Tab (backwards)
                if (document.activeElement === firstFocusable) {
                    e.preventDefault();
                    lastFocusable.focus();
                }
            } else {
                // Tab (forwards)
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
            if (returnFocus && previouslyFocused && previouslyFocused.focus) {
                previouslyFocused.focus();
            }
        };
    }

    /**
     * Add arrow key navigation to a list of elements
     * 
     * @param {HTMLElement[]} elements - Array of elements to navigate
     * @param {Object} options - Configuration options
     * @param {string} options.orientation - 'horizontal' or 'vertical'
     * @param {boolean} options.loop - Whether to loop at ends
     * @param {Function} options.onSelect - Callback when element is selected
     * @returns {Function} Cleanup function
     */
    static addArrowNavigation(elements, options = {}) {
        if (!elements || elements.length === 0) {
            return () => {};
        }

        const {
            orientation = 'vertical',
            loop = true,
            onSelect = null
        } = options;

        const handlers = [];

        elements.forEach((element, index) => {
            const handler = (e) => {
                let handled = false;
                let newIndex = index;

                if (orientation === 'vertical') {
                    if (e.key === this.Keys.ARROW_UP) {
                        newIndex = index > 0 ? index - 1 : (loop ? elements.length - 1 : index);
                        handled = true;
                    } else if (e.key === this.Keys.ARROW_DOWN) {
                        newIndex = index < elements.length - 1 ? index + 1 : (loop ? 0 : index);
                        handled = true;
                    }
                } else {
                    if (e.key === this.Keys.ARROW_LEFT) {
                        newIndex = index > 0 ? index - 1 : (loop ? elements.length - 1 : index);
                        handled = true;
                    } else if (e.key === this.Keys.ARROW_RIGHT) {
                        newIndex = index < elements.length - 1 ? index + 1 : (loop ? 0 : index);
                        handled = true;
                    }
                }

                // Home key - go to first
                if (e.key === this.Keys.HOME) {
                    newIndex = 0;
                    handled = true;
                }

                // End key - go to last
                if (e.key === this.Keys.END) {
                    newIndex = elements.length - 1;
                    handled = true;
                }

                if (handled) {
                    e.preventDefault();
                    e.stopPropagation();

                    const newElement = elements[newIndex];
                    if (newElement) {
                        newElement.focus();

                        if (onSelect) {
                            onSelect(newElement, newIndex);
                        }
                    }
                }
            };

            element.addEventListener('keydown', handler);
            handlers.push({ element, handler });

            // Ensure element is focusable
            if (!element.hasAttribute('tabindex')) {
                element.setAttribute('tabindex', index === 0 ? '0' : '-1');
            }
        });

        // Return cleanup function
        return () => {
            handlers.forEach(({ element, handler }) => {
                element.removeEventListener('keydown', handler);
            });
        };
    }

    /**
     * Add keyboard shortcut
     * 
     * @param {string} key - Key to bind
     * @param {Function} callback - Callback function
     * @param {Object} options - Configuration options
     * @param {boolean} options.ctrl - Require Ctrl key
     * @param {boolean} options.alt - Require Alt key
     * @param {boolean} options.shift - Require Shift key
     * @param {boolean} options.meta - Require Meta/Command key
     * @param {HTMLElement} options.element - Element to bind to (default: document)
     * @returns {Function} Cleanup function
     */
    static addShortcut(key, callback, options = {}) {
        if (!key || !callback) {
            return () => {};
        }

        const {
            ctrl = false,
            alt = false,
            shift = false,
            meta = false,
            element = document
        } = options;

        const handler = (e) => {
            const keyMatch = e.key.toLowerCase() === key.toLowerCase();
            const ctrlMatch = ctrl ? e.ctrlKey : !e.ctrlKey;
            const altMatch = alt ? e.altKey : !e.altKey;
            const shiftMatch = shift ? e.shiftKey : !e.shiftKey;
            const metaMatch = meta ? e.metaKey : !e.metaKey;

            if (keyMatch && ctrlMatch && altMatch && shiftMatch && metaMatch) {
                e.preventDefault();
                callback(e);
            }
        };

        element.addEventListener('keydown', handler);

        return () => {
            element.removeEventListener('keydown', handler);
        };
    }

    /**
     * Get all focusable elements within a container
     * 
     * @param {HTMLElement} container - Container element
     * @returns {HTMLElement[]} Array of focusable elements
     */
    static getFocusableElements(container) {
        if (!container) {
            return [];
        }

        const selector = 
            'a[href]:not([disabled]), ' +
            'button:not([disabled]), ' +
            'textarea:not([disabled]), ' +
            'input:not([disabled]), ' +
            'select:not([disabled]), ' +
            '[tabindex]:not([tabindex="-1"]):not([disabled])';

        return Array.from(container.querySelectorAll(selector));
    }

    /**
     * Get first focusable element in container
     * 
     * @param {HTMLElement} container - Container element
     * @returns {HTMLElement|null} First focusable element or null
     */
    static getFirstFocusable(container) {
        const elements = this.getFocusableElements(container);
        return elements.length > 0 ? elements[0] : null;
    }

    /**
     * Get last focusable element in container
     * 
     * @param {HTMLElement} container - Container element
     * @returns {HTMLElement|null} Last focusable element or null
     */
    static getLastFocusable(container) {
        const elements = this.getFocusableElements(container);
        return elements.length > 0 ? elements[elements.length - 1] : null;
    }

    /**
     * Move focus to next focusable element
     * 
     * @param {HTMLElement} container - Container element
     * @param {boolean} loop - Whether to loop to first element
     * @returns {boolean} Whether focus was moved
     */
    static focusNext(container, loop = true) {
        const elements = this.getFocusableElements(container);
        const currentIndex = elements.indexOf(document.activeElement);

        if (currentIndex === -1) {
            // No element focused, focus first
            if (elements.length > 0) {
                elements[0].focus();
                return true;
            }
            return false;
        }

        const nextIndex = currentIndex + 1;

        if (nextIndex < elements.length) {
            elements[nextIndex].focus();
            return true;
        } else if (loop && elements.length > 0) {
            elements[0].focus();
            return true;
        }

        return false;
    }

    /**
     * Move focus to previous focusable element
     * 
     * @param {HTMLElement} container - Container element
     * @param {boolean} loop - Whether to loop to last element
     * @returns {boolean} Whether focus was moved
     */
    static focusPrevious(container, loop = true) {
        const elements = this.getFocusableElements(container);
        const currentIndex = elements.indexOf(document.activeElement);

        if (currentIndex === -1) {
            // No element focused, focus last
            if (elements.length > 0) {
                elements[elements.length - 1].focus();
                return true;
            }
            return false;
        }

        const prevIndex = currentIndex - 1;

        if (prevIndex >= 0) {
            elements[prevIndex].focus();
            return true;
        } else if (loop && elements.length > 0) {
            elements[elements.length - 1].focus();
            return true;
        }

        return false;
    }

    /**
     * Check if element is focusable
     * 
     * @param {HTMLElement} element - Element to check
     * @returns {boolean} Whether element is focusable
     */
    static isFocusable(element) {
        if (!element) {
            return false;
        }

        // Check if disabled
        if (element.disabled) {
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

        // Check tabindex
        const tabindex = element.getAttribute('tabindex');
        if (tabindex === '-1') {
            return false;
        }

        // Check if naturally focusable or has tabindex
        const focusableTags = ['A', 'BUTTON', 'INPUT', 'SELECT', 'TEXTAREA'];
        if (focusableTags.includes(element.tagName)) {
            return true;
        }

        if (tabindex !== null) {
            return true;
        }

        return false;
    }

    /**
     * Restore focus to element with fallback
     * 
     * @param {HTMLElement} element - Element to focus
     * @param {HTMLElement} fallback - Fallback element if first fails
     * @returns {boolean} Whether focus was successful
     */
    static restoreFocus(element, fallback = null) {
        if (element && element.focus && this.isFocusable(element)) {
            try {
                element.focus();
                return true;
            } catch (e) {
                console.warn('[KeyboardNavigationHelper] Failed to focus element:', e);
            }
        }

        if (fallback && fallback.focus && this.isFocusable(fallback)) {
            try {
                fallback.focus();
                return true;
            } catch (e) {
                console.warn('[KeyboardNavigationHelper] Failed to focus fallback:', e);
            }
        }

        return false;
    }

    /**
     * Create roving tabindex manager for a group of elements
     * Only one element in the group is tabbable at a time
     * 
     * @param {HTMLElement[]} elements - Array of elements
     * @param {number} initialIndex - Initial active index
     * @returns {Object} Manager object with methods
     */
    static createRovingTabindex(elements, initialIndex = 0) {
        if (!elements || elements.length === 0) {
            return null;
        }

        let activeIndex = initialIndex;

        const setActive = (index) => {
            if (index < 0 || index >= elements.length) {
                return;
            }

            // Remove tabindex from all
            elements.forEach(el => {
                el.setAttribute('tabindex', '-1');
            });

            // Set active
            elements[index].setAttribute('tabindex', '0');
            activeIndex = index;
        };

        // Initialize
        setActive(initialIndex);

        return {
            setActive,
            getActive: () => activeIndex,
            getActiveElement: () => elements[activeIndex],
            focusActive: () => {
                if (elements[activeIndex]) {
                    elements[activeIndex].focus();
                }
            }
        };
    }
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = KeyboardNavigationHelper;
}

// Make available globally
if (typeof window !== 'undefined') {
    window.KeyboardNavigationHelper = KeyboardNavigationHelper;
}
