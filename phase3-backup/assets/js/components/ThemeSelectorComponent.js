/**
 * Theme Selector Component
 * 
 * Manages theme selection, preview, and application.
 * Provides UI for browsing predefined themes and custom themes.
 * 
 * @class ThemeSelectorComponent
 * @extends Component
 */
class ThemeSelectorComponent extends Component {
    /**
     * Create theme selector component
     * 
     * @param {HTMLElement} element - DOM element for this component
     * @param {APIClient} apiClient - API client instance
     * @param {StateManager} stateManager - State manager instance
     * @param {EventBus} eventBus - Event bus instance
     */
    constructor(element, apiClient, stateManager, eventBus) {
        super(element, apiClient, stateManager, eventBus);

        // Component name
        this.name = 'ThemeSelectorComponent';

        // Local state
        this.setState({
            themes: [],
            currentTheme: null,
            previewTheme: null,
            loading: false,
            error: null,
            filter: 'all' // 'all', 'predefined', 'custom'
        });
    }

    /**
     * Initialize component
     * 
     * @returns {void}
     */
    init() {
        this.log('Initializing...');

        // Load themes
        this.loadThemes();

        // Call parent init
        super.init();
    }

    /**
     * Render component UI
     * 
     * @returns {void}
     */
    render() {
        if (!this.element) {
            return;
        }

        const state = this.getState();

        // Build HTML
        const html = `
            <div class="mas-theme-selector">
                <div class="mas-theme-selector-header">
                    <h3>Theme Selector</h3>
                    <div class="mas-theme-filter">
                        <button class="mas-filter-btn ${state.filter === 'all' ? 'active' : ''}" data-filter="all">
                            All Themes
                        </button>
                        <button class="mas-filter-btn ${state.filter === 'predefined' ? 'active' : ''}" data-filter="predefined">
                            Predefined
                        </button>
                        <button class="mas-filter-btn ${state.filter === 'custom' ? 'active' : ''}" data-filter="custom">
                            Custom
                        </button>
                    </div>
                </div>

                ${state.loading ? this.renderLoading() : ''}
                ${state.error ? this.renderError() : ''}
                
                <div class="mas-theme-grid">
                    ${this.renderThemes()}
                </div>

                <div class="mas-theme-actions">
                    <button class="mas-btn mas-btn-secondary" id="mas-import-theme">
                        <span class="dashicons dashicons-upload"></span>
                        Import Theme
                    </button>
                    <button class="mas-btn mas-btn-secondary" id="mas-export-theme">
                        <span class="dashicons dashicons-download"></span>
                        Export Current Theme
                    </button>
                </div>
            </div>
        `;

        this.element.innerHTML = html;

        // Re-bind events after render
        this.bindEvents();
    }

    /**
     * Render loading state
     * 
     * @returns {string} Loading HTML
     */
    renderLoading() {
        return `
            <div class="mas-theme-loading">
                <span class="spinner is-active"></span>
                <p>Loading themes...</p>
            </div>
        `;
    }

    /**
     * Render error state
     * 
     * @returns {string} Error HTML
     */
    renderError() {
        const state = this.getState();
        return `
            <div class="mas-theme-error notice notice-error">
                <p>${state.error}</p>
                <button class="button" id="mas-retry-load-themes">Retry</button>
            </div>
        `;
    }

    /**
     * Render themes grid
     * 
     * @returns {string} Themes HTML
     */
    renderThemes() {
        const state = this.getState();
        const filteredThemes = this.getFilteredThemes();

        if (filteredThemes.length === 0) {
            return `
                <div class="mas-theme-empty">
                    <p>No themes found.</p>
                </div>
            `;
        }

        return filteredThemes.map(theme => this.renderThemeCard(theme)).join('');
    }

    /**
     * Render individual theme card
     * 
     * @param {Object} theme - Theme object
     * @returns {string} Theme card HTML
     */
    renderThemeCard(theme) {
        const state = this.getState();
        const isActive = state.currentTheme === theme.id;
        const isPreviewing = state.previewTheme === theme.id;

        return `
            <div class="mas-theme-card ${isActive ? 'active' : ''} ${isPreviewing ? 'previewing' : ''}" 
                 data-theme-id="${theme.id}"
                 data-theme-type="${theme.type || 'predefined'}">
                
                <div class="mas-theme-preview" style="${this.getThemePreviewStyle(theme)}">
                    <div class="mas-theme-preview-menu"></div>
                    <div class="mas-theme-preview-content"></div>
                </div>

                <div class="mas-theme-info">
                    <h4 class="mas-theme-name">${this.escapeHtml(theme.name)}</h4>
                    <p class="mas-theme-type">${theme.type === 'custom' ? 'Custom' : 'Predefined'}</p>
                    
                    ${isActive ? '<span class="mas-theme-badge">Active</span>' : ''}
                </div>

                <div class="mas-theme-actions">
                    ${!isActive ? `
                        <button class="mas-btn mas-btn-primary mas-apply-theme" data-theme-id="${theme.id}">
                            Apply
                        </button>
                    ` : ''}
                    
                    ${theme.type === 'custom' ? `
                        <button class="mas-btn mas-btn-secondary mas-edit-theme" data-theme-id="${theme.id}">
                            Edit
                        </button>
                        <button class="mas-btn mas-btn-danger mas-delete-theme" data-theme-id="${theme.id}">
                            Delete
                        </button>
                    ` : ''}
                </div>
            </div>
        `;
    }

    /**
     * Get theme preview style
     * 
     * @param {Object} theme - Theme object
     * @returns {string} CSS style string
     */
    getThemePreviewStyle(theme) {
        const settings = theme.settings || {};
        return `
            background: ${settings.menu_background || '#1e1e2e'};
            color: ${settings.menu_text_color || '#ffffff'};
        `;
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

        // Filter buttons
        const filterButtons = this.element.querySelectorAll('.mas-filter-btn');
        filterButtons.forEach(btn => {
            this.addEventListener(btn, 'click', this.getBoundMethod('handleFilterChange'));
        });

        // Theme cards - hover for preview
        const themeCards = this.element.querySelectorAll('.mas-theme-card');
        themeCards.forEach(card => {
            this.addEventListener(card, 'mouseenter', this.getBoundMethod('handleThemeHover'));
            this.addEventListener(card, 'mouseleave', this.getBoundMethod('handleThemeLeave'));
        });

        // Apply theme buttons
        const applyButtons = this.element.querySelectorAll('.mas-apply-theme');
        applyButtons.forEach(btn => {
            this.addEventListener(btn, 'click', this.getBoundMethod('handleApplyTheme'));
        });

        // Edit theme buttons
        const editButtons = this.element.querySelectorAll('.mas-edit-theme');
        editButtons.forEach(btn => {
            this.addEventListener(btn, 'click', this.getBoundMethod('handleEditTheme'));
        });

        // Delete theme buttons
        const deleteButtons = this.element.querySelectorAll('.mas-delete-theme');
        deleteButtons.forEach(btn => {
            this.addEventListener(btn, 'click', this.getBoundMethod('handleDeleteTheme'));
        });

        // Import/Export buttons
        const importBtn = this.element.querySelector('#mas-import-theme');
        if (importBtn) {
            this.addEventListener(importBtn, 'click', this.getBoundMethod('handleImportTheme'));
        }

        const exportBtn = this.element.querySelector('#mas-export-theme');
        if (exportBtn) {
            this.addEventListener(exportBtn, 'click', this.getBoundMethod('handleExportTheme'));
        }

        // Retry button
        const retryBtn = this.element.querySelector('#mas-retry-load-themes');
        if (retryBtn) {
            this.addEventListener(retryBtn, 'click', this.getBoundMethod('loadThemes'));
        }

        // Subscribe to global events
        this.subscribe('theme:applied', this.getBoundMethod('handleThemeApplied'));
        this.subscribe('settings:saved', this.getBoundMethod('handleSettingsSaved'));
    }

    /**
     * Load themes from API
     * 
     * @returns {Promise<void>}
     */
    async loadThemes() {
        this.setState({ loading: true, error: null });

        try {
            this.log('Loading themes...');

            const response = await this.api.getThemes();
            const themes = response.data || response;

            // Get current theme from settings
            const settings = this.state.get('settings') || {};
            const currentTheme = settings.current_theme || 'default';

            this.setState({
                themes: themes,
                currentTheme: currentTheme,
                loading: false
            });

            this.log('Themes loaded:', themes.length);

            // Emit event
            this.emit('themes:loaded', { themes });

        } catch (error) {
            this.handleError('Failed to load themes', error);
            this.setState({
                loading: false,
                error: 'Failed to load themes. Please try again.'
            });
        }
    }

    /**
     * Get filtered themes based on current filter
     * 
     * @returns {Array} Filtered themes
     */
    getFilteredThemes() {
        const state = this.getState();
        const { themes, filter } = state;

        if (filter === 'all') {
            return themes;
        }

        return themes.filter(theme => theme.type === filter);
    }

    /**
     * Handle filter change
     * 
     * @param {Event} event - Click event
     * @returns {void}
     */
    handleFilterChange(event) {
        const filter = event.target.dataset.filter;
        this.log('Filter changed:', filter);

        this.setState({ filter });
    }

    /**
     * Handle theme hover (preview)
     * 
     * @param {Event} event - Mouse enter event
     * @returns {void}
     */
    handleThemeHover(event) {
        const card = event.currentTarget;
        const themeId = card.dataset.themeId;

        this.log('Theme hover:', themeId);

        // Set preview theme
        this.setState({ previewTheme: themeId });

        // Emit preview event
        this.emit('theme:preview', { themeId });
    }

    /**
     * Handle theme leave (clear preview)
     * 
     * @param {Event} event - Mouse leave event
     * @returns {void}
     */
    handleThemeLeave(event) {
        this.log('Theme leave');

        // Clear preview
        this.setState({ previewTheme: null });

        // Emit clear preview event
        this.emit('theme:preview:clear');
    }

    /**
     * Handle apply theme
     * 
     * @param {Event} event - Click event
     * @returns {Promise<void>}
     */
    async handleApplyTheme(event) {
        event.preventDefault();
        event.stopPropagation();

        const themeId = event.target.dataset.themeId;
        this.log('Applying theme:', themeId);

        try {
            // Disable button
            event.target.disabled = true;
            event.target.textContent = 'Applying...';

            // Apply theme via API
            const response = await this.api.applyTheme(themeId);

            this.log('Theme applied:', response);

            // Update current theme
            this.setState({ currentTheme: themeId });

            // Update global state
            if (response.data && response.data.settings) {
                this.state.setState({ settings: response.data.settings });
            }

            // Emit event
            this.emit('theme:applied', { themeId, settings: response.data?.settings });

            // Show success notification
            this.emit('notification:show', {
                type: 'success',
                message: `Theme "${this.getThemeName(themeId)}" applied successfully!`,
                duration: 3000
            });

        } catch (error) {
            this.handleError('Failed to apply theme', error);

            // Show error notification
            this.emit('notification:show', {
                type: 'error',
                message: 'Failed to apply theme. Please try again.',
                duration: 5000
            });

        } finally {
            // Re-enable button
            event.target.disabled = false;
            event.target.textContent = 'Apply';
        }
    }

    /**
     * Handle edit theme
     * 
     * @param {Event} event - Click event
     * @returns {void}
     */
    handleEditTheme(event) {
        event.preventDefault();
        event.stopPropagation();

        const themeId = event.target.dataset.themeId;
        this.log('Editing theme:', themeId);

        // Emit event for other components to handle
        this.emit('theme:edit', { themeId });

        // Show notification
        this.emit('notification:show', {
            type: 'info',
            message: 'Theme editing is not yet implemented.',
            duration: 3000
        });
    }

    /**
     * Handle delete theme
     * 
     * @param {Event} event - Click event
     * @returns {Promise<void>}
     */
    async handleDeleteTheme(event) {
        event.preventDefault();
        event.stopPropagation();

        const themeId = event.target.dataset.themeId;
        const themeName = this.getThemeName(themeId);

        // Confirm deletion
        if (!confirm(`Are you sure you want to delete the theme "${themeName}"?`)) {
            return;
        }

        this.log('Deleting theme:', themeId);

        try {
            // Delete theme via API
            await this.api.request('DELETE', `/themes/${themeId}`);

            this.log('Theme deleted:', themeId);

            // Remove from local state
            const state = this.getState();
            const themes = state.themes.filter(t => t.id !== themeId);
            this.setState({ themes });

            // Emit event
            this.emit('theme:deleted', { themeId });

            // Show success notification
            this.emit('notification:show', {
                type: 'success',
                message: `Theme "${themeName}" deleted successfully!`,
                duration: 3000
            });

        } catch (error) {
            this.handleError('Failed to delete theme', error);

            // Show error notification
            this.emit('notification:show', {
                type: 'error',
                message: 'Failed to delete theme. Please try again.',
                duration: 5000
            });
        }
    }

    /**
     * Handle import theme
     * 
     * @param {Event} event - Click event
     * @returns {void}
     */
    handleImportTheme(event) {
        event.preventDefault();

        this.log('Import theme clicked');

        // Create file input
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = '.json';

        input.onchange = async (e) => {
            const file = e.target.files[0];
            if (!file) return;

            try {
                const text = await file.text();
                const themeData = JSON.parse(text);

                this.log('Theme data loaded:', themeData);

                // Import theme via API
                const response = await this.api.request('POST', '/themes', themeData);

                this.log('Theme imported:', response);

                // Reload themes
                await this.loadThemes();

                // Show success notification
                this.emit('notification:show', {
                    type: 'success',
                    message: 'Theme imported successfully!',
                    duration: 3000
                });

            } catch (error) {
                this.handleError('Failed to import theme', error);

                // Show error notification
                this.emit('notification:show', {
                    type: 'error',
                    message: 'Failed to import theme. Please check the file format.',
                    duration: 5000
                });
            }
        };

        input.click();
    }

    /**
     * Handle export theme
     * 
     * @param {Event} event - Click event
     * @returns {void}
     */
    handleExportTheme(event) {
        event.preventDefault();

        this.log('Export theme clicked');

        const state = this.getState();
        const currentTheme = state.themes.find(t => t.id === state.currentTheme);

        if (!currentTheme) {
            this.emit('notification:show', {
                type: 'error',
                message: 'No theme selected to export.',
                duration: 3000
            });
            return;
        }

        // Create download
        const dataStr = JSON.stringify(currentTheme, null, 2);
        const dataBlob = new Blob([dataStr], { type: 'application/json' });
        const url = URL.createObjectURL(dataBlob);

        const link = document.createElement('a');
        link.href = url;
        link.download = `mas-theme-${currentTheme.id}.json`;
        link.click();

        URL.revokeObjectURL(url);

        this.log('Theme exported:', currentTheme.id);

        // Show success notification
        this.emit('notification:show', {
            type: 'success',
            message: 'Theme exported successfully!',
            duration: 3000
        });
    }

    /**
     * Handle theme applied event
     * 
     * @param {Object} data - Event data
     * @returns {void}
     */
    handleThemeApplied(data) {
        this.log('Theme applied event received:', data);

        // Update current theme
        if (data.themeId) {
            this.setState({ currentTheme: data.themeId });
        }
    }

    /**
     * Handle settings saved event
     * 
     * @param {Object} data - Event data
     * @returns {void}
     */
    handleSettingsSaved(data) {
        this.log('Settings saved event received');

        // Update current theme if changed
        if (data.settings && data.settings.current_theme) {
            this.setState({ currentTheme: data.settings.current_theme });
        }
    }

    /**
     * Get theme name by ID
     * 
     * @param {string} themeId - Theme ID
     * @returns {string} Theme name
     */
    getThemeName(themeId) {
        const state = this.getState();
        const theme = state.themes.find(t => t.id === themeId);
        return theme ? theme.name : themeId;
    }

    /**
     * Escape HTML
     * 
     * @param {string} text - Text to escape
     * @returns {string} Escaped text
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ThemeSelectorComponent;
}

// Make available globally
if (typeof window !== 'undefined') {
    window.ThemeSelectorComponent = ThemeSelectorComponent;
}
