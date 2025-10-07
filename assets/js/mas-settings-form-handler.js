/**
 * Modern Admin Styler V2 - Unified Settings Form Handler
 * 
 * Uses REST API by default with graceful fallback to AJAX.
 * Replaces both admin-settings-simple.js and SettingsManager.js
 * to eliminate dual handler conflicts.
 * 
 * @package ModernAdminStylerV2
 * @since 2.2.0
 */

(function(window, $) {
    'use strict';
    
    /**
     * MAS Settings Form Handler
     * 
     * Handles form submission using REST API with AJAX fallback
     */
    class MASSettingsFormHandler {
        /**
         * Constructor
         */
        constructor() {
            this.form = null;
            this.submitButton = null;
            this.client = null;
            this.useRest = false;
            this.isSubmitting = false;
            
            // Configuration
            this.config = {
                formSelector: '#mas-v2-settings-form',
                submitButtonSelector: 'button[type="submit"]',
                resetButtonSelector: '.mas-reset-settings',
                ajaxUrl: window.masV2Global?.ajaxUrl || window.ajaxurl || '/wp-admin/admin-ajax.php',
                ajaxNonce: window.masV2Global?.nonce || '',
                debug: window.masV2Global?.debug_mode || false
            };
            
            this.log('Initializing...');
            this.init();
        }
        
        /**
         * Initialize handler
         */
        init() {
            // Wait for DOM ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => this.setup());
            } else {
                this.setup();
            }
        }
        
        /**
         * Setup form handler
         */
        setup() {
            // Find form
            this.form = document.querySelector(this.config.formSelector);
            if (!this.form) {
                this.log('Form not found:', this.config.formSelector);
                return;
            }
            
            this.log('Form found');
            
            // Find submit button
            this.submitButton = this.form.querySelector(this.config.submitButtonSelector);
            
            // Initialize client (REST or AJAX)
            this.initializeClient();
            
            // Remove any existing handlers to prevent conflicts
            this.removeExistingHandlers();
            
            // Attach our handler
            this.attachHandlers();
            
            this.log('Setup complete', {
                useRest: this.useRest,
                hasClient: !!this.client
            });
        }
        
        /**
         * Initialize REST or AJAX client
         */
        initializeClient() {
            // Try to use REST API if available
            if (this.isRestAvailable()) {
                try {
                    this.client = new window.MASRestClient({
                        debug: this.config.debug
                    });
                    this.useRest = true;
                    this.log('Using REST API');
                } catch (error) {
                    this.log('Failed to initialize REST client:', error);
                    this.useRest = false;
                }
            } else {
                this.log('REST API not available, using AJAX');
                this.useRest = false;
            }
        }
        
        /**
         * Check if REST API is available
         * 
         * @returns {boolean}
         */
        isRestAvailable() {
            return !!(
                window.wpApiSettings &&
                window.wpApiSettings.root &&
                window.wpApiSettings.nonce &&
                window.MASRestClient
            );
        }
        
        /**
         * Remove existing form handlers to prevent conflicts
         */
        removeExistingHandlers() {
            // Remove jQuery handlers if jQuery is available
            if (typeof $ !== 'undefined' && $.fn) {
                $(this.form).off('submit');
                this.log('Removed jQuery submit handlers');
            }
            
            // Clone and replace form to remove all event listeners
            // This is a nuclear option but ensures no conflicts
            const newForm = this.form.cloneNode(true);
            this.form.parentNode.replaceChild(newForm, this.form);
            this.form = newForm;
            
            // Re-find submit button in new form
            this.submitButton = this.form.querySelector(this.config.submitButtonSelector);
            
            this.log('Removed all existing handlers');
        }
        
        /**
         * Attach event handlers
         */
        attachHandlers() {
            // Form submit
            this.form.addEventListener('submit', (e) => this.handleSubmit(e));
            
            // Reset button
            const resetButton = document.querySelector(this.config.resetButtonSelector);
            if (resetButton) {
                resetButton.addEventListener('click', (e) => this.handleReset(e));
            }
            
            // Tab switching
            const tabButtons = document.querySelectorAll('.mas-tab-button');
            tabButtons.forEach(button => {
                button.addEventListener('click', (e) => this.handleTabSwitch(e));
            });
            
            this.log('Event handlers attached');
        }
        
        /**
         * Handle form submission
         * 
         * @param {Event} e Submit event
         */
        async handleSubmit(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Prevent double submission
            if (this.isSubmitting) {
                this.log('Already submitting, ignoring');
                return;
            }
            
            this.isSubmitting = true;
            this.setLoadingState(true);
            
            try {
                // Collect ALL form data
                const settings = this.collectFormData();
                
                this.log('Submitting settings:', {
                    fieldCount: Object.keys(settings).length,
                    useRest: this.useRest,
                    fields: Object.keys(settings)
                });
                
                // Submit via REST or AJAX
                let result;
                if (this.useRest && this.client) {
                    try {
                        result = await this.submitViaRest(settings);
                    } catch (error) {
                        this.log('REST failed, falling back to AJAX:', error);
                        this.showWarning('REST API unavailable, using fallback method');
                        result = await this.submitViaAjax(settings);
                    }
                } else {
                    result = await this.submitViaAjax(settings);
                }
                
                // Handle success
                this.handleSuccess(result);
                
            } catch (error) {
                // Handle error
                this.handleError(error);
            } finally {
                this.isSubmitting = false;
                this.setLoadingState(false);
            }
        }
        
        /**
         * Collect ALL form data including checkboxes
         * 
         * @returns {Object} Form data as object
         */
        collectFormData() {
            const formData = new FormData(this.form);
            const settings = {};
            
            // Get all form fields
            for (const [key, value] of formData.entries()) {
                settings[key] = value;
            }
            
            // CRITICAL: Add unchecked checkboxes as false/0
            // FormData only includes checked checkboxes
            const checkboxes = this.form.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                const name = checkbox.getAttribute('name');
                if (name && !settings.hasOwnProperty(name)) {
                    settings[name] = '0'; // Unchecked = 0
                }
            });
            
            // Remove non-setting fields
            delete settings.action;
            delete settings.nonce;
            delete settings._wpnonce;
            delete settings._wp_http_referer;
            
            return settings;
        }
        
        /**
         * Submit via REST API
         * 
         * @param {Object} settings Settings data
         * @returns {Promise<Object>} Result
         */
        async submitViaRest(settings) {
            this.log('Submitting via REST API');
            
            const result = await this.client.saveSettings(settings);
            
            this.log('REST API success:', result);
            
            return {
                success: true,
                data: result,
                method: 'REST'
            };
        }
        
        /**
         * Submit via AJAX fallback
         * 
         * @param {Object} settings Settings data
         * @returns {Promise<Object>} Result
         */
        async submitViaAjax(settings) {
            this.log('Submitting via AJAX');
            
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: this.config.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'mas_v2_save_settings',
                        nonce: this.config.ajaxNonce,
                        ...settings
                    },
                    success: (response) => {
                        this.log('AJAX success:', response);
                        
                        if (response.success) {
                            resolve({
                                success: true,
                                data: response.data,
                                method: 'AJAX'
                            });
                        } else {
                            reject(new Error(response.data?.message || 'Save failed'));
                        }
                    },
                    error: (xhr, status, error) => {
                        this.log('AJAX error:', { xhr, status, error });
                        reject(new Error(error || 'Network error'));
                    }
                });
            });
        }
        
        /**
         * Handle successful submission
         * 
         * @param {Object} result Result data
         */
        handleSuccess(result) {
            this.log('Save successful:', result);
            
            // Show success message
            let message = '✅ Settings saved successfully!';
            if (result.data?.settings_count) {
                message += ` (${result.data.settings_count} settings)`;
            }
            if (result.method) {
                message += ` [${result.method}]`;
            }
            
            this.showSuccess(message);
            
            // Update button text temporarily
            if (this.submitButton) {
                const originalText = this.submitButton.textContent;
                this.submitButton.textContent = '✓ Saved!';
                setTimeout(() => {
                    this.submitButton.textContent = originalText;
                }, 2000);
            }
            
            // Dispatch custom event
            this.dispatchEvent('mas-settings-saved', result);
        }
        
        /**
         * Handle submission error
         * 
         * @param {Error} error Error object
         */
        handleError(error) {
            this.log('Save failed:', error);
            
            // Build error message
            let message = '❌ Error saving settings: ';
            
            if (error.getUserMessage && typeof error.getUserMessage === 'function') {
                message += error.getUserMessage();
            } else if (error.message) {
                message += error.message;
            } else {
                message += 'Unknown error';
            }
            
            this.showError(message);
            
            // Dispatch custom event
            this.dispatchEvent('mas-settings-error', { error });
        }
        
        /**
         * Handle reset button click
         * 
         * @param {Event} e Click event
         */
        async handleReset(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to reset all settings to defaults? This cannot be undone.')) {
                return;
            }
            
            this.setLoadingState(true, 'Resetting...');
            
            try {
                let result;
                
                if (this.useRest && this.client) {
                    try {
                        result = await this.client.resetSettings();
                    } catch (error) {
                        this.log('REST reset failed, falling back to AJAX:', error);
                        result = await this.resetViaAjax();
                    }
                } else {
                    result = await this.resetViaAjax();
                }
                
                this.showSuccess('✅ Settings reset successfully! Reloading...');
                
                // Reload page after short delay
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
                
            } catch (error) {
                this.handleError(error);
                this.setLoadingState(false);
            }
        }
        
        /**
         * Reset via AJAX
         * 
         * @returns {Promise<Object>} Result
         */
        async resetViaAjax() {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: this.config.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'mas_v2_reset_settings',
                        nonce: this.config.ajaxNonce
                    },
                    success: (response) => {
                        if (response.success) {
                            resolve(response.data);
                        } else {
                            reject(new Error(response.data?.message || 'Reset failed'));
                        }
                    },
                    error: (xhr, status, error) => {
                        reject(new Error(error || 'Network error'));
                    }
                });
            });
        }
        
        /**
         * Handle tab switching
         * 
         * @param {Event} e Click event
         */
        handleTabSwitch(e) {
            e.preventDefault();
            
            const button = e.currentTarget;
            const tab = button.getAttribute('data-tab');
            
            if (!tab) return;
            
            // Hide all tabs
            document.querySelectorAll('.mas-tab-content').forEach(content => {
                content.style.display = 'none';
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.mas-tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            const tabContent = document.getElementById('mas-tab-' + tab);
            if (tabContent) {
                tabContent.style.display = 'block';
            }
            
            // Add active class to button
            button.classList.add('active');
        }
        
        /**
         * Set loading state
         * 
         * @param {boolean} isLoading Loading state
         * @param {string} text Optional button text
         */
        setLoadingState(isLoading, text = null) {
            if (!this.submitButton) return;
            
            if (isLoading) {
                this.submitButton.disabled = true;
                this.submitButton.classList.add('loading');
                if (text) {
                    this.submitButton.textContent = text;
                } else {
                    this.submitButton.textContent = 'Saving...';
                }
            } else {
                this.submitButton.disabled = false;
                this.submitButton.classList.remove('loading');
                this.submitButton.textContent = 'Save Settings';
            }
        }
        
        /**
         * Show success notification
         * 
         * @param {string} message Message text
         */
        showSuccess(message) {
            this.showNotification(message, 'success');
        }
        
        /**
         * Show error notification
         * 
         * @param {string} message Message text
         */
        showError(message) {
            this.showNotification(message, 'error');
        }
        
        /**
         * Show warning notification
         * 
         * @param {string} message Message text
         */
        showWarning(message) {
            this.showNotification(message, 'warning');
        }
        
        /**
         * Show notification
         * 
         * @param {string} message Message text
         * @param {string} type Notification type (success, error, warning, info)
         */
        showNotification(message, type = 'info') {
            // Try to use WordPress admin notices
            const noticesContainer = document.querySelector('.wrap h1');
            
            if (noticesContainer) {
                const notice = document.createElement('div');
                notice.className = `notice notice-${type} is-dismissible`;
                notice.innerHTML = `<p>${message}</p>`;
                
                // Insert after h1
                noticesContainer.parentNode.insertBefore(notice, noticesContainer.nextSibling);
                
                // Auto-dismiss after 5 seconds
                setTimeout(() => {
                    notice.style.opacity = '0';
                    setTimeout(() => notice.remove(), 300);
                }, 5000);
            } else {
                // Fallback to alert
                if (type === 'error') {
                    alert(message);
                } else {
                    console.log(`[MAS] ${message}`);
                }
            }
        }
        
        /**
         * Dispatch custom event
         * 
         * @param {string} eventName Event name
         * @param {Object} detail Event detail
         */
        dispatchEvent(eventName, detail = {}) {
            const event = new CustomEvent(eventName, {
                detail: {
                    ...detail,
                    timestamp: Date.now()
                },
                bubbles: true,
                cancelable: true
            });
            
            document.dispatchEvent(event);
            this.log('Event dispatched:', eventName, detail);
        }
        
        /**
         * Log message (if debug enabled)
         * 
         * @param {...any} args Arguments to log
         */
        log(...args) {
            if (this.config.debug) {
                console.log('[MAS Form Handler]', ...args);
            }
        }
    }
    
    // Initialize when script loads
    if (typeof $ !== 'undefined') {
        window.masFormHandler = new MASSettingsFormHandler();
    } else {
        console.error('[MAS Form Handler] jQuery not available');
    }
    
})(window, jQuery);
