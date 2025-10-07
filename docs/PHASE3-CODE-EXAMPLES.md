# Phase 3 Code Examples

This document provides practical code examples for common tasks in the Phase 3 frontend architecture.

## Table of Contents

1. [Creating a Custom Component](#creating-a-custom-component)
2. [Using the Event Bus](#using-the-event-bus)
3. [State Management Patterns](#state-management-patterns)
4. [API Client Usage](#api-client-usage)
5. [Error Handling](#error-handling)
6. [Form Validation](#form-validation)
7. [Live Preview Implementation](#live-preview-implementation)
8. [Notification System](#notification-system)
9. [Accessibility Features](#accessibility-features)
10. [Performance Optimization](#performance-optimization)

## Creating a Custom Component

### Example 1: Simple Counter Component

```javascript
/**
 * Simple Counter Component
 * Demonstrates basic component structure and state management
 */
class CounterComponent extends Component {
    init() {
        // Initialize local state
        this.localState = {
            count: 0,
            step: 1
        };
        
        // Call parent init (renders and binds events)
        super.init();
    }
    
    render() {
        const { count, step } = this.localState;
        
        this.element.innerHTML = `
            <div class="counter-component">
                <h3>Counter: <span class="count">${count}</span></h3>
                <div class="controls">
                    <button class="decrement">-${step}</button>
                    <input type="number" class="step-input" value="${step}" min="1">
                    <button class="increment">+${step}</button>
                    <button class="reset">Reset</button>
                </div>
            </div>
        `;
    }
    
    bindEvents() {
        // Increment button
        this.addEventListener(
            this.$('.increment'),
            'click',
            this.getBoundMethod('handleIncrement')
        );
        
        // Decrement button
        this.addEventListener(
            this.$('.decrement'),
            'click',
            this.getBoundMethod('handleDecrement')
        );
        
        // Reset button
        this.addEventListener(
            this.$('.reset'),
            'click',
            this.getBoundMethod('handleReset')
        );
        
        // Step input
        this.addEventListener(
            this.$('.step-input'),
            'change',
            this.getBoundMethod('handleStepChange')
        );
    }
    
    handleIncrement() {
        this.setState({
            count: this.localState.count + this.localState.step
        });
        
        this.emit('counter:changed', {
            count: this.localState.count
        });
    }
    
    handleDecrement() {
        this.setState({
            count: this.localState.count - this.localState.step
        });
        
        this.emit('counter:changed', {
            count: this.localState.count
        });
    }
    
    handleReset() {
        this.setState({ count: 0 });
        this.emit('counter:reset');
    }
    
    handleStepChange(e) {
        const step = parseInt(e.target.value) || 1;
        this.setState({ step });
    }
}

// Register component
const counterElement = document.querySelector('#counter');
if (counterElement) {
    const counter = app.createComponent(CounterComponent, counterElement);
    app.registerComponent('counter', counter);
}
```

### Example 2: Data List Component with API

```javascript
/**
 * Data List Component
 * Demonstrates API integration and list rendering
 */
class DataListComponent extends Component {
    init() {
        this.localState = {
            items: [],
            loading: false,
            error: null,
            selectedId: null
        };
        
        super.init();
        
        // Load data on init
        this.loadData();
    }
    
    render() {
        const { items, loading, error, selectedId } = this.localState;
        
        let content = '';
        
        if (loading) {
            content = '<div class="loading">Loading...</div>';
        } else if (error) {
            content = `<div class="error">${error}</div>`;
        } else if (items.length === 0) {
            content = '<div class="empty">No items found</div>';
        } else {
            content = `
                <ul class="item-list">
                    ${items.map(item => `
                        <li class="item ${item.id === selectedId ? 'selected' : ''}" 
                            data-id="${item.id}">
                            <h4>${item.name}</h4>
                            <p>${item.description}</p>
                        </li>
                    `).join('')}
                </ul>
            `;
        }
        
        this.element.innerHTML = `
            <div class="data-list-component">
                <div class="header">
                    <h3>Items</h3>
                    <button class="refresh-btn">Refresh</button>
                </div>
                ${content}
            </div>
        `;
    }
    
    bindEvents() {
        // Refresh button
        const refreshBtn = this.$('.refresh-btn');
        if (refreshBtn) {
            this.addEventListener(refreshBtn, 'click', () => this.loadData());
        }
        
        // Item click (event delegation)
        this.delegateEvent('click', '.item', (e, target) => {
            const id = parseInt(target.dataset.id);
            this.handleItemClick(id);
        });
    }
    
    async loadData() {
        this.setState({ loading: true, error: null });
        
        try {
            const response = await this.api.request('GET', '/items');
            const items = response.data || [];
            
            this.setState({
                items,
                loading: false
            });
            
        } catch (error) {
            this.handleError('Failed to load items', error);
            this.setState({
                loading: false,
                error: 'Failed to load items. Please try again.'
            });
        }
    }
    
    handleItemClick(id) {
        this.setState({ selectedId: id });
        this.emit('item:selected', { id });
    }
}
```

## Using the Event Bus

### Example 1: Basic Pub/Sub

```javascript
// Subscribe to event
const unsubscribe = eventBus.on('user:logged-in', (event) => {
    console.log('User logged in:', event.data.username);
    console.log('Timestamp:', event.timestamp);
});

// Emit event
eventBus.emit('user:logged-in', {
    userId: 123,
    username: 'john_doe'
});

// Unsubscribe when done
unsubscribe();
```

### Example 2: Component Communication

```javascript
// Component A - Publisher
class ComponentA extends Component {
    saveData() {
        const data = this.collectData();
        
        // Emit event
        this.emit('data:saved', {
            data,
            source: 'ComponentA'
        });
    }
}

// Component B - Subscriber
class ComponentB extends Component {
    bindEvents() {
        // Subscribe to event from Component A
        this.subscribe('data:saved', (event) => {
            console.log('Data saved by:', event.data.source);
            this.updateDisplay(event.data.data);
        });
    }
    
    updateDisplay(data) {
        // Update UI with new data
    }
}
```

### Example 3: Request-Response Pattern

```javascript
// Requester
function requestData(id) {
    // Send request
    eventBus.emit('data:request', { id });
    
    // Wait for response
    return new Promise((resolve) => {
        eventBus.once(`data:response:${id}`, (event) => {
            resolve(event.data);
        });
    });
}

// Responder
eventBus.on('data:request', async (event) => {
    const id = event.data.id;
    const data = await fetchDataFromAPI(id);
    
    // Send response
    eventBus.emit(`data:response:${id}`, { data });
});

// Usage
const data = await requestData(123);
console.log('Received data:', data);
```

## State Management Patterns

### Example 1: Reading and Updating State

```javascript
// Get entire state
const state = stateManager.getState();
console.log('Current settings:', state.settings);

// Get specific path
const loading = stateManager.get('ui.loading');
const activeTab = stateManager.get('ui.activeTab');

// Update state
stateManager.setState({
    ui: {
        loading: true,
        activeTab: 'advanced'
    }
});

// Update single value
stateManager.set('ui.loading', false);
```

### Example 2: Subscribing to State Changes

```javascript
// Subscribe to all state changes
const unsubscribe = stateManager.subscribe((state) => {
    console.log('State changed:', state);
    
    // Update UI based on state
    if (state.ui.loading) {
        showLoadingSpinner();
    } else {
        hideLoadingSpinner();
    }
});

// Unsubscribe when component is destroyed
component.destroy = function() {
    unsubscribe();
    Component.prototype.destroy.call(this);
};
```

### Example 3: Undo/Redo Implementation

```javascript
// Add undo/redo buttons
class UndoRedoComponent extends Component {
    render() {
        const historyInfo = this.state.getHistoryInfo();
        
        this.element.innerHTML = `
            <div class="undo-redo">
                <button class="undo-btn" ${!historyInfo.canUndo ? 'disabled' : ''}>
                    Undo
                </button>
                <button class="redo-btn" ${!historyInfo.canRedo ? 'disabled' : ''}>
                    Redo
                </button>
                <span class="history-info">
                    ${historyInfo.index + 1} / ${historyInfo.length}
                </span>
            </div>
        `;
    }
    
    bindEvents() {
        this.addEventListener(this.$('.undo-btn'), 'click', () => {
            if (this.state.undo()) {
                this.render();
                this.emit('notification:show', {
                    type: 'info',
                    message: 'Undo successful',
                    duration: 2000
                });
            }
        });
        
        this.addEventListener(this.$('.redo-btn'), 'click', () => {
            if (this.state.redo()) {
                this.render();
                this.emit('notification:show', {
                    type: 'info',
                    message: 'Redo successful',
                    duration: 2000
                });
            }
        });
        
        // Update buttons when state changes
        this.subscribe('state:changed', () => {
            this.render();
        });
    }
}
```

## API Client Usage

### Example 1: Basic CRUD Operations

```javascript
// Get settings
async function getSettings() {
    try {
        const response = await apiClient.getSettings();
        return response.data;
    } catch (error) {
        console.error('Failed to get settings:', error);
        throw error;
    }
}

// Save settings
async function saveSettings(settings) {
    try {
        const response = await apiClient.saveSettings(settings);
        console.log('Settings saved:', response.data);
        return response.data;
    } catch (error) {
        console.error('Failed to save settings:', error);
        throw error;
    }
}

// Update settings (partial)
async function updateSetting(key, value) {
    try {
        const response = await apiClient.updateSettings({
            [key]: value
        });
        return response.data;
    } catch (error) {
        console.error('Failed to update setting:', error);
        throw error;
    }
}
```

### Example 2: Error Handling with Retry

```javascript
async function saveWithRetry(settings) {
    const errorHandler = new ErrorHandler();
    
    try {
        const result = await errorHandler.wrap(async () => {
            return await apiClient.saveSettings(settings);
        }, {
            autoRetry: true,
            maxRetries: 3,
            context: 'Saving settings'
        });
        
        return result;
        
    } catch (error) {
        // All retries failed
        if (error instanceof MASAPIError) {
            if (error.isNetworkError()) {
                throw new Error('Network error. Please check your connection.');
            } else if (error.isValidationError()) {
                throw new Error('Invalid settings. Please check your input.');
            }
        }
        
        throw error;
    }
}
```

### Example 3: Caching Strategy

```javascript
// Get settings with cache
async function getSettingsWithCache() {
    // First try will fetch from API and cache
    const settings1 = await apiClient.getSettings();
    
    // Second try will use cache (if within TTL)
    const settings2 = await apiClient.getSettings();
    
    return settings2;
}

// Invalidate cache after update
async function updateAndInvalidate(settings) {
    await apiClient.saveSettings(settings);
    
    // Invalidate settings cache
    apiClient.invalidateCache('/settings');
    
    // Next get will fetch fresh data
    const freshSettings = await apiClient.getSettings();
    return freshSettings;
}

// Get cache statistics
function logCacheStats() {
    const stats = apiClient.getCacheStats();
    console.log('Cache enabled:', stats.enabled);
    console.log('Cache size:', stats.size);
    console.log('Cache max size:', stats.maxSize);
    console.log('Cache TTL:', stats.ttl);
}
```

## Error Handling

### Example 1: Component Error Handling

```javascript
class MyComponent extends Component {
    async performAction() {
        try {
            this.setState({ loading: true });
            
            const result = await this.api.saveSettings(this.collectData());
            
            this.setState({ loading: false });
            
            // Show success notification
            this.emit('notification:show', {
                type: 'success',
                message: 'Action completed successfully!',
                duration: 3000
            });
            
            return result;
            
        } catch (error) {
            this.setState({ loading: false });
            
            // Handle error
            this.handleError('Action failed', error);
            
            // Show error notification with retry
            this.emit('notification:show', {
                type: 'error',
                message: 'Action failed. Would you like to retry?',
                duration: 0,
                actions: [
                    {
                        label: 'Retry',
                        callback: () => this.performAction()
                    },
                    {
                        label: 'Cancel',
                        callback: null
                    }
                ]
            });
            
            throw error;
        }
    }
}
```

### Example 2: Global Error Handler

```javascript
// Setup global error handler
function setupGlobalErrorHandler(app) {
    // Handle unhandled promise rejections
    window.addEventListener('unhandledrejection', (event) => {
        console.error('Unhandled promise rejection:', event.reason);
        
        app.eventBus.emit('app:error', {
            context: 'Unhandled promise rejection',
            error: event.reason
        });
        
        // Show notification
        app.eventBus.emit('notification:show', {
            type: 'error',
            message: 'An unexpected error occurred.',
            duration: 5000
        });
        
        event.preventDefault();
    });
    
    // Handle global errors
    window.addEventListener('error', (event) => {
        // Only handle errors from our code
        if (event.filename && event.filename.includes('mas-')) {
            console.error('Global error:', event.error);
            
            app.eventBus.emit('app:error', {
                context: 'Global error',
                error: event.error
            });
        }
    });
}
```

### Example 3: Validation Error Handling

```javascript
async function saveWithValidation(settings) {
    try {
        // Validate before saving
        const validation = validateSettings(settings);
        
        if (!validation.valid) {
            throw ErrorHandler.createValidationError(
                validation.errors,
                'Settings validation failed'
            );
        }
        
        // Save if valid
        const result = await apiClient.saveSettings(settings);
        return result;
        
    } catch (error) {
        if (error instanceof MASValidationError) {
            // Handle validation errors
            const fieldErrors = error.getFieldErrors();
            
            for (const [field, message] of Object.entries(fieldErrors)) {
                console.error(`${field}: ${message}`);
                
                // Highlight field in UI
                const fieldElement = document.querySelector(`[name="${field}"]`);
                if (fieldElement) {
                    fieldElement.classList.add('error');
                    // Show error message
                }
            }
        }
        
        throw error;
    }
}
```

## Form Validation

### Example 1: Field Validators

```javascript
// Color validator
function validateColor(value) {
    if (!value) {
        return { valid: true }; // Empty is valid
    }
    
    if (!/^#[0-9A-F]{6}$/i.test(value)) {
        return {
            valid: false,
            message: 'Invalid color format. Use hex format like #FF0000'
        };
    }
    
    return { valid: true };
}

// CSS unit validator
function validateCSSUnit(value) {
    if (!value) {
        return { valid: true };
    }
    
    if (!/^\d+(px|em|rem|%|vh|vw)$/.test(value)) {
        return {
            valid: false,
            message: 'Invalid CSS unit. Use px, em, rem, %, vh, or vw'
        };
    }
    
    return { valid: true };
}

// Number range validator
function validateRange(value, min, max) {
    const num = parseFloat(value);
    
    if (isNaN(num)) {
        return {
            valid: false,
            message: 'Must be a valid number'
        };
    }
    
    if (num < min || num > max) {
        return {
            valid: false,
            message: `Must be between ${min} and ${max}`
        };
    }
    
    return { valid: true };
}
```

### Example 2: Form Validation Component

```javascript
class ValidatedFormComponent extends Component {
    init() {
        // Setup validators
        this.validators = new Map();
        this.setupValidators();
        
        super.init();
    }
    
    setupValidators() {
        // Add validators for each field
        this.validators.set('menu_background', validateColor);
        this.validators.set('menu_text_color', validateColor);
        this.validators.set('menu_width', validateCSSUnit);
        this.validators.set('opacity', (value) => validateRange(value, 0, 1));
    }
    
    validateField(field, value) {
        const validator = this.validators.get(field);
        
        if (!validator) {
            return { valid: true };
        }
        
        return validator(value);
    }
    
    validateForm(data) {
        const errors = [];
        
        for (const [field, value] of Object.entries(data)) {
            const result = this.validateField(field, value);
            
            if (!result.valid) {
                errors.push({
                    field,
                    message: result.message
                });
            }
        }
        
        return {
            valid: errors.length === 0,
            errors
        };
    }
    
    showFieldError(field, message) {
        const fieldElement = this.element.querySelector(`[name="${field}"]`);
        
        if (!fieldElement) {
            return;
        }
        
        // Mark field as invalid
        fieldElement.classList.add('error');
        fieldElement.setAttribute('aria-invalid', 'true');
        
        // Create or update error message
        let errorElement = fieldElement.parentNode.querySelector('.field-error');
        
        if (!errorElement) {
            errorElement = document.createElement('span');
            errorElement.className = 'field-error';
            errorElement.setAttribute('role', 'alert');
            fieldElement.parentNode.appendChild(errorElement);
        }
        
        errorElement.textContent = message;
        
        // Link error to field
        if (!errorElement.id) {
            errorElement.id = `${field}-error`;
        }
        fieldElement.setAttribute('aria-describedby', errorElement.id);
    }
    
    clearFieldError(field) {
        const fieldElement = this.element.querySelector(`[name="${field}"]`);
        
        if (!fieldElement) {
            return;
        }
        
        fieldElement.classList.remove('error');
        fieldElement.removeAttribute('aria-invalid');
        
        const errorElement = fieldElement.parentNode.querySelector('.field-error');
        if (errorElement) {
            errorElement.remove();
        }
    }
}
```

## Live Preview Implementation

### Example: Simple Live Preview

```javascript
class SimpleLivePreview extends Component {
    init() {
        this.localState = {
            enabled: false,
            previewCSS: null
        };
        
        this.debouncedUpdate = this.debounce(this.updatePreview.bind(this), 300);
        
        super.init();
    }
    
    bindEvents() {
        // Subscribe to field changes
        this.subscribe('field:changed', (event) => {
            if (this.localState.enabled) {
                this.debouncedUpdate(event.data.settings);
            }
        });
        
        // Toggle button
        const toggleBtn = this.$('.preview-toggle');
        if (toggleBtn) {
            this.addEventListener(toggleBtn, 'click', () => {
                this.toggle();
            });
        }
    }
    
    toggle() {
        if (this.localState.enabled) {
            this.disable();
        } else {
            this.enable();
        }
    }
    
    enable() {
        this.setState({ enabled: true });
        this.emit('preview:enabled');
    }
    
    disable() {
        this.removePreviewCSS();
        this.setState({ enabled: false, previewCSS: null });
        this.emit('preview:disabled');
    }
    
    async updatePreview(settings) {
        try {
            const response = await this.api.generatePreview(settings);
            const css = response.data.css;
            
            this.applyPreviewCSS(css);
            this.setState({ previewCSS: css });
            
        } catch (error) {
            this.handleError('Preview failed', error);
        }
    }
    
    applyPreviewCSS(css) {
        let styleElement = document.getElementById('mas-preview-styles');
        
        if (!styleElement) {
            styleElement = document.createElement('style');
            styleElement.id = 'mas-preview-styles';
            document.head.appendChild(styleElement);
        }
        
        styleElement.textContent = css;
    }
    
    removePreviewCSS() {
        const styleElement = document.getElementById('mas-preview-styles');
        if (styleElement) {
            styleElement.remove();
        }
    }
}
```

## Notification System

### Example 1: Basic Notifications

```javascript
// Success notification
eventBus.emit('notification:show', {
    type: 'success',
    message: 'Settings saved successfully!',
    duration: 3000
});

// Error notification
eventBus.emit('notification:show', {
    type: 'error',
    message: 'Failed to save settings.',
    duration: 5000
});

// Warning notification
eventBus.emit('notification:show', {
    type: 'warning',
    message: 'Some settings may not be applied immediately.',
    duration: 4000
});

// Info notification
eventBus.emit('notification:show', {
    type: 'info',
    message: 'Preview mode is now active.',
    duration: 3000
});
```

### Example 2: Notifications with Actions

```javascript
// Error with retry action
eventBus.emit('notification:show', {
    type: 'error',
    message: 'Failed to save settings. Would you like to retry?',
    duration: 0, // Persistent
    actions: [
        {
            label: 'Retry',
            callback: () => {
                saveSettings();
            }
        },
        {
            label: 'Cancel',
            callback: null
        }
    ]
});

// Confirmation with actions
eventBus.emit('notification:show', {
    type: 'warning',
    message: 'You have unsaved changes. Save before leaving?',
    duration: 0,
    actions: [
        {
            label: 'Save',
            callback: () => {
                saveSettings();
            }
        },
        {
            label: 'Discard',
            callback: () => {
                discardChanges();
            }
        },
        {
            label: 'Cancel',
            callback: null
        }
    ]
});
```

### Example 3: Using NotificationSystem Directly

```javascript
const notifications = app.getComponent('notifications');

// Success
notifications.success('Operation completed!', 3000);

// Error with retry
notifications.errorWithRetry(
    'Failed to load data',
    () => loadData()
);

// Network error
notifications.networkError(() => retryOperation());

// Unexpected error with report
notifications.unexpectedError(new Error('Something went wrong'));
```

## Accessibility Features

### Example 1: ARIA Attributes

```javascript
// Mark field as invalid
function markFieldInvalid(field, errorMessage) {
    field.setAttribute('aria-invalid', 'true');
    
    // Create error message element
    const errorId = `${field.id}-error`;
    let errorElement = document.getElementById(errorId);
    
    if (!errorElement) {
        errorElement = document.createElement('span');
        errorElement.id = errorId;
        errorElement.className = 'error-message';
        errorElement.setAttribute('role', 'alert');
        field.parentNode.appendChild(errorElement);
    }
    
    errorElement.textContent = errorMessage;
    field.setAttribute('aria-describedby', errorId);
}

// Mark field as valid
function markFieldValid(field) {
    field.removeAttribute('aria-invalid');
    
    const errorId = `${field.id}-error`;
    const errorElement = document.getElementById(errorId);
    
    if (errorElement) {
        errorElement.remove();
    }
    
    field.removeAttribute('aria-describedby');
}
```

### Example 2: Keyboard Navigation

```javascript
// Add keyboard shortcut
const cleanup = KeyboardNavigationHelper.addShortcut(
    's',
    (e) => {
        e.preventDefault();
        saveSettings();
    },
    {
        ctrl: true,
        element: document.body
    }
);

// Add escape handler
const escapeCleanup = KeyboardNavigationHelper.addEscapeHandler(
    modalElement,
    () => {
        closeModal();
    }
);

// Cleanup when done
cleanup();
escapeCleanup();
```

### Example 3: Screen Reader Announcements

```javascript
// Announce success
AccessibilityHelper.announce('Settings saved successfully', 'polite');

// Announce error (assertive for important messages)
AccessibilityHelper.announce('Error: Failed to save settings', 'assertive');

// Announce loading state
AccessibilityHelper.announce('Loading settings...', 'polite');
```

## Performance Optimization

### Example 1: Debouncing and Throttling

```javascript
class OptimizedComponent extends Component {
    init() {
        // Debounce search (wait for user to stop typing)
        this.debouncedSearch = this.debounce(this.performSearch.bind(this), 300);
        
        // Throttle scroll (limit frequency)
        this.throttledScroll = this.throttle(this.handleScroll.bind(this), 100);
        
        super.init();
    }
    
    bindEvents() {
        // Debounced search input
        const searchInput = this.$('.search-input');
        if (searchInput) {
            this.addEventListener(searchInput, 'input', (e) => {
                this.debouncedSearch(e.target.value);
            });
        }
        
        // Throttled scroll handler
        window.addEventListener('scroll', this.throttledScroll);
    }
    
    performSearch(query) {
        console.log('Searching for:', query);
        // Perform search...
    }
    
    handleScroll() {
        console.log('Scroll position:', window.scrollY);
        // Handle scroll...
    }
}
```

### Example 2: Batch DOM Updates

```javascript
class BatchUpdateComponent extends Component {
    updateMultipleElements(data) {
        // Batch all DOM updates together
        this.batchUpdate(() => {
            // All these updates happen in one reflow
            this.$('.title').textContent = data.title;
            this.$('.description').textContent = data.description;
            this.$('.status').classList.add('active');
            this.$('.count').textContent = data.count;
        });
    }
    
    scheduleAnimation() {
        // Schedule update with requestAnimationFrame
        this.scheduleUpdate('my-animation', () => {
            const element = this.$('.animated-element');
            element.style.transform = 'translateX(100px)';
        });
    }
}
```

### Example 3: Virtual Scrolling for Large Lists

```javascript
// Create virtual list for 10,000 items
const container = document.querySelector('#large-list');
const items = generateLargeDataset(10000);

const virtualList = new VirtualList(container, {
    itemHeight: 50,
    items: items,
    renderItem: (item, index) => {
        return `
            <div class="list-item">
                <h4>${item.title}</h4>
                <p>${item.description}</p>
            </div>
        `;
    },
    onItemClick: (item, index) => {
        console.log('Clicked item:', item);
    }
});

// Update items
virtualList.updateItems(newItems);

// Scroll to item
virtualList.scrollToIndex(500);

// Destroy when done
virtualList.destroy();
```

---

For more examples and detailed documentation, see:
- [Phase 3 Developer Guide](PHASE3-DEVELOPER-GUIDE.md)
- [Phase 3 Core Architecture Guide](PHASE3-CORE-ARCHITECTURE-GUIDE.md)
- [Phase 3 Component System Guide](PHASE3-COMPONENT-SYSTEM-GUIDE.md)
