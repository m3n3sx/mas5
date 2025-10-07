/**
 * Live Preview Component
 * 
 * Implements real-time preview of styling changes without saving.
 * Injects temporary CSS, manages preview state, and provides smooth transitions.
 * 
 * @class LivePreviewComponent
 * @extends Component
 */
class LivePreviewComponent extends Component {
    /**
     * Initialize component
     * Sets up preview state and debounced handlers
     */
    init() {
        // Initialize local state
        this.localState = {
            enabled: false,
            active: false,
            loading: false,
            originalCSS: null,
            currentPreviewCSS: null,
            lastSettings: null,
            error: null
        };

        // Preview style element ID
        this.previewStyleId = 'mas-live-preview-styles';

        // Debounce delay for preview updates (300ms)
        this.previewDebounceDelay = 300;

        // Create debounced preview update handler
        this.debouncedUpdatePreview = this.debounce(
            this.updatePreview.bind(this),
            this.previewDebounceDelay
        );

        // Pending preview request (for cancellation)
        this.pendingPreviewRequest = null;

        // Call parent init
        super.init();

        this.log('LivePreviewComponent initialized');
    }

    /**
     * Bind event listeners
     */
    bindEvents() {
        // Subscribe to field change events from form
        this.subscribe('field:changed', this.getBoundMethod('handleFieldChange'));

        // Subscribe to settings saved event (to disable preview)
        this.subscribe('settings:saved', this.getBoundMethod('handleSettingsSaved'));

        // Subscribe to settings update rolled back (to restore preview)
        this.subscribe('settings:update-rolled-back', this.getBoundMethod('handleRollback'));

        // Find and bind preview toggle button if it exists
        const toggleButton = this.$('.mas-preview-toggle');
        if (toggleButton) {
            this.addEventListener(toggleButton, 'click', this.getBoundMethod('handleToggleClick'));
        }

        // Find and bind preview reset button if it exists
        const resetButton = this.$('.mas-preview-reset');
        if (resetButton) {
            this.addEventListener(resetButton, 'click', this.getBoundMethod('handleResetClick'));
        }

        this.log('Event listeners bound');
    }

    /**
     * Enable live preview
     * Stores original CSS and activates preview mode
     * 
     * @returns {void}
     */
    enablePreview() {
        if (this.localState.enabled) {
            this.log('Preview already enabled');
            return;
        }

        this.log('Enabling preview...');

        // Store original CSS before any modifications
        this.storeOriginalCSS();

        // Update state
        this.setState({
            enabled: true,
            active: false,
            error: null
        });

        // Update UI
        this.updateUI();

        // Emit event
        this.emit('preview:enabled');

        this.log('Preview enabled');
    }

    /**
     * Disable live preview
     * Restores original CSS and deactivates preview mode
     * 
     * @returns {void}
     */
    disablePreview() {
        if (!this.localState.enabled) {
            this.log('Preview already disabled');
            return;
        }

        this.log('Disabling preview...');

        // Restore original CSS
        this.restoreOriginalCSS();

        // Cancel any pending preview requests
        this.cancelPendingPreview();

        // Update state
        this.setState({
            enabled: false,
            active: false,
            currentPreviewCSS: null,
            lastSettings: null,
            error: null
        });

        // Update UI
        this.updateUI();

        // Emit event
        this.emit('preview:disabled');

        this.log('Preview disabled');
    }

    /**
     * Update preview with new settings
     * Generates preview CSS via API and applies it
     * 
     * @param {Object} settings - Settings to preview
     * @returns {Promise<void>}
     */
    async updatePreview(settings) {
        if (!this.localState.enabled) {
            this.log('Preview not enabled, skipping update');
            return;
        }

        // Check if settings actually changed
        if (this.settingsEqual(settings, this.localState.lastSettings)) {
            this.log('Settings unchanged, skipping preview update');
            return;
        }

        this.log('Updating preview with settings:', settings);

        // Update state
        this.setState({
            loading: true,
            error: null,
            lastSettings: { ...settings }
        });

        // Update UI to show loading
        this.updateUI();

        try {
            // Cancel any pending preview request
            this.cancelPendingPreview();

            // Generate preview CSS via API
            this.log('Generating preview CSS via API...');
            const response = await this.api.generatePreview(settings);

            // Check if this request was cancelled
            if (this.pendingPreviewRequest === null) {
                this.log('Preview request was cancelled, ignoring response');
                return;
            }

            const previewCSS = response.data?.css || response.css;

            if (!previewCSS) {
                throw new Error('No CSS returned from preview API');
            }

            this.log('Preview CSS generated successfully');

            // Apply preview CSS
            this.applyPreviewCSS(previewCSS);

            // Update state
            this.setState({
                loading: false,
                active: true,
                currentPreviewCSS: previewCSS,
                error: null
            });

            // Update UI
            this.updateUI();

            // Emit success event
            this.emit('preview:updated', { settings, css: previewCSS });

        } catch (error) {
            this.handleError('Failed to update preview', error);

            // Update state with error
            this.setState({
                loading: false,
                error: error.message || 'Preview generation failed'
            });

            // Update UI to show error
            this.updateUI();

            // Fallback to previous state
            this.restoreOriginalCSS();

            // Emit error event
            this.emit('preview:error', { 
                settings, 
                error: error.message || 'Preview generation failed' 
            });

            // Show error notification
            this.emit('notification:show', {
                type: 'warning',
                message: 'Preview update failed. Showing original styles.',
                duration: 3000
            });
        } finally {
            this.pendingPreviewRequest = null;
        }
    }

    /**
     * Apply preview CSS to the page
     * Creates or updates style element with smooth transitions
     * 
     * @param {string} css - CSS to apply
     * @returns {void}
     */
    applyPreviewCSS(css) {
        if (!css) {
            this.log('No CSS to apply');
            return;
        }

        this.log('Applying preview CSS...');

        // Find or create preview style element
        let styleElement = document.getElementById(this.previewStyleId);

        if (!styleElement) {
            styleElement = document.createElement('style');
            styleElement.id = this.previewStyleId;
            styleElement.type = 'text/css';
            
            // Add data attribute for identification
            styleElement.setAttribute('data-mas-preview', 'true');
            
            // Insert at end of head to ensure it overrides other styles
            document.head.appendChild(styleElement);
            
            this.log('Created preview style element');
        }

        // Add transition CSS for smooth changes
        const transitionCSS = this.getTransitionCSS();
        
        // Combine transition CSS with preview CSS
        styleElement.textContent = transitionCSS + '\n\n' + css;

        this.log('Preview CSS applied');
    }

    /**
     * Get transition CSS for smooth preview updates
     * 
     * @returns {string} Transition CSS
     */
    getTransitionCSS() {
        return `
/* Live Preview Transitions */
#adminmenu,
#adminmenu li,
#adminmenu a,
#wpadminbar,
.wp-admin #wpadminbar {
    transition: background-color 0.3s ease, 
                color 0.3s ease, 
                border-color 0.3s ease,
                box-shadow 0.3s ease,
                transform 0.3s ease !important;
}

/* Prevent transition on hover to maintain responsiveness */
#adminmenu a:hover,
#adminmenu li:hover {
    transition-duration: 0.15s !important;
}
        `.trim();
    }

    /**
     * Store original CSS before preview modifications
     * Captures current computed styles for restoration
     * 
     * @returns {void}
     */
    storeOriginalCSS() {
        this.log('Storing original CSS...');

        // Check if already stored
        if (this.localState.originalCSS) {
            this.log('Original CSS already stored');
            return;
        }

        // Get current MAS styles (if any)
        const masStyleElement = document.getElementById('mas-v2-dynamic-styles');
        const originalCSS = masStyleElement ? masStyleElement.textContent : '';

        // Store in state
        this.setState({
            originalCSS: originalCSS
        });

        this.log('Original CSS stored');
    }

    /**
     * Restore original CSS
     * Removes preview styles and restores original state
     * 
     * @returns {void}
     */
    restoreOriginalCSS() {
        this.log('Restoring original CSS...');

        // Remove preview style element
        const previewStyleElement = document.getElementById(this.previewStyleId);
        if (previewStyleElement) {
            previewStyleElement.remove();
            this.log('Preview style element removed');
        }

        // If we have original CSS stored, we could restore it here
        // But typically the original MAS styles remain in place
        // and we just remove the preview overlay

        this.log('Original CSS restored');
    }

    /**
     * Handle field change event from form
     * Triggers debounced preview update
     * 
     * @param {Object} event - Field change event
     * @returns {void}
     */
    handleFieldChange(event) {
        if (!this.localState.enabled) {
            return;
        }

        const { settings } = event.data;

        if (!settings) {
            this.log('No settings in field change event');
            return;
        }

        this.log('Field changed, scheduling preview update');

        // Trigger debounced preview update
        this.debouncedUpdatePreview(settings);
    }

    /**
     * Handle settings saved event
     * Disables preview since settings are now saved
     * 
     * @param {Object} event - Settings saved event
     * @returns {void}
     */
    handleSettingsSaved(event) {
        if (!this.localState.enabled) {
            return;
        }

        this.log('Settings saved, disabling preview');

        // Disable preview
        this.disablePreview();

        // Show notification
        this.emit('notification:show', {
            type: 'info',
            message: 'Preview disabled. Settings have been saved.',
            duration: 2000
        });
    }

    /**
     * Handle rollback event
     * Restores preview if it was active
     * 
     * @param {Object} event - Rollback event
     * @returns {void}
     */
    handleRollback(event) {
        if (!this.localState.enabled || !this.localState.active) {
            return;
        }

        this.log('Settings rolled back, restoring preview');

        // Restore preview with last settings
        if (this.localState.lastSettings) {
            this.updatePreview(this.localState.lastSettings);
        }
    }

    /**
     * Handle preview toggle button click
     * Enables or disables preview mode
     * 
     * @param {Event} e - Click event
     * @returns {void}
     */
    handleToggleClick(e) {
        e.preventDefault();

        if (this.localState.enabled) {
            this.disablePreview();
        } else {
            this.enablePreview();
        }
    }

    /**
     * Handle preview reset button click
     * Resets preview to original state
     * 
     * @param {Event} e - Click event
     * @returns {void}
     */
    handleResetClick(e) {
        e.preventDefault();

        if (!this.localState.enabled) {
            return;
        }

        this.log('Resetting preview...');

        // Restore original CSS
        this.restoreOriginalCSS();

        // Update state
        this.setState({
            active: false,
            currentPreviewCSS: null,
            lastSettings: null,
            error: null
        });

        // Update UI
        this.updateUI();

        // Emit event
        this.emit('preview:reset');

        // Show notification
        this.emit('notification:show', {
            type: 'info',
            message: 'Preview reset to original state.',
            duration: 2000
        });
    }

    /**
     * Cancel pending preview request
     * 
     * @returns {void}
     */
    cancelPendingPreview() {
        if (this.pendingPreviewRequest) {
            this.log('Cancelling pending preview request');
            this.pendingPreviewRequest = null;
            
            // Cancel API request if possible
            if (this.api.cancelRequest) {
                this.api.cancelRequest('POST', '/preview');
            }
        }
    }

    /**
     * Check if two settings objects are equal
     * 
     * @param {Object} settings1 - First settings object
     * @param {Object} settings2 - Second settings object
     * @returns {boolean} Whether settings are equal
     */
    settingsEqual(settings1, settings2) {
        if (!settings1 || !settings2) {
            return false;
        }

        // Simple deep equality check for settings
        const keys1 = Object.keys(settings1).sort();
        const keys2 = Object.keys(settings2).sort();

        if (keys1.length !== keys2.length) {
            return false;
        }

        for (let i = 0; i < keys1.length; i++) {
            if (keys1[i] !== keys2[i]) {
                return false;
            }

            const val1 = settings1[keys1[i]];
            const val2 = settings2[keys1[i]];

            if (val1 !== val2) {
                return false;
            }
        }

        return true;
    }

    /**
     * Update UI based on current state
     * Updates buttons, indicators, and status messages
     * 
     * @returns {void}
     */
    updateUI() {
        // Update toggle button
        const toggleButton = this.$('.mas-preview-toggle');
        if (toggleButton) {
            if (this.localState.enabled) {
                toggleButton.textContent = 'Disable Preview';
                toggleButton.classList.add('active');
            } else {
                toggleButton.textContent = 'Enable Preview';
                toggleButton.classList.remove('active');
            }

            toggleButton.disabled = this.localState.loading;
        }

        // Update reset button
        const resetButton = this.$('.mas-preview-reset');
        if (resetButton) {
            resetButton.disabled = !this.localState.enabled || !this.localState.active;
        }

        // Update status indicator
        const statusIndicator = this.$('.mas-preview-status');
        if (statusIndicator) {
            if (this.localState.loading) {
                statusIndicator.textContent = 'Generating preview...';
                statusIndicator.className = 'mas-preview-status loading';
            } else if (this.localState.error) {
                statusIndicator.textContent = `Error: ${this.localState.error}`;
                statusIndicator.className = 'mas-preview-status error';
            } else if (this.localState.active) {
                statusIndicator.textContent = 'Preview active';
                statusIndicator.className = 'mas-preview-status active';
            } else if (this.localState.enabled) {
                statusIndicator.textContent = 'Preview enabled (make changes to see preview)';
                statusIndicator.className = 'mas-preview-status enabled';
            } else {
                statusIndicator.textContent = 'Preview disabled';
                statusIndicator.className = 'mas-preview-status disabled';
            }
        }

        // Update container class
        if (this.element) {
            if (this.localState.enabled) {
                this.element.classList.add('preview-enabled');
            } else {
                this.element.classList.remove('preview-enabled');
            }

            if (this.localState.active) {
                this.element.classList.add('preview-active');
            } else {
                this.element.classList.remove('preview-active');
            }

            if (this.localState.loading) {
                this.element.classList.add('preview-loading');
            } else {
                this.element.classList.remove('preview-loading');
            }
        }
    }

    /**
     * Destroy component and cleanup
     */
    destroy() {
        this.log('Destroying LivePreviewComponent...');

        // Disable preview and restore original CSS
        if (this.localState.enabled) {
            this.disablePreview();
        }

        // Cancel any pending requests
        this.cancelPendingPreview();

        // Call parent destroy
        super.destroy();

        this.log('LivePreviewComponent destroyed');
    }
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = LivePreviewComponent;
}

// Make available globally for WordPress
if (typeof window !== 'undefined') {
    window.LivePreviewComponent = LivePreviewComponent;
}
