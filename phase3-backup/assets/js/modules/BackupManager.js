/**
 * Backup Manager Module
 * 
 * Handles backup operations with user confirmations, progress indicators,
 * and error handling for the Modern Admin Styler V2 plugin.
 * 
 * @package ModernAdminStylerV2
 * @since 2.2.0
 */

(function(window) {
    'use strict';
    
    /**
     * Backup Manager Class
     * 
     * Provides high-level backup operations with UI feedback
     */
    class BackupManager {
        /**
         * Constructor
         * 
         * @param {MASRestClient} restClient REST API client instance
         * @param {Object} options Configuration options
         */
        constructor(restClient, options = {}) {
            this.restClient = restClient;
            this.options = {
                confirmRestore: true,
                confirmDelete: true,
                showProgress: true,
                autoRefresh: true,
                ...options
            };
            
            this.backups = [];
            this.isLoading = false;
        }
        
        /**
         * List all backups
         * 
         * @param {boolean} refresh Force refresh from server
         * @returns {Promise<Array>} Array of backups
         */
        async listBackups(refresh = false) {
            if (!refresh && this.backups.length > 0) {
                return this.backups;
            }
            
            try {
                this.setLoading(true);
                this.backups = await this.restClient.listBackups();
                this.setLoading(false);
                
                return this.backups;
            } catch (error) {
                this.setLoading(false);
                this.handleError(error, 'Failed to load backups');
                throw error;
            }
        }
        
        /**
         * Create a new backup
         * 
         * @param {string} note Optional note about the backup
         * @returns {Promise<Object>} Created backup data
         */
        async createBackup(note = '') {
            try {
                this.showProgress('Creating backup...');
                
                const backup = await this.restClient.createBackup({ note });
                
                this.hideProgress();
                this.showNotification('Backup created successfully', 'success');
                
                // Refresh backup list if auto-refresh is enabled
                if (this.options.autoRefresh) {
                    await this.listBackups(true);
                }
                
                return backup;
            } catch (error) {
                this.hideProgress();
                this.handleError(error, 'Failed to create backup');
                throw error;
            }
        }
        
        /**
         * Restore a backup with confirmation
         * 
         * @param {string} backupId Backup ID to restore
         * @param {boolean} skipConfirmation Skip confirmation dialog
         * @returns {Promise<Object>} Restore result
         */
        async restoreBackup(backupId, skipConfirmation = false) {
            // Show confirmation dialog if enabled
            if (this.options.confirmRestore && !skipConfirmation) {
                const confirmed = await this.confirmAction(
                    'Restore Backup',
                    'Are you sure you want to restore this backup? Your current settings will be replaced. A backup of your current settings will be created automatically.',
                    'Restore',
                    'warning'
                );
                
                if (!confirmed) {
                    return null;
                }
            }
            
            try {
                this.showProgress('Restoring backup...');
                
                const result = await this.restClient.restoreBackup(backupId);
                
                this.hideProgress();
                this.showNotification('Backup restored successfully. The page will reload.', 'success');
                
                // Reload page after short delay to apply restored settings
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
                
                return result;
            } catch (error) {
                this.hideProgress();
                this.handleError(error, 'Failed to restore backup');
                throw error;
            }
        }
        
        /**
         * Delete a backup with confirmation
         * 
         * @param {string} backupId Backup ID to delete
         * @param {boolean} skipConfirmation Skip confirmation dialog
         * @returns {Promise<Object>} Delete result
         */
        async deleteBackup(backupId, skipConfirmation = false) {
            // Show confirmation dialog if enabled
            if (this.options.confirmDelete && !skipConfirmation) {
                const confirmed = await this.confirmAction(
                    'Delete Backup',
                    'Are you sure you want to delete this backup? This action cannot be undone.',
                    'Delete',
                    'danger'
                );
                
                if (!confirmed) {
                    return null;
                }
            }
            
            try {
                this.showProgress('Deleting backup...');
                
                const result = await this.restClient.deleteBackup(backupId);
                
                this.hideProgress();
                this.showNotification('Backup deleted successfully', 'success');
                
                // Refresh backup list if auto-refresh is enabled
                if (this.options.autoRefresh) {
                    await this.listBackups(true);
                }
                
                return result;
            } catch (error) {
                this.hideProgress();
                this.handleError(error, 'Failed to delete backup');
                throw error;
            }
        }
        
        /**
         * Get backup by ID
         * 
         * @param {string} backupId Backup ID
         * @returns {Object|null} Backup object or null if not found
         */
        getBackup(backupId) {
            return this.backups.find(backup => backup.id === backupId) || null;
        }
        
        /**
         * Format backup date for display
         * 
         * @param {number} timestamp Unix timestamp
         * @returns {string} Formatted date string
         */
        formatBackupDate(timestamp) {
            const date = new Date(timestamp * 1000);
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);
            
            // Relative time for recent backups
            if (diffMins < 1) {
                return 'Just now';
            } else if (diffMins < 60) {
                return `${diffMins} minute${diffMins > 1 ? 's' : ''} ago`;
            } else if (diffHours < 24) {
                return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
            } else if (diffDays < 7) {
                return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
            }
            
            // Absolute date for older backups
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
        }
        
        /**
         * Show confirmation dialog
         * 
         * @param {string} title Dialog title
         * @param {string} message Dialog message
         * @param {string} confirmText Confirm button text
         * @param {string} type Dialog type (info, warning, danger)
         * @returns {Promise<boolean>} True if confirmed
         */
        async confirmAction(title, message, confirmText = 'Confirm', type = 'info') {
            // Use native confirm as fallback
            // In production, this should be replaced with a custom modal
            return new Promise((resolve) => {
                const confirmed = window.confirm(`${title}\n\n${message}`);
                resolve(confirmed);
            });
        }
        
        /**
         * Show progress indicator
         * 
         * @param {string} message Progress message
         */
        showProgress(message) {
            if (!this.options.showProgress) {
                return;
            }
            
            // Dispatch custom event for UI to handle
            window.dispatchEvent(new CustomEvent('mas:backup:progress', {
                detail: { message, show: true }
            }));
            
            // Also log to console in debug mode
            if (this.restClient.debug) {
                console.log('[Backup Manager] Progress:', message);
            }
        }
        
        /**
         * Hide progress indicator
         */
        hideProgress() {
            if (!this.options.showProgress) {
                return;
            }
            
            // Dispatch custom event for UI to handle
            window.dispatchEvent(new CustomEvent('mas:backup:progress', {
                detail: { show: false }
            }));
        }
        
        /**
         * Set loading state
         * 
         * @param {boolean} loading Loading state
         */
        setLoading(loading) {
            this.isLoading = loading;
            
            // Dispatch custom event for UI to handle
            window.dispatchEvent(new CustomEvent('mas:backup:loading', {
                detail: { loading }
            }));
        }
        
        /**
         * Show notification
         * 
         * @param {string} message Notification message
         * @param {string} type Notification type (success, error, warning, info)
         */
        showNotification(message, type = 'info') {
            // Dispatch custom event for UI to handle
            window.dispatchEvent(new CustomEvent('mas:backup:notification', {
                detail: { message, type }
            }));
            
            // Also log to console
            console.log(`[Backup Manager] ${type.toUpperCase()}:`, message);
        }
        
        /**
         * Handle error
         * 
         * @param {Error} error Error object
         * @param {string} defaultMessage Default error message
         */
        handleError(error, defaultMessage) {
            let message = defaultMessage;
            
            if (error instanceof window.MASRestError) {
                message = error.getUserMessage();
            } else if (error.message) {
                message = error.message;
            }
            
            this.showNotification(message, 'error');
            
            // Log full error in console
            console.error('[Backup Manager] Error:', error);
        }
        
        /**
         * Get backup statistics
         * 
         * @returns {Object} Statistics object
         */
        getStatistics() {
            const automatic = this.backups.filter(b => b.type === 'automatic').length;
            const manual = this.backups.filter(b => b.type === 'manual').length;
            
            return {
                total: this.backups.length,
                automatic,
                manual,
                oldest: this.backups.length > 0 
                    ? Math.min(...this.backups.map(b => b.timestamp))
                    : null,
                newest: this.backups.length > 0
                    ? Math.max(...this.backups.map(b => b.timestamp))
                    : null
            };
        }
        
        /**
         * Export backup as JSON file
         * 
         * @param {string} backupId Backup ID to export
         */
        async exportBackup(backupId) {
            try {
                const backup = this.getBackup(backupId);
                
                if (!backup) {
                    throw new Error('Backup not found');
                }
                
                // Create JSON blob
                const json = JSON.stringify(backup, null, 2);
                const blob = new Blob([json], { type: 'application/json' });
                
                // Create download link
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = `mas-backup-${backupId}.json`;
                
                // Trigger download
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                // Clean up
                URL.revokeObjectURL(url);
                
                this.showNotification('Backup exported successfully', 'success');
            } catch (error) {
                this.handleError(error, 'Failed to export backup');
            }
        }
    }
    
    // Export to global scope
    window.BackupManager = BackupManager;
    
    // Create default instance if REST client is available
    if (window.masRestClient) {
        window.masBackupManager = new BackupManager(window.masRestClient);
    }
    
})(window);
