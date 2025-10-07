# Phase 3 Developer Guide - Frontend Architecture

## Table of Contents

1. [Introduction](#introduction)
2. [Architecture Overview](#architecture-overview)
3. [Core Systems](#core-systems)
4. [Component System](#component-system)
5. [Creating Custom Components](#creating-custom-components)
6. [Event Bus Communication](#event-bus-communication)
7. [State Management](#state-management)
8. [API Client Usage](#api-client-usage)
9. [Error Handling](#error-handling)
10. [Performance Optimization](#performance-optimization)
11. [Accessibility Guidelines](#accessibility-guidelines)
12. [Testing](#testing)
13. [Best Practices](#best-practices)

## Introduction

Phase 3 introduces a modern, unified JavaScript architecture for Modern Admin Styler V2. This guide will help you understand the architecture, extend functionality, and follow best practices.

### Key Features

- **Single Entry Point**: One application instance eliminates handler conflicts
- **Component-Based**: Reusable, testable UI components with clear lifecycle
- **Event-Driven**: Decoupled communication via event bus
- **Reactive State**: Centralized state management with history
- **Progressive Enhancement**: Graceful degradation with REST API and AJAX fallback
- **Performance Optimized**: Code splitting, lazy loading, and DOM optimization
- **Accessible**: WCAG AA compliant with full keyboard navigation

### Prerequisites

- Basic JavaScript (ES6+) knowledge
- Understanding of WordPress admin interface
- Familiarity with REST APIs
- Basic understanding of component-based architecture

## Architecture Overview

### High-Level Structure

```
MASAdminApp (Entry Point)
├── EventBus (Communication)
├── StateManager (State)
├── APIClient (Data)
├── ErrorHandler (Errors)
└── Components
    ├── SettingsFormComponent
    ├── LivePreviewComponent
    ├── NotificationSystem
    ├── ThemeSelectorComponent
    ├── BackupManagerComponent
    └── TabManager
```

### Design Patterns

1. **Singleton Pattern**: Single app instance
2. **Observer Pattern**: Event bus for pub/sub
3. **Component Pattern**: Reusable UI components
4. **Factory Pattern**: Component creation with dependency injection
5. **Strategy Pattern**: Error recovery strategies

### File Organization

```
assets/js/
├── core/
│   ├── EventBus.js          # Event system
│   ├── StateManager.js      # State management
│   ├── APIClient.js         # API communication
│   └── ErrorHandler.js      # Error handling
├── components/
│   ├── Component.js         # Base component class
│   ├── SettingsFormComponent.js
│   ├── LivePreviewComponent.js
│   ├── NotificationSystem.js
│   ├── ThemeSelectorComponent.js
│   ├── BackupManagerComponent.js
│   └── TabManager.js
├── utils/
│   ├── Validator.js         # Validation utilities
│   ├── Debouncer.js         # Debouncing utilities
│   ├── VirtualList.js       # Virtual scrolling
│   ├── LazyLoader.js        # Code splitting
│   ├── DOMOptimizer.js      # DOM performance
│   ├── AccessibilityHelper.js
│   ├── KeyboardNavigationHelper.js
│   ├── ColorContrastHelper.js
│   └── FocusManager.js
└── mas-admin-app.js         # Main application
```

## Core Systems

### EventBus

The EventBus provides conflict-free event communication between components.

**Key Features:**
- Namespaced events
- Automatic cleanup
- Event history for debugging
- Error isolation

**Basic Usage:**

```javascript
// Subscribe to event
const unsubscribe = eventBus.on('settings:saved', (event) => {
    console.log('Settings saved:', event.data);
});

// Emit event
eventBus.emit('settings:saved', { settings: {...} });

// Unsubscribe
unsubscribe();
```

**Event Naming Convention:**

```
<domain>:<action>:<detail>

Examples:
- settings:saved
- settings:validation:failed
- preview:enabled
- preview:update:complete
- component:registered
- component:error
```

### StateManager

Centralized state management with history and undo/redo support.

**State Structure:**

```javascript
{
    settings: {},      // Current settings
    themes: [],        // Available themes
    backups: [],       // Backup list
    ui: {
        loading: false,
        saving: false,
        activeTab: 'general',
        hasUnsavedChanges: false
    },
    preview: {
        active: false,
        settings: null
    }
}
```

**Basic Usage:**

```javascript
// Get entire state
const state = stateManager.getState();

// Get specific path
const loading = stateManager.get('ui.loading');

// Update state
stateManager.setState({
    ui: { loading: true }
});

// Set specific path
stateManager.set('ui.loading', false);

// Subscribe to changes
const unsubscribe = stateManager.subscribe((state) => {
    console.log('State changed:', state);
});

// Undo/Redo
stateManager.undo();
stateManager.redo();
```

### APIClient

Progressive enhancement API client with retry logic and fallback.

**Features:**
- REST API with AJAX fallback
- Automatic retry with exponential backoff
- Request deduplication
- Response caching with ETags
- Timeout handling

**Basic Usage:**

```javascript
// Get settings
const settings = await apiClient.getSettings();

// Save settings
const result = await apiClient.saveSettings(settings);

// Generate preview
const preview = await apiClient.generatePreview(settings);

// List backups
const backups = await apiClient.listBackups();

// Apply theme
await apiClient.applyTheme('dark-blue');
```

**Advanced Usage:**

```javascript
// Custom request with options
const response = await apiClient.request('POST', '/custom-endpoint', {
    data: {...}
}, {
    timeout: 10000,
    maxRetries: 5,
    skipCache: true
});

// Cancel pending request
apiClient.cancelRequest('POST', '/settings');

// Clear cache
apiClient.invalidateCache('/settings');

// Get cache stats
const stats = apiClient.getCacheStats();
```

### ErrorHandler

Comprehensive error handling with recovery strategies.

**Error Types:**

```javascript
// Base error
throw new MASError('Something went wrong', 'error_code');

// API error
throw new MASAPIError('API failed', 'api_error', 500);

// Validation error
throw new MASValidationError('Validation failed', {
    field1: 'Error message',
    field2: 'Another error'
});
```

**Basic Usage:**

```javascript
const errorHandler = new ErrorHandler();

// Handle error
const result = errorHandler.handle(error, {
    context: 'Saving settings'
});

// Wrap operation with auto-retry
const data = await errorHandler.wrap(async () => {
    return await apiClient.saveSettings(settings);
}, {
    autoRetry: true,
    maxRetries: 3
});

// Manual retry
const data = await errorHandler.retry(async () => {
    return await apiClient.saveSettings(settings);
});
```

## Component System

### Base Component Class

All components extend the `Component` base class which provides:

- Lifecycle management (init, render, destroy)
- Event subscription with automatic cleanup
- Local state management
- DOM utilities
- Performance optimization helpers

### Component Lifecycle

```javascript
class MyComponent extends Component {
    // 1. Constructor - Initialize properties
    constructor(element, apiClient, stateManager, eventBus) {
        super(element, apiClient, stateManager, eventBus);
        this.myProperty = 'value';
    }
    
    // 2. Init - Setup component (called by constructor)
    init() {
        super.init(); // Calls render() and bindEvents()
        // Custom initialization
    }
    
    // 3. Render - Create/update UI
    render() {
        this.element.innerHTML = `
            <div class="my-component">
                <h2>My Component</h2>
            </div>
        `;
    }
    
    // 4. BindEvents - Attach event listeners
    bindEvents() {
        this.addEventListener(
            this.$('.my-button'),
            'click',
            this.getBoundMethod('handleClick')
        );
        
        this.subscribe('some:event', this.handleEvent.bind(this));
    }
    
    // 5. Destroy - Cleanup (automatic)
    destroy() {
        // Custom cleanup
        super.destroy(); // Automatic cleanup
    }
}
```

### Component Registration

Components are registered with the main application:

```javascript
// In MASAdminApp.initializeComponents()
const component = this.createComponent(
    MyComponent,
    document.querySelector('#my-element')
);

this.registerComponent('myComponent', component, {
    dependencies: ['notifications']
});

// Access component
const myComponent = app.getComponent('myComponent');
```

## Creating Custom Components

### Step 1: Create Component Class

```javascript
/**
 * Custom Component Example
 * 
 * @class CustomComponent
 * @extends Component
 */
class CustomComponent extends Component {
    /**
     * Initialize component
     */
    init() {
        // Initialize local state
        this.localState = {
            count: 0,
            loading: false
        };
        
        // Call parent init
        super.init();
        
        this.log('CustomComponent initialized');
    }
    
    /**
     * Render component UI
     */
    render() {
        const { count, loading } = this.localState;
        
        this.element.innerHTML = `
            <div class="custom-component">
                <h3>Counter: ${count}</h3>
                <button class="increment-btn" ${loading ? 'disabled' : ''}>
                    ${loading ? 'Loading...' : 'Increment'}
                </button>
                <button class="reset-btn">Reset</button>
            </div>
        `;
    }
    
    /**
     * Bind event listeners
     */
    bindEvents() {
        // DOM events
        this.addEventListener(
            this.$('.increment-btn'),
            'click',
            this.getBoundMethod('handleIncrement')
        );
        
        this.addEventListener(
            this.$('.reset-btn'),
            'click',
            this.getBoundMethod('handleReset')
        );
        
        // Event bus subscriptions
        this.subscribe('counter:reset-all', () => {
            this.setState({ count: 0 });
        });
    }
    
    /**
     * Handle increment button click
     */
    async handleIncrement() {
        this.setState({ loading: true });
        
        try {
            // Simulate API call
            await this.sleep(500);
            
            // Update count
            this.setState({
                count: this.localState.count + 1,
                loading: false
            });
            
            // Emit event
            this.emit('counter:incremented', {
                count: this.localState.count
            });
            
            // Show notification
            this.emit('notification:show', {
                type: 'success',
                message: `Count is now ${this.localState.count}`,
                duration: 2000
            });
            
        } catch (error) {
            this.handleError('Failed to increment', error);
            this.setState({ loading: false });
        }
    }
    
    /**
     * Handle reset button click
     */
    handleReset() {
        this.setState({ count: 0 });
        this.emit('counter:reset', { count: 0 });
    }
    
    /**
     * Sleep utility
     */
    sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
}

// Export
if (typeof window !== 'undefined') {
    window.CustomComponent = CustomComponent;
}
```

### Step 2: Register Component

```javascript
// In your initialization code or MASAdminApp
const element = document.querySelector('#custom-component');

if (element && typeof CustomComponent !== 'undefined') {
    const component = app.createComponent(
        CustomComponent,
        element
    );
    
    app.registerComponent('customComponent', component, {
        dependencies: ['notifications']
    });
}
```

### Step 3: Use Component

```html
<!-- In your HTML -->
<div id="custom-component"></div>
```

```javascript
// Access component
const customComponent = app.getComponent('customComponent');

// Interact with component
customComponent.setState({ count: 10 });

// Listen to component events
eventBus.on('counter:incremented', (event) => {
    console.log('Counter incremented:', event.data.count);
});
```

## Event Bus Communication

### Publishing Events

```javascript
// Simple event
eventBus.emit('user:logged-in', {
    userId: 123,
    username: 'john'
});

// Event with metadata
eventBus.emit('settings:saved', {
    settings: {...},
    timestamp: Date.now(),
    source: 'form'
});
```

### Subscribing to Events

```javascript
// Subscribe
const unsubscribe = eventBus.on('settings:saved', (event) => {
    console.log('Event type:', event.type);
    console.log('Event data:', event.data);
    console.log('Timestamp:', event.timestamp);
});

// Subscribe once
eventBus.once('app:ready', (event) => {
    console.log('App is ready!');
});

// Unsubscribe
unsubscribe();
```

### Event Patterns

**Request-Response Pattern:**

```javascript
// Requester
eventBus.emit('data:request', { id: 123 });

eventBus.once('data:response:123', (event) => {
    console.log('Received data:', event.data);
});

// Responder
eventBus.on('data:request', async (event) => {
    const data = await fetchData(event.data.id);
    eventBus.emit(`data:response:${event.data.id}`, { data });
});
```

**Command Pattern:**

```javascript
// Command
eventBus.emit('command:save-settings', {
    settings: {...}
});

// Handler
eventBus.on('command:save-settings', async (event) => {
    try {
        await apiClient.saveSettings(event.data.settings);
        eventBus.emit('command:save-settings:success');
    } catch (error) {
        eventBus.emit('command:save-settings:error', { error });
    }
});
```

### Standard Events

**Application Events:**
- `app:ready` - Application initialized
- `app:error` - Application error occurred
- `app:destroy` - Application being destroyed

**Component Events:**
- `component:registered` - Component registered
- `component:unregistered` - Component unregistered
- `component:error` - Component error occurred

**Settings Events:**
- `settings:saved` - Settings saved successfully
- `settings:optimistic-update` - Optimistic update applied
- `settings:update-confirmed` - Server confirmed update
- `settings:update-rolled-back` - Update rolled back

**Field Events:**
- `field:changed` - Form field changed
- `field:validated` - Field validation completed

**Preview Events:**
- `preview:enabled` - Preview mode enabled
- `preview:disabled` - Preview mode disabled
- `preview:updated` - Preview CSS updated
- `preview:error` - Preview generation failed

**Notification Events:**
- `notification:show` - Show notification
- `notification:hide` - Hide notification
- `notification:hideAll` - Hide all notifications

## State Management

### Reading State

```javascript
// Get entire state
const state = stateManager.getState();

// Get nested value
const activeTab = stateManager.get('ui.activeTab');
const hasChanges = stateManager.get('ui.hasUnsavedChanges');
```

### Updating State

```javascript
// Update multiple values
stateManager.setState({
    settings: newSettings,
    ui: {
        loading: false,
        hasUnsavedChanges: false
    }
});

// Update single value
stateManager.set('ui.loading', true);

// Update without adding to history
stateManager.setState({ ui: { loading: true } }, false);
```

### Subscribing to Changes

```javascript
// Subscribe to all changes
const unsubscribe = stateManager.subscribe((state) => {
    console.log('State changed:', state);
    updateUI(state);
});

// Listen via event bus
eventBus.on('state:changed', (event) => {
    console.log('Previous:', event.data.previousState);
    console.log('Updates:', event.data.updates);
    console.log('Current:', event.data.state);
});
```

### History Management

```javascript
// Undo last change
if (stateManager.canUndo()) {
    stateManager.undo();
}

// Redo change
if (stateManager.canRedo()) {
    stateManager.redo();
}

// Get history info
const historyInfo = stateManager.getHistoryInfo();
console.log('History length:', historyInfo.length);
console.log('Current index:', historyInfo.index);

// Clear history
stateManager.clearHistory();
```

## API Client Usage

### Basic Operations

```javascript
// Get settings
try {
    const response = await apiClient.getSettings();
    const settings = response.data;
} catch (error) {
    console.error('Failed to get settings:', error);
}

// Save settings
try {
    const result = await apiClient.saveSettings({
        menu_background: '#1e1e2e',
        menu_text_color: '#ffffff'
    });
    console.log('Settings saved:', result);
} catch (error) {
    console.error('Failed to save settings:', error);
}
```

### Theme Operations

```javascript
// Get all themes
const themes = await apiClient.getThemes();

// Apply theme
await apiClient.applyTheme('dark-blue');
```

### Backup Operations

```javascript
// List backups
const backups = await apiClient.listBackups();

// Create backup
const backup = await apiClient.createBackup({
    note: 'Before major changes'
});

// Restore backup
await apiClient.restoreBackup(backupId);

// Delete backup
await apiClient.deleteBackup(backupId);
```

### Import/Export

```javascript
// Export settings
const exportData = await apiClient.exportSettings();

// Import settings
await apiClient.importSettings(importData, true); // true = create backup
```

### Preview

```javascript
// Generate preview
const preview = await apiClient.generatePreview({
    menu_background: '#2d2d44',
    menu_text_color: '#ffffff'
});

const previewCSS = preview.data.css;
```

### Cache Management

```javascript
// Clear all cache
apiClient.invalidateCache();

// Clear specific cache
apiClient.invalidateCache('/settings');

// Get cache stats
const stats = apiClient.getCacheStats();
console.log('Cache size:', stats.size);
console.log('Cache enabled:', stats.enabled);

// Enable/disable cache
apiClient.enableCache();
apiClient.disableCache();
```

## Error Handling

### Try-Catch Pattern

```javascript
try {
    const result = await apiClient.saveSettings(settings);
    // Success handling
} catch (error) {
    // Error handling
    if (error instanceof MASAPIError) {
        if (error.isNetworkError()) {
            // Handle network error
        } else if (error.isValidationError()) {
            // Handle validation error
        }
    }
}
```

### Error Handler Wrapper

```javascript
const errorHandler = new ErrorHandler();

// Wrap with automatic retry
const result = await errorHandler.wrap(async () => {
    return await apiClient.saveSettings(settings);
}, {
    autoRetry: true,
    maxRetries: 3,
    context: 'Saving settings'
});
```

### Component Error Handling

```javascript
class MyComponent extends Component {
    async saveData() {
        try {
            await this.api.saveSettings(this.collectData());
            
            this.emit('notification:show', {
                type: 'success',
                message: 'Saved successfully!'
            });
            
        } catch (error) {
            this.handleError('Failed to save', error);
            
            this.emit('notification:show', {
                type: 'error',
                message: 'Failed to save. Please try again.',
                actions: [
                    {
                        label: 'Retry',
                        callback: () => this.saveData()
                    }
                ]
            });
        }
    }
}
```

## Performance Optimization

### Debouncing

```javascript
// In component
this.debouncedSearch = this.debounce(this.performSearch.bind(this), 300);

// Use debounced function
this.addEventListener(searchInput, 'input', (e) => {
    this.debouncedSearch(e.target.value);
});
```

### Throttling

```javascript
// Throttle scroll handler
this.throttledScroll = this.throttle(this.handleScroll.bind(this), 100);

window.addEventListener('scroll', this.throttledScroll);
```

### DOM Optimization

```javascript
// Batch DOM updates
this.batchUpdate(() => {
    element1.textContent = 'New text';
    element2.classList.add('active');
    element3.style.color = 'red';
});

// Schedule update with requestAnimationFrame
this.scheduleUpdate('my-update', () => {
    this.updateUI();
});

// Event delegation
this.delegateEvent('click', '.item', (e, target) => {
    console.log('Item clicked:', target);
});
```

### Virtual Scrolling

```javascript
// For large lists
const virtualList = new VirtualList(container, {
    itemHeight: 50,
    items: largeArray,
    renderItem: (item, index) => {
        return `<div class="item">${item.name}</div>`;
    }
});
```

### Lazy Loading

```javascript
// Lazy load component
await app.lazyLoadComponent('backupManager', element);

// Preload components
await app.preloadComponents(['backupManager', 'themeSelector']);
```

## Accessibility Guidelines

### ARIA Attributes

```javascript
// Mark field as invalid
field.setAttribute('aria-invalid', 'true');
field.setAttribute('aria-describedby', 'error-message-id');

// Loading state
button.setAttribute('aria-busy', 'true');

// Live regions
container.setAttribute('aria-live', 'polite');
container.setAttribute('aria-atomic', 'true');
```

### Keyboard Navigation

```javascript
// Add keyboard shortcut
const cleanup = KeyboardNavigationHelper.addShortcut(
    's',
    (e) => this.save(),
    { ctrl: true }
);

// Add escape handler
const cleanup = KeyboardNavigationHelper.addEscapeHandler(
    element,
    () => this.close()
);

// Trap focus in modal
const cleanup = FocusManager.trapFocus(modalElement);
```

### Screen Reader Announcements

```javascript
// Announce message
AccessibilityHelper.announce('Settings saved successfully', 'polite');

// Announce error
AccessibilityHelper.announce('Error: Invalid input', 'assertive');
```

### Color Contrast

```javascript
// Check contrast ratio
const ratio = ColorContrastHelper.getContrastRatio('#ffffff', '#000000');

if (ratio >= 4.5) {
    console.log('Meets WCAG AA standard');
}

// Get accessible text color
const textColor = ColorContrastHelper.getAccessibleTextColor('#1e1e2e');
```

## Testing

### Unit Testing Components

```javascript
// tests/js/components/MyComponent.test.js
describe('MyComponent', () => {
    let component;
    let element;
    let apiClient;
    let stateManager;
    let eventBus;
    
    beforeEach(() => {
        element = document.createElement('div');
        apiClient = new APIClient();
        stateManager = new StateManager(new EventBus());
        eventBus = new EventBus();
        
        component = new MyComponent(
            element,
            apiClient,
            stateManager,
            eventBus
        );
    });
    
    afterEach(() => {
        component.destroy();
    });
    
    test('initializes correctly', () => {
        expect(component.isInitialized).toBe(true);
        expect(component.element).toBe(element);
    });
    
    test('renders UI', () => {
        component.render();
        expect(element.innerHTML).toContain('my-component');
    });
    
    test('handles button click', async () => {
        const button = element.querySelector('.my-button');
        button.click();
        
        await waitFor(() => {
            expect(component.localState.clicked).toBe(true);
        });
    });
});
```

### Integration Testing

```javascript
// tests/js/integration/settings-workflow.test.js
describe('Settings Workflow', () => {
    let app;
    
    beforeEach(async () => {
        app = new MASAdminApp({ debug: false });
        await app.init();
    });
    
    afterEach(() => {
        app.destroy();
    });
    
    test('complete settings save workflow', async () => {
        const form = app.getComponent('settingsForm');
        const notifications = app.getComponent('notifications');
        
        // Update form
        form.updateFormFields({
            menu_background: '#1e1e2e'
        });
        
        // Submit form
        await form.handleSubmit(new Event('submit'));
        
        // Verify notification
        expect(notifications.notifications.size).toBeGreaterThan(0);
        
        // Verify state updated
        const state = app.stateManager.getState();
        expect(state.settings.menu_background).toBe('#1e1e2e');
    });
});
```

## Best Practices

### Component Design

1. **Single Responsibility**: Each component should have one clear purpose
2. **Dependency Injection**: Pass dependencies through constructor
3. **Event-Driven**: Use event bus for component communication
4. **Cleanup**: Always cleanup in destroy() method
5. **Error Handling**: Handle errors gracefully with user feedback

### State Management

1. **Immutability**: Never mutate state directly
2. **Minimal State**: Store only what's necessary
3. **Derived Data**: Calculate derived values in getters
4. **History**: Use history for undo/redo functionality
5. **Subscriptions**: Clean up subscriptions in destroy()

### Performance

1. **Debounce**: Debounce expensive operations
2. **Throttle**: Throttle high-frequency events
3. **Lazy Load**: Lazy load non-critical components
4. **Virtual Scrolling**: Use for large lists
5. **Request Deduplication**: Prevent duplicate API calls

### Accessibility

1. **ARIA**: Use proper ARIA attributes
2. **Keyboard**: Support full keyboard navigation
3. **Focus**: Manage focus properly
4. **Announcements**: Announce important changes
5. **Contrast**: Ensure sufficient color contrast

### Error Handling

1. **Try-Catch**: Wrap async operations
2. **User Feedback**: Show clear error messages
3. **Recovery**: Provide recovery actions
4. **Logging**: Log errors for debugging
5. **Graceful Degradation**: Fallback to simpler functionality

### Code Organization

1. **Modularity**: Keep files focused and small
2. **Documentation**: Document public APIs with JSDoc
3. **Naming**: Use clear, descriptive names
4. **Consistency**: Follow established patterns
5. **Testing**: Write tests for critical functionality

## Conclusion

This guide covers the essential aspects of the Phase 3 frontend architecture. For more detailed information, refer to:

- [Phase 3 Core Architecture Guide](PHASE3-CORE-ARCHITECTURE-GUIDE.md)
- [Phase 3 Component System Guide](PHASE3-COMPONENT-SYSTEM-GUIDE.md)
- [API Documentation](API-DOCUMENTATION.md)
- [Migration Guide](PHASE3-MIGRATION-GUIDE.md)

For questions or issues, please refer to the project repository or contact the development team.
