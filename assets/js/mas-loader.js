/**
 * Modern Admin Styler V2 - Enhanced Module Loader
 * Advanced module loading with dependency resolution, retry logic, and error handling
 */

(function() {
    'use strict';
    
    // Enhanced module configuration with dependency information
    const moduleConfig = {
        // Core modules with dependency order (loaded in sequence)
        core: [
            {
                name: 'NotificationManager',
                path: 'modules/NotificationManager.js',
                dependencies: [],
                timeout: 5000,
                retries: 3
            },
            {
                name: 'ThemeManager',
                path: 'modules/ThemeManager.js',
                dependencies: ['NotificationManager'],
                timeout: 5000,
                retries: 3
            },
            {
                name: 'BodyClassManager',
                path: 'modules/BodyClassManager.js',
                dependencies: [],
                timeout: 5000,
                retries: 3
            },
            {
                name: 'MenuManagerFixed',
                path: 'modules/MenuManagerFixed.js',
                dependencies: ['BodyClassManager'],
                timeout: 5000,
                retries: 3
            },
            {
                name: 'PaletteManager',
                path: 'modules/PaletteManager.js',
                dependencies: ['ThemeManager'],
                timeout: 5000,
                retries: 3
            },
            {
                name: 'ModernAdminApp',
                path: 'modules/ModernAdminApp.js',
                dependencies: ['NotificationManager', 'ThemeManager', 'BodyClassManager', 'MenuManagerFixed', 'PaletteManager'],
                timeout: 10000,
                retries: 3
            }
        ],
        // Settings page modules (only on settings page)
        settings: [
            {
                name: 'LivePreviewManager',
                path: 'modules/LivePreviewManager.js',
                dependencies: ['ModernAdminApp'],
                timeout: 5000,
                retries: 3
            },
            {
                name: 'SettingsManager',
                path: 'modules/SettingsManager.js',
                dependencies: ['ModernAdminApp', 'LivePreviewManager'],
                timeout: 5000,
                retries: 3
            }
        ]
    };
    
    // Enhanced loader state management
    const loaderState = {
        loadedModules: new Set(),
        failedModules: new Set(),
        loadingPromises: new Map(),
        retryAttempts: new Map(),
        startTime: Date.now()
    };
    
    // Detect page type
    const pageType = detectPageType();
    
    // Load modules with enhanced error handling and dependency resolution
    loadModules(pageType).then((results) => {
        const totalTime = Date.now() - loaderState.startTime;
        console.log(`✅ Modern Admin Styler V2 modules loaded in ${totalTime}ms`);
        console.log(`📊 Success: ${results.successful.length}, Failed: ${results.failed.length}`);
        
        if (results.failed.length > 0) {
            console.warn('⚠️ Some modules failed to load:', results.failed);
            // Attempt fallback mechanisms
            handleFailedModules(results.failed);
        }
        
        console.log('🔄 Ready for admin-global.js/admin-modern.js initialization...');
        
        // Dispatch ready event for main scripts
        document.dispatchEvent(new CustomEvent('mas-modules-ready', {
            detail: { 
                successful: results.successful, 
                failed: results.failed,
                loadTime: totalTime
            }
        }));
        
    }).catch(error => {
        console.error('❌ Critical error in module loading system:', error);
        // Attempt emergency fallback
        handleCriticalFailure(error);
    });
    
    function detectPageType() {
        const url = window.location.href;
        const isSettingsPage = url.includes('page=modern-admin-styler') || 
                              url.includes('mas-v2-settings') ||
                              document.querySelector('#mas-v2-settings-form') !== null;
        
        return {
            isSettings: isSettingsPage,
            isGlobal: true
        };
    }
    
    async function loadModules(pageType) {
        const basePath = getBasePath();
        const results = { successful: [], failed: [] };
        
        try {
            // Determine which modules to load
            const modulesToLoad = [...moduleConfig.core];
            if (pageType.isSettings) {
                modulesToLoad.push(...moduleConfig.settings);
            }
            
            console.log(`🔄 Loading ${modulesToLoad.length} modules with dependency resolution...`);
            
            // Load modules with dependency resolution
            const loadedModules = await loadModulesWithDependencies(modulesToLoad, basePath);
            
            // Process results
            for (const [moduleName, result] of loadedModules) {
                if (result.success) {
                    results.successful.push(moduleName);
                    loaderState.loadedModules.add(moduleName);
                } else {
                    results.failed.push({ name: moduleName, error: result.error });
                    loaderState.failedModules.add(moduleName);
                }
            }
            
            return results;
            
        } catch (error) {
            console.error('❌ Critical error in module loading:', error);
            throw error;
        }
    }
    
    async function loadModulesWithDependencies(modules, basePath) {
        const loadedModules = new Map();
        const loadingQueue = [...modules];
        const maxIterations = modules.length * 2; // Prevent infinite loops
        let iterations = 0;
        
        while (loadingQueue.length > 0 && iterations < maxIterations) {
            iterations++;
            const currentModule = loadingQueue.shift();
            
            // Check if dependencies are loaded
            const dependenciesLoaded = currentModule.dependencies.every(dep => 
                loaderState.loadedModules.has(dep)
            );
            
            if (!dependenciesLoaded) {
                // Move to end of queue if dependencies not ready
                loadingQueue.push(currentModule);
                continue;
            }
            
            // Load the module
            try {
                const result = await loadModuleWithRetry(currentModule, basePath);
                loadedModules.set(currentModule.name, result);
                
                if (result.success) {
                    console.log(`📦 Module loaded: ${currentModule.name}`);
                } else {
                    console.warn(`⚠️ Module failed: ${currentModule.name}`, result.error);
                }
                
            } catch (error) {
                console.error(`❌ Critical error loading ${currentModule.name}:`, error);
                loadedModules.set(currentModule.name, { success: false, error });
            }
        }
        
        // Check for unresolved dependencies
        if (loadingQueue.length > 0) {
            console.warn('⚠️ Some modules could not be loaded due to unresolved dependencies:', 
                loadingQueue.map(m => m.name));
            
            // Add failed modules to results
            for (const module of loadingQueue) {
                loadedModules.set(module.name, { 
                    success: false, 
                    error: new Error('Unresolved dependencies: ' + module.dependencies.join(', '))
                });
            }
        }
        
        return loadedModules;
    }
    
    async function loadModuleWithRetry(moduleConfig, basePath) {
        const modulePath = basePath + moduleConfig.path;
        const maxRetries = moduleConfig.retries || 3;
        const timeout = moduleConfig.timeout || 5000;
        
        for (let attempt = 1; attempt <= maxRetries; attempt++) {
            try {
                // Track retry attempts
                const retryKey = moduleConfig.name;
                loaderState.retryAttempts.set(retryKey, attempt);
                
                if (attempt > 1) {
                    console.log(`🔄 Retry ${attempt}/${maxRetries} for ${moduleConfig.name}`);
                    // Exponential backoff delay
                    await delay(Math.pow(2, attempt - 1) * 1000);
                }
                
                await loadScriptWithTimeout(modulePath, timeout);
                
                // Verify module was actually loaded (check if class exists)
                if (!verifyModuleLoaded(moduleConfig.name)) {
                    throw new Error(`Module class ${moduleConfig.name} not found after loading`);
                }
                
                return { success: true, attempts: attempt };
                
            } catch (error) {
                console.warn(`⚠️ Attempt ${attempt}/${maxRetries} failed for ${moduleConfig.name}:`, error.message);
                
                if (attempt === maxRetries) {
                    return { success: false, error, attempts: attempt };
                }
            }
        }
    }
    
    function getBasePath() {
        // Próbuj znaleźć base path na podstawie aktualnie ładowanych skryptów
        const scripts = document.querySelectorAll('script[src*="mas-loader"]');
        if (scripts.length > 0) {
            const loaderSrc = scripts[0].src;
            return loaderSrc.substring(0, loaderSrc.lastIndexOf('/') + 1);
        }
        
        // Fallback - próbuj na podstawie innych skryptów wtyczki
        const masScripts = document.querySelectorAll('script[src*="modern-admin-styler"]');
        if (masScripts.length > 0) {
            const src = masScripts[0].src;
            const assetsIndex = src.indexOf('/assets/js/');
            if (assetsIndex !== -1) {
                return src.substring(0, assetsIndex + '/assets/js/'.length);
            }
        }
        
        // Ostatni fallback
        console.warn('⚠️ Nie można określić base path, używam relatywnej ścieżki');
        return './';
    }
    
    function loadScriptWithTimeout(src, timeout = 5000) {
        return new Promise((resolve, reject) => {
            // Check if script already loaded
            const existingScript = document.querySelector(`script[src="${src}"]`);
            if (existingScript) {
                resolve();
                return;
            }
            
            // Check if already loading
            if (loaderState.loadingPromises.has(src)) {
                return loaderState.loadingPromises.get(src);
            }
            
            const script = document.createElement('script');
            script.src = src;
            script.async = false; // Maintain load order
            
            // Timeout handling
            const timeoutId = setTimeout(() => {
                script.remove();
                loaderState.loadingPromises.delete(src);
                reject(new Error(`Timeout loading ${src} after ${timeout}ms`));
            }, timeout);
            
            script.onload = () => {
                clearTimeout(timeoutId);
                loaderState.loadingPromises.delete(src);
                resolve();
            };
            
            script.onerror = () => {
                clearTimeout(timeoutId);
                script.remove();
                loaderState.loadingPromises.delete(src);
                reject(new Error(`Failed to load ${src}`));
            };
            
            // Store loading promise
            const loadingPromise = new Promise((res, rej) => {
                script.onload = () => {
                    clearTimeout(timeoutId);
                    res();
                };
                script.onerror = () => {
                    clearTimeout(timeoutId);
                    script.remove();
                    rej(new Error(`Failed to load ${src}`));
                };
            });
            
            loaderState.loadingPromises.set(src, loadingPromise);
            document.head.appendChild(script);
        });
    }
    
    function verifyModuleLoaded(moduleName) {
        // Check if the module class exists in global scope
        return typeof window[moduleName] === 'function';
    }
    
    function delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
    
    function handleFailedModules(failedModules) {
        console.log('🔧 Attempting fallback mechanisms for failed modules...');
        
        // Attempt to load fallback versions or provide graceful degradation
        failedModules.forEach(({ name, error }) => {
            console.warn(`⚠️ Module ${name} failed: ${error.message}`);
            
            // Dispatch event for each failed module so other parts can handle gracefully
            document.dispatchEvent(new CustomEvent('mas-module-failed', {
                detail: { moduleName: name, error: error.message }
            }));
        });
        
        // If critical modules failed, provide emergency fallback
        const criticalModules = ['ModernAdminApp', 'NotificationManager'];
        const criticalFailures = failedModules.filter(f => criticalModules.includes(f.name));
        
        if (criticalFailures.length > 0) {
            console.error('❌ Critical modules failed, enabling emergency mode');
            document.body.classList.add('mas-emergency-mode');
            
            // Create minimal fallback functionality
            window.MASEmergencyMode = {
                enabled: true,
                failedModules: criticalFailures.map(f => f.name),
                message: 'Some features may be limited due to loading errors'
            };
        }
    }
    
    function handleCriticalFailure(error) {
        console.error('💥 Critical failure in module loading system:', error);
        
        // Enable emergency mode
        document.body.classList.add('mas-critical-failure');
        
        // Provide minimal functionality
        window.MASEmergencyMode = {
            enabled: true,
            criticalFailure: true,
            error: error.message,
            message: 'Plugin functionality severely limited due to loading errors'
        };
        
        // Dispatch critical failure event
        document.dispatchEvent(new CustomEvent('mas-critical-failure', {
            detail: { error: error.message }
        }));
    }
    
    // Task 15: Memory management for module loader
    function cleanupLoader() {
        // Clear loading promises
        loaderState.loadingPromises.clear();
        
        // Clear retry attempts
        loaderState.retryAttempts.clear();
        
        // Reset state
        loaderState.startTime = Date.now();
        
        console.log('🧹 Module loader cleanup completed');
    }
    
    // Task 15: Performance monitoring for module loading
    function getPerformanceMetrics() {
        const totalTime = Date.now() - loaderState.startTime;
        const totalModules = loaderState.loadedModules.size + loaderState.failedModules.size;
        
        return {
            totalLoadTime: totalTime,
            totalModules,
            loadedCount: loaderState.loadedModules.size,
            failedCount: loaderState.failedModules.size,
            successRate: totalModules > 0 ? (loaderState.loadedModules.size / totalModules) * 100 : 0,
            averageLoadTime: totalModules > 0 ? totalTime / totalModules : 0,
            memoryUsage: performance.memory ? {
                used: performance.memory.usedJSHeapSize,
                total: performance.memory.totalJSHeapSize
            } : null
        };
    }

    // Enhanced loader API for debugging and monitoring
    window.MASLoader = {
        moduleConfig,
        detectPageType,
        loadModules,
        getBasePath,
        
        // Enhanced debugging and monitoring
        getLoaderState: () => ({ ...loaderState }),
        getLoadedModules: () => Array.from(loaderState.loadedModules),
        getFailedModules: () => Array.from(loaderState.failedModules),
        getRetryAttempts: () => new Map(loaderState.retryAttempts),
        
        // Task 15: Performance and memory management
        cleanup: cleanupLoader,
        getPerformanceMetrics,
        
        // Manual retry functionality
        retryFailedModule: async (moduleName) => {
            const moduleConfig = [...moduleConfig.core, ...moduleConfig.settings]
                .find(m => m.name === moduleName);
            
            if (!moduleConfig) {
                throw new Error(`Module ${moduleName} not found in configuration`);
            }
            
            console.log(`🔄 Manual retry for ${moduleName}...`);
            const basePath = getBasePath();
            const result = await loadModuleWithRetry(moduleConfig, basePath);
            
            if (result.success) {
                loaderState.failedModules.delete(moduleName);
                loaderState.loadedModules.add(moduleName);
                console.log(`✅ Manual retry successful for ${moduleName}`);
            }
            
            return result;
        },
        
        // Health check functionality
        healthCheck: () => {
            const totalModules = moduleConfig.core.length + 
                (detectPageType().isSettings ? moduleConfig.settings.length : 0);
            const loadedCount = loaderState.loadedModules.size;
            const failedCount = loaderState.failedModules.size;
            
            return {
                status: failedCount === 0 ? 'healthy' : failedCount < totalModules ? 'degraded' : 'critical',
                totalModules,
                loadedCount,
                failedCount,
                loadTime: Date.now() - loaderState.startTime,
                modules: {
                    loaded: Array.from(loaderState.loadedModules),
                    failed: Array.from(loaderState.failedModules)
                }
            };
        },
        
        version: '2.1.0-enhanced'
    };
    
    // Task 15: Automatic cleanup on page unload
    window.addEventListener('beforeunload', () => {
        cleanupLoader();
    });
    
})(); 