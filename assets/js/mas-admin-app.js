/**
 * MAS Admin Application
 * 
 * Single entry point for Modern Admin Styler V2 frontend.
 * Replaces all existing handlers to eliminate conflicts.
 * 
 * @class MASAdminApp
 */
class MASAdminApp {
    constructor(config = {}) {
        // Merge configuration with defaults
        this.config = this.mergeConfig(config);

        // Core dependencies
        this.eventBus = new EventBus();
        this.stateManager = new StateManager(this.eventBus);
        this.apiClient = new APIClient(this.config.api);

        // Lazy loader for code splitting
        this.lazyLoader = typeof LazyLoader !== 'undefined' ? new LazyLoader() : null;

        // Component registry
        this.components = new Map();

        // Lifecycle state
        this.initialized = false;
        this.destroyed = false;

        // Debug mode
        this.debug = this.config.debug || false;

        this.log('MASAdminApp created', this.config);
    }

    /**
     * Initialize application
     * Prevents multiple initialization
     * 
     * @returns {Promise<void>}
     */
    async init() {
        if (this.initialized) {
            this.log('Already initialized, skipping');
            return;
        }

        this.log('Initializing...');

        try {
            // Remove any existing handlers to prevent conflicts
            this.removeExistingHandlers();

            // Initialize components
            await this.initializeComponents();

            // Setup global error handling
            this.setupErrorHandling();

            // Mark as initialized
            this.initialized = true;

            // Emit ready event
            this.eventBus.emit('app:ready', { app: this });

            this.log('Initialization complete');

        } catch (error) {
            this.handleError('Initialization failed', error);
            throw error;
        }
    }

    /**
     * Remove existing handlers to prevent conflicts
     * This is critical to eliminate dual handler issues
     */
    removeExistingHandlers() {
        this.log('Removing existing handlers...');

        // Remove jQuery handlers if present
        if (typeof jQuery !== 'undefined') {
            const $form = jQuery('#mas-v2-settings-form');
            if ($form.length) {
                $form.off('submit');
                this.log('Removed jQuery form submit handlers');
            }

            jQuery('.mas-reset-settings').off('click');
            jQuery('.mas-tab-button').off('click');
            jQuery('.mas-theme-selector').off('change');
        }

        // Remove vanilla JS handlers by cloning and replacing elements
        const form = document.querySelector('#mas-v2-settings-form');
        if (form) {
            const newForm = form.cloneNode(true);
            form.parentNode.replaceChild(newForm, form);
            this.log('Cloned form to remove vanilla JS handlers');
        }

        // Remove any global MAS objects
        if (window.masFormHandler) {
            this.log('Removing existing masFormHandler');
            delete window.masFormHandler;
        }

        if (window.MASSettingsManager) {
            this.log('Removing existing MASSettingsManager');
            delete window.MASSettingsManager;
        }

        // Disable ModernAdminApp if present
        if (window.MASDisableModules !== undefined) {
            window.MASDisableModules = true;
            this.log('Disabled ModernAdminApp modules');
        }

        // Remove old event listeners from document
        const oldHandlers = ['mas:settings:save', 'mas:settings:reset', 'mas:theme:apply'];
        oldHandlers.forEach(event => {
            document.removeEventListener(event, () => {});
        });

        this.log('Existing handlers removed');
    }

    /**
     * Initialize all components
     * Uses dependency injection pattern for clean component creation
     * 
     * @returns {Promise<void>}
     */
    async initializeComponents() {
        this.log('Initializing components...');

        // Note: Component classes will be implemented in subsequent tasks
        // For now, we just set up the structure with the new registration system

        // Notification System (Task 5) - Initialize first as other components may depend on it
        if (typeof NotificationSystem !== 'undefined') {
            const notificationSystem = new NotificationSystem(this.eventBus);
            this.registerComponent('notifications', notificationSystem, {
                dependencies: []
            });
        }

        // Tab Manager (Task 6) - No dependencies
        const tabContainer = document.querySelector('.mas-tabs');
        if (tabContainer && typeof TabManager !== 'undefined') {
            this.registerAndCreateComponent(
                'tabManager',
                TabManager,
                tabContainer,
                { dependencies: [] }
            );
        }

        // Settings Form Component (Task 3) - Depends on notifications
        const formElement = document.querySelector('#mas-v2-settings-form');
        if (formElement && typeof SettingsFormComponent !== 'undefined') {
            this.registerAndCreateComponent(
                'settingsForm',
                SettingsFormComponent,
                formElement,
                { dependencies: ['notifications'] }
            );
        }

        // Live Preview Component (Task 4) - Depends on settingsForm
        const previewContainer = document.querySelector('#mas-live-preview');
        if (previewContainer && typeof LivePreviewComponent !== 'undefined') {
            this.registerAndCreateComponent(
                'livePreview',
                LivePreviewComponent,
                previewContainer,
                { dependencies: ['settingsForm', 'notifications'] }
            );
        }

        // Backup Manager Component (Task 6) - Lazy loaded
        const backupElement = document.querySelector('#mas-backup-manager');
        if (backupElement) {
            // Check if lazy loading is enabled
            if (this.config.features.lazyLoadComponents && this.lazyLoader) {
                // Lazy load on demand (when tab is clicked or element becomes visible)
                this.setupLazyLoadTrigger('backupManager', backupElement);
            } else if (typeof BackupManagerComponent !== 'undefined') {
                // Load immediately if lazy loading disabled
                this.registerAndCreateComponent(
                    'backupManager',
                    BackupManagerComponent,
                    backupElement,
                    { dependencies: ['notifications'] }
                );
            }
        }

        // Theme Selector Component (Task 6) - Lazy loaded  
        const themeSelectorElement = document.querySelector('#mas-theme-selector');
        if (themeSelectorElement) {
            // Check if lazy loading is enabled
            if (this.config.features.lazyLoadComponents && this.lazyLoader) {
                // Lazy load on demand
                this.setupLazyLoadTrigger('themeSelector', themeSelectorElement);
            } else if (typeof ThemeSelectorComponent !== 'undefined') {
                // Load immediately if lazy loading disabled
                this.registerAndCreateComponent(
                    'themeSelector',
                    ThemeSelectorComponent,
                    themeSelectorElement,
                    { dependencies: ['settingsForm', 'notifications'] }
                );
            }
        }

        this.log(`${this.components.size} components initialized`);

        // Emit all components initialized event
        this.eventBus.emit('components:initialized', {
            count: this.components.size,
            components: this.getComponents()
        });
    }

    /**
     * Register a component
     * Automatically cleans up existing component if replacing
     * 
     * @param {string} name - Component name
     * @param {Object} component - Component instance
     * @param {Object} options - Registration options
     * @param {boolean} options.replace - Whether to replace existing component (default: true)
     * @param {Array<string>} options.dependencies - Component dependencies
     * @returns {Object} Registered component
     */
    registerComponent(name, component, options = {}) {
        const { replace = true, dependencies = [] } = options;

        // Check if component already exists
        if (this.components.has(name)) {
            if (!replace) {
                this.log(`Component ${name} already registered, skipping`);
                return this.components.get(name);
            }

            this.log(`Component ${name} already registered, replacing`);
            const existing = this.components.get(name);
            
            // Cleanup existing component
            if (existing && existing.destroy) {
                try {
                    existing.destroy();
                } catch (error) {
                    console.error(`Error destroying existing component ${name}:`, error);
                }
            }
        }

        // Validate dependencies
        if (dependencies.length > 0) {
            const missingDeps = dependencies.filter(dep => !this.components.has(dep));
            if (missingDeps.length > 0) {
                console.warn(`[${name}] Missing dependencies:`, missingDeps);
            }
        }

        // Store component with metadata
        this.components.set(name, component);
        
        // Store component metadata
        if (!this.componentMetadata) {
            this.componentMetadata = new Map();
        }
        this.componentMetadata.set(name, {
            registeredAt: Date.now(),
            dependencies,
            type: component.constructor.name
        });

        this.log(`Component registered: ${name}`);

        // Emit component registered event
        this.eventBus.emit('component:registered', { 
            name, 
            component,
            dependencies 
        });

        return component;
    }

    /**
     * Unregister a component
     * 
     * @param {string} name - Component name
     * @returns {boolean} Whether component was unregistered
     */
    unregisterComponent(name) {
        if (!this.components.has(name)) {
            this.log(`Component ${name} not found`);
            return false;
        }

        const component = this.components.get(name);

        // Destroy component
        if (component && component.destroy) {
            try {
                component.destroy();
            } catch (error) {
                console.error(`Error destroying component ${name}:`, error);
            }
        }

        // Remove from registry
        this.components.delete(name);
        if (this.componentMetadata) {
            this.componentMetadata.delete(name);
        }

        this.log(`Component unregistered: ${name}`);

        // Emit component unregistered event
        this.eventBus.emit('component:unregistered', { name });

        return true;
    }

    /**
     * Get a component by name
     * 
     * @param {string} name - Component name
     * @returns {Object|undefined} Component instance
     */
    getComponent(name) {
        return this.components.get(name);
    }

    /**
     * Check if component exists
     * 
     * @param {string} name - Component name
     * @returns {boolean} Whether component exists
     */
    hasComponent(name) {
        return this.components.has(name);
    }

    /**
     * Get all registered components
     * 
     * @returns {Array<string>} Array of component names
     */
    getComponents() {
        return Array.from(this.components.keys());
    }

    /**
     * Get component metadata
     * 
     * @param {string} name - Component name
     * @returns {Object|undefined} Component metadata
     */
    getComponentMetadata(name) {
        return this.componentMetadata?.get(name);
    }

    /**
     * Create component with dependency injection
     * Factory method that injects common dependencies
     * 
     * @param {Function} ComponentClass - Component class constructor
     * @param {HTMLElement} element - DOM element
     * @param {Object} additionalDeps - Additional dependencies
     * @returns {Object} Component instance
     */
    createComponent(ComponentClass, element, additionalDeps = {}) {
        if (!ComponentClass) {
            throw new Error('ComponentClass is required');
        }

        if (!element) {
            throw new Error('Element is required');
        }

        // Inject standard dependencies
        const component = new ComponentClass(
            element,
            this.apiClient,
            this.stateManager,
            this.eventBus,
            additionalDeps
        );

        this.log(`Component created: ${ComponentClass.name}`);

        return component;
    }

    /**
     * Register and create component in one step
     * 
     * @param {string} name - Component name
     * @param {Function} ComponentClass - Component class constructor
     * @param {HTMLElement} element - DOM element
     * @param {Object} options - Registration options
     * @returns {Object|null} Component instance or null if element not found
     */
    registerAndCreateComponent(name, ComponentClass, element, options = {}) {
        if (!element) {
            this.log(`Element not found for component: ${name}`);
            return null;
        }

        const component = this.createComponent(ComponentClass, element, options.additionalDeps);
        this.registerComponent(name, component, options);

        return component;
    }

    /**
     * Setup global error handling
     */
    setupErrorHandling() {
        // Catch unhandled promise rejections
        window.addEventListener('unhandledrejection', (event) => {
            this.handleError('Unhandled promise rejection', event.reason);
            event.preventDefault(); // Prevent default browser error
        });

        // Catch global errors
        window.addEventListener('error', (event) => {
            // Only handle errors from our code
            if (event.filename && event.filename.includes('mas-')) {
                this.handleError('Global error', event.error);
            }
        });

        this.log('Global error handling setup complete');
    }

    /**
     * Handle errors
     * 
     * @param {string} context - Error context
     * @param {Error} error - Error object
     */
    handleError(context, error) {
        console.error(`[MAS Admin App] ${context}:`, error);

        // Emit error event for components to handle
        this.eventBus.emit('app:error', {
            context,
            error,
            timestamp: Date.now(),
            message: error?.message || 'Unknown error'
        });

        // Show user-friendly notification if notification system is available
        if (this.hasComponent('notifications')) {
            const notifications = this.getComponent('notifications');
            if (notifications && notifications.show) {
                notifications.show({
                    type: 'error',
                    message: this.getUserFriendlyErrorMessage(error),
                    duration: 5000
                });
            }
        }
    }

    /**
     * Get user-friendly error message
     * 
     * @param {Error} error - Error object
     * @returns {string} User-friendly message
     */
    getUserFriendlyErrorMessage(error) {
        const errorMessages = {
            'network_error': 'Network error. Please check your connection.',
            'timeout': 'Request timed out. Please try again.',
            'rest_forbidden': 'You do not have permission to perform this action.',
            'validation_failed': 'Please check your input and try again.',
            'database_error': 'A database error occurred. Please try again.'
        };

        return errorMessages[error?.code] || error?.message || 'An unexpected error occurred.';
    }

    /**
     * Setup lazy load trigger for a component
     * 
     * @param {string} componentName - Component name
     * @param {HTMLElement} element - Component element
     */
    setupLazyLoadTrigger(componentName, element) {
        if (!this.lazyLoader) {
            return;
        }

        // Use Intersection Observer for visibility-based loading
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.lazyLoadComponent(componentName, element);
                        observer.disconnect();
                    }
                });
            }, {
                rootMargin: '50px' // Start loading 50px before element is visible
            });

            observer.observe(element);
        } else {
            // Fallback: load immediately if IntersectionObserver not supported
            this.lazyLoadComponent(componentName, element);
        }
    }

    /**
     * Lazy load a component
     * 
     * @param {string} componentName - Component name
     * @param {HTMLElement} element - Component element
     * @returns {Promise<Object>} Loaded component
     */
    async lazyLoadComponent(componentName, element) {
        if (!this.lazyLoader) {
            console.warn('LazyLoader not available');
            return null;
        }

        // Check if already loaded
        if (this.hasComponent(componentName)) {
            return this.getComponent(componentName);
        }

        // Check if already loading
        if (this.lazyLoader.isLoading(componentName)) {
            return null;
        }

        this.log(`Lazy loading component: ${componentName}`);

        try {
            // Map component names to their module paths
            const componentPaths = {
                'backupManager': './components/BackupManagerComponent.js',
                'themeSelector': './components/ThemeSelectorComponent.js'
            };

            const modulePath = componentPaths[componentName];
            if (!modulePath) {
                throw new Error(`Unknown component: ${componentName}`);
            }

            // Load the component module
            const module = await this.lazyLoader.loadComponent(
                componentName,
                modulePath,
                {
                    container: element,
                    showLoader: true,
                    onProgress: (progress) => {
                        this.eventBus.emit('component:loading', progress);
                    }
                }
            );

            // Get the component class from the module
            const ComponentClass = module.default || module[Object.keys(module)[0]];

            if (!ComponentClass) {
                throw new Error(`Component class not found in module: ${componentName}`);
            }

            // Create and register the component
            const dependencies = {
                'backupManager': ['notifications'],
                'themeSelector': ['settingsForm', 'notifications']
            };

            const component = this.registerAndCreateComponent(
                componentName,
                ComponentClass,
                element,
                { dependencies: dependencies[componentName] || [] }
            );

            this.log(`Component lazy loaded: ${componentName}`);

            // Emit lazy load complete event
            this.eventBus.emit('component:lazy-loaded', {
                name: componentName,
                component
            });

            return component;

        } catch (error) {
            console.error(`Failed to lazy load component ${componentName}:`, error);
            this.handleError(`Lazy load failed: ${componentName}`, error);
            return null;
        }
    }

    /**
     * Preload components for better performance
     * 
     * @param {Array<string>} componentNames - Array of component names to preload
     * @returns {Promise<void>}
     */
    async preloadComponents(componentNames) {
        if (!this.lazyLoader) {
            return;
        }

        this.log('Preloading components:', componentNames);

        const componentPaths = {
            'backupManager': './components/BackupManagerComponent.js',
            'themeSelector': './components/ThemeSelectorComponent.js'
        };

        const componentsToLoad = componentNames
            .filter(name => componentPaths[name])
            .map(name => ({
                name,
                path: componentPaths[name]
            }));

        try {
            await this.lazyLoader.preloadComponents(componentsToLoad);
            this.log('Components preloaded successfully');
        } catch (error) {
            console.error('Failed to preload components:', error);
        }
    }

    /**
     * Destroy application and cleanup
     */
    destroy() {
        if (this.destroyed) {
            return;
        }

        this.log('Destroying application...');

        // Emit destroy event
        this.eventBus.emit('app:destroy');

        // Destroy all components
        for (const [name, component] of this.components) {
            if (component.destroy) {
                try {
                    component.destroy();
                    this.log(`Component destroyed: ${name}`);
                } catch (error) {
                    console.error(`Error destroying component ${name}:`, error);
                }
            }
        }

        this.components.clear();

        // Clear event bus
        if (this.eventBus.destroy) {
            this.eventBus.destroy();
        }

        // Clear state manager
        if (this.stateManager.destroy) {
            this.stateManager.destroy();
        }

        // Clear API client pending requests
        if (this.apiClient.clearPendingRequests) {
            this.apiClient.clearPendingRequests();
        }

        this.destroyed = true;
        this.initialized = false;

        this.log('Application destroyed');
    }

    /**
     * Restart application
     * 
     * @returns {Promise<void>}
     */
    async restart() {
        this.log('Restarting application...');
        this.destroy();
        await this.init();
    }

    /**
     * Merge configuration with defaults
     * 
     * @param {Object} config - User configuration
     * @returns {Object} Merged configuration
     */
    mergeConfig(config) {
        return {
            debug: false,
            api: {
                baseUrl: window.wpApiSettings?.root || '/wp-json/',
                namespace: 'mas-v2/v1',
                nonce: window.wpApiSettings?.nonce || '',
                timeout: 30000,
                maxRetries: 3,
                retryDelay: 1000,
                useAjaxFallback: true
            },
            features: {
                livePreview: true,
                autoSave: false,
                offlineSupport: false,
                lazyLoadComponents: true // Enable lazy loading by default
            },
            ...config,
            api: {
                ...(config.api || {})
            }
        };
    }

    /**
     * Log message (if debug enabled)
     * 
     * @param {...*} args - Arguments to log
     */
    log(...args) {
        if (this.debug) {
            console.log('[MAS Admin App]', ...args);
        }
    }

    /**
     * Enable debug mode
     * 
     * @param {boolean} enabled - Whether to enable debug mode
     */
    setDebug(enabled) {
        this.debug = enabled;
        this.eventBus.setDebug(enabled);
        this.stateManager.setDebug(enabled);
        this.log(`Debug mode ${enabled ? 'enabled' : 'disabled'}`);
    }

    /**
     * Get application info
     * 
     * @returns {Object} Application info
     */
    getInfo() {
        const componentInfo = {};
        for (const [name, component] of this.components) {
            componentInfo[name] = {
                type: component.constructor.name,
                initialized: component.isInitialized,
                destroyed: component.isDestroyed,
                metadata: this.getComponentMetadata(name)
            };
        }

        return {
            initialized: this.initialized,
            destroyed: this.destroyed,
            componentCount: this.components.size,
            components: Array.from(this.components.keys()),
            componentDetails: componentInfo,
            config: this.config,
            state: this.stateManager.getState(),
            pendingRequests: this.apiClient.getPendingRequestCount?.() || 0
        };
    }
}

// Auto-initialize when DOM is ready
if (typeof window !== 'undefined') {
    // Make available globally
    window.MASAdminApp = MASAdminApp;

    // Auto-initialize if config is present
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            if (window.masAdminConfig && window.masAdminConfig.autoInit !== false) {
                window.masApp = new MASAdminApp(window.masAdminConfig);
                window.masApp.init().catch(error => {
                    console.error('[MAS Admin App] Auto-initialization failed:', error);
                });
            }
        });
    } else {
        // DOM already loaded
        if (window.masAdminConfig && window.masAdminConfig.autoInit !== false) {
            window.masApp = new MASAdminApp(window.masAdminConfig);
            window.masApp.init().catch(error => {
                console.error('[MAS Admin App] Auto-initialization failed:', error);
            });
        }
    }
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = MASAdminApp;
}
