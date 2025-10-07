/**
 * Focus Manager
 * 
 * Manages focus state, focus trapping for modals/dialogs,
 * focus restoration, and logical tab order.
 * 
 * @class FocusManager
 */
class FocusManager {
    /**
     * Initialize focus manager
     */
    constructor() {
        this.focusHistory = [];
        this.maxHistorySize = 10;
        this.traps = new Map();
        this.init();
    }

    /**
     * Initialize focus tracking
     */
    init() {
        // Track focus changes
        document.addEventListener('focusin', (e) => {
            this.recordFocus(e.target);
        });

        // Detect keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                document.body.classList.add('keyboard-navigation-active');
            }
        });

        // Detect mouse navigation
        document.addEventListener('mousedown', () => {
            document.body.classList.remove('keyboard-navigation-active');
        });
    }

    /**
     * Record focused element in history
     * 
     * @param {HTMLElement} element - Focused element
     */
    recordFocus(element) {
        if (!element || element === document.body) {
            return;
        }

        // Add to history
        this.focusHistory.push({
            element: element,
            timestamp: Date.now()
        });

        // Limit history size
        if (this.focusHistory.length > this.maxHistorySize) {
            this.focusHistory.shift();
        }
    }

    /**
     * Get previously focused element
     * 
     * @param {number} stepsBack - How many steps back (default: 1)
     * @returns {HTMLElement|null} Previously focused element
     */
    getPreviousFocus(stepsBack = 1) {
        const index = this.focusHistory.length - 1 - stepsBack;
        if (index >= 0 && index < this.focusHistory.length) {
            return this.focusHistory[index].element;
        }
        return null;
    }

    /**
     * Restore focus to previous element
     * 
     * @param {number} stepsBack - How many steps back
     * @returns {boolean} Whether focus was restored
     */
    restorePreviousFocus(stepsBack = 1) {
        const element = this.getPreviousFocus(stepsBack);
        if (element && this.isFocusable(element)) {
            try {
                element.focus();
                return true;
            } catch (e) {
                console.warn('[FocusManager] Failed to restore focus:', e);
            }
        }
        return false;
    }

    /**
     * Save current focus for later restoration
     * 
     * @param {string} key - Key to save focus under
     * @returns {HTMLElement|null} Saved element
     */
    saveFocus(key) {
        const element = document.activeElement;
        if (element && element !== document.body) {
            this[`saved_${key}`] = element;
            return element;
        }
        return null;
    }

    /**
     * Restore saved focus
     * 
     * @param {string} key - Key to restore focus from
     * @returns {boolean} Whether focus was restored
     */
    restoreSavedFocus(key) {
        const element = this[`saved_${key}`];
        if (element && this.isFocusable(element)) {
            try {
                element.focus();
                delete this[`saved_${key}`];
                return true;
            } catch (e) {
                console.warn('[FocusManager] Failed to restore saved focus:', e);
            }
        }
        return false;
    }

    /**
     * Create focus trap for modal/dialog
     * 
     * @param {HTMLElement} container - Container element
     * @param {Object} options - Configuration options
     * @returns {string} Trap ID
     */
    createTrap(container, options = {}) {
        if (!container) {
            return null;
        }

        const {
            initialFocus = null,
            returnFocus = true,
            escapeDeactivates = true,
            clickOutsideDeactivates = false
        } = options;

        const trapId = `trap_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;

        // Save current focus
        const previousFocus = document.activeElement;

        // Get focusable elements
        const getFocusableElements = () => {
            const selector = 
                'a[href]:not([disabled]), ' +
                'button:not([disabled]), ' +
                'textarea:not([disabled]), ' +
                'input:not([disabled]), ' +
                'select:not([disabled]), ' +
                '[tabindex]:not([tabindex="-1"]):not([disabled])';
            
            return Array.from(container.querySelectorAll(selector))
                .filter(el => this.isFocusable(el));
        };

        // Focus initial element
        const focusInitial = () => {
            const focusableElements = getFocusableElements();
            const elementToFocus = initialFocus || focusableElements[0];
            
            if (elementToFocus) {
                setTimeout(() => {
                    elementToFocus.focus();
                }, 10);
            }
        };

        // Handle tab key
        const handleTab = (e) => {
            if (e.key !== 'Tab') {
                return;
            }

            const focusableElements = getFocusableElements();
            if (focusableElements.length === 0) {
                e.preventDefault();
                return;
            }

            const firstFocusable = focusableElements[0];
            const lastFocusable = focusableElements[focusableElements.length - 1];

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

        // Handle escape key
        const handleEscape = (e) => {
            if (escapeDeactivates && e.key === 'Escape') {
                this.releaseTrap(trapId);
            }
        };

        // Handle click outside
        const handleClickOutside = (e) => {
            if (clickOutsideDeactivates && !container.contains(e.target)) {
                this.releaseTrap(trapId);
            }
        };

        // Add event listeners
        container.addEventListener('keydown', handleTab);
        document.addEventListener('keydown', handleEscape);
        
        if (clickOutsideDeactivates) {
            document.addEventListener('mousedown', handleClickOutside);
        }

        // Focus initial element
        focusInitial();

        // Store trap info
        this.traps.set(trapId, {
            container,
            previousFocus: returnFocus ? previousFocus : null,
            handleTab,
            handleEscape,
            handleClickOutside,
            clickOutsideDeactivates
        });

        return trapId;
    }

    /**
     * Release focus trap
     * 
     * @param {string} trapId - Trap ID
     * @returns {boolean} Whether trap was released
     */
    releaseTrap(trapId) {
        const trap = this.traps.get(trapId);
        if (!trap) {
            return false;
        }

        // Remove event listeners
        trap.container.removeEventListener('keydown', trap.handleTab);
        document.removeEventListener('keydown', trap.handleEscape);
        
        if (trap.clickOutsideDeactivates) {
            document.removeEventListener('mousedown', trap.handleClickOutside);
        }

        // Restore focus
        if (trap.previousFocus && this.isFocusable(trap.previousFocus)) {
            try {
                trap.previousFocus.focus();
            } catch (e) {
                console.warn('[FocusManager] Failed to restore focus:', e);
            }
        }

        // Remove trap
        this.traps.delete(trapId);

        return true;
    }

    /**
     * Release all focus traps
     */
    releaseAllTraps() {
        const trapIds = Array.from(this.traps.keys());
        trapIds.forEach(id => this.releaseTrap(id));
    }

    /**
     * Check if element is focusable
     * 
     * @param {HTMLElement} element - Element to check
     * @returns {boolean} Whether element is focusable
     */
    isFocusable(element) {
        if (!element || !element.offsetParent) {
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

        return true;
    }

    /**
     * Get all focusable elements in container
     * 
     * @param {HTMLElement} container - Container element
     * @returns {HTMLElement[]} Array of focusable elements
     */
    getFocusableElements(container = document) {
        const selector = 
            'a[href]:not([disabled]), ' +
            'button:not([disabled]), ' +
            'textarea:not([disabled]), ' +
            'input:not([disabled]), ' +
            'select:not([disabled]), ' +
            '[tabindex]:not([tabindex="-1"]):not([disabled])';

        return Array.from(container.querySelectorAll(selector))
            .filter(el => this.isFocusable(el));
    }

    /**
     * Focus first element in container
     * 
     * @param {HTMLElement} container - Container element
     * @returns {boolean} Whether focus was successful
     */
    focusFirst(container) {
        const elements = this.getFocusableElements(container);
        if (elements.length > 0) {
            try {
                elements[0].focus();
                return true;
            } catch (e) {
                console.warn('[FocusManager] Failed to focus first element:', e);
            }
        }
        return false;
    }

    /**
     * Focus last element in container
     * 
     * @param {HTMLElement} container - Container element
     * @returns {boolean} Whether focus was successful
     */
    focusLast(container) {
        const elements = this.getFocusableElements(container);
        if (elements.length > 0) {
            try {
                elements[elements.length - 1].focus();
                return true;
            } catch (e) {
                console.warn('[FocusManager] Failed to focus last element:', e);
            }
        }
        return false;
    }

    /**
     * Add visible focus indicator to element
     * 
     * @param {HTMLElement} element - Element
     * @param {string} style - Focus style
     */
    addFocusIndicator(element, style = 'outline') {
        if (!element) {
            return;
        }

        element.classList.add('has-focus-indicator');
        element.dataset.focusStyle = style;
    }

    /**
     * Remove focus indicator from element
     * 
     * @param {HTMLElement} element - Element
     */
    removeFocusIndicator(element) {
        if (!element) {
            return;
        }

        element.classList.remove('has-focus-indicator');
        delete element.dataset.focusStyle;
    }

    /**
     * Ensure logical tab order for elements
     * 
     * @param {HTMLElement[]} elements - Array of elements
     */
    ensureTabOrder(elements) {
        if (!elements || elements.length === 0) {
            return;
        }

        elements.forEach((element, index) => {
            if (element) {
                element.setAttribute('tabindex', index === 0 ? '0' : '-1');
            }
        });
    }

    /**
     * Create skip link
     * 
     * @param {string} targetId - ID of target element
     * @param {string} label - Link label
     * @returns {HTMLElement} Skip link element
     */
    createSkipLink(targetId, label = 'Skip to main content') {
        const skipLink = document.createElement('a');
        skipLink.href = `#${targetId}`;
        skipLink.className = 'skip-link';
        skipLink.textContent = label;

        skipLink.addEventListener('click', (e) => {
            e.preventDefault();
            const target = document.getElementById(targetId);
            if (target) {
                // Make target focusable if not already
                if (!target.hasAttribute('tabindex')) {
                    target.setAttribute('tabindex', '-1');
                }
                target.focus();
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });

        return skipLink;
    }

    /**
     * Add skip links to page
     * 
     * @param {Array} links - Array of {targetId, label} objects
     */
    addSkipLinks(links) {
        if (!links || links.length === 0) {
            return;
        }

        const container = document.createElement('div');
        container.className = 'skip-links';
        container.setAttribute('role', 'navigation');
        container.setAttribute('aria-label', 'Skip links');

        links.forEach(link => {
            const skipLink = this.createSkipLink(link.targetId, link.label);
            container.appendChild(skipLink);
        });

        // Insert at beginning of body
        document.body.insertBefore(container, document.body.firstChild);
    }

    /**
     * Highlight focusable elements (for debugging)
     * 
     * @param {HTMLElement} container - Container to highlight within
     * @param {boolean} enable - Whether to enable highlighting
     */
    highlightFocusable(container = document, enable = true) {
        const elements = this.getFocusableElements(container);

        elements.forEach(element => {
            if (enable) {
                element.style.outline = '2px dashed red';
                element.style.outlineOffset = '2px';
            } else {
                element.style.outline = '';
                element.style.outlineOffset = '';
            }
        });
    }

    /**
     * Get focus order for container
     * 
     * @param {HTMLElement} container - Container element
     * @returns {Array} Array of elements in tab order
     */
    getFocusOrder(container = document) {
        const elements = this.getFocusableElements(container);

        // Sort by tabindex and DOM order
        return elements.sort((a, b) => {
            const aIndex = parseInt(a.getAttribute('tabindex')) || 0;
            const bIndex = parseInt(b.getAttribute('tabindex')) || 0;

            if (aIndex !== bIndex) {
                return aIndex - bIndex;
            }

            // Same tabindex, use DOM order
            return a.compareDocumentPosition(b) & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1;
        });
    }

    /**
     * Validate focus order (check for issues)
     * 
     * @param {HTMLElement} container - Container element
     * @returns {Object} Validation results
     */
    validateFocusOrder(container = document) {
        const elements = this.getFocusableElements(container);
        const issues = [];

        // Check for positive tabindex values (anti-pattern)
        elements.forEach(element => {
            const tabindex = parseInt(element.getAttribute('tabindex'));
            if (tabindex > 0) {
                issues.push({
                    element,
                    issue: 'positive-tabindex',
                    message: `Element has positive tabindex (${tabindex}), which can cause unexpected tab order`
                });
            }
        });

        // Check for elements that might be missed
        const hiddenFocusable = Array.from(container.querySelectorAll(
            'a[href], button, input, select, textarea'
        )).filter(el => !this.isFocusable(el) && !el.disabled);

        hiddenFocusable.forEach(element => {
            issues.push({
                element,
                issue: 'hidden-focusable',
                message: 'Element is focusable but hidden or has tabindex="-1"'
            });
        });

        return {
            valid: issues.length === 0,
            issues,
            focusableCount: elements.length
        };
    }
}

// Create singleton instance
const focusManager = new FocusManager();

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = focusManager;
}

// Make available globally
if (typeof window !== 'undefined') {
    window.FocusManager = focusManager;
}
