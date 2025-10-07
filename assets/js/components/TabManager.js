/**
 * Tab Manager Component
 * 
 * Manages tab navigation with keyboard support and accessibility.
 * Persists tab state across page reloads.
 * 
 * @class TabManager
 * @extends Component
 */
class TabManager extends Component {
    /**
     * Create tab manager component
     * 
     * @param {HTMLElement} element - DOM element for this component
     * @param {APIClient} apiClient - API client instance
     * @param {StateManager} stateManager - State manager instance
     * @param {EventBus} eventBus - Event bus instance
     */
    constructor(element, apiClient, stateManager, eventBus) {
        super(element, apiClient, stateManager, eventBus);

        // Component name
        this.name = 'TabManager';

        // Storage key for persisting active tab
        this.storageKey = 'mas_active_tab';

        // Local state
        this.setState({
            activeTab: this.getPersistedTab() || this.getDefaultTab(),
            tabs: [],
            panels: []
        });
    }

    /**
     * Initialize component
     * 
     * @returns {void}
     */
    init() {
        this.log('Initializing...');

        // Discover tabs and panels
        this.discoverTabs();

        // Call parent init
        super.init();

        // Activate initial tab
        this.activateTab(this.getState().activeTab, false);
    }

    /**
     * Discover tabs and panels in DOM
     * 
     * @returns {void}
     */
    discoverTabs() {
        if (!this.element) {
            return;
        }

        // Find all tab buttons
        const tabButtons = this.element.querySelectorAll('[role="tab"]');
        const tabs = Array.from(tabButtons).map(button => ({
            id: button.getAttribute('aria-controls') || button.dataset.tab,
            button: button,
            label: button.textContent.trim()
        }));

        // Find all tab panels
        const tabPanels = this.element.querySelectorAll('[role="tabpanel"]');
        const panels = Array.from(tabPanels).map(panel => ({
            id: panel.id || panel.dataset.panel,
            element: panel
        }));

        this.setState({ tabs, panels });

        this.log('Discovered tabs:', tabs.length, 'panels:', panels.length);
    }

    /**
     * Render component UI
     * Note: TabManager doesn't render HTML, it manages existing tabs
     * 
     * @returns {void}
     */
    render() {
        // TabManager works with existing DOM structure
        // No rendering needed
        this.log('Render called (managing existing tabs)');
    }

    /**
     * Bind event listeners
     * 
     * @returns {void}
     */
    bindEvents() {
        if (!this.element) {
            return;
        }

        const state = this.getState();

        // Tab button clicks
        state.tabs.forEach(tab => {
            this.addEventListener(tab.button, 'click', this.getBoundMethod('handleTabClick'));
        });

        // Keyboard navigation
        state.tabs.forEach(tab => {
            this.addEventListener(tab.button, 'keydown', this.getBoundMethod('handleKeyDown'));
        });

        // Subscribe to global events
        this.subscribe('tab:activate', this.getBoundMethod('handleTabActivateEvent'));
    }

    /**
     * Handle tab click
     * 
     * @param {Event} event - Click event
     * @returns {void}
     */
    handleTabClick(event) {
        event.preventDefault();

        const button = event.currentTarget;
        const tabId = button.getAttribute('aria-controls') || button.dataset.tab;

        this.log('Tab clicked:', tabId);

        this.activateTab(tabId);
    }

    /**
     * Handle keyboard navigation with full ARIA tab pattern support
     * 
     * @param {Event} event - Keydown event
     * @returns {void}
     */
    handleKeyDown(event) {
        const state = this.getState();
        const currentIndex = state.tabs.findIndex(tab => tab.id === state.activeTab);

        let handled = false;
        let newIndex = currentIndex;

        // Use KeyboardNavigationHelper if available
        const Keys = typeof KeyboardNavigationHelper !== 'undefined' 
            ? KeyboardNavigationHelper.Keys 
            : {
                ARROW_LEFT: 'ArrowLeft',
                ARROW_RIGHT: 'ArrowRight',
                ARROW_UP: 'ArrowUp',
                ARROW_DOWN: 'ArrowDown',
                HOME: 'Home',
                END: 'End',
                ENTER: 'Enter',
                SPACE: ' '
            };

        switch (event.key) {
            case Keys.ARROW_LEFT:
            case Keys.ARROW_UP:
                // Move to previous tab
                newIndex = currentIndex > 0 ? currentIndex - 1 : state.tabs.length - 1;
                handled = true;
                break;

            case Keys.ARROW_RIGHT:
            case Keys.ARROW_DOWN:
                // Move to next tab
                newIndex = currentIndex < state.tabs.length - 1 ? currentIndex + 1 : 0;
                handled = true;
                break;

            case Keys.HOME:
                // Move to first tab
                newIndex = 0;
                handled = true;
                break;

            case Keys.END:
                // Move to last tab
                newIndex = state.tabs.length - 1;
                handled = true;
                break;

            case Keys.ENTER:
            case Keys.SPACE:
                // Activate current tab (if not already active)
                const button = event.currentTarget;
                const tabId = button.getAttribute('aria-controls') || button.dataset.tab;
                this.activateTab(tabId);
                handled = true;
                break;
        }

        if (handled) {
            event.preventDefault();
            event.stopPropagation();

            // Focus and activate new tab
            if (newIndex !== currentIndex) {
                const newTab = state.tabs[newIndex];
                
                // Skip disabled tabs
                if (newTab.button.disabled) {
                    // Try to find next available tab
                    const direction = newIndex > currentIndex ? 1 : -1;
                    let searchIndex = newIndex;
                    let found = false;
                    
                    for (let i = 0; i < state.tabs.length; i++) {
                        searchIndex = (searchIndex + direction + state.tabs.length) % state.tabs.length;
                        if (!state.tabs[searchIndex].button.disabled) {
                            newIndex = searchIndex;
                            found = true;
                            break;
                        }
                    }
                    
                    if (!found) {
                        return; // All tabs disabled
                    }
                }
                
                const targetTab = state.tabs[newIndex];
                this.activateTab(targetTab.id);
                targetTab.button.focus();
            }
        }
    }

    /**
     * Handle tab activate event
     * 
     * @param {Object} data - Event data
     * @returns {void}
     */
    handleTabActivateEvent(data) {
        if (data.tabId) {
            this.log('Tab activate event received:', data.tabId);
            this.activateTab(data.tabId);
        }
    }

    /**
     * Activate a tab
     * 
     * @param {string} tabId - Tab ID to activate
     * @param {boolean} persist - Whether to persist to storage (default: true)
     * @returns {void}
     */
    activateTab(tabId, persist = true) {
        const state = this.getState();

        // Find tab and panel
        const tab = state.tabs.find(t => t.id === tabId);
        const panel = state.panels.find(p => p.id === tabId);

        if (!tab || !panel) {
            console.warn(`[${this.name}] Tab or panel not found:`, tabId);
            return;
        }

        this.log('Activating tab:', tabId);

        // Deactivate all tabs
        state.tabs.forEach(t => {
            t.button.setAttribute('aria-selected', 'false');
            t.button.setAttribute('tabindex', '-1');
            t.button.classList.remove('active');
        });

        // Hide all panels
        state.panels.forEach(p => {
            p.element.setAttribute('hidden', '');
            p.element.classList.remove('active');
        });

        // Activate selected tab
        tab.button.setAttribute('aria-selected', 'true');
        tab.button.setAttribute('tabindex', '0');
        tab.button.classList.add('active');

        // Show selected panel
        panel.element.removeAttribute('hidden');
        panel.element.classList.add('active');

        // Update state
        this.setState({ activeTab: tabId });

        // Persist to storage
        if (persist) {
            this.persistTab(tabId);
        }

        // Emit event
        this.emit('tab:changed', { 
            tabId, 
            tab, 
            panel,
            previousTab: state.activeTab 
        });

        this.log('Tab activated:', tabId);
    }

    /**
     * Get active tab ID
     * 
     * @returns {string} Active tab ID
     */
    getActiveTab() {
        return this.getState().activeTab;
    }

    /**
     * Get tab by ID
     * 
     * @param {string} tabId - Tab ID
     * @returns {Object|null} Tab object or null
     */
    getTab(tabId) {
        const state = this.getState();
        return state.tabs.find(t => t.id === tabId) || null;
    }

    /**
     * Get panel by ID
     * 
     * @param {string} panelId - Panel ID
     * @returns {Object|null} Panel object or null
     */
    getPanel(panelId) {
        const state = this.getState();
        return state.panels.find(p => p.id === panelId) || null;
    }

    /**
     * Get all tabs
     * 
     * @returns {Array} Array of tab objects
     */
    getTabs() {
        return this.getState().tabs;
    }

    /**
     * Get default tab (first tab)
     * 
     * @returns {string|null} Default tab ID
     */
    getDefaultTab() {
        const state = this.getState();
        return state.tabs.length > 0 ? state.tabs[0].id : null;
    }

    /**
     * Persist active tab to storage
     * 
     * @param {string} tabId - Tab ID to persist
     * @returns {void}
     */
    persistTab(tabId) {
        try {
            localStorage.setItem(this.storageKey, tabId);
            this.log('Tab persisted:', tabId);
        } catch (error) {
            console.warn(`[${this.name}] Failed to persist tab:`, error);
        }
    }

    /**
     * Get persisted tab from storage
     * 
     * @returns {string|null} Persisted tab ID or null
     */
    getPersistedTab() {
        try {
            const tabId = localStorage.getItem(this.storageKey);
            if (tabId) {
                this.log('Persisted tab found:', tabId);
                return tabId;
            }
        } catch (error) {
            console.warn(`[${this.name}] Failed to get persisted tab:`, error);
        }
        return null;
    }

    /**
     * Clear persisted tab
     * 
     * @returns {void}
     */
    clearPersistedTab() {
        try {
            localStorage.removeItem(this.storageKey);
            this.log('Persisted tab cleared');
        } catch (error) {
            console.warn(`[${this.name}] Failed to clear persisted tab:`, error);
        }
    }

    /**
     * Enable tab
     * 
     * @param {string} tabId - Tab ID to enable
     * @returns {void}
     */
    enableTab(tabId) {
        const tab = this.getTab(tabId);
        if (tab) {
            tab.button.disabled = false;
            tab.button.setAttribute('aria-disabled', 'false');
            this.log('Tab enabled:', tabId);
        }
    }

    /**
     * Disable tab
     * 
     * @param {string} tabId - Tab ID to disable
     * @returns {void}
     */
    disableTab(tabId) {
        const tab = this.getTab(tabId);
        if (tab) {
            tab.button.disabled = true;
            tab.button.setAttribute('aria-disabled', 'true');
            
            // If this is the active tab, switch to another
            if (this.getState().activeTab === tabId) {
                const nextTab = this.getNextAvailableTab(tabId);
                if (nextTab) {
                    this.activateTab(nextTab.id);
                }
            }
            
            this.log('Tab disabled:', tabId);
        }
    }

    /**
     * Get next available (enabled) tab
     * 
     * @param {string} excludeTabId - Tab ID to exclude
     * @returns {Object|null} Next available tab or null
     */
    getNextAvailableTab(excludeTabId) {
        const state = this.getState();
        return state.tabs.find(tab => 
            tab.id !== excludeTabId && 
            !tab.button.disabled
        ) || null;
    }

    /**
     * Show tab
     * 
     * @param {string} tabId - Tab ID to show
     * @returns {void}
     */
    showTab(tabId) {
        const tab = this.getTab(tabId);
        if (tab) {
            tab.button.style.display = '';
            tab.button.removeAttribute('hidden');
            this.log('Tab shown:', tabId);
        }
    }

    /**
     * Hide tab
     * 
     * @param {string} tabId - Tab ID to hide
     * @returns {void}
     */
    hideTab(tabId) {
        const tab = this.getTab(tabId);
        if (tab) {
            tab.button.style.display = 'none';
            tab.button.setAttribute('hidden', '');
            
            // If this is the active tab, switch to another
            if (this.getState().activeTab === tabId) {
                const nextTab = this.getNextAvailableTab(tabId);
                if (nextTab) {
                    this.activateTab(nextTab.id);
                }
            }
            
            this.log('Tab hidden:', tabId);
        }
    }

    /**
     * Add badge to tab
     * 
     * @param {string} tabId - Tab ID
     * @param {string|number} badge - Badge content
     * @returns {void}
     */
    addBadge(tabId, badge) {
        const tab = this.getTab(tabId);
        if (tab) {
            let badgeElement = tab.button.querySelector('.mas-tab-badge');
            
            if (!badgeElement) {
                badgeElement = document.createElement('span');
                badgeElement.className = 'mas-tab-badge';
                tab.button.appendChild(badgeElement);
            }
            
            badgeElement.textContent = badge;
            this.log('Badge added to tab:', tabId, badge);
        }
    }

    /**
     * Remove badge from tab
     * 
     * @param {string} tabId - Tab ID
     * @returns {void}
     */
    removeBadge(tabId) {
        const tab = this.getTab(tabId);
        if (tab) {
            const badgeElement = tab.button.querySelector('.mas-tab-badge');
            if (badgeElement) {
                badgeElement.remove();
                this.log('Badge removed from tab:', tabId);
            }
        }
    }

    /**
     * Setup ARIA attributes for accessibility
     * Called during initialization
     * 
     * @returns {void}
     */
    setupAccessibility() {
        const state = this.getState();

        // Setup tab list
        const tabList = this.element.querySelector('[role="tablist"]');
        if (tabList) {
            tabList.setAttribute('aria-label', 'Settings tabs');
        }

        // Setup each tab
        state.tabs.forEach((tab, index) => {
            // Ensure proper ARIA attributes
            tab.button.setAttribute('role', 'tab');
            tab.button.setAttribute('aria-controls', tab.id);
            tab.button.setAttribute('id', `tab-${tab.id}`);
            
            // Set initial tabindex
            if (tab.id === state.activeTab) {
                tab.button.setAttribute('tabindex', '0');
                tab.button.setAttribute('aria-selected', 'true');
            } else {
                tab.button.setAttribute('tabindex', '-1');
                tab.button.setAttribute('aria-selected', 'false');
            }
        });

        // Setup each panel
        state.panels.forEach(panel => {
            // Ensure proper ARIA attributes
            panel.element.setAttribute('role', 'tabpanel');
            panel.element.setAttribute('aria-labelledby', `tab-${panel.id}`);
            
            // Set initial visibility
            if (panel.id === state.activeTab) {
                panel.element.removeAttribute('hidden');
            } else {
                panel.element.setAttribute('hidden', '');
            }
        });

        this.log('Accessibility attributes setup complete');
    }

    /**
     * Destroy component
     * 
     * @returns {void}
     */
    destroy() {
        // Clear persisted tab if needed
        // this.clearPersistedTab();

        // Call parent destroy
        super.destroy();
    }
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = TabManager;
}

// Make available globally
if (typeof window !== 'undefined') {
    window.TabManager = TabManager;
}
