/**
 * Settings Form Component
 * 
 * Manages the main settings form with validation, submission, and real-time updates.
 * Handles form data collection including unchecked checkboxes, validation, and API communication.
 * 
 * @class SettingsFormComponent
 * @extends Component
 */
class SettingsFormComponent extends Component {
    /**
     * Initialize component
     * Sets up form elements, validators, and debounced handlers
     */
    init() {
        // Get form elements
        this.form = this.element;
        this.submitButton = this.form.querySelector('button[type="submit"]');
        this.resetButton = this.form.querySelector('.mas-reset-settings');

        // Initialize local state
        this.localState = {
            loading: false,
            submitting: false,
            validationErrors: {},
            originalSettings: {}
        };

        // Validation rules map
        this.validators = new Map();
        this.setupValidators();

        // Debounced change handler for live preview
        this.debouncedChange = this.debounce(this.handleFieldChange.bind(this), 300);

        // Setup accessibility attributes
        this.setupAccessibility();

        // Call parent init
        super.init();

        // Load initial settings
        this.loadSettings();
    }

    /**
     * Setup accessibility attributes for form fields
     * Adds proper ARIA labels, descriptions, and required indicators
     */
    setupAccessibility() {
        if (!this.form) {
            return;
        }

        this.log('Setting up accessibility attributes...');

        // Add aria-label to form if not present
        if (!this.form.getAttribute('aria-label')) {
            this.form.setAttribute('aria-label', 'Plugin settings form');
        }

        // Setup all form fields with proper ARIA attributes
        const fields = this.form.querySelectorAll('input, select, textarea');
        
        fields.forEach(field => {
            // Ensure field has an ID for proper labeling
            if (typeof AccessibilityHelper !== 'undefined') {
                AccessibilityHelper.ensureId(field, 'mas-field');
            } else if (!field.id) {
                field.id = `mas-field-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
            }

            // Find associated label
            const label = this.findFieldLabel(field);
            
            // If no label exists, add aria-label from name or placeholder
            if (!label && !field.getAttribute('aria-label')) {
                const name = field.getAttribute('name');
                const placeholder = field.getAttribute('placeholder');
                const ariaLabel = placeholder || this.humanizeFieldName(name);
                
                if (ariaLabel) {
                    field.setAttribute('aria-label', ariaLabel);
                }
            }

            // Link label to field if both exist
            if (label && field.id && !label.getAttribute('for')) {
                label.setAttribute('for', field.id);
            }

            // Add aria-required for required fields
            if (field.hasAttribute('required') && !field.getAttribute('aria-required')) {
                field.setAttribute('aria-required', 'true');
            }

            // Add aria-describedby for fields with descriptions
            const description = this.findFieldDescription(field);
            if (description && typeof AccessibilityHelper !== 'undefined') {
                AccessibilityHelper.ensureId(description, 'mas-desc');
                AccessibilityHelper.addDescribedBy(field, description.id);
            }
        });

        // Add aria-label to submit button if not present
        if (this.submitButton && !this.submitButton.getAttribute('aria-label')) {
            this.submitButton.setAttribute('aria-label', 'Save settings');
        }

        // Add aria-label to reset button if not present
        if (this.resetButton && !this.resetButton.getAttribute('aria-label')) {
            this.resetButton.setAttribute('aria-label', 'Reset all settings to defaults');
        }

        this.log('Accessibility attributes setup complete');
    }

    /**
     * Find label element for a form field
     * 
     * @param {HTMLElement} field - Form field
     * @returns {HTMLElement|null} Label element or null
     */
    findFieldLabel(field) {
        if (!field) {
            return null;
        }

        // Try by ID
        if (field.id) {
            const label = this.form.querySelector(`label[for="${field.id}"]`);
            if (label) {
                return label;
            }
        }

        // Try parent label
        let parent = field.parentElement;
        while (parent && parent !== this.form) {
            if (parent.tagName === 'LABEL') {
                return parent;
            }
            parent = parent.parentElement;
        }

        return null;
    }

    /**
     * Find description element for a form field
     * 
     * @param {HTMLElement} field - Form field
     * @returns {HTMLElement|null} Description element or null
     */
    findFieldDescription(field) {
        if (!field || !field.parentElement) {
            return null;
        }

        // Look for common description classes
        const descriptionSelectors = [
            '.field-description',
            '.description',
            '.help-text',
            '.field-help'
        ];

        for (const selector of descriptionSelectors) {
            const desc = field.parentElement.querySelector(selector);
            if (desc) {
                return desc;
            }
        }

        return null;
    }

    /**
     * Convert field name to human-readable label
     * 
     * @param {string} name - Field name
     * @returns {string} Human-readable label
     */
    humanizeFieldName(name) {
        if (!name) {
            return '';
        }

        return name
            .replace(/_/g, ' ')
            .replace(/\b\w/g, char => char.toUpperCase());
    }

    /**
     * Bind event listeners
     */
    bindEvents() {
        // Form submission
        this.addEventListener(this.form, 'submit', this.getBoundMethod('handleSubmit'));

        // Reset button
        if (this.resetButton) {
            this.addEventListener(this.resetButton, 'click', this.getBoundMethod('handleReset'));
        }

        // Field changes for live preview and unsaved changes tracking
        this.addEventListener(this.form, 'input', (e) => {
            this.debouncedChange(e);
        });

        this.addEventListener(this.form, 'change', (e) => {
            this.debouncedChange(e);
        });

        // Keyboard shortcuts
        this.setupKeyboardShortcuts();

        // Warn on unsaved changes
        window.addEventListener('beforeunload', this.getBoundMethod('handleBeforeUnload'));

        // Subscribe to state changes
        this.subscribe('state:changed', (event) => {
            if (event.data.updates.settings) {
                this.updateFormFields(event.data.state.settings);
            }
        });

        // Subscribe to theme application
        this.subscribe('theme:applied', (event) => {
            if (event.data.settings) {
                this.updateFormFields(event.data.settings);
            }
        });
    }

    /**
     * Setup keyboard shortcuts for form
     */
    setupKeyboardShortcuts() {
        if (typeof KeyboardNavigationHelper === 'undefined') {
            return;
        }

        // Ctrl+S to save
        this.keyboardCleanups = this.keyboardCleanups || [];
        
        const saveShortcut = KeyboardNavigationHelper.addShortcut(
            's',
            (e) => {
                this.log('Save shortcut triggered');
                this.handleSubmit(e);
            },
            {
                ctrl: true,
                element: this.form
            }
        );
        
        this.keyboardCleanups.push(saveShortcut);

        // Ctrl+R to reset (with confirmation)
        const resetShortcut = KeyboardNavigationHelper.addShortcut(
            'r',
            (e) => {
                if (this.resetButton) {
                    this.log('Reset shortcut triggered');
                    this.handleReset(e);
                }
            },
            {
                ctrl: true,
                shift: true,
                element: this.form
            }
        );
        
        this.keyboardCleanups.push(resetShortcut);

        this.log('Keyboard shortcuts setup complete');
    }

    /**
     * Load settings from API
     * Populates form with current settings
     * 
     * @returns {Promise<void>}
     */
    async loadSettings() {
        try {
            this.setState({ loading: true });
            this.setLoadingState(true, 'Loading settings...');

            this.log('Loading settings from API...');

            const response = await this.api.getSettings();
            const settings = response.data || response;

            this.log('Settings loaded:', settings);

            // Store original settings for comparison
            this.setState({ 
                originalSettings: { ...settings },
                loading: false 
            });

            // Update global state
            this.state.setState({
                settings,
                ui: { loading: false, hasUnsavedChanges: false }
            });

            // Update form fields
            this.updateFormFields(settings);

            this.setLoadingState(false);

        } catch (error) {
            this.handleError('Failed to load settings', error);
            this.setState({ loading: false });
            this.setLoadingState(false);

            // Show error notification
            this.emit('notification:show', {
                type: 'error',
                message: 'Failed to load settings. Please refresh the page.',
                duration: 5000
            });
        }
    }

    /**
     * Update form fields from settings object
     * Handles all input types including checkboxes, radios, and selects
     * 
     * @param {Object} settings - Settings object
     */
    updateFormFields(settings) {
        if (!settings || typeof settings !== 'object') {
            this.log('Invalid settings object:', settings);
            return;
        }

        this.log('Updating form fields with settings:', settings);

        for (const [key, value] of Object.entries(settings)) {
            const field = this.form.elements[key];

            if (!field) {
                // Field not found in form, skip
                continue;
            }

            // Handle different input types
            if (field.type === 'checkbox') {
                field.checked = value === '1' || value === true || value === 1;
            } else if (field.type === 'radio') {
                const radio = this.form.querySelector(`input[name="${key}"][value="${value}"]`);
                if (radio) {
                    radio.checked = true;
                }
            } else if (field.tagName === 'SELECT') {
                // Handle select elements
                field.value = value;
            } else {
                // Text, color, number, etc.
                field.value = value || '';
            }

            // Trigger change event for any listeners (like color pickers)
            const changeEvent = new Event('change', { bubbles: true });
            field.dispatchEvent(changeEvent);
        }

        this.log('Form fields updated');
    }

    /**
     * Handle form submission
     * Validates, saves settings, and provides feedback
     * Uses optimistic UI updates with rollback on failure
     * 
     * @param {Event} e - Submit event
     * @returns {Promise<void>}
     */
    async handleSubmit(e) {
        e.preventDefault();
        e.stopPropagation();

        // Prevent double submission
        if (this.localState.submitting) {
            this.log('Already submitting, ignoring duplicate submission');
            return;
        }

        this.log('Form submitted');

        this.setState({ submitting: true });
        this.setLoadingState(true, 'Saving settings...');

        // Store previous state for rollback
        const previousSettings = this.state.get('settings');
        const previousOriginalSettings = { ...this.localState.originalSettings };

        try {
            // Collect form data
            const settings = this.collectFormData();
            this.log('Collected form data:', settings);

            // Validate
            const validation = this.validateSettings(settings);
            if (!validation.valid) {
                this.log('Validation failed:', validation.errors);
                this.showValidationErrors(validation.errors);
                this.setState({ submitting: false });
                this.setLoadingState(false);
                return;
            }

            this.log('Validation passed');

            // Clear any previous validation errors
            this.clearValidationErrors();

            // === OPTIMISTIC UPDATE ===
            // Update UI immediately before API call for better UX
            this.applyOptimisticUpdate(settings);

            // Save via API
            this.log('Saving settings via API...');
            const result = await this.api.saveSettings(settings);
            this.log('Settings saved successfully:', result);

            // === SUCCESS ===
            // Confirm optimistic update with server response
            this.confirmOptimisticUpdate(result.data?.settings || settings);

            // Show success notification
            this.emit('notification:show', {
                type: 'success',
                message: 'Settings saved successfully!',
                duration: 3000
            });

            // Emit settings saved event
            this.emit('settings:saved', { settings: result.data?.settings || settings });

        } catch (error) {
            this.handleError('Failed to save settings', error);

            // === ROLLBACK ===
            // Revert optimistic update on failure
            this.rollbackOptimisticUpdate(previousSettings, previousOriginalSettings);

            // Show error notification with retry option
            this.emit('notification:show', {
                type: 'error',
                message: 'Failed to save settings. Changes have been reverted.',
                duration: 5000,
                actions: [
                    {
                        label: 'Retry',
                        callback: () => this.handleSubmit(e)
                    }
                ]
            });

        } finally {
            this.setState({ submitting: false });
            this.setLoadingState(false);
        }
    }

    /**
     * Apply optimistic update
     * Updates UI immediately before API call
     * 
     * @param {Object} settings - New settings
     */
    applyOptimisticUpdate(settings) {
        this.log('Applying optimistic update');

        // Update global state
        this.state.setState({
            settings,
            ui: { 
                saving: true, 
                hasUnsavedChanges: false,
                optimisticUpdate: true 
            }
        }, false); // Don't add to history during optimistic update

        // Update local state
        this.setState({ 
            originalSettings: { ...settings },
            optimisticUpdate: true
        });

        // Emit optimistic update event
        this.emit('settings:optimistic-update', { settings });
    }

    /**
     * Confirm optimistic update
     * Called when API save succeeds
     * 
     * @param {Object} settings - Server-confirmed settings
     */
    confirmOptimisticUpdate(settings) {
        this.log('Confirming optimistic update');

        // Update state with server response
        this.state.setState({
            settings,
            ui: { 
                saving: false, 
                hasUnsavedChanges: false,
                optimisticUpdate: false
            }
        });

        // Update local state
        this.setState({ 
            originalSettings: { ...settings },
            optimisticUpdate: false
        });

        // Clear unsaved changes indicator
        this.updateUnsavedChangesIndicator(false);

        // Emit confirmation event
        this.emit('settings:update-confirmed', { settings });
    }

    /**
     * Rollback optimistic update
     * Called when API save fails
     * 
     * @param {Object} previousSettings - Previous settings to restore
     * @param {Object} previousOriginalSettings - Previous original settings
     */
    rollbackOptimisticUpdate(previousSettings, previousOriginalSettings) {
        this.log('Rolling back optimistic update');

        // Restore previous state
        this.state.setState({
            settings: previousSettings,
            ui: { 
                saving: false, 
                hasUnsavedChanges: true,
                optimisticUpdate: false
            }
        });

        // Restore local state
        this.setState({ 
            originalSettings: previousOriginalSettings,
            optimisticUpdate: false
        });

        // Restore form fields
        this.updateFormFields(previousSettings);

        // Emit rollback event
        this.emit('settings:update-rolled-back', { 
            previousSettings,
            reason: 'API save failed'
        });
    }

    /**
     * Collect all form data including unchecked checkboxes
     * Returns a complete settings object ready for API submission
     * 
     * @returns {Object} Settings object
     */
    collectFormData() {
        const formData = new FormData(this.form);
        const settings = {};

        // Get all form fields from FormData
        for (const [key, value] of formData.entries()) {
            settings[key] = value;
        }

        // Add unchecked checkboxes as '0'
        // This is critical for proper settings persistence
        const checkboxes = this.form.querySelectorAll('input[type="checkbox"]');
        for (const checkbox of checkboxes) {
            const name = checkbox.getAttribute('name');
            if (name && !settings.hasOwnProperty(name)) {
                settings[name] = '0';
            }
        }

        // Remove WordPress and form-specific fields
        delete settings.action;
        delete settings.nonce;
        delete settings._wpnonce;
        delete settings._wp_http_referer;
        delete settings.submit;

        this.log('Collected form data:', settings);

        return settings;
    }

    /**
     * Validate settings against validation rules
     * 
     * @param {Object} settings - Settings to validate
     * @returns {Object} Validation result { valid: boolean, errors: Array }
     */
    validateSettings(settings) {
        const errors = [];

        for (const [field, validator] of this.validators) {
            const value = settings[field];
            
            try {
                const result = validator(value, settings);

                if (!result.valid) {
                    errors.push({
                        field,
                        message: result.message
                    });
                }
            } catch (error) {
                this.log(`Validator error for field ${field}:`, error);
                errors.push({
                    field,
                    message: 'Validation error occurred'
                });
            }
        }

        return {
            valid: errors.length === 0,
            errors
        };
    }

    /**
     * Setup field validators
     * Defines validation rules for form fields
     */
    setupValidators() {
        // Color field validator
        const colorValidator = (value) => {
            if (!value) {
                return { valid: true }; // Empty is valid (will use default)
            }
            if (!/^#[0-9A-F]{6}$/i.test(value)) {
                return { valid: false, message: 'Invalid color format. Use hex format like #FF0000' };
            }
            return { valid: true };
        };

        // Add color validators for all color fields
        const colorFields = [
            'menu_background',
            'menu_text_color',
            'menu_hover_background',
            'menu_hover_text_color',
            'menu_active_background',
            'menu_active_text_color',
            'admin_bar_background',
            'admin_bar_text_color'
        ];

        colorFields.forEach(field => {
            this.validators.set(field, colorValidator);
        });

        // CSS unit validator (for width, height, etc.)
        const cssUnitValidator = (value) => {
            if (!value) {
                return { valid: true };
            }
            if (!/^\d+(px|em|rem|%|vh|vw)$/.test(value)) {
                return { valid: false, message: 'Invalid CSS unit. Use px, em, rem, %, vh, or vw' };
            }
            return { valid: true };
        };

        this.validators.set('menu_width', cssUnitValidator);
        this.validators.set('menu_item_height', cssUnitValidator);

        // Border radius validator
        this.validators.set('menu_border_radius', cssUnitValidator);

        this.log('Validators setup complete');
    }

    /**
     * Handle field change for live preview and unsaved changes tracking
     * 
     * @param {Event} e - Change event
     */
    handleFieldChange(e) {
        const field = e.target;
        const name = field.getAttribute('name');

        if (!name) {
            return;
        }

        // Get field value based on type
        let value;
        if (field.type === 'checkbox') {
            value = field.checked;
        } else {
            value = field.value;
        }

        this.log(`Field changed: ${name} = ${value}`);

        // Check if value actually changed from original
        const hasChanges = this.hasUnsavedChanges();

        // Mark as having unsaved changes
        this.state.setState({
            ui: { hasUnsavedChanges: hasChanges }
        });

        // Update unsaved changes indicator
        this.updateUnsavedChangesIndicator(hasChanges);

        // Emit field change event for live preview
        this.emit('field:changed', {
            field: name,
            value: value,
            settings: this.collectFormData(),
            hasUnsavedChanges: hasChanges
        });
    }

    /**
     * Check if form has unsaved changes
     * Compares current form data with original settings
     * 
     * @returns {boolean} Whether there are unsaved changes
     */
    hasUnsavedChanges() {
        const currentSettings = this.collectFormData();
        const originalSettings = this.localState.originalSettings;

        // Compare each field
        for (const key in currentSettings) {
            const currentValue = String(currentSettings[key]);
            const originalValue = String(originalSettings[key] || '');

            if (currentValue !== originalValue) {
                return true;
            }
        }

        return false;
    }

    /**
     * Update unsaved changes indicator in UI
     * 
     * @param {boolean} hasChanges - Whether there are unsaved changes
     */
    updateUnsavedChangesIndicator(hasChanges) {
        // Add/remove indicator class to form
        if (hasChanges) {
            this.form.classList.add('has-unsaved-changes');
            
            // Add indicator to submit button if not already present
            if (this.submitButton && !this.submitButton.querySelector('.unsaved-indicator')) {
                const indicator = document.createElement('span');
                indicator.className = 'unsaved-indicator';
                indicator.textContent = ' *';
                indicator.title = 'You have unsaved changes';
                this.submitButton.appendChild(indicator);
            }
        } else {
            this.form.classList.remove('has-unsaved-changes');
            
            // Remove indicator from submit button
            if (this.submitButton) {
                const indicator = this.submitButton.querySelector('.unsaved-indicator');
                if (indicator) {
                    indicator.remove();
                }
            }
        }
    }

    /**
     * Handle reset button click
     * Resets all settings to defaults with confirmation
     * 
     * @param {Event} e - Click event
     * @returns {Promise<void>}
     */
    async handleReset(e) {
        e.preventDefault();

        const confirmed = confirm(
            'Are you sure you want to reset all settings to defaults?\n\n' +
            'This action cannot be undone. A backup will be created automatically.'
        );

        if (!confirmed) {
            return;
        }

        this.log('Resetting settings to defaults');

        try {
            this.setLoadingState(true, 'Resetting settings...');

            await this.api.resetSettings();

            this.log('Settings reset successfully');

            this.emit('notification:show', {
                type: 'success',
                message: 'Settings reset successfully! Reloading page...',
                duration: 2000
            });

            // Reload page after short delay
            setTimeout(() => {
                window.location.reload();
            }, 2000);

        } catch (error) {
            this.handleError('Failed to reset settings', error);
            this.setLoadingState(false);

            this.emit('notification:show', {
                type: 'error',
                message: 'Failed to reset settings. Please try again.',
                duration: 5000
            });
        }
    }

    /**
     * Handle before unload event
     * Warns user about unsaved changes
     * 
     * @param {Event} e - Before unload event
     * @returns {string|undefined} Warning message
     */
    handleBeforeUnload(e) {
        if (this.state.get('ui.hasUnsavedChanges')) {
            const message = 'You have unsaved changes. Are you sure you want to leave?';
            e.preventDefault();
            e.returnValue = message;
            return message;
        }
    }

    /**
     * Set loading state on submit button with ARIA announcements
     * 
     * @param {boolean} loading - Whether loading
     * @param {string} text - Optional loading text
     */
    setLoadingState(loading, text = null) {
        if (!this.submitButton) {
            return;
        }

        if (loading) {
            this.submitButton.disabled = true;
            this.submitButton.classList.add('loading');
            this.submitButton.setAttribute('aria-busy', 'true');
            
            if (text) {
                this.submitButton.dataset.originalText = this.submitButton.textContent;
                this.submitButton.textContent = text;
                
                // Announce loading state to screen readers
                if (typeof AccessibilityHelper !== 'undefined') {
                    AccessibilityHelper.announce(text, 'polite');
                }
            }
        } else {
            this.submitButton.disabled = false;
            this.submitButton.classList.remove('loading');
            this.submitButton.removeAttribute('aria-busy');
            
            if (this.submitButton.dataset.originalText) {
                this.submitButton.textContent = this.submitButton.dataset.originalText;
                delete this.submitButton.dataset.originalText;
            }
        }
    }

    /**
     * Show validation errors in the form
     * Highlights fields and displays error messages with ARIA attributes
     * 
     * @param {Array} errors - Array of error objects
     */
    showValidationErrors(errors) {
        this.log('Showing validation errors:', errors);

        for (const error of errors) {
            const field = this.form.elements[error.field];
            
            if (!field) {
                continue;
            }

            // Add error class to field
            field.classList.add('error');

            // Use AccessibilityHelper to mark field as invalid with proper ARIA
            if (typeof AccessibilityHelper !== 'undefined') {
                AccessibilityHelper.markInvalid(field, error.message);
            } else {
                // Fallback if AccessibilityHelper not loaded
                field.setAttribute('aria-invalid', 'true');
                
                // Find or create error message element
                let errorElement = field.parentNode.querySelector('.field-error');
                
                if (!errorElement) {
                    errorElement = document.createElement('span');
                    errorElement.className = 'field-error';
                    errorElement.setAttribute('role', 'alert');
                    field.parentNode.appendChild(errorElement);
                }

                errorElement.textContent = error.message;
                errorElement.style.display = 'block';
            }

            // Remove error on field change
            const removeError = () => {
                field.classList.remove('error');
                
                if (typeof AccessibilityHelper !== 'undefined') {
                    AccessibilityHelper.markValid(field);
                } else {
                    field.removeAttribute('aria-invalid');
                    const errorElement = field.parentNode.querySelector('.field-error');
                    if (errorElement) {
                        errorElement.style.display = 'none';
                    }
                }
                
                field.removeEventListener('input', removeError);
                field.removeEventListener('change', removeError);
            };

            field.addEventListener('input', removeError);
            field.addEventListener('change', removeError);
        }

        // Announce errors to screen readers
        if (typeof AccessibilityHelper !== 'undefined') {
            const errorCount = errors.length;
            const message = `${errorCount} validation error${errorCount > 1 ? 's' : ''} found. Please fix before saving.`;
            AccessibilityHelper.announce(message, 'assertive');
        }

        // Show general error notification
        this.emit('notification:show', {
            type: 'error',
            message: `Please fix ${errors.length} validation error${errors.length > 1 ? 's' : ''} before saving.`,
            duration: 5000
        });

        // Scroll to first error
        if (errors.length > 0) {
            const firstErrorField = this.form.elements[errors[0].field];
            if (firstErrorField) {
                firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstErrorField.focus();
            }
        }
    }

    /**
     * Clear all validation errors
     */
    clearValidationErrors() {
        // Remove error classes
        const errorFields = this.form.querySelectorAll('.error');
        errorFields.forEach(field => field.classList.remove('error'));

        // Hide error messages
        const errorMessages = this.form.querySelectorAll('.field-error');
        errorMessages.forEach(msg => msg.style.display = 'none');

        this.log('Validation errors cleared');
    }

    /**
     * Destroy component and cleanup
     */
    destroy() {
        // Remove beforeunload listener
        window.removeEventListener('beforeunload', this.getBoundMethod('handleBeforeUnload'));

        // Cleanup keyboard shortcuts
        if (this.keyboardCleanups) {
            this.keyboardCleanups.forEach(cleanup => cleanup());
            this.keyboardCleanups = [];
        }

        // Call parent destroy
        super.destroy();
    }
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SettingsFormComponent;
}

// Make available globally for WordPress
if (typeof window !== 'undefined') {
    window.SettingsFormComponent = SettingsFormComponent;
}
