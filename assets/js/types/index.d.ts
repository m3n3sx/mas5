/**
 * TypeScript definitions for Modern Admin Styler V2 Phase 3
 * 
 * These type definitions provide IntelliSense and type checking
 * for the JavaScript codebase.
 */

// ============================================================================
// Core Types
// ============================================================================

/**
 * Event object structure
 */
export interface MASEvent<T = any> {
    type: string;
    data: T;
    timestamp: number;
}

/**
 * Event callback function
 */
export type EventCallback<T = any> = (event: MASEvent<T>) => void;

/**
 * Unsubscribe function
 */
export type UnsubscribeFunction = () => void;

/**
 * Settings object structure
 */
export interface Settings {
    // Menu settings
    menu_background?: string;
    menu_text_color?: string;
    menu_hover_background?: string;
    menu_hover_text_color?: string;
    menu_active_background?: string;
    menu_active_text_color?: string;
    menu_width?: string;
    menu_item_height?: string;
    menu_border_radius?: string;
    menu_detached?: boolean | string;
    
    // Admin bar settings
    admin_bar_background?: string;
    admin_bar_text_color?: string;
    admin_bar_floating?: boolean | string;
    
    // Effects
    glassmorphism_enabled?: boolean | string;
    glassmorphism_blur?: string;
    shadow_effects_enabled?: boolean | string;
    shadow_intensity?: string;
    animations_enabled?: boolean | string;
    animation_speed?: string;
    
    // Theme
    current_theme?: string;
    
    // Advanced
    performance_mode?: boolean | string;
    debug_mode?: boolean | string;
    
    [key: string]: any;
}

/**
 * Application state structure
 */
export interface AppState {
    settings: Settings;
    themes: Theme[];
    backups: Backup[];
    ui: UIState;
    preview: PreviewState;
}

/**
 * UI state
 */
export interface UIState {
    loading: boolean;
    saving: boolean;
    activeTab: string;
    hasUnsavedChanges: boolean;
    optimisticUpdate?: boolean;
}

/**
 * Preview state
 */
export interface PreviewState {
    active: boolean;
    settings: Settings | null;
}

/**
 * Theme structure
 */
export interface Theme {
    id: string;
    name: string;
    type: 'predefined' | 'custom';
    readonly: boolean;
    settings: Settings;
    metadata?: {
        author?: string;
        version?: string;
        created?: string;
    };
}

/**
 * Backup structure
 */
export interface Backup {
    id: number;
    timestamp: number;
    date: string;
    type: 'manual' | 'automatic';
    settings: Settings;
    metadata?: {
        plugin_version?: string;
        wordpress_version?: string;
        user_id?: number;
        note?: string;
    };
}

/**
 * API response structure
 */
export interface APIResponse<T = any> {
    success: boolean;
    message?: string;
    data?: T;
}

/**
 * Validation result
 */
export interface ValidationResult {
    valid: boolean;
    errors?: ValidationError[];
}

/**
 * Validation error
 */
export interface ValidationError {
    field: string;
    message: string;
}

/**
 * Notification options
 */
export interface NotificationOptions {
    type: 'success' | 'error' | 'warning' | 'info';
    message: string;
    title?: string;
    duration?: number;
    actions?: NotificationAction[];
}

/**
 * Notification action
 */
export interface NotificationAction {
    id: string;
    label: string;
    callback?: () => void;
    dismissAfter?: boolean;
}

// ============================================================================
// EventBus
// ============================================================================

export class EventBus {
    constructor();
    
    /**
     * Subscribe to an event
     */
    on<T = any>(event: string, callback: EventCallback<T>, context?: any): UnsubscribeFunction;
    
    /**
     * Subscribe to an event once
     */
    once<T = any>(event: string, callback: EventCallback<T>, context?: any): UnsubscribeFunction;
    
    /**
     * Unsubscribe from an event
     */
    off(event: string, callback: EventCallback): void;
    
    /**
     * Emit an event
     */
    emit<T = any>(event: string, data?: T): void;
    
    /**
     * Clear all listeners for an event
     */
    clear(event?: string): void;
    
    /**
     * Get listener count for an event
     */
    getListenerCount(event: string): number;
    
    /**
     * Get all registered event names
     */
    getEventNames(): string[];
    
    /**
     * Enable debug mode
     */
    setDebug(enabled: boolean): void;
    
    /**
     * Get event history
     */
    getHistory(): MASEvent[];
    
    /**
     * Clear event history
     */
    clearHistory(): void;
    
    /**
     * Destroy event bus
     */
    destroy(): void;
}

// ============================================================================
// StateManager
// ============================================================================

export class StateManager {
    constructor(eventBus: EventBus);
    
    /**
     * Get current state
     */
    getState(): AppState;
    
    /**
     * Get specific state path
     */
    get(path: string): any;
    
    /**
     * Update state
     */
    setState(updates: Partial<AppState>, addToHistory?: boolean): void;
    
    /**
     * Set specific state path
     */
    set(path: string, value: any, addToHistory?: boolean): void;
    
    /**
     * Subscribe to state changes
     */
    subscribe(callback: (state: AppState) => void): UnsubscribeFunction;
    
    /**
     * Undo last state change
     */
    undo(): boolean;
    
    /**
     * Redo state change
     */
    redo(): boolean;
    
    /**
     * Check if undo is available
     */
    canUndo(): boolean;
    
    /**
     * Check if redo is available
     */
    canRedo(): boolean;
    
    /**
     * Clear history
     */
    clearHistory(): void;
    
    /**
     * Reset state to initial values
     */
    reset(addToHistory?: boolean): void;
    
    /**
     * Enable debug mode
     */
    setDebug(enabled: boolean): void;
    
    /**
     * Get history information
     */
    getHistoryInfo(): {
        length: number;
        index: number;
        canUndo: boolean;
        canRedo: boolean;
    };
    
    /**
     * Destroy state manager
     */
    destroy(): void;
}

// ============================================================================
// APIClient
// ============================================================================

export interface APIClientConfig {
    baseUrl?: string;
    namespace?: string;
    nonce?: string;
    timeout?: number;
    maxRetries?: number;
    retryDelay?: number;
    useAjaxFallback?: boolean;
    debug?: boolean;
    cacheEnabled?: boolean;
    cacheTTL?: number;
    cacheMaxSize?: number;
}

export interface RequestOptions {
    method?: string;
    headers?: Record<string, string>;
    timeout?: number;
    maxRetries?: number;
    skipCache?: boolean;
}

export class APIClient {
    constructor(config?: APIClientConfig);
    
    /**
     * Make API request
     */
    request(method: string, endpoint: string, data?: any, options?: RequestOptions): Promise<APIResponse>;
    
    /**
     * Get settings
     */
    getSettings(): Promise<APIResponse<Settings>>;
    
    /**
     * Save settings
     */
    saveSettings(settings: Settings): Promise<APIResponse<{ settings: Settings }>>;
    
    /**
     * Update settings (partial)
     */
    updateSettings(settings: Partial<Settings>): Promise<APIResponse<{ settings: Settings }>>;
    
    /**
     * Reset settings
     */
    resetSettings(): Promise<APIResponse>;
    
    /**
     * Get themes
     */
    getThemes(): Promise<APIResponse<Theme[]>>;
    
    /**
     * Apply theme
     */
    applyTheme(themeId: string): Promise<APIResponse<{ settings: Settings }>>;
    
    /**
     * Generate preview
     */
    generatePreview(settings: Settings): Promise<APIResponse<{ css: string }>>;
    
    /**
     * List backups
     */
    listBackups(): Promise<APIResponse<Backup[]>>;
    
    /**
     * Create backup
     */
    createBackup(options?: { note?: string }): Promise<APIResponse<Backup>>;
    
    /**
     * Restore backup
     */
    restoreBackup(backupId: number): Promise<APIResponse<{ settings: Settings }>>;
    
    /**
     * Delete backup
     */
    deleteBackup(backupId: number): Promise<APIResponse>;
    
    /**
     * Export settings
     */
    exportSettings(): Promise<APIResponse<any>>;
    
    /**
     * Import settings
     */
    importSettings(data: any, createBackup?: boolean): Promise<APIResponse<{ settings: Settings }>>;
    
    /**
     * Cancel pending request
     */
    cancelRequest(method: string, endpoint: string): void;
    
    /**
     * Clear all pending requests
     */
    clearPendingRequests(): void;
    
    /**
     * Get pending request count
     */
    getPendingRequestCount(): number;
    
    /**
     * Invalidate cache
     */
    invalidateCache(pattern?: string): void;
    
    /**
     * Get cache statistics
     */
    getCacheStats(): {
        enabled: boolean;
        size: number;
        maxSize: number;
        ttl: number;
        etagCount: number;
    };
    
    /**
     * Enable cache
     */
    enableCache(): void;
    
    /**
     * Disable cache
     */
    disableCache(): void;
}

// ============================================================================
// ErrorHandler
// ============================================================================

export class MASError extends Error {
    code: string;
    context: any;
    timestamp: number;
    
    constructor(message: string, code?: string, context?: any);
    
    toJSON(): {
        name: string;
        message: string;
        code: string;
        context: any;
        timestamp: number;
        stack?: string;
    };
}

export class MASAPIError extends MASError {
    status: number;
    response: any;
    
    constructor(message: string, code?: string, status?: number, response?: any);
    
    isNetworkError(): boolean;
    isAuthError(): boolean;
    isValidationError(): boolean;
    isServerError(): boolean;
    isRetryable(): boolean;
}

export class MASValidationError extends MASError {
    errors: Record<string, string>;
    
    constructor(message: string, errors?: Record<string, string>);
    
    getFieldErrors(): Record<string, string>;
    hasFieldError(field: string): boolean;
    getFieldError(field: string): string | null;
}

export interface ErrorHandlerConfig {
    maxRetries?: number;
    retryDelay?: number;
    showNotifications?: boolean;
    logErrors?: boolean;
    debug?: boolean;
}

export interface ErrorHandlingResult {
    error: MASError;
    strategy: string;
    recovered: boolean;
    userMessage: string;
    actions: Array<{
        label: string;
        action: string;
        primary: boolean;
    }>;
}

export class ErrorHandler {
    constructor(config?: ErrorHandlerConfig);
    
    /**
     * Handle error with recovery strategies
     */
    handle(error: Error, options?: { context?: string }): ErrorHandlingResult;
    
    /**
     * Retry operation with exponential backoff
     */
    retry<T>(operation: () => Promise<T>, options?: {
        maxRetries?: number;
        retryDelay?: number;
        id?: string;
    }): Promise<T>;
    
    /**
     * Wrap operation with error handling
     */
    wrap<T>(operation: () => Promise<T>, options?: {
        autoRetry?: boolean;
        context?: string;
    }): Promise<T>;
    
    /**
     * Get error history
     */
    getHistory(): Array<{ error: MASError; timestamp: number }>;
    
    /**
     * Clear error history
     */
    clearHistory(): void;
    
    /**
     * Create validation error
     */
    static createValidationError(errors: Record<string, string>, message?: string): MASValidationError;
    
    /**
     * Create API error
     */
    static createAPIError(message: string, code: string, status: number, response?: any): MASAPIError;
    
    /**
     * Check if error is retryable
     */
    static isRetryable(error: Error): boolean;
}

// ============================================================================
// Component
// ============================================================================

export class Component {
    element: HTMLElement;
    api: APIClient;
    state: StateManager;
    events: EventBus;
    localState: any;
    isInitialized: boolean;
    isDestroyed: boolean;
    name: string;
    
    constructor(
        element: HTMLElement,
        apiClient: APIClient,
        stateManager: StateManager,
        eventBus: EventBus
    );
    
    /**
     * Initialize component
     */
    init(): void;
    
    /**
     * Render component UI
     */
    render(): void;
    
    /**
     * Bind event listeners
     */
    bindEvents(): void;
    
    /**
     * Update local component state
     */
    setState(updates: any): void;
    
    /**
     * Get local component state
     */
    getState(): any;
    
    /**
     * Get bound method
     */
    getBoundMethod(methodName: string): Function;
    
    /**
     * Subscribe to event bus event
     */
    subscribe<T = any>(event: string, callback: EventCallback<T>): UnsubscribeFunction;
    
    /**
     * Subscribe to event once
     */
    subscribeOnce<T = any>(event: string, callback: EventCallback<T>): UnsubscribeFunction;
    
    /**
     * Emit event through event bus
     */
    emit<T = any>(event: string, data?: T): void;
    
    /**
     * Add DOM event listener with automatic cleanup
     */
    addEventListener(
        element: HTMLElement,
        event: string,
        handler: EventListener,
        options?: AddEventListenerOptions
    ): void;
    
    /**
     * Query selector within component element
     */
    $(selector: string): HTMLElement | null;
    
    /**
     * Query selector all within component element
     */
    $$(selector: string): NodeListOf<HTMLElement>;
    
    /**
     * Show component element
     */
    show(): void;
    
    /**
     * Hide component element
     */
    hide(): void;
    
    /**
     * Enable component
     */
    enable(): void;
    
    /**
     * Disable component
     */
    disable(): void;
    
    /**
     * Schedule DOM update
     */
    scheduleUpdate(key: string, callback: () => void): void;
    
    /**
     * Batch multiple DOM updates
     */
    batchUpdate(updateFn: () => void): void;
    
    /**
     * Delegate event handler
     */
    delegateEvent(eventType: string, selector: string, handler: (e: Event, target: HTMLElement) => void): () => void;
    
    /**
     * Debounce a function
     */
    debounce<T extends (...args: any[]) => any>(func: T, wait: number): T;
    
    /**
     * Throttle a function
     */
    throttle<T extends (...args: any[]) => any>(func: T, limit: number): T;
    
    /**
     * Handle error
     */
    handleError(context: string, error: Error): void;
    
    /**
     * Log message
     */
    log(...args: any[]): void;
    
    /**
     * Destroy component
     */
    destroy(): void;
    
    /**
     * Refresh component
     */
    refresh(): void;
}

// ============================================================================
// MASAdminApp
// ============================================================================

export interface MASAdminAppConfig {
    debug?: boolean;
    api?: APIClientConfig;
    features?: {
        livePreview?: boolean;
        autoSave?: boolean;
        offlineSupport?: boolean;
        lazyLoadComponents?: boolean;
    };
}

export interface ComponentMetadata {
    registeredAt: number;
    dependencies: string[];
    type: string;
}

export class MASAdminApp {
    config: MASAdminAppConfig;
    eventBus: EventBus;
    stateManager: StateManager;
    apiClient: APIClient;
    components: Map<string, Component>;
    initialized: boolean;
    destroyed: boolean;
    debug: boolean;
    
    constructor(config?: MASAdminAppConfig);
    
    /**
     * Initialize application
     */
    init(): Promise<void>;
    
    /**
     * Register a component
     */
    registerComponent(
        name: string,
        component: Component,
        options?: {
            replace?: boolean;
            dependencies?: string[];
        }
    ): Component;
    
    /**
     * Unregister a component
     */
    unregisterComponent(name: string): boolean;
    
    /**
     * Get a component by name
     */
    getComponent(name: string): Component | undefined;
    
    /**
     * Check if component exists
     */
    hasComponent(name: string): boolean;
    
    /**
     * Get all registered components
     */
    getComponents(): string[];
    
    /**
     * Get component metadata
     */
    getComponentMetadata(name: string): ComponentMetadata | undefined;
    
    /**
     * Create component with dependency injection
     */
    createComponent<T extends Component>(
        ComponentClass: new (...args: any[]) => T,
        element: HTMLElement,
        additionalDeps?: any
    ): T;
    
    /**
     * Register and create component in one step
     */
    registerAndCreateComponent<T extends Component>(
        name: string,
        ComponentClass: new (...args: any[]) => T,
        element: HTMLElement,
        options?: {
            replace?: boolean;
            dependencies?: string[];
            additionalDeps?: any;
        }
    ): T | null;
    
    /**
     * Lazy load a component
     */
    lazyLoadComponent(componentName: string, element: HTMLElement): Promise<Component | null>;
    
    /**
     * Preload components
     */
    preloadComponents(componentNames: string[]): Promise<void>;
    
    /**
     * Destroy application
     */
    destroy(): void;
    
    /**
     * Restart application
     */
    restart(): Promise<void>;
    
    /**
     * Enable debug mode
     */
    setDebug(enabled: boolean): void;
    
    /**
     * Get application info
     */
    getInfo(): {
        initialized: boolean;
        destroyed: boolean;
        componentCount: number;
        components: string[];
        componentDetails: Record<string, any>;
        config: MASAdminAppConfig;
        state: AppState;
        pendingRequests: number;
    };
}

// ============================================================================
// NotificationSystem
// ============================================================================

export class NotificationSystem {
    constructor(eventBus: EventBus);
    
    /**
     * Show notification
     */
    show(options: NotificationOptions): number;
    
    /**
     * Hide notification
     */
    hide(id: number): void;
    
    /**
     * Hide all notifications
     */
    hideAll(): void;
    
    /**
     * Show success notification
     */
    success(message: string, duration?: number): number;
    
    /**
     * Show error notification
     */
    error(message: string, options?: { duration?: number; actions?: NotificationAction[] }): number;
    
    /**
     * Show warning notification
     */
    warning(message: string, duration?: number): number;
    
    /**
     * Show info notification
     */
    info(message: string, duration?: number): number;
    
    /**
     * Show error with retry action
     */
    errorWithRetry(message: string, retryCallback: () => void): number;
    
    /**
     * Show error with report action
     */
    errorWithReport(message: string, errorDetails?: any): number;
    
    /**
     * Show network error with retry
     */
    networkError(retryCallback: () => void): number;
    
    /**
     * Show unexpected error with report option
     */
    unexpectedError(error: Error): number;
    
    /**
     * Destroy notification system
     */
    destroy(): void;
}

// ============================================================================
// Global Declarations
// ============================================================================

declare global {
    interface Window {
        EventBus: typeof EventBus;
        StateManager: typeof StateManager;
        APIClient: typeof APIClient;
        ErrorHandler: typeof ErrorHandler;
        MASError: typeof MASError;
        MASAPIError: typeof MASAPIError;
        MASValidationError: typeof MASValidationError;
        Component: typeof Component;
        MASAdminApp: typeof MASAdminApp;
        NotificationSystem: typeof NotificationSystem;
        masApp?: MASAdminApp;
        masAdminConfig?: MASAdminAppConfig;
        wpApiSettings?: {
            root: string;
            nonce: string;
        };
        ajaxurl?: string;
    }
}

export {};
