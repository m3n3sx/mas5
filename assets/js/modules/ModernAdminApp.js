/**
 * Modern Admin Styler V2 - Main Application Orchestrator
 * Główny koordynator wszystkich modułów aplikacji
 */

class ModernAdminApp {
    constructor() {
        this.modules = new Map();
        this.moduleRegistry = new Map(); // Registry for module configurations
        this.settings = {};
        this.isInitialized = false;
        this.initPromise = null;
        this.eventListeners = new Map();
        this.autoRecoveryInterval = null;
        this.moduleStates = new Map(); // Track module states
    }
    
    // module_registration_system - Module registration system
    registerModule(config) {
        if (!config.name || !config.className) {
            throw new Error('Module registration requires name and className');
        }
        
        const moduleConfig = {
            name: config.name,
            className: config.className,
            fallbackClassName: config.fallbackClassName,
            priority: config.priority || 10,
            global: config.global !== false, // Default to global
            dependencies: config.dependencies || [],
            required: config.required || false,
            ...config
        };
        
        this.moduleRegistry.set(config.name, moduleConfig);
        console.log(`📝 Module registered: ${config.name}`);
        
        return this;
    }
    
    unregisterModule(name) {
        if (this.moduleRegistry.has(name)) {
            this.moduleRegistry.delete(name);
            console.log(`📝 Module unregistered: ${name}`);
        }
        return this;
    }
    
    // Enhanced event system for module communication
    addEventListener(eventType, callback, moduleContext = null) {
        if (!this.eventListeners.has(eventType)) {
            this.eventListeners.set(eventType, []);
        }
        
        const listener = {
            callback,
            moduleContext,
            id: Date.now() + Math.random(),
            priority: 0, // Default priority
            once: false  // Default to persistent listener
        };
        
        this.eventListeners.get(eventType).push(listener);
        
        // Sort listeners by priority (higher priority first)
        this.eventListeners.get(eventType).sort((a, b) => b.priority - a.priority);
        
        return listener.id; // Return ID for removal
    }
    
    // Enhanced addEventListener with options
    addEventListenerWithOptions(eventType, callback, options = {}) {
        if (!this.eventListeners.has(eventType)) {
            this.eventListeners.set(eventType, []);
        }
        
        const listener = {
            callback,
            moduleContext: options.moduleContext || null,
            id: Date.now() + Math.random(),
            priority: options.priority || 0,
            once: options.once || false,
            condition: options.condition || null // Function that must return true for listener to execute
        };
        
        this.eventListeners.get(eventType).push(listener);
        
        // Sort listeners by priority (higher priority first)
        this.eventListeners.get(eventType).sort((a, b) => b.priority - a.priority);
        
        return listener.id;
    }
    
    removeEventListener(eventType, listenerId) {
        if (this.eventListeners.has(eventType)) {
            const listeners = this.eventListeners.get(eventType);
            const index = listeners.findIndex(l => l.id === listenerId);
            if (index !== -1) {
                listeners.splice(index, 1);
                return true;
            }
        }
        return false;
    }
    
    // Remove all event listeners for a specific module
    removeModuleEventListeners(moduleContext) {
        let removedCount = 0;
        
        for (const [eventType, listeners] of this.eventListeners) {
            const originalLength = listeners.length;
            this.eventListeners.set(eventType, 
                listeners.filter(l => l.moduleContext !== moduleContext)
            );
            removedCount += originalLength - this.eventListeners.get(eventType).length;
        }
        
        console.log(`🧹 Removed ${removedCount} event listeners for module: ${moduleContext}`);
        return removedCount;
    }
    
    // Enhanced event dispatching with error handling and recovery
    dispatchModuleEvent(eventType, data = null, targetModule = null) {
        const event = {
            type: eventType,
            data,
            targetModule,
            timestamp: Date.now(),
            source: 'ModernAdminApp',
            preventDefault: false,
            stopPropagation: false
        };
        
        // Track event for debugging
        this.trackEventDispatch(eventType, data, targetModule);
        
        // Dispatch to registered listeners
        if (this.eventListeners.has(eventType)) {
            const listeners = this.eventListeners.get(eventType);
            const listenersToRemove = [];
            
            for (let i = 0; i < listeners.length; i++) {
                const listener = listeners[i];
                
                try {
                    // Check if target module specified and matches
                    if (targetModule && listener.moduleContext !== targetModule) {
                        continue;
                    }
                    
                    // Check condition if specified
                    if (listener.condition && !listener.condition(event)) {
                        continue;
                    }
                    
                    // Execute callback
                    const result = listener.callback(event);
                    
                    // Handle async callbacks
                    if (result instanceof Promise) {
                        result.catch(error => {
                            console.error(`❌ Async error in event listener for ${eventType}:`, error);
                            this.handleListenerError(eventType, listener, error);
                        });
                    }
                    
                    // Mark for removal if it's a once listener
                    if (listener.once) {
                        listenersToRemove.push(i);
                    }
                    
                    // Stop propagation if requested
                    if (event.stopPropagation) {
                        break;
                    }
                    
                } catch (error) {
                    console.error(`❌ Error in event listener for ${eventType}:`, error);
                    this.handleListenerError(eventType, listener, error);
                }
            }
            
            // Remove once listeners (in reverse order to maintain indices)
            for (let i = listenersToRemove.length - 1; i >= 0; i--) {
                listeners.splice(listenersToRemove[i], 1);
            }
        }
        
        // Also dispatch as DOM event for backward compatibility (if not prevented)
        if (!event.preventDefault) {
            this.dispatchAppEvent(eventType, data);
        }
        
        return event;
    }
    
    // Track event dispatches for debugging and monitoring
    trackEventDispatch(eventType, data, targetModule) {
        if (!this.eventTracker) {
            this.eventTracker = {
                dispatches: new Map(),
                recentEvents: []
            };
        }
        
        // Track dispatch count
        const currentCount = this.eventTracker.dispatches.get(eventType) || 0;
        this.eventTracker.dispatches.set(eventType, currentCount + 1);
        
        // Track recent events (keep last 50)
        this.eventTracker.recentEvents.push({
            type: eventType,
            timestamp: Date.now(),
            targetModule,
            dataSize: data ? JSON.stringify(data).length : 0
        });
        
        if (this.eventTracker.recentEvents.length > 50) {
            this.eventTracker.recentEvents.shift();
        }
    }
    
    // Handle listener errors with recovery attempts
    handleListenerError(eventType, listener, error) {
        const errorInfo = {
            eventType,
            moduleContext: listener.moduleContext,
            error: error.message,
            timestamp: Date.now()
        };
        
        // Log error details
        console.error('🚨 Event listener error details:', errorInfo);
        
        // Attempt module recovery if it's a module-specific listener
        if (listener.moduleContext) {
            this.attemptModuleRecovery(listener.moduleContext, error);
        }
        
        // Dispatch error event for monitoring
        this.dispatchModuleEvent('listener-error', errorInfo);
    }
    
    // module_lifecycle_management - Module lifecycle management
    setModuleState(moduleName, state, data = null) {
        const stateInfo = {
            state,
            data,
            timestamp: Date.now(),
            previousState: this.moduleStates.get(moduleName)?.state
        };
        
        this.moduleStates.set(moduleName, stateInfo);
        
        // Dispatch state change event
        this.dispatchModuleEvent('module-state-changed', {
            module: moduleName,
            ...stateInfo
        });
        
        console.log(`📊 Module ${moduleName} state: ${state}`);
    }
    
    getModuleState(moduleName) {
        return this.moduleStates.get(moduleName);
    }
    
    // Enhanced module management
    async reloadModule(moduleName) {
        console.log(`🔄 Reloading module: ${moduleName}`);
        
        try {
            // Get module configuration
            const config = this.moduleRegistry.get(moduleName);
            if (!config) {
                throw new Error(`Module ${moduleName} not found in registry`);
            }
            
            // Destroy existing module if it exists
            if (this.modules.has(moduleName)) {
                const existingModule = this.modules.get(moduleName);
                if (typeof existingModule.destroy === 'function') {
                    await existingModule.destroy();
                }
                this.modules.delete(moduleName);
            }
            
            // Set state to loading
            this.setModuleState(moduleName, 'loading');
            
            // Reload the module
            const result = await this.loadSingleModule(config);
            
            if (result.success) {
                this.setModuleState(moduleName, 'active');
                console.log(`✅ Module ${moduleName} reloaded successfully`);
            } else {
                this.setModuleState(moduleName, 'failed', result.error);
                console.error(`❌ Module ${moduleName} reload failed:`, result.error);
            }
            
            return result;
            
        } catch (error) {
            this.setModuleState(moduleName, 'error', error.message);
            console.error(`❌ Error reloading module ${moduleName}:`, error);
            return { success: false, error: error.message };
        }
    }
    
    async init(initialSettings = {}) {
        if (this.initPromise) {
            return this.initPromise;
        }
        
        this.initPromise = this._initializeApp(initialSettings);
        return this.initPromise;
    }
    
    async _initializeApp(initialSettings) {
        try {
            console.log('🚀 Initializing Modern Admin Styler V2...');
            
            this.settings = initialSettings;
            
            // Register default modules
            this.registerDefaultModules();
            
            // Setup global event listeners
            this.setupGlobalEventListeners();
            
            // Initialize modules with dependency resolution
            const moduleResults = await this.initializeModules();
            
            // Apply initial settings
            this.applyInitialSettings();
            
            // Start auto-recovery system
            this.startAutoRecovery();
            
            this.isInitialized = true;
            this.dispatchAppEvent('initialized', {
                moduleResults,
                settings: this.settings
            });
            
            console.log('✅ Modern Admin Styler V2 initialized successfully');
            
            // Perform initial health check
            setTimeout(() => this.performHealthCheck(), 1000);
            
        } catch (error) {
            console.error('❌ Error during Modern Admin Styler V2 initialization:', error);
            
            // Attempt emergency initialization
            await this.attemptEmergencyInitialization(error);
            
            this.dispatchAppEvent('init-error', { error: error.message });
            throw error;
        }
    }
    
    registerDefaultModules() {
        // Register all default modules
        this.registerModule({
            name: 'notificationManager',
            className: 'NotificationManager',
            priority: 1,
            global: true,
            dependencies: [],
            required: true
        });
        
        this.registerModule({
            name: 'themeManager',
            className: 'ThemeManager',
            priority: 2,
            global: true,
            dependencies: ['notificationManager'],
            required: false
        });
        
        this.registerModule({
            name: 'bodyClassManager',
            className: 'BodyClassManager',
            priority: 3,
            global: true,
            dependencies: [],
            required: false
        });
        
        this.registerModule({
            name: 'menuManager',
            className: 'MenuManagerFixed',
            fallbackClassName: 'MenuManager',
            priority: 4,
            global: true,
            dependencies: ['bodyClassManager'],
            required: false
        });
        
        this.registerModule({
            name: 'paletteManager',
            className: 'PaletteManager',
            priority: 5,
            global: true,
            dependencies: ['themeManager'],
            required: false
        });
        
        this.registerModule({
            name: 'livePreviewManager',
            className: 'LivePreviewManager',
            priority: 6,
            global: false,
            dependencies: ['notificationManager'],
            required: false
        });
        
        this.registerModule({
            name: 'settingsManager',
            className: 'SettingsManager',
            priority: 7,
            global: false,
            dependencies: ['notificationManager', 'livePreviewManager'],
            required: false
        });
        
        console.log(`📝 Registered ${this.moduleRegistry.size} default modules`);
    }
    
    async attemptEmergencyInitialization(originalError) {
        // emergency_initialization_system
        console.log('🚨 Attempting emergency initialization...');
        
        try {
            // Try to initialize only critical modules
            const criticalModules = Array.from(this.moduleRegistry.values())
                .filter(config => config.required);
            
            console.log(`🚨 Loading ${criticalModules.length} critical modules only`);
            
            for (const config of criticalModules) {
                try {
                    const result = await this.loadSingleModule(config);
                    if (result.success) {
                        console.log(`🚨 Critical module loaded: ${config.name}`);
                    } else {
                        // Create fallback for critical modules
                        const fallback = await this.attemptModuleFallback(config);
                        if (fallback.success) {
                            console.log(`🚨 Fallback created for critical module: ${config.name}`);
                        }
                    }
                } catch (error) {
                    console.error(`🚨 Failed to load critical module ${config.name}:`, error);
                }
            }
            
            // Apply basic settings
            this.applyInitialSettings();
            
            // Mark as partially initialized
            this.isInitialized = true;
            this.emergencyMode = true;
            
            this.dispatchAppEvent('emergency-initialized', {
                originalError: originalError.message,
                loadedModules: Array.from(this.modules.keys())
            });
            
            console.log('🚨 Emergency initialization completed');
            
        } catch (emergencyError) {
            console.error('🚨 Emergency initialization failed:', emergencyError);
            
            // Last resort: create minimal functionality
            this.createMinimalFunctionality();
        }
    }
    
    createMinimalFunctionality() {
        console.log('🚨 Creating minimal functionality...');
        
        // Create minimal notification system
        if (!this.modules.has('notificationManager')) {
            this.modules.set('notificationManager', {
                show: (message, type = 'info') => {
                    console.log(`[${type.toUpperCase()}] ${message}`);
                    
                    // Create simple toast notification
                    const toast = document.createElement('div');
                    toast.textContent = message;
                    toast.style.cssText = `
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        background: #333;
                        color: white;
                        padding: 10px 20px;
                        border-radius: 5px;
                        z-index: 999999;
                        opacity: 0;
                        transition: opacity 0.3s;
                    `;
                    
                    document.body.appendChild(toast);
                    
                    setTimeout(() => toast.style.opacity = '1', 10);
                    setTimeout(() => {
                        toast.style.opacity = '0';
                        setTimeout(() => toast.remove(), 300);
                    }, 3000);
                },
                success: function(msg) { this.show(msg, 'success'); },
                error: function(msg) { this.show(msg, 'error'); },
                warning: function(msg) { this.show(msg, 'warning'); },
                info: function(msg) { this.show(msg, 'info'); }
            });
        }
        
        this.isInitialized = true;
        this.emergencyMode = true;
        this.minimalMode = true;
        
        console.log('🚨 Minimal functionality created');
    }
    
    async initializeModules() {
        // Get modules from registry
        const allModules = Array.from(this.moduleRegistry.values());
        
        if (allModules.length === 0) {
            console.warn('⚠️ No modules registered for initialization');
            return [];
        }
        
        // Check if we're on settings page
        const isSettingsPage = this.isSettingsPage();
        
        // Filter modules based on page type
        const modulesToLoad = allModules.filter(config => 
            config.global || isSettingsPage
        );
        
        // Sort by priority
        modulesToLoad.sort((a, b) => a.priority - b.priority);
        
        console.log(`📦 Initializing ${modulesToLoad.length} modules with dependency resolution...`);
        console.log(`📋 Modules to load: ${modulesToLoad.map(m => m.name).join(', ')}`);
        
        // Initialize modules with dependency resolution
        const results = await this.initializeModulesWithDependencies(modulesToLoad);
        
        // Log results
        const successful = results.filter(r => r.success).length;
        const failed = results.filter(r => !r.success).length;
        
        console.log(`📊 Module initialization complete: ${successful} successful, ${failed} failed`);
        
        if (failed > 0) {
            const failedModules = results.filter(r => !r.success);
            console.warn('⚠️ Failed modules:', failedModules.map(r => r.name));
            
            // Check if any required modules failed
            const failedRequired = failedModules.filter(r => {
                const config = modulesToLoad.find(m => m.name === r.name);
                return config?.required;
            });
            
            if (failedRequired.length > 0) {
                console.error('❌ Critical modules failed:', failedRequired.map(r => r.name));
                this.dispatchModuleEvent('critical-modules-failed', { 
                    failed: failedRequired 
                });
            }
        }
        
        // Set initial states for all loaded modules
        results.forEach(result => {
            if (result.success) {
                this.setModuleState(result.name, 'active');
            } else {
                this.setModuleState(result.name, 'failed', result.error);
            }
        });
        
        return results;
    }
    
    async initializeModulesWithDependencies(moduleConfigs) {
        // enhanced_dependency_resolution_system
        const results = [];
        const loadingQueue = [...moduleConfigs];
        const dependencyGraph = this.buildDependencyGraph(moduleConfigs);
        const maxIterations = moduleConfigs.length * 3; // Prevent infinite loops
        let iterations = 0;
        
        // Validate dependency graph for circular dependencies
        const circularDeps = this.detectCircularDependencies(dependencyGraph);
        if (circularDeps.length > 0) {
            console.error('❌ Circular dependencies detected:', circularDeps);
            this.dispatchModuleEvent('circular-dependencies-detected', { cycles: circularDeps });
        }
        
        while (loadingQueue.length > 0 && iterations < maxIterations) {
            iterations++;
            const currentModule = loadingQueue.shift();
            
            // Check if dependencies are loaded and healthy
            const dependencyStatus = this.checkModuleDependencies(currentModule);
            
            if (!dependencyStatus.allLoaded) {
                // Move to end of queue if dependencies not ready
                loadingQueue.push(currentModule);
                
                // If we've been through all modules and none can load, break
                if (iterations > moduleConfigs.length && loadingQueue.length === moduleConfigs.length) {
                    console.warn('⚠️ Dependency deadlock detected, attempting resolution...');
                    
                    // Try to resolve deadlock by loading modules with partial dependencies
                    const resolvedModules = await this.resolveDependencyDeadlock(loadingQueue);
                    results.push(...resolvedModules);
                    break;
                }
                continue;
            }
            
            // Check if any dependencies are unhealthy
            if (dependencyStatus.unhealthyDependencies.length > 0) {
                console.warn(`⚠️ Module ${currentModule.name} has unhealthy dependencies:`, 
                    dependencyStatus.unhealthyDependencies);
                
                // Attempt to recover unhealthy dependencies first
                for (const depName of dependencyStatus.unhealthyDependencies) {
                    await this.attemptModuleRecovery(depName, new Error('Dependency health check failed'));
                }
            }
            
            // Attempt to load the module
            const result = await this.loadSingleModule(currentModule);
            results.push(result);
            
            if (result.success) {
                console.log(`✅ Module loaded: ${currentModule.name}`);
                
                // Notify dependent modules that this module is now available
                this.dispatchModuleEvent('module-dependency-available', {
                    moduleName: currentModule.name,
                    dependentModules: this.findDependentModules(currentModule.name, loadingQueue)
                });
            } else {
                console.warn(`⚠️ Module failed: ${currentModule.name}`, result.error);
                
                // If it's a required module, try fallback strategies
                if (currentModule.required) {
                    const fallbackResult = await this.attemptModuleFallback(currentModule);
                    if (fallbackResult.success) {
                        console.log(`🔄 Fallback successful for ${currentModule.name}`);
                        results[results.length - 1] = fallbackResult; // Replace the failed result
                    }
                }
                
                // Notify about failed dependency
                this.dispatchModuleEvent('module-dependency-failed', {
                    moduleName: currentModule.name,
                    error: result.error,
                    dependentModules: this.findDependentModules(currentModule.name, loadingQueue)
                });
            }
        }
        
        // Handle any remaining modules that couldn't be loaded due to dependencies
        if (loadingQueue.length > 0) {
            console.warn('⚠️ Some modules could not be loaded due to unresolved dependencies:', 
                loadingQueue.map(m => m.name));
            
            // Add failed results for unloaded modules
            for (const module of loadingQueue) {
                results.push({
                    name: module.name,
                    success: false,
                    error: new Error(`Unresolved dependencies: ${module.dependencies.join(', ')}`)
                });
            }
        }
        
        return results;
    }
    
    // Build dependency graph for analysis
    buildDependencyGraph(moduleConfigs) {
        const graph = new Map();
        
        for (const config of moduleConfigs) {
            graph.set(config.name, {
                dependencies: config.dependencies || [],
                dependents: [],
                required: config.required || false,
                priority: config.priority || 10
            });
        }
        
        // Build reverse dependencies (dependents)
        for (const [moduleName, moduleInfo] of graph) {
            for (const dep of moduleInfo.dependencies) {
                if (graph.has(dep)) {
                    graph.get(dep).dependents.push(moduleName);
                }
            }
        }
        
        return graph;
    }
    
    // Detect circular dependencies
    detectCircularDependencies(graph) {
        const visited = new Set();
        const recursionStack = new Set();
        const cycles = [];
        
        const dfs = (node, path = []) => {
            if (recursionStack.has(node)) {
                // Found a cycle
                const cycleStart = path.indexOf(node);
                cycles.push(path.slice(cycleStart).concat(node));
                return;
            }
            
            if (visited.has(node)) {
                return;
            }
            
            visited.add(node);
            recursionStack.add(node);
            path.push(node);
            
            const nodeInfo = graph.get(node);
            if (nodeInfo) {
                for (const dep of nodeInfo.dependencies) {
                    dfs(dep, [...path]);
                }
            }
            
            recursionStack.delete(node);
        };
        
        for (const node of graph.keys()) {
            if (!visited.has(node)) {
                dfs(node);
            }
        }
        
        return cycles;
    }
    
    // Check module dependencies status
    checkModuleDependencies(moduleConfig) {
        const status = {
            allLoaded: true,
            loadedDependencies: [],
            missingDependencies: [],
            unhealthyDependencies: []
        };
        
        for (const depName of moduleConfig.dependencies) {
            if (this.modules.has(depName)) {
                status.loadedDependencies.push(depName);
                
                // Check if dependency is healthy
                const depState = this.getModuleState(depName);
                if (depState && depState.state !== 'active') {
                    status.unhealthyDependencies.push(depName);
                }
            } else {
                status.missingDependencies.push(depName);
                status.allLoaded = false;
            }
        }
        
        return status;
    }
    
    // Find modules that depend on a given module
    findDependentModules(moduleName, moduleConfigs) {
        return moduleConfigs
            .filter(config => config.dependencies.includes(moduleName))
            .map(config => config.name);
    }
    
    // Resolve dependency deadlock by attempting partial loading
    async resolveDependencyDeadlock(deadlockedModules) {
        console.log('🔧 Attempting to resolve dependency deadlock...');
        
        const results = [];
        
        // Try to load modules with the fewest missing dependencies first
        const sortedModules = deadlockedModules.sort((a, b) => {
            const aMissing = a.dependencies.filter(dep => !this.modules.has(dep)).length;
            const bMissing = b.dependencies.filter(dep => !this.modules.has(dep)).length;
            return aMissing - bMissing;
        });
        
        for (const module of sortedModules) {
            try {
                // Attempt to load with partial dependencies
                console.log(`🔧 Attempting partial load of ${module.name}...`);
                
                const result = await this.loadSingleModule(module);
                results.push(result);
                
                if (result.success) {
                    console.log(`✅ Deadlock resolved: ${module.name} loaded`);
                    
                    // Remove from deadlocked list
                    const index = deadlockedModules.indexOf(module);
                    if (index > -1) {
                        deadlockedModules.splice(index, 1);
                    }
                } else {
                    console.warn(`⚠️ Partial load failed for ${module.name}`);
                }
                
            } catch (error) {
                console.error(`❌ Error during deadlock resolution for ${module.name}:`, error);
                results.push({
                    name: module.name,
                    success: false,
                    error: error.message
                });
            }
        }
        
        this.dispatchModuleEvent('dependency-deadlock-resolved', {
            resolvedModules: results.filter(r => r.success).map(r => r.name),
            failedModules: results.filter(r => !r.success).map(r => r.name)
        });
        
        return results;
    }
    
    async loadSingleModule(config) {
        try {
            // Get the class constructor
            const ModuleClass = this.getModuleClass(config);
            
            if (!ModuleClass) {
                throw new Error(`Module class not found: ${config.className}`);
            }
            
            console.log(`📦 Initializing module: ${config.name}`);
            
            // Create module instance
            const moduleInstance = new ModuleClass(this);
            
            // Initialize the module
            if (typeof moduleInstance.init === 'function') {
                if (config.global) {
                    await moduleInstance.init(this.settings);
                } else {
                    await moduleInstance.init();
                }
            }
            
            // Store the module
            this.modules.set(config.name, moduleInstance);
            
            // Dispatch success event
            this.dispatchAppEvent('module-loaded', { 
                module: config.name,
                instance: moduleInstance
            });
            
            return {
                name: config.name,
                success: true,
                instance: moduleInstance
            };
            
        } catch (error) {
            console.error(`❌ Error initializing module ${config.name}:`, error);
            
            // Dispatch error event
            this.dispatchAppEvent('module-error', { 
                module: config.name, 
                error: error.message
            });
            
            return {
                name: config.name,
                success: false,
                error: error.message
            };
        }
    }
    
    getModuleClass(config) {
        // module_class_verification_system - Try primary class name
        if (typeof window[config.className] === 'function') {
            return window[config.className];
        }
        
        // Try fallback class name if available
        if (config.fallbackClassName && typeof window[config.fallbackClassName] === 'function') {
            console.log(`🔄 Using fallback class ${config.fallbackClassName} for ${config.name}`);
            return window[config.fallbackClassName];
        }
        
        return null;
    }
    
    async attemptModuleFallback(config) {
        console.log(`🔄 Attempting fallback for critical module: ${config.name}`);
        
        try {
            // Create a minimal fallback implementation
            const fallbackModule = this.createFallbackModule(config);
            
            if (fallbackModule) {
                this.modules.set(config.name, fallbackModule);
                
                console.log(`✅ Fallback module created for ${config.name}`);
                
                return {
                    name: config.name,
                    success: true,
                    instance: fallbackModule,
                    fallback: true
                };
            }
            
        } catch (error) {
            console.error(`❌ Fallback failed for ${config.name}:`, error);
        }
        
        return {
            name: config.name,
            success: false,
            error: 'Fallback creation failed'
        };
    }
    
    createFallbackModule(config) {
        // fallback_module_creation_system - Create minimal fallback implementations for critical modules
        switch (config.name) {
            case 'notificationManager':
                return {
                    show: (message, type = 'info') => {
                        console.log(`[${type.toUpperCase()}] ${message}`);
                    },
                    success: (message) => console.log(`[SUCCESS] ${message}`),
                    error: (message) => console.error(`[ERROR] ${message}`),
                    warning: (message) => console.warn(`[WARNING] ${message}`),
                    info: (message) => console.info(`[INFO] ${message}`),
                    init: () => {},
                    destroy: () => {}
                };
            
            default:
                return {
                    init: () => {},
                    destroy: () => {},
                    updateSettings: () => {},
                    getCurrentState: () => ({ status: 'fallback' })
                };
        }
    }
    
    isSettingsPage() {
        // Sprawdź czy jesteśmy na stronie ustawień wtyczki
        const url = window.location.href;
        return url.includes('page=modern-admin-styler') || 
               url.includes('mas-v2-settings') ||
               document.querySelector('#mas-v2-settings-form') !== null;
    }
    
    setupGlobalEventListeners() {
        // Nasłuchuj zmian ustawień i propaguj do modułów
        document.addEventListener('mas-settings-changed', (e) => {
            this.settings = { ...this.settings, ...e.detail.settings };
            this.propagateSettingsToModules();
        });
        
        // Nasłuchuj błędów modułów
        document.addEventListener('mas-module-error', (e) => {
            console.error('Błąd modułu:', e.detail);
        });
        
        // Cleanup przy unload
        window.addEventListener('beforeunload', () => {
            this.destroy();
        });
    }
    
    applyInitialSettings() {
        // Zastosuj ustawienia początkowe do wszystkich modułów
        this.propagateSettingsToModules();
        
        // Wyślij event o załadowaniu ustawień
        this.dispatchAppEvent('settings-applied', this.settings);
    }
    
    propagateSettingsToModules() {
        this.modules.forEach((module, name) => {
            try {
                if (typeof module.updateSettings === 'function') {
                    module.updateSettings(this.settings);
                } else if (typeof module.applySettings === 'function') {
                    module.applySettings(this.settings);
                }
            } catch (error) {
                console.error(`❌ Error updating settings for module ${name}:`, error);
                
                // Attempt to recover the module
                this.attemptModuleRecovery(name, error);
            }
        });
    }
    
    async attemptModuleRecovery(moduleName, error) {
        // error_handling_and_recovery_mechanisms
        console.log(`🔄 Attempting recovery for module: ${moduleName}`);
        
        try {
            const module = this.modules.get(moduleName);
            
            if (!module) {
                console.warn(`⚠️ Module ${moduleName} not found for recovery`);
                return false;
            }
            
            // Try to reinitialize the module
            if (typeof module.init === 'function') {
                await module.init(this.settings);
                console.log(`✅ Module ${moduleName} recovered successfully`);
                
                this.dispatchAppEvent('module-recovered', { 
                    module: moduleName,
                    originalError: error.message
                });
                
                return true;
            }
            
        } catch (recoveryError) {
            console.error(`❌ Recovery failed for module ${moduleName}:`, recoveryError);
            
            // Mark module as failed and remove it
            this.modules.delete(moduleName);
            
            this.dispatchAppEvent('module-recovery-failed', { 
                module: moduleName,
                originalError: error.message,
                recoveryError: recoveryError.message
            });
        }
        
        return false;
    }
    
    // Enhanced module health checking system
    async performHealthCheck(targetModule = null) {
        const healthReport = {
            timestamp: Date.now(),
            totalModules: this.modules.size,
            healthyModules: 0,
            unhealthyModules: 0,
            criticalModules: 0,
            modules: {},
            systemHealth: 'unknown'
        };
        
        const modulesToCheck = targetModule ? 
            [[targetModule, this.modules.get(targetModule)]] : 
            Array.from(this.modules.entries());
        
        for (const [name, module] of modulesToCheck) {
            try {
                let moduleHealth = await this.checkModuleHealth(name, module);
                
                healthReport.modules[name] = moduleHealth;
                
                // Categorize module health
                switch (moduleHealth.status) {
                    case 'healthy':
                        healthReport.healthyModules++;
                        break;
                    case 'degraded':
                        healthReport.unhealthyModules++;
                        console.warn(`⚠️ Module ${name} is degraded:`, moduleHealth);
                        break;
                    case 'unhealthy':
                        healthReport.unhealthyModules++;
                        console.warn(`⚠️ Module ${name} is unhealthy:`, moduleHealth);
                        break;
                    case 'critical':
                        healthReport.criticalModules++;
                        console.error(`❌ Module ${name} is in critical state:`, moduleHealth);
                        break;
                    case 'error':
                        healthReport.unhealthyModules++;
                        console.error(`❌ Health check failed for module ${name}:`, moduleHealth);
                        break;
                }
                
            } catch (error) {
                healthReport.modules[name] = { 
                    status: 'error', 
                    error: error.message,
                    timestamp: Date.now()
                };
                healthReport.unhealthyModules++;
                console.error(`❌ Health check failed for module ${name}:`, error);
            }
        }
        
        // Determine overall system health
        healthReport.systemHealth = this.calculateSystemHealth(healthReport);
        
        // Dispatch health report event
        this.dispatchModuleEvent('health-check-complete', healthReport);
        
        // Trigger automatic recovery if needed
        if (healthReport.criticalModules > 0 || healthReport.unhealthyModules > healthReport.healthyModules) {
            this.triggerAutoRecovery(healthReport);
        }
        
        return healthReport;
    }
    
    // Comprehensive module health checking
    async checkModuleHealth(name, module) {
        const health = {
            status: 'healthy',
            timestamp: Date.now(),
            checks: {},
            metrics: {},
            issues: []
        };
        
        try {
            // 1. Basic existence and initialization check
            if (!module) {
                health.status = 'critical';
                health.issues.push('Module instance not found');
                return health;
            }
            
            // 2. Check if module has required methods
            const requiredMethods = ['init'];
            const optionalMethods = ['destroy', 'updateSettings', 'getCurrentState', 'healthCheck'];
            
            for (const method of requiredMethods) {
                health.checks[`has_${method}`] = typeof module[method] === 'function';
                if (!health.checks[`has_${method}`]) {
                    health.issues.push(`Missing required method: ${method}`);
                    health.status = 'degraded';
                }
            }
            
            for (const method of optionalMethods) {
                health.checks[`has_${method}`] = typeof module[method] === 'function';
            }
            
            // 3. Check module state
            const moduleState = this.getModuleState(name);
            if (moduleState) {
                health.checks.state = moduleState.state;
                health.metrics.stateAge = Date.now() - moduleState.timestamp;
                
                if (moduleState.state === 'failed' || moduleState.state === 'error') {
                    health.status = 'unhealthy';
                    health.issues.push(`Module state is ${moduleState.state}`);
                }
            }
            
            // 4. Custom health check if available
            if (typeof module.healthCheck === 'function') {
                try {
                    const customHealth = await module.healthCheck();
                    health.checks.custom = customHealth;
                    
                    if (customHealth.status && customHealth.status !== 'healthy') {
                        health.status = this.combineHealthStatus(health.status, customHealth.status);
                        if (customHealth.issues) {
                            health.issues.push(...customHealth.issues);
                        }
                    }
                } catch (error) {
                    health.checks.custom = { error: error.message };
                    health.issues.push(`Custom health check failed: ${error.message}`);
                    health.status = 'degraded';
                }
            }
            
            // 5. Check current state if available
            if (typeof module.getCurrentState === 'function') {
                try {
                    const currentState = module.getCurrentState();
                    health.checks.currentState = currentState;
                    
                    if (currentState.error) {
                        health.issues.push(`Module reports error: ${currentState.error}`);
                        health.status = 'unhealthy';
                    }
                } catch (error) {
                    health.checks.currentState = { error: error.message };
                    health.issues.push(`getCurrentState failed: ${error.message}`);
                    health.status = 'degraded';
                }
            }
            
            // 6. Check memory usage (if available)
            if (typeof module.getMemoryUsage === 'function') {
                try {
                    const memoryUsage = module.getMemoryUsage();
                    health.metrics.memoryUsage = memoryUsage;
                    
                    // Flag high memory usage
                    if (memoryUsage > 10 * 1024 * 1024) { // 10MB threshold
                        health.issues.push(`High memory usage: ${Math.round(memoryUsage / 1024 / 1024)}MB`);
                        health.status = this.combineHealthStatus(health.status, 'degraded');
                    }
                } catch (error) {
                    health.checks.memoryCheck = { error: error.message };
                }
            }
            
            // 7. Check event listener count (prevent memory leaks)
            const moduleListeners = this.getModuleEventListenerCount(name);
            health.metrics.eventListeners = moduleListeners;
            
            if (moduleListeners > 50) { // Threshold for too many listeners
                health.issues.push(`High event listener count: ${moduleListeners}`);
                health.status = this.combineHealthStatus(health.status, 'degraded');
            }
            
            // 8. Check dependencies health
            const config = this.moduleRegistry.get(name);
            if (config && config.dependencies.length > 0) {
                const depHealth = await this.checkDependenciesHealth(config.dependencies);
                health.checks.dependencies = depHealth;
                
                if (depHealth.unhealthyCount > 0) {
                    health.issues.push(`${depHealth.unhealthyCount} unhealthy dependencies`);
                    health.status = this.combineHealthStatus(health.status, 'degraded');
                }
            }
            
        } catch (error) {
            health.status = 'error';
            health.error = error.message;
            health.issues.push(`Health check exception: ${error.message}`);
        }
        
        return health;
    }
    
    // Calculate overall system health
    calculateSystemHealth(healthReport) {
        const total = healthReport.totalModules;
        const healthy = healthReport.healthyModules;
        const unhealthy = healthReport.unhealthyModules;
        const critical = healthReport.criticalModules;
        
        if (critical > 0) {
            return 'critical';
        }
        
        const healthyPercentage = (healthy / total) * 100;
        
        if (healthyPercentage >= 90) {
            return 'excellent';
        } else if (healthyPercentage >= 75) {
            return 'good';
        } else if (healthyPercentage >= 50) {
            return 'degraded';
        } else {
            return 'poor';
        }
    }
    
    // Combine health statuses (return the worse status)
    combineHealthStatus(status1, status2) {
        const statusPriority = {
            'healthy': 0,
            'degraded': 1,
            'unhealthy': 2,
            'critical': 3,
            'error': 4
        };
        
        const priority1 = statusPriority[status1] || 0;
        const priority2 = statusPriority[status2] || 0;
        
        return priority1 > priority2 ? status1 : status2;
    }
    
    // Get event listener count for a module
    getModuleEventListenerCount(moduleName) {
        let count = 0;
        
        for (const listeners of this.eventListeners.values()) {
            count += listeners.filter(l => l.moduleContext === moduleName).length;
        }
        
        return count;
    }
    
    // Check health of module dependencies
    async checkDependenciesHealth(dependencies) {
        const depHealth = {
            total: dependencies.length,
            healthy: 0,
            unhealthyCount: 0,
            details: {}
        };
        
        for (const depName of dependencies) {
            if (this.modules.has(depName)) {
                const health = await this.checkModuleHealth(depName, this.modules.get(depName));
                depHealth.details[depName] = health.status;
                
                if (health.status === 'healthy') {
                    depHealth.healthy++;
                } else {
                    depHealth.unhealthyCount++;
                }
            } else {
                depHealth.details[depName] = 'missing';
                depHealth.unhealthyCount++;
            }
        }
        
        return depHealth;
    }
    
    // Trigger automatic recovery based on health report
    async triggerAutoRecovery(healthReport) {
        console.log('🔧 Triggering automatic recovery based on health report...');
        
        const recoveryActions = [];
        
        // Prioritize critical modules first
        const criticalModules = Object.entries(healthReport.modules)
            .filter(([name, health]) => health.status === 'critical')
            .map(([name]) => name);
        
        const unhealthyModules = Object.entries(healthReport.modules)
            .filter(([name, health]) => health.status === 'unhealthy' || health.status === 'degraded')
            .map(([name]) => name);
        
        // Recover critical modules first
        for (const moduleName of criticalModules) {
            recoveryActions.push(this.attemptModuleRecovery(moduleName, new Error('Critical health status')));
        }
        
        // Then recover unhealthy modules
        for (const moduleName of unhealthyModules) {
            recoveryActions.push(this.attemptModuleRecovery(moduleName, new Error('Unhealthy status')));
        }
        
        // Wait for all recovery attempts
        const recoveryResults = await Promise.allSettled(recoveryActions);
        
        // Log recovery results
        const successful = recoveryResults.filter(r => r.status === 'fulfilled' && r.value).length;
        const failed = recoveryResults.length - successful;
        
        console.log(`🔧 Auto-recovery complete: ${successful} successful, ${failed} failed`);
        
        // Dispatch recovery complete event
        this.dispatchModuleEvent('auto-recovery-complete', {
            healthReport,
            recoveryResults: recoveryResults.map((result, index) => ({
                module: [...criticalModules, ...unhealthyModules][index],
                success: result.status === 'fulfilled' && result.value,
                error: result.status === 'rejected' ? result.reason : null
            }))
        });
        
        return recoveryResults;
    }
    
    // Enhanced automatic recovery system
    startAutoRecovery() {
        if (this.autoRecoveryInterval) {
            clearInterval(this.autoRecoveryInterval);
        }
        
        // Initialize recovery configuration
        this.recoveryConfig = {
            healthCheckInterval: 30000, // 30 seconds
            quickCheckInterval: 5000,   // 5 seconds for critical issues
            maxRecoveryAttempts: 3,
            recoveryBackoffMultiplier: 2,
            criticalModuleThreshold: 1,
            unhealthyModuleThreshold: 2
        };
        
        // Start regular health checks
        this.autoRecoveryInterval = setInterval(async () => {
            await this.performAutoRecoveryCheck();
        }, this.recoveryConfig.healthCheckInterval);
        
        // Start quick checks for critical issues
        this.quickRecoveryInterval = setInterval(async () => {
            await this.performQuickHealthCheck();
        }, this.recoveryConfig.quickCheckInterval);
        
        console.log('🔄 Enhanced auto-recovery system started');
        
        // Dispatch recovery system started event
        this.dispatchModuleEvent('auto-recovery-started', {
            config: this.recoveryConfig
        });
    }
    
    // Perform comprehensive auto-recovery check
    async performAutoRecoveryCheck() {
        try {
            const healthReport = await this.performHealthCheck();
            
            // Check if recovery is needed
            const needsRecovery = this.assessRecoveryNeed(healthReport);
            
            if (needsRecovery.required) {
                console.log(`🔄 Auto-recovery triggered: ${needsRecovery.reason}`);
                
                // Execute recovery strategy
                await this.executeRecoveryStrategy(healthReport, needsRecovery);
            }
            
            // Update recovery statistics
            this.updateRecoveryStats(healthReport);
            
        } catch (error) {
            console.error('❌ Error during auto-recovery check:', error);
            
            // Dispatch recovery error event
            this.dispatchModuleEvent('auto-recovery-error', {
                error: error.message,
                timestamp: Date.now()
            });
        }
    }
    
    // Quick health check for critical issues
    async performQuickHealthCheck() {
        try {
            // Only check critical modules and recently failed modules
            const criticalModules = this.getCriticalModules();
            const recentlyFailedModules = this.getRecentlyFailedModules();
            const modulesToCheck = [...criticalModules, ...recentlyFailedModules];
            
            if (modulesToCheck.length === 0) {
                return; // No critical modules to check
            }
            
            for (const moduleName of modulesToCheck) {
                if (this.modules.has(moduleName)) {
                    const health = await this.checkModuleHealth(moduleName, this.modules.get(moduleName));
                    
                    if (health.status === 'critical' || health.status === 'error') {
                        console.log(`🚨 Critical issue detected in ${moduleName}, immediate recovery...`);
                        await this.attemptModuleRecovery(moduleName, new Error(`Critical status: ${health.status}`));
                    }
                }
            }
            
        } catch (error) {
            console.error('❌ Error during quick health check:', error);
        }
    }
    
    // Assess if recovery is needed
    assessRecoveryNeed(healthReport) {
        const assessment = {
            required: false,
            reason: '',
            priority: 'low',
            strategy: 'standard'
        };
        
        // Critical modules failed
        if (healthReport.criticalModules > 0) {
            assessment.required = true;
            assessment.reason = `${healthReport.criticalModules} critical modules failed`;
            assessment.priority = 'critical';
            assessment.strategy = 'emergency';
            return assessment;
        }
        
        // Too many unhealthy modules
        if (healthReport.unhealthyModules >= this.recoveryConfig.unhealthyModuleThreshold) {
            assessment.required = true;
            assessment.reason = `${healthReport.unhealthyModules} unhealthy modules detected`;
            assessment.priority = 'high';
            assessment.strategy = 'comprehensive';
            return assessment;
        }
        
        // System health is poor
        if (healthReport.systemHealth === 'poor' || healthReport.systemHealth === 'critical') {
            assessment.required = true;
            assessment.reason = `System health is ${healthReport.systemHealth}`;
            assessment.priority = 'high';
            assessment.strategy = 'system-wide';
            return assessment;
        }
        
        // Individual module issues
        const degradedModules = Object.entries(healthReport.modules)
            .filter(([name, health]) => health.status === 'degraded').length;
        
        if (degradedModules > 0) {
            assessment.required = true;
            assessment.reason = `${degradedModules} degraded modules detected`;
            assessment.priority = 'medium';
            assessment.strategy = 'targeted';
        }
        
        return assessment;
    }
    
    // Execute recovery strategy based on assessment
    async executeRecoveryStrategy(healthReport, assessment) {
        const strategy = assessment.strategy;
        const recoveryResults = [];
        
        switch (strategy) {
            case 'emergency':
                recoveryResults.push(...await this.executeEmergencyRecovery(healthReport));
                break;
                
            case 'comprehensive':
                recoveryResults.push(...await this.executeComprehensiveRecovery(healthReport));
                break;
                
            case 'system-wide':
                recoveryResults.push(...await this.executeSystemWideRecovery(healthReport));
                break;
                
            case 'targeted':
                recoveryResults.push(...await this.executeTargetedRecovery(healthReport));
                break;
                
            default:
                recoveryResults.push(...await this.executeStandardRecovery(healthReport));
        }
        
        // Log recovery results
        const successful = recoveryResults.filter(r => r.success).length;
        const failed = recoveryResults.length - successful;
        
        console.log(`🔧 Recovery strategy '${strategy}' complete: ${successful} successful, ${failed} failed`);
        
        // Dispatch recovery complete event
        this.dispatchModuleEvent('recovery-strategy-complete', {
            strategy,
            assessment,
            results: recoveryResults,
            healthReport
        });
        
        return recoveryResults;
    }
    
    // Emergency recovery for critical failures
    async executeEmergencyRecovery(healthReport) {
        console.log('🚨 Executing emergency recovery...');
        
        const results = [];
        
        // 1. Stop all non-critical modules to free resources
        await this.stopNonCriticalModules();
        
        // 2. Attempt to recover critical modules with maximum priority
        const criticalModules = Object.entries(healthReport.modules)
            .filter(([name, health]) => health.status === 'critical')
            .map(([name]) => name);
        
        for (const moduleName of criticalModules) {
            const result = await this.attemptEmergencyModuleRecovery(moduleName);
            results.push(result);
        }
        
        // 3. Restart essential services
        await this.restartEssentialServices();
        
        return results;
    }
    
    // Comprehensive recovery for multiple issues
    async executeComprehensiveRecovery(healthReport) {
        console.log('🔧 Executing comprehensive recovery...');
        
        const results = [];
        
        // 1. Prioritize modules by importance and dependency order
        const modulePriorities = this.calculateModulePriorities(healthReport);
        
        // 2. Recover modules in priority order
        for (const [moduleName, priority] of modulePriorities) {
            const health = healthReport.modules[moduleName];
            
            if (health.status !== 'healthy') {
                const result = await this.attemptModuleRecovery(moduleName, 
                    new Error(`Comprehensive recovery: ${health.status}`));
                results.push(result);
                
                // Add delay between recoveries to prevent resource conflicts
                await this.delay(1000);
            }
        }
        
        return results;
    }
    
    // System-wide recovery for overall system health issues
    async executeSystemWideRecovery(healthReport) {
        console.log('🔄 Executing system-wide recovery...');
        
        const results = [];
        
        // 1. Clear all event listeners to prevent conflicts
        this.clearAllEventListeners();
        
        // 2. Restart the entire module system
        const restartResult = await this.restartModuleSystem();
        results.push(restartResult);
        
        // 3. Re-apply current settings
        if (this.settings) {
            this.propagateSettingsToModules();
        }
        
        return results;
    }
    
    // Targeted recovery for specific module issues
    async executeTargetedRecovery(healthReport) {
        console.log('🎯 Executing targeted recovery...');
        
        const results = [];
        
        // Only recover modules that are degraded or unhealthy
        const targetModules = Object.entries(healthReport.modules)
            .filter(([name, health]) => health.status === 'degraded' || health.status === 'unhealthy')
            .map(([name]) => name);
        
        for (const moduleName of targetModules) {
            const result = await this.attemptModuleRecovery(moduleName, 
                new Error('Targeted recovery for degraded module'));
            results.push(result);
        }
        
        return results;
    }
    
    // Standard recovery for minor issues
    async executeStandardRecovery(healthReport) {
        console.log('🔧 Executing standard recovery...');
        
        const results = [];
        
        // Simple recovery for unhealthy modules
        for (const [name, health] of Object.entries(healthReport.modules)) {
            if (health.status !== 'healthy') {
                const result = await this.attemptModuleRecovery(name, 
                    new Error(health.error || 'Standard recovery'));
                results.push(result);
            }
        }
        
        return results;
    }
    
    // Helper methods for recovery strategies
    getCriticalModules() {
        return Array.from(this.moduleRegistry.values())
            .filter(config => config.required)
            .map(config => config.name);
    }
    
    getRecentlyFailedModules() {
        const recentThreshold = Date.now() - (5 * 60 * 1000); // 5 minutes
        
        return Array.from(this.moduleStates.entries())
            .filter(([name, state]) => 
                (state.state === 'failed' || state.state === 'error') && 
                state.timestamp > recentThreshold
            )
            .map(([name]) => name);
    }
    
    calculateModulePriorities(healthReport) {
        const priorities = new Map();
        
        // Base priority on module configuration and current health
        for (const [name, health] of Object.entries(healthReport.modules)) {
            const config = this.moduleRegistry.get(name);
            let priority = config ? config.priority : 10;
            
            // Adjust priority based on health status
            switch (health.status) {
                case 'critical':
                    priority += 1000;
                    break;
                case 'unhealthy':
                    priority += 100;
                    break;
                case 'degraded':
                    priority += 10;
                    break;
            }
            
            // Required modules get higher priority
            if (config && config.required) {
                priority += 500;
            }
            
            priorities.set(name, priority);
        }
        
        // Sort by priority (higher first)
        return Array.from(priorities.entries()).sort((a, b) => b[1] - a[1]);
    }
    
    async stopNonCriticalModules() {
        const criticalModules = this.getCriticalModules();
        
        for (const [name, module] of this.modules) {
            if (!criticalModules.includes(name) && typeof module.destroy === 'function') {
                try {
                    await module.destroy();
                    console.log(`🛑 Stopped non-critical module: ${name}`);
                } catch (error) {
                    console.warn(`⚠️ Error stopping module ${name}:`, error);
                }
            }
        }
    }
    
    async attemptEmergencyModuleRecovery(moduleName) {
        console.log(`🚨 Emergency recovery for ${moduleName}...`);
        
        // Use more aggressive recovery with higher retry count
        const maxAttempts = 5;
        
        for (let attempt = 1; attempt <= maxAttempts; attempt++) {
            try {
                const result = await this.reloadModule(moduleName);
                
                if (result.success) {
                    console.log(`✅ Emergency recovery successful for ${moduleName} (attempt ${attempt})`);
                    return { moduleName, success: true, attempts: attempt };
                }
                
                // Exponential backoff with jitter
                const delay = Math.min(1000 * Math.pow(2, attempt - 1) + Math.random() * 1000, 10000);
                await this.delay(delay);
                
            } catch (error) {
                console.error(`❌ Emergency recovery attempt ${attempt} failed for ${moduleName}:`, error);
                
                if (attempt === maxAttempts) {
                    return { moduleName, success: false, error: error.message, attempts: attempt };
                }
            }
        }
        
        return { moduleName, success: false, error: 'Max attempts exceeded', attempts: maxAttempts };
    }
    
    async restartEssentialServices() {
        console.log('🔄 Restarting essential services...');
        
        // Restart notification system
        if (!this.modules.has('notificationManager')) {
            const config = this.moduleRegistry.get('notificationManager');
            if (config) {
                await this.loadSingleModule(config);
            }
        }
        
        // Restart event system
        this.setupGlobalEventListeners();
        
        console.log('✅ Essential services restarted');
    }
    
    async restartModuleSystem() {
        console.log('🔄 Restarting entire module system...');
        
        try {
            // 1. Destroy all modules
            for (const [name, module] of this.modules) {
                if (typeof module.destroy === 'function') {
                    try {
                        await module.destroy();
                    } catch (error) {
                        console.warn(`⚠️ Error destroying module ${name}:`, error);
                    }
                }
            }
            
            // 2. Clear module registry
            this.modules.clear();
            this.moduleStates.clear();
            
            // 3. Re-register default modules
            this.registerDefaultModules();
            
            // 4. Re-initialize modules
            const moduleResults = await this.initializeModules();
            
            console.log('✅ Module system restarted successfully');
            
            return { success: true, moduleResults };
            
        } catch (error) {
            console.error('❌ Error restarting module system:', error);
            return { success: false, error: error.message };
        }
    }
    
    clearAllEventListeners() {
        console.log('🧹 Clearing all event listeners...');
        
        const totalListeners = Array.from(this.eventListeners.values())
            .reduce((sum, listeners) => sum + listeners.length, 0);
        
        this.eventListeners.clear();
        
        console.log(`🧹 Cleared ${totalListeners} event listeners`);
    }
    
    updateRecoveryStats(healthReport) {
        if (!this.recoveryStats) {
            this.recoveryStats = {
                totalChecks: 0,
                recoveryAttempts: 0,
                successfulRecoveries: 0,
                failedRecoveries: 0,
                lastHealthReport: null
            };
        }
        
        this.recoveryStats.totalChecks++;
        this.recoveryStats.lastHealthReport = healthReport;
    }
    
    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
    
    stopAutoRecovery() {
        if (this.autoRecoveryInterval) {
            clearInterval(this.autoRecoveryInterval);
            this.autoRecoveryInterval = null;
            console.log('🔄 Auto-recovery system stopped');
        }
    }
    
    // Enhanced public API for modules
    getModule(name) {
        return this.modules.get(name);
    }
    
    hasModule(name) {
        return this.modules.has(name);
    }
    
    // Get module with health check
    getHealthyModule(name) {
        const module = this.modules.get(name);
        if (!module) return null;
        
        const state = this.getModuleState(name);
        if (state && state.state === 'active') {
            return module;
        }
        
        return null;
    }
    
    // Safe module method call with error handling
    async callModuleMethod(moduleName, methodName, ...args) {
        try {
            const module = this.getModule(moduleName);
            if (!module) {
                throw new Error(`Module ${moduleName} not found`);
            }
            
            if (typeof module[methodName] !== 'function') {
                throw new Error(`Method ${methodName} not found in module ${moduleName}`);
            }
            
            const result = await module[methodName](...args);
            
            // Dispatch successful method call event
            this.dispatchModuleEvent('module-method-called', {
                moduleName,
                methodName,
                success: true,
                timestamp: Date.now()
            });
            
            return result;
            
        } catch (error) {
            console.error(`❌ Error calling ${methodName} on ${moduleName}:`, error);
            
            // Dispatch error event
            this.dispatchModuleEvent('module-method-error', {
                moduleName,
                methodName,
                error: error.message,
                timestamp: Date.now()
            });
            
            // Attempt module recovery if method call failed
            this.attemptModuleRecovery(moduleName, error);
            
            throw error;
        }
    }
    
    // Broadcast message to all modules
    broadcastToModules(methodName, data = null) {
        const results = new Map();
        
        for (const [name, module] of this.modules) {
            try {
                if (typeof module[methodName] === 'function') {
                    const result = module[methodName](data);
                    results.set(name, { success: true, result });
                } else {
                    results.set(name, { success: false, error: `Method ${methodName} not found` });
                }
            } catch (error) {
                results.set(name, { success: false, error: error.message });
                console.error(`❌ Error broadcasting ${methodName} to ${name}:`, error);
            }
        }
        
        // Dispatch broadcast complete event
        this.dispatchModuleEvent('broadcast-complete', {
            methodName,
            data,
            results: Object.fromEntries(results),
            timestamp: Date.now()
        });
        
        return results;
    }
    
    // Get system status for debugging
    getSystemStatus() {
        return {
            isInitialized: this.isInitialized,
            emergencyMode: this.emergencyMode || false,
            minimalMode: this.minimalMode || false,
            totalModules: this.modules.size,
            registeredModules: this.moduleRegistry.size,
            eventListeners: Array.from(this.eventListeners.entries()).map(([type, listeners]) => ({
                type,
                count: listeners.length
            })),
            moduleStates: Object.fromEntries(this.moduleStates),
            recoveryStats: this.recoveryStats || null,
            eventTracker: this.eventTracker || null
        };
    }
    
    // Get detailed module information
    getModuleInfo(name) {
        const module = this.modules.get(name);
        const config = this.moduleRegistry.get(name);
        const state = this.moduleStates.get(name);
        
        if (!module && !config) {
            return null;
        }
        
        return {
            name,
            exists: !!module,
            config: config || null,
            state: state || null,
            methods: module ? Object.getOwnPropertyNames(Object.getPrototypeOf(module))
                .filter(name => typeof module[name] === 'function' && name !== 'constructor') : [],
            eventListeners: this.getModuleEventListenerCount(name)
        };
    }
    
    // Get all modules information
    getAllModulesInfo() {
        const allModuleNames = new Set([
            ...this.modules.keys(),
            ...this.moduleRegistry.keys()
        ]);
        
        const modulesInfo = {};
        
        for (const name of allModuleNames) {
            modulesInfo[name] = this.getModuleInfo(name);
        }
        
        return modulesInfo;
    }
    
    // Enhanced debugging methods
    getEventStatistics() {
        if (!this.eventTracker) {
            return { message: 'Event tracking not initialized' };
        }
        
        return {
            totalDispatches: Array.from(this.eventTracker.dispatches.values())
                .reduce((sum, count) => sum + count, 0),
            eventTypes: Object.fromEntries(this.eventTracker.dispatches),
            recentEvents: this.eventTracker.recentEvents.slice(-10), // Last 10 events
            activeListeners: Array.from(this.eventListeners.entries()).map(([type, listeners]) => ({
                type,
                count: listeners.length,
                modules: [...new Set(listeners.map(l => l.moduleContext).filter(Boolean))]
            }))
        };
    }
    
    // Performance monitoring
    getPerformanceMetrics() {
        const metrics = {
            initializationTime: this.initPromise ? Date.now() - (this.initStartTime || Date.now()) : null,
            moduleCount: this.modules.size,
            eventListenerCount: Array.from(this.eventListeners.values())
                .reduce((sum, listeners) => sum + listeners.length, 0),
            memoryUsage: this.estimateMemoryUsage(),
            healthCheckCount: this.recoveryStats ? this.recoveryStats.totalChecks : 0,
            recoveryAttempts: this.recoveryStats ? this.recoveryStats.recoveryAttempts : 0
        };
        
        return metrics;
    }
    
    // Estimate memory usage (rough calculation)
    estimateMemoryUsage() {
        try {
            let estimatedSize = 0;
            
            // Estimate module memory
            estimatedSize += this.modules.size * 1024; // ~1KB per module base
            
            // Estimate event listener memory
            const totalListeners = Array.from(this.eventListeners.values())
                .reduce((sum, listeners) => sum + listeners.length, 0);
            estimatedSize += totalListeners * 100; // ~100 bytes per listener
            
            // Estimate settings memory
            if (this.settings) {
                estimatedSize += JSON.stringify(this.settings).length;
            }
            
            return estimatedSize;
            
        } catch (error) {
            return { error: 'Could not estimate memory usage' };
        }
    }
    
    // Module communication helpers
    
    // Send message to specific module
    sendMessageToModule(targetModule, message, data = null) {
        return this.dispatchModuleEvent('module-message', {
            message,
            data,
            timestamp: Date.now()
        }, targetModule);
    }
    
    // Request data from module
    async requestDataFromModule(moduleName, dataType, params = null) {
        const module = this.getModule(moduleName);
        if (!module) {
            throw new Error(`Module ${moduleName} not found`);
        }
        
        // Try standard data request method
        if (typeof module.getData === 'function') {
            return await module.getData(dataType, params);
        }
        
        // Try specific getter method
        const getterMethod = `get${dataType.charAt(0).toUpperCase() + dataType.slice(1)}`;
        if (typeof module[getterMethod] === 'function') {
            return await module[getterMethod](params);
        }
        
        throw new Error(`Module ${moduleName} does not support data type: ${dataType}`);
    }
    
    // Subscribe to module events
    subscribeToModuleEvents(moduleName, eventTypes, callback) {
        const subscriptions = [];
        
        for (const eventType of eventTypes) {
            const listenerId = this.addEventListenerWithOptions(eventType, callback, {
                moduleContext: moduleName,
                priority: 5
            });
            subscriptions.push({ eventType, listenerId });
        }
        
        return {
            unsubscribe: () => {
                for (const { eventType, listenerId } of subscriptions) {
                    this.removeEventListener(eventType, listenerId);
                }
            },
            subscriptions
        };
    }
    
    hasModule(name) {
        return this.modules.has(name);
    }
    
    getAllModules() {
        return new Map(this.modules);
    }
    
    getSettings() {
        return { ...this.settings };
    }
    
    updateSettings(newSettings) {
        this.settings = { ...this.settings, ...newSettings };
        this.propagateSettingsToModules();
        this.dispatchAppEvent('settings-updated', this.settings);
    }
    
    // API dla zewnętrznych skryptów
    enableFloatingMenu() {
        const bodyManager = this.getModule('bodyClassManager');
        const menuManager = this.getModule('menuManager');
        
        if (bodyManager) {
            bodyManager.enableFloatingMenu();
        }
        
        if (menuManager) {
            menuManager.setFloating(true);
        }
        
        this.updateSettings({ menu_detached: true });
    }
    
    disableFloatingMenu() {
        const bodyManager = this.getModule('bodyClassManager');
        const menuManager = this.getModule('menuManager');
        
        if (bodyManager) {
            bodyManager.disableFloatingMenu();
        }
        
        if (menuManager) {
            menuManager.setFloating(false);
        }
        
        this.updateSettings({ menu_detached: false });
    }
    
    toggleTheme() {
        const themeManager = this.getModule('themeManager');
        if (themeManager) {
            themeManager.toggleTheme();
        }
    }
    
    enableLivePreview() {
        const livePreview = this.getModule('livePreviewManager');
        if (livePreview) {
            livePreview.enable();
        }
    }
    
    disableLivePreview() {
        const livePreview = this.getModule('livePreviewManager');
        if (livePreview) {
            livePreview.disable();
        }
    }
    
    saveSettings() {
        const settingsManager = this.getModule('settingsManager');
        if (settingsManager) {
            return settingsManager.saveSettings();
        }
        return Promise.reject(new Error('SettingsManager nie jest dostępny'));
    }
    
    exportSettings() {
        const settingsManager = this.getModule('settingsManager');
        if (settingsManager) {
            return settingsManager.exportSettings();
        }
        throw new Error('SettingsManager nie jest dostępny');
    }
    
    // Debugging i diagnostyka
    getSystemInfo() {
        const info = {
            isInitialized: this.isInitialized,
            isSettingsPage: this.isSettingsPage(),
            loadedModules: Array.from(this.modules.keys()),
            settings: this.settings,
            modules: {}
        };
        
        this.modules.forEach((module, name) => {
            try {
                if (typeof module.getCurrentState === 'function') {
                    info.modules[name] = module.getCurrentState();
                } else {
                    info.modules[name] = { status: 'active' };
                }
            } catch (error) {
                info.modules[name] = { status: 'error', error: error.message };
            }
        });
        
        return info;
    }
    
    logSystemInfo() {
        console.group('🔍 Modern Admin Styler V2 - System Info');
        console.log(this.getSystemInfo());
        console.groupEnd();
    }
    
    // Event system
    dispatchAppEvent(eventType, data = null) {
        const event = new CustomEvent(`mas-app-${eventType}`, {
            detail: {
                app: this,
                eventType,
                data,
                timestamp: Date.now()
            }
        });
        document.dispatchEvent(event);
    }
    
    // Enhanced cleanup
    async destroy() {
        console.log('🧹 Cleaning up Modern Admin Styler V2...');
        
        // Stop auto-recovery system
        this.stopAutoRecovery();
        
        // Destroy modules in reverse order of initialization
        const moduleNames = Array.from(this.modules.keys()).reverse();
        
        for (const name of moduleNames) {
            try {
                const module = this.modules.get(name);
                if (typeof module.destroy === 'function') {
                    await module.destroy();
                    console.log(`✅ Module ${name} cleaned up`);
                } else {
                    console.log(`ℹ️ Module ${name} has no destroy method`);
                }
                
                this.setModuleState(name, 'destroyed');
                
            } catch (error) {
                console.error(`❌ Error cleaning up module ${name}:`, error);
            }
        }
        
        // Clear all collections
        this.modules.clear();
        this.moduleRegistry.clear();
        this.eventListeners.clear();
        this.moduleStates.clear();
        
        // Reset state
        this.isInitialized = false;
        this.initPromise = null;
        this.emergencyMode = false;
        this.minimalMode = false;
        
        this.dispatchAppEvent('destroyed');
        
        console.log('🧹 Modern Admin Styler V2 cleanup complete');
    }
    
    // graceful_shutdown_system - Graceful shutdown
    async gracefulShutdown(reason = 'Manual shutdown') {
        console.log(`🔄 Initiating graceful shutdown: ${reason}`);
        
        this.dispatchAppEvent('shutdown-initiated', { reason });
        
        try {
            // Give modules time to save state or complete operations
            await new Promise(resolve => setTimeout(resolve, 1000));
            
            // Destroy all modules
            await this.destroy();
            
            this.dispatchAppEvent('shutdown-complete', { reason });
            
        } catch (error) {
            console.error('❌ Error during graceful shutdown:', error);
            this.dispatchAppEvent('shutdown-error', { reason, error: error.message });
        }
    }
    
    // Task 15: Memory management and cleanup methods
    cleanup() {
        console.log('🧹 Starting ModernAdminApp cleanup...');
        
        try {
            // Clear all event listeners
            this.eventListeners.clear();
            
            // Cleanup modules
            for (const [name, module] of this.modules) {
                if (module && typeof module.cleanup === 'function') {
                    try {
                        module.cleanup();
                        console.log(`🧹 Cleaned up module: ${name}`);
                    } catch (error) {
                        console.warn(`⚠️ Error cleaning up module ${name}:`, error);
                    }
                }
            }
            
            // Clear module references
            this.modules.clear();
            this.moduleRegistry.clear();
            this.moduleStates.clear();
            
            // Clear auto-recovery interval
            if (this.autoRecoveryInterval) {
                clearInterval(this.autoRecoveryInterval);
                this.autoRecoveryInterval = null;
            }
            
            // Clear settings reference
            this.settings = {};
            
            // Reset initialization state
            this.isInitialized = false;
            this.initPromise = null;
            
            console.log('✅ ModernAdminApp cleanup completed');
            
        } catch (error) {
            console.error('❌ Error during ModernAdminApp cleanup:', error);
        }
    }
    
    // Task 15: Performance monitoring for JavaScript modules
    getPerformanceMetrics() {
        const metrics = {
            moduleCount: this.modules.size,
            eventListenerCount: Array.from(this.eventListeners.values())
                .reduce((total, listeners) => total + listeners.length, 0),
            memoryUsage: performance.memory ? {
                used: performance.memory.usedJSHeapSize,
                total: performance.memory.totalJSHeapSize,
                limit: performance.memory.jsHeapSizeLimit
            } : null,
            moduleStates: Object.fromEntries(this.moduleStates),
            isInitialized: this.isInitialized,
            emergencyMode: this.emergencyMode || false
        };
        
        return metrics;
    }
    
    // Task 15: Automatic memory cleanup based on usage
    performAutomaticCleanup() {
        const metrics = this.getPerformanceMetrics();
        
        // Check if memory usage is high
        if (metrics.memoryUsage && metrics.memoryUsage.used > metrics.memoryUsage.limit * 0.8) {
            console.warn('⚠️ High memory usage detected, performing cleanup...');
            
            // Remove unused event listeners
            for (const [eventType, listeners] of this.eventListeners) {
                const activeListeners = listeners.filter(listener => {
                    // Remove listeners from destroyed modules
                    if (listener.moduleContext && !this.modules.has(listener.moduleContext)) {
                        return false;
                    }
                    return true;
                });
                
                if (activeListeners.length !== listeners.length) {
                    this.eventListeners.set(eventType, activeListeners);
                    console.log(`🧹 Cleaned up ${listeners.length - activeListeners.length} orphaned listeners for ${eventType}`);
                }
            }
            
            // Force garbage collection if available
            if (window.gc) {
                window.gc();
                console.log('🧹 Forced garbage collection');
            }
        }
    }
    
    // Singleton pattern dla globalnego dostępu
    static getInstance() {
        if (!ModernAdminApp.instance) {
            ModernAdminApp.instance = new ModernAdminApp();
        }
        return ModernAdminApp.instance;
    }
}

// Globalna instancja
window.ModernAdminApp = ModernAdminApp;

// Task 15: Automatic cleanup on page unload to prevent memory leaks
window.addEventListener('beforeunload', () => {
    const app = ModernAdminApp.getInstance();
    if (app.isInitialized) {
        app.cleanup();
    }
});

// Task 15: Periodic automatic cleanup every 5 minutes
setInterval(() => {
    const app = ModernAdminApp.getInstance();
    if (app.isInitialized) {
        app.performAutomaticCleanup();
    }
}, 300000); // 5 minutes

// Auto-inicjalizacja jeśli mamy dane
// Auto-initialization disabled - handled by admin-global.js to prevent double initialization
// This ensures proper coordination with the module loader system

// Eksport dla użycia w innych modułach
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ModernAdminApp;
} 