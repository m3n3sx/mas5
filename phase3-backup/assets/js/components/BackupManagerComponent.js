/**
 * Backup Manager Component
 * 
 * Manages backup creation, restoration, and deletion.
 * Implements virtual scrolling for large backup lists.
 * 
 * @class BackupManagerComponent
 * @extends Component
 */
class BackupManagerComponent extends Component {
    /**
     * Create backup manager component
     * 
     * @param {HTMLElement} element - DOM element for this component
     * @param {APIClient} apiClient - API client instance
     * @param {StateManager} stateManager - State manager instance
     * @param {EventBus} eventBus - Event bus instance
     */
    constructor(element, apiClient, stateManager, eventBus) {
        super(element, apiClient, stateManager, eventBus);

        // Component name
        this.name = 'BackupManagerComponent';

        // Virtual list instance
        this.virtualList = null;

        // Local state
        this.setState({
            backups: [],
            loading: false,
            error: null,
            creating: false,
            restoring: null, // ID of backup being restored
            deleting: null, // ID of backup being deleted
            sortBy: 'date', // 'date', 'type'
            sortOrder: 'desc' // 'asc', 'desc'
        });
    }

    /**
     * Initialize component
     * 
     * @returns {void}
     */
    init() {
        this.log('Initializing...');

        // Load backups
        this.loadBackups();

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
            <div class="mas-backup-manager">
                <div class="mas-backup-header">
                    <h3>Backup Manager</h3>
                    <button class="mas-btn mas-btn-primary" id="mas-create-backup" ${state.creating ? 'disabled' : ''}>
                        <span class="dashicons dashicons-backup"></span>
                        ${state.creating ? 'Creating...' : 'Create Backup'}
                    </button>
                </div>

                <div class="mas-backup-controls">
                    <div class="mas-backup-sort">
                        <label>Sort by:</label>
                        <select id="mas-backup-sort">
                            <option value="date" ${state.sortBy === 'date' ? 'selected' : ''}>Date</option>
                            <option value="type" ${state.sortBy === 'type' ? 'selected' : ''}>Type</option>
                        </select>
                        <button class="mas-btn-icon" id="mas-toggle-sort-order" title="Toggle sort order">
                            <span class="dashicons dashicons-sort"></span>
                        </button>
                    </div>
                    <div class="mas-backup-info">
                        <span>${state.backups.length} backup${state.backups.length !== 1 ? 's' : ''}</span>
                    </div>
                </div>

                ${state.loading ? this.renderLoading() : ''}
                ${state.error ? this.renderError() : ''}
                
                ${state.backups.length > 0 ? this.renderBackupList() : this.renderEmpty()}
            </div>
        `;

        this.element.innerHTML = html;

        // Setup virtual scrolling if needed (for large lists)
        if (state.backups.length > 20 && typeof VirtualList !== 'undefined') {
            this.setupVirtualScrolling();
        }

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
            <div class="mas-backup-loading">
                <span class="spinner is-active"></span>
                <p>Loading backups...</p>
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
            <div class="mas-backup-error notice notice-error">
                <p>${state.error}</p>
                <button class="button" id="mas-retry-load-backups">Retry</button>
            </div>
        `;
    }

    /**
     * Render empty state
     * 
     * @returns {string} Empty HTML
     */
    renderEmpty() {
        return `
            <div class="mas-backup-empty">
                <span class="dashicons dashicons-backup"></span>
                <p>No backups found.</p>
                <p class="description">Create your first backup to get started.</p>
            </div>
        `;
    }

    /**
     * Render backup list
     * 
     * @returns {string} Backup list HTML
     */
    renderBackupList() {
        const state = this.getState();
        const sortedBackups = this.getSortedBackups();

        // Use virtual scrolling for large lists
        if (sortedBackups.length > this.virtualScroll.visibleItems) {
            return this.renderVirtualList(sortedBackups);
        }

        // Regular rendering for small lists
        return `
            <div class="mas-backup-list">
                ${sortedBackups.map(backup => this.renderBackupItem(backup)).join('')}
            </div>
        `;
    }

    /**
     * Render virtual list with scrolling
     * 
     * @param {Array} backups - Sorted backups
     * @returns {string} Virtual list HTML
     */
    renderVirtualList(backups) {
        const { itemHeight, visibleItems, buffer } = this.virtualScroll;
        const totalHeight = backups.length * itemHeight;
        const viewportHeight = visibleItems * itemHeight;

        // Calculate visible range
        const startIndex = Math.max(0, this.virtualScroll.startIndex - buffer);
        const endIndex = Math.min(backups.length, this.virtualScroll.endIndex + buffer);
        const visibleBackups = backups.slice(startIndex, endIndex);

        // Calculate offset for positioning
        const offsetY = startIndex * itemHeight;

        return `
            <div class="mas-backup-list-container" style="height: ${viewportHeight}px; overflow-y: auto;">
                <div class="mas-backup-list-spacer" style="height: ${totalHeight}px; position: relative;">
                    <div class="mas-backup-list" style="transform: translateY(${offsetY}px);">
                        ${visibleBackups.map(backup => this.renderBackupItem(backup)).join('')}
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Render individual backup item
     * 
     * @param {Object} backup - Backup object
     * @returns {string} Backup item HTML
     */
    renderBackupItem(backup) {
        const state = this.getState();
        const isRestoring = state.restoring === backup.id;
        const isDeleting = state.deleting === backup.id;

        return `
            <div class="mas-backup-item" data-backup-id="${backup.id}">
                <div class="mas-backup-icon">
                    <span class="dashicons dashicons-${backup.type === 'automatic' ? 'backup' : 'admin-generic'}"></span>
                </div>
                
                <div class="mas-backup-details">
                    <div class="mas-backup-date">
                        ${this.formatDate(backup.timestamp)}
                    </div>
                    <div class="mas-backup-meta">
                        <span class="mas-backup-type ${backup.type}">${backup.type}</span>
                        ${backup.metadata?.note ? `<span class="mas-backup-note">${this.escapeHtml(backup.metadata.note)}</span>` : ''}
                    </div>
                    ${backup.metadata ? `
                        <div class="mas-backup-info-small">
                            Plugin v${backup.metadata.plugin_version || 'unknown'} | 
                            WP ${backup.metadata.wordpress_version || 'unknown'}
                        </div>
                    ` : ''}
                </div>

                <div class="mas-backup-actions">
                    <button class="mas-btn mas-btn-secondary mas-restore-backup" 
                            data-backup-id="${backup.id}"
                            ${isRestoring || isDeleting ? 'disabled' : ''}>
                        ${isRestoring ? 'Restoring...' : 'Restore'}
                    </button>
                    
                    <button class="mas-btn mas-btn-secondary mas-download-backup" 
                            data-backup-id="${backup.id}"
                            ${isRestoring || isDeleting ? 'disabled' : ''}>
                        <span class="dashicons dashicons-download"></span>
                    </button>
                    
                    <button class="mas-btn mas-btn-danger mas-delete-backup" 
                            data-backup-id="${backup.id}"
                            ${isRestoring || isDeleting ? 'disabled' : ''}>
                        ${isDeleting ? 'Deleting...' : 'Delete'}
                    </button>
                </div>
            </div>
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

        // Create backup button
        const createBtn = this.element.querySelector('#mas-create-backup');
        if (createBtn) {
            this.addEventListener(createBtn, 'click', this.getBoundMethod('handleCreateBackup'));
        }

        // Sort controls
        const sortSelect = this.element.querySelector('#mas-backup-sort');
        if (sortSelect) {
            this.addEventListener(sortSelect, 'change', this.getBoundMethod('handleSortChange'));
        }

        const sortOrderBtn = this.element.querySelector('#mas-toggle-sort-order');
        if (sortOrderBtn) {
            this.addEventListener(sortOrderBtn, 'click', this.getBoundMethod('handleToggleSortOrder'));
        }

        // Restore buttons
        const restoreButtons = this.element.querySelectorAll('.mas-restore-backup');
        restoreButtons.forEach(btn => {
            this.addEventListener(btn, 'click', this.getBoundMethod('handleRestoreBackup'));
        });

        // Download buttons
        const downloadButtons = this.element.querySelectorAll('.mas-download-backup');
        downloadButtons.forEach(btn => {
            this.addEventListener(btn, 'click', this.getBoundMethod('handleDownloadBackup'));
        });

        // Delete buttons
        const deleteButtons = this.element.querySelectorAll('.mas-delete-backup');
        deleteButtons.forEach(btn => {
            this.addEventListener(btn, 'click', this.getBoundMethod('handleDeleteBackup'));
        });

        // Retry button
        const retryBtn = this.element.querySelector('#mas-retry-load-backups');
        if (retryBtn) {
            this.addEventListener(retryBtn, 'click', this.getBoundMethod('loadBackups'));
        }

        // Virtual scrolling
        const listContainer = this.element.querySelector('.mas-backup-list-container');
        if (listContainer) {
            this.addEventListener(
                listContainer, 
                'scroll', 
                this.throttle(this.getBoundMethod('handleScroll'), 100)
            );
        }

        // Subscribe to global events
        this.subscribe('backup:created', this.getBoundMethod('handleBackupCreated'));
        this.subscribe('backup:restored', this.getBoundMethod('handleBackupRestored'));
    }

    /**
     * Setup virtual scrolling using VirtualList utility
     * 
     * @returns {void}
     */
    setupVirtualScrolling() {
        const state = this.getState();
        
        // Check if VirtualList is available
        if (typeof VirtualList === 'undefined') {
            this.log('VirtualList not available, using regular rendering');
            return;
        }

        // Find or create container for virtual list
        let container = this.element.querySelector('.mas-backup-list-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'mas-backup-list-container';
            container.style.height = '500px'; // Default height
            
            const listPlaceholder = this.element.querySelector('.mas-backup-list');
            if (listPlaceholder && listPlaceholder.parentNode) {
                listPlaceholder.parentNode.replaceChild(container, listPlaceholder);
            }
        }

        // Destroy existing virtual list if present
        if (this.virtualList) {
            this.virtualList.destroy();
        }

        // Create new virtual list
        this.virtualList = new VirtualList(container, {
            itemHeight: 80,
            buffer: 5,
            renderItem: (backup, index) => this.renderBackupItemElement(backup, index),
            onScroll: (scrollInfo) => {
                this.emit('backups:scroll', scrollInfo);
            }
        });

        // Set items
        const sortedBackups = this.getSortedBackups();
        this.virtualList.setItems(sortedBackups);

        this.log('Virtual scrolling enabled for', state.backups.length, 'items');
    }

    /**
     * Render backup item as DOM element for VirtualList
     * 
     * @param {Object} backup - Backup object
     * @param {number} index - Item index
     * @returns {HTMLElement} Backup item element
     */
    renderBackupItemElement(backup, index) {
        const state = this.getState();
        const isRestoring = state.restoring === backup.id;
        const isDeleting = state.deleting === backup.id;

        const div = document.createElement('div');
        div.className = 'mas-backup-item';
        div.dataset.backupId = backup.id;
        div.innerHTML = `
            <div class="mas-backup-icon">
                <span class="dashicons dashicons-${backup.type === 'automatic' ? 'backup' : 'admin-generic'}"></span>
            </div>
            
            <div class="mas-backup-details">
                <div class="mas-backup-date">
                    ${this.formatDate(backup.timestamp)}
                </div>
                <div class="mas-backup-meta">
                    <span class="mas-backup-type ${backup.type}">${backup.type}</span>
                    ${backup.metadata?.note ? `<span class="mas-backup-note">${this.escapeHtml(backup.metadata.note)}</span>` : ''}
                </div>
                ${backup.metadata ? `
                    <div class="mas-backup-info-small">
                        Plugin v${backup.metadata.plugin_version || 'unknown'} | 
                        WP ${backup.metadata.wordpress_version || 'unknown'}
                    </div>
                ` : ''}
            </div>

            <div class="mas-backup-actions">
                <button class="mas-btn mas-btn-secondary mas-restore-backup" 
                        data-backup-id="${backup.id}"
                        ${isRestoring || isDeleting ? 'disabled' : ''}>
                    ${isRestoring ? 'Restoring...' : 'Restore'}
                </button>
                
                <button class="mas-btn mas-btn-secondary mas-download-backup" 
                        data-backup-id="${backup.id}"
                        ${isDeleting ? 'disabled' : ''}>
                    Download
                </button>
                
                <button class="mas-btn mas-btn-danger mas-delete-backup" 
                        data-backup-id="${backup.id}"
                        ${isRestoring || isDeleting ? 'disabled' : ''}>
                    ${isDeleting ? 'Deleting...' : 'Delete'}
                </button>
            </div>
        `;

        return div;
    }

    /**
     * Load backups from API
     * 
     * @returns {Promise<void>}
     */
    async loadBackups() {
        this.setState({ loading: true, error: null });

        try {
            this.log('Loading backups...');

            const response = await this.api.listBackups();
            const backups = response.data || response;

            this.setState({
                backups: backups,
                loading: false
            });

            this.log('Backups loaded:', backups.length);

            // Emit event
            this.emit('backups:loaded', { backups });

        } catch (error) {
            this.handleError('Failed to load backups', error);
            this.setState({
                loading: false,
                error: 'Failed to load backups. Please try again.'
            });
        }
    }

    /**
     * Get sorted backups
     * 
     * @returns {Array} Sorted backups
     */
    getSortedBackups() {
        const state = this.getState();
        const { backups, sortBy, sortOrder } = state;

        const sorted = [...backups].sort((a, b) => {
            let comparison = 0;

            if (sortBy === 'date') {
                comparison = a.timestamp - b.timestamp;
            } else if (sortBy === 'type') {
                comparison = a.type.localeCompare(b.type);
            }

            return sortOrder === 'asc' ? comparison : -comparison;
        });

        return sorted;
    }

    /**
     * Handle sort change
     * 
     * @param {Event} event - Change event
     * @returns {void}
     */
    handleSortChange(event) {
        const sortBy = event.target.value;
        this.log('Sort changed:', sortBy);

        this.setState({ sortBy });
    }

    /**
     * Handle toggle sort order
     * 
     * @param {Event} event - Click event
     * @returns {void}
     */
    handleToggleSortOrder(event) {
        event.preventDefault();

        const state = this.getState();
        const sortOrder = state.sortOrder === 'asc' ? 'desc' : 'asc';

        this.log('Sort order toggled:', sortOrder);

        this.setState({ sortOrder });
    }

    /**
     * Handle create backup
     * 
     * @param {Event} event - Click event
     * @returns {Promise<void>}
     */
    async handleCreateBackup(event) {
        event.preventDefault();

        this.log('Creating backup...');

        this.setState({ creating: true });

        try {
            const response = await this.api.createBackup({
                type: 'manual',
                note: 'Manual backup'
            });

            this.log('Backup created:', response);

            // Reload backups
            await this.loadBackups();

            // Emit event
            this.emit('backup:created', { backup: response.data });

            // Show success notification
            this.emit('notification:show', {
                type: 'success',
                message: 'Backup created successfully!',
                duration: 3000
            });

        } catch (error) {
            this.handleError('Failed to create backup', error);

            // Show error notification
            this.emit('notification:show', {
                type: 'error',
                message: 'Failed to create backup. Please try again.',
                duration: 5000
            });

        } finally {
            this.setState({ creating: false });
        }
    }

    /**
     * Handle restore backup
     * 
     * @param {Event} event - Click event
     * @returns {Promise<void>}
     */
    async handleRestoreBackup(event) {
        event.preventDefault();

        const backupId = parseInt(event.target.dataset.backupId);
        const backup = this.getBackupById(backupId);

        // Confirm restoration
        if (!confirm(`Are you sure you want to restore this backup from ${this.formatDate(backup.timestamp)}? Current settings will be replaced.`)) {
            return;
        }

        this.log('Restoring backup:', backupId);

        this.setState({ restoring: backupId });

        try {
            const response = await this.api.restoreBackup(backupId);

            this.log('Backup restored:', response);

            // Update global state with restored settings
            if (response.data && response.data.settings) {
                this.state.setState({ settings: response.data.settings });
            }

            // Emit event
            this.emit('backup:restored', { backupId, settings: response.data?.settings });

            // Show success notification
            this.emit('notification:show', {
                type: 'success',
                message: 'Backup restored successfully! Page will reload.',
                duration: 3000
            });

            // Reload page after short delay
            setTimeout(() => {
                window.location.reload();
            }, 2000);

        } catch (error) {
            this.handleError('Failed to restore backup', error);

            // Show error notification
            this.emit('notification:show', {
                type: 'error',
                message: 'Failed to restore backup. Please try again.',
                duration: 5000
            });

            this.setState({ restoring: null });
        }
    }

    /**
     * Handle download backup
     * 
     * @param {Event} event - Click event
     * @returns {void}
     */
    handleDownloadBackup(event) {
        event.preventDefault();

        const backupId = parseInt(event.target.dataset.backupId);
        const backup = this.getBackupById(backupId);

        if (!backup) {
            return;
        }

        this.log('Downloading backup:', backupId);

        // Create download
        const dataStr = JSON.stringify(backup, null, 2);
        const dataBlob = new Blob([dataStr], { type: 'application/json' });
        const url = URL.createObjectURL(dataBlob);

        const link = document.createElement('a');
        link.href = url;
        link.download = `mas-backup-${backupId}.json`;
        link.click();

        URL.revokeObjectURL(url);

        // Show success notification
        this.emit('notification:show', {
            type: 'success',
            message: 'Backup downloaded successfully!',
            duration: 2000
        });
    }

    /**
     * Handle delete backup
     * 
     * @param {Event} event - Click event
     * @returns {Promise<void>}
     */
    async handleDeleteBackup(event) {
        event.preventDefault();

        const backupId = parseInt(event.target.dataset.backupId);
        const backup = this.getBackupById(backupId);

        // Confirm deletion
        if (!confirm(`Are you sure you want to delete this backup from ${this.formatDate(backup.timestamp)}?`)) {
            return;
        }

        this.log('Deleting backup:', backupId);

        this.setState({ deleting: backupId });

        try {
            await this.api.deleteBackup(backupId);

            this.log('Backup deleted:', backupId);

            // Remove from local state
            const state = this.getState();
            const backups = state.backups.filter(b => b.id !== backupId);
            this.setState({ backups, deleting: null });

            // Emit event
            this.emit('backup:deleted', { backupId });

            // Show success notification
            this.emit('notification:show', {
                type: 'success',
                message: 'Backup deleted successfully!',
                duration: 3000
            });

        } catch (error) {
            this.handleError('Failed to delete backup', error);

            // Show error notification
            this.emit('notification:show', {
                type: 'error',
                message: 'Failed to delete backup. Please try again.',
                duration: 5000
            });

            this.setState({ deleting: null });
        }
    }

    /**
     * Handle backup created event
     * 
     * @param {Object} data - Event data
     * @returns {void}
     */
    handleBackupCreated(data) {
        this.log('Backup created event received:', data);
        // Reload backups to show new one
        this.loadBackups();
    }

    /**
     * Handle backup restored event
     * 
     * @param {Object} data - Event data
     * @returns {void}
     */
    handleBackupRestored(data) {
        this.log('Backup restored event received:', data);
    }

    /**
     * Get backup by ID
     * 
     * @param {number} backupId - Backup ID
     * @returns {Object|null} Backup object or null
     */
    getBackupById(backupId) {
        const state = this.getState();
        return state.backups.find(b => b.id === backupId) || null;
    }

    /**
     * Format date
     * 
     * @param {number} timestamp - Unix timestamp
     * @returns {string} Formatted date
     */
    formatDate(timestamp) {
        const date = new Date(timestamp * 1000);
        return date.toLocaleString();
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
    module.exports = BackupManagerComponent;
}

// Make available globally
if (typeof window !== 'undefined') {
    window.BackupManagerComponent = BackupManagerComponent;
}
