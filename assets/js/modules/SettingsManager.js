/**
 * Modern Admin Styler V2 - Settings Manager Module
 * Zarządzanie ustawieniami, formularzami, save/load/export/import
 */

class SettingsManager {
    constructor(app) {
        this.app = app;
        this.notificationManager = null;
        this.form = null;
        this.settings = {};
        this.hasChanges = false;
        this.autoSaveInterval = null;
        this.isInitialized = false;
    }
    
    init() {
        if (this.isInitialized) {
            console.warn('SettingsManager już został zainicjalizowany');
            return;
        }
        
        this.form = document.querySelector('#mas-v2-settings-form');
        if (!this.form) {
            console.warn('Nie znaleziono formularza ustawień');
            return;
        }
        
        this.loadSettings();
        this.setupEventListeners();
        this.notificationManager = this.app.getModule('notificationManager');
        this.initAutoSave();
        this.isInitialized = true;
    }
    
    setupEventListeners() {
        // Submit formularza
        this.form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveSettings();
        });
        
        // Przyciski akcji
        document.addEventListener('click', (e) => {
            const target = e.target;
            
            if (target.id === 'mas-v2-save-btn') {
                e.preventDefault();
                this.saveSettings();
            } else if (target.id === 'mas-v2-reset-btn') {
                e.preventDefault();
                this.resetSettings();
            } else if (target.id === 'mas-v2-export-btn') {
                e.preventDefault();
                this.exportSettings();
            } else if (target.id === 'mas-v2-import-btn') {
                e.preventDefault();
                this.triggerImport();
            }
        });
        
        // Import pliku
        document.addEventListener('change', (e) => {
            if (e.target.id === 'mas-v2-import-file') {
                this.handleImportFile(e);
            }
        });
        
        // Obserwuj zmiany w formularzu
        this.form.addEventListener('change', () => this.markAsChanged());
        this.form.addEventListener('input', () => this.markAsChanged());
        this.form.addEventListener('keyup', () => this.markAsChanged());
    }
    
    loadSettings() {
        // Pobierz aktualne ustawienia z formularza
        const formData = new FormData(this.form);
        this.settings = {};
        
        for (let [key, value] of formData.entries()) {
            // Obsługa checkboxów
            const input = this.form.querySelector(`[name="${key}"]`);
            if (input && input.type === 'checkbox') {
                this.settings[key] = input.checked;
            } else {
                this.settings[key] = value;
            }
        }
        
        // Dodaj niezaznaczone checkboxy
        const checkboxes = this.form.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach(checkbox => {
            if (!formData.has(checkbox.name)) {
                this.settings[checkbox.name] = false;
            }
        });
        
        this.dispatchSettingsEvent('loaded');
    }
    
    saveSettings() {
        this.showLoadingState(true);
        
        // Pobierz aktualne dane z formularza
        this.loadSettings();
        
        // Przygotuj dane do wysłania
        const formData = new FormData(this.form);
        formData.append('action', 'mas_v2_save_settings');
        formData.append('nonce', masV2Admin.nonce || '');
        
        // Wyślij AJAX request
        fetch(masV2Admin.ajaxUrl || ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            this.showLoadingState(false);
            
            if (data.success) {
                this.hasChanges = false;
                this.updateSaveButton();
                
                // Show success message with additional info
                let message = '✅ Settings saved successfully!';
                if (data.data && data.data.settings_count) {
                    message += ` (${data.data.settings_count} settings)`;
                }
                this.notificationManager?.success(message, 4000);
                
                // Show warnings if any
                if (data.data && data.data.warnings && data.data.warnings.length > 0) {
                    const warningMsg = '⚠️ Warnings: ' + data.data.warnings.slice(0, 2).join(', ');
                    this.notificationManager?.warning(warningMsg, 6000);
                }
                
                this.dispatchSettingsEvent('saved', this.settings);
            } else {
                // Enhanced error handling
                let errorMsg = '❌ Error saving settings: ';
                if (data.data && data.data.message) {
                    errorMsg += data.data.message;
                } else if (data.data && typeof data.data === 'string') {
                    errorMsg += data.data;
                } else {
                    errorMsg += 'Unknown error';
                }
                
                // Show specific error codes
                if (data.data && data.data.code) {
                    switch (data.data.code) {
                        case 'invalid_nonce':
                            errorMsg += ' Please refresh the page and try again.';
                            break;
                        case 'validation_failed':
                            errorMsg += ' Please check your settings and try again.';
                            break;
                        case 'save_failed':
                            errorMsg += ' Settings were restored from backup.';
                            break;
                    }
                }
                
                this.notificationManager?.error(errorMsg, 8000);
                
                // Log detailed error for debugging
                console.error('Settings save error:', data);
            }
        })
        .catch(error => {
            this.showLoadingState(false);
            console.error('Network error during save:', error);
            this.notificationManager?.error('❌ Network error during save. Please check your connection.', 5000);
        });
    }
    
    resetSettings() {
        if (!confirm('Czy na pewno chcesz przywrócić domyślne ustawienia? Ta operacja jest nieodwracalna.')) {
            return;
        }
        
        this.showLoadingState(true);
        
        const formData = new FormData();
        formData.append('action', 'mas_v2_reset_settings');
        formData.append('nonce', masV2Admin.nonce || '');
        
        fetch(masV2Admin.ajaxUrl || ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            this.showLoadingState(false);
            
            if (data.success) {
                // Show success message with details
                let message = '✅ Settings reset to defaults successfully!';
                if (data.data && data.data.settings_count) {
                    message += ` (${data.data.settings_count} default settings loaded)`;
                }
                this.notificationManager?.success(message, 3000);
                
                // Show backup info if available
                if (data.data && data.data.backup_created) {
                    this.notificationManager?.info('💾 Previous settings backed up automatically', 4000);
                }
                
                // Show warnings if any
                if (data.data && data.data.warnings && data.data.warnings.length > 0) {
                    const warningMsg = '⚠️ Reset warnings: ' + data.data.warnings.slice(0, 2).join(', ');
                    this.notificationManager?.warning(warningMsg, 6000);
                }
                
                // Reload page after a short delay to show messages
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                // Enhanced error handling for reset
                let errorMsg = '❌ Error resetting settings: ';
                if (data.data && data.data.message) {
                    errorMsg += data.data.message;
                } else if (data.data && typeof data.data === 'string') {
                    errorMsg += data.data;
                } else {
                    errorMsg += 'Unknown error';
                }
                
                // Show recovery info if backup was restored
                if (data.data && data.data.restored_backup) {
                    errorMsg += ' Previous settings have been restored.';
                }
                
                this.notificationManager?.error(errorMsg, 8000);
                console.error('Settings reset error:', data);
            }
        })
        .catch(error => {
            this.showLoadingState(false);
            console.error('Network error during reset:', error);
            this.notificationManager?.error('❌ Network error during reset. Please check your connection.', 5000);
        });
    }
    
    exportSettings() {
        this.showLoadingState(true);
        
        // Use server-side export for enhanced functionality
        const formData = new FormData();
        formData.append('action', 'mas_v2_export_settings');
        formData.append('nonce', masV2Admin.nonce || '');
        formData.append('export_type', 'full');
        
        fetch(masV2Admin.ajaxUrl || ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            this.showLoadingState(false);
            
            if (data.success) {
                // Create and download the export file
                const exportData = data.data.data;
                const filename = data.data.filename;
                
                const dataStr = JSON.stringify(exportData, null, 2);
                const dataBlob = new Blob([dataStr], { type: 'application/json' });
                
                const link = document.createElement('a');
                link.href = URL.createObjectURL(dataBlob);
                link.download = filename;
                link.click();
                
                // Show success message with details
                let message = `📤 Successfully exported ${data.data.settings_count} settings`;
                if (data.data.export_size) {
                    message += ` (${Math.round(data.data.export_size / 1024)}KB)`;
                }
                this.notificationManager?.success(message, 4000);
                
                this.dispatchSettingsEvent('exported', exportData);
            } else {
                let errorMsg = '❌ Export failed: ';
                if (data.data && data.data.message) {
                    errorMsg += data.data.message;
                } else {
                    errorMsg += 'Unknown error';
                }
                this.notificationManager?.error(errorMsg, 6000);
                console.error('Export error:', data);
            }
        })
        .catch(error => {
            this.showLoadingState(false);
            console.error('Network error during export:', error);
            this.notificationManager?.error('❌ Network error during export. Please check your connection.', 5000);
        });
    }
    
    triggerImport() {
        let fileInput = document.querySelector('#mas-v2-import-file');
        
        if (!fileInput) {
            fileInput = document.createElement('input');
            fileInput.type = 'file';
            fileInput.id = 'mas-v2-import-file';
            fileInput.accept = '.json';
            fileInput.style.display = 'none';
            document.body.appendChild(fileInput);
        }
        
        fileInput.click();
    }
    
    handleImportFile(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        if (file.type !== 'application/json' && !file.name.endsWith('.json')) {
            this.notificationManager?.error('❌ Proszę wybrać plik JSON', 4000);
            return;
        }
        
        const reader = new FileReader();
        reader.onload = (event) => {
            try {
                const importData = JSON.parse(event.target.result);
                this.importSettings(importData);
            } catch (error) {
                console.error('Błąd parsowania JSON:', error);
                this.notificationManager?.error('❌ Nieprawidłowy format pliku JSON', 4000);
            }
        };
        
        reader.readAsText(file);
    }
    
    importSettings(importData) {
        // Enhanced validation
        if (!importData) {
            this.notificationManager?.error('❌ Invalid import file structure', 4000);
            return;
        }
        
        // Support both new and legacy formats
        let settings = null;
        if (importData.settings) {
            settings = importData.settings;
        } else if (typeof importData === 'object' && !importData.format_version) {
            // Legacy format
            settings = importData;
        }
        
        if (!settings) {
            this.notificationManager?.error('❌ No settings found in import file', 4000);
            return;
        }
        
        // Show confirmation with more details
        let confirmMessage = 'Are you sure you want to import these settings? Current settings will be overwritten.';
        if (importData.settings_count) {
            confirmMessage += `\n\nImporting ${importData.settings_count} settings`;
        }
        if (importData.plugin_version && importData.plugin_version !== masV2Admin.version) {
            confirmMessage += `\nSource version: ${importData.plugin_version}`;
        }
        
        if (!confirm(confirmMessage)) {
            return;
        }
        
        this.showLoadingState(true);
        
        // Use server-side import for enhanced validation and error handling
        const formData = new FormData();
        formData.append('action', 'mas_v2_import_settings');
        formData.append('nonce', masV2Admin.nonce || '');
        formData.append('data', JSON.stringify(importData));
        
        fetch(masV2Admin.ajaxUrl || ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            this.showLoadingState(false);
            
            if (data.success) {
                // Apply imported settings to form
                this.applySettingsToForm(settings);
                this.loadSettings();
                this.markAsChanged();
                
                // Show success message with details
                let message = `📥 Successfully imported ${data.data.imported_count} settings!`;
                if (data.data.backup_created) {
                    message += ' Previous settings backed up automatically.';
                }
                this.notificationManager?.success(message, 5000);
                
                // Show warnings if any
                if (data.data.warnings && data.data.warnings.length > 0) {
                    const warningMsg = `⚠️ Import warnings: ${data.data.warnings.slice(0, 2).join(', ')}`;
                    this.notificationManager?.warning(warningMsg, 6000);
                }
                
                // Show version mismatch warning
                if (data.data.version_mismatch) {
                    this.notificationManager?.info('ℹ️ Settings imported from different plugin version', 4000);
                }
                
                this.dispatchSettingsEvent('imported', settings);
            } else {
                let errorMsg = '❌ Import failed: ';
                if (data.data && data.data.message) {
                    errorMsg += data.data.message;
                } else {
                    errorMsg += 'Unknown error';
                }
                
                // Show specific error details
                if (data.data && data.data.code) {
                    switch (data.data.code) {
                        case 'validation_failed':
                            errorMsg += ' Please check the file format.';
                            break;
                        case 'sanitization_failed':
                            errorMsg += ' Settings validation failed.';
                            break;
                        case 'save_failed':
                            errorMsg += ' Could not save to database.';
                            break;
                    }
                }
                
                this.notificationManager?.error(errorMsg, 8000);
                console.error('Import error:', data);
            }
        })
        .catch(error => {
            this.showLoadingState(false);
            console.error('Network error during import:', error);
            this.notificationManager?.error('❌ Network error during import. Please check your connection.', 5000);
        });
    }
    
    applySettingsToForm(settings) {
        Object.keys(settings).forEach(key => {
            const input = this.form.querySelector(`[name="${key}"]`);
            if (input) {
                if (input.type === 'checkbox') {
                    input.checked = Boolean(settings[key]);
                } else if (input.type === 'radio') {
                    if (input.value === settings[key]) {
                        input.checked = true;
                    }
                } else {
                    input.value = settings[key];
                }
                
                // Trigger change event for live preview
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    }
    
    markAsChanged() {
        if (!this.hasChanges) {
            this.hasChanges = true;
            this.updateSaveButton();
            this.dispatchSettingsEvent('changed');
        }
    }
    
    updateSaveButton() {
        const saveBtn = document.querySelector('#mas-v2-save-btn');
        if (saveBtn) {
            if (this.hasChanges) {
                saveBtn.textContent = 'Zapisz zmiany *';
                saveBtn.classList.add('has-changes');
            } else {
                saveBtn.textContent = 'Zapisz ustawienia';
                saveBtn.classList.remove('has-changes');
            }
        }
    }
    
    initAutoSave() {
        // Opcjonalne auto-save co 30 sekund
        if (masV2Admin && masV2Admin.autoSave) {
            this.autoSaveInterval = setInterval(() => {
                if (this.hasChanges) {
                    this.saveSettings();
                }
            }, 30000); // 30 sekund
        }
    }
    
    showLoadingState(isLoading) {
        const buttons = document.querySelectorAll('#mas-v2-save-btn, #mas-v2-reset-btn, #mas-v2-export-btn, #mas-v2-import-btn');
        
        buttons.forEach(btn => {
            if (isLoading) {
                btn.disabled = true;
                btn.classList.add('loading');
                if (btn.id === 'mas-v2-save-btn') {
                    btn.innerHTML = '<span class="spinner"></span> Zapisywanie...';
                }
            } else {
                btn.disabled = false;
                btn.classList.remove('loading');
                if (btn.id === 'mas-v2-save-btn') {
                    btn.innerHTML = this.hasChanges ? 'Zapisz zmiany *' : 'Zapisz ustawienia';
                }
            }
        });
    }
    

    
    dispatchSettingsEvent(action, data = null) {
        const event = new CustomEvent('mas-settings-' + action, {
            detail: {
                action,
                settings: this.settings,
                data,
                timestamp: Date.now()
            }
        });
        document.dispatchEvent(event);
    }
    
    // Task 12: Enhanced backup and restore functionality
    listBackups() {
        return new Promise((resolve, reject) => {
            const formData = new FormData();
            formData.append('action', 'mas_v2_list_backups');
            formData.append('nonce', masV2Admin.nonce || '');
            
            fetch(masV2Admin.ajaxUrl || ajaxurl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resolve(data.data.backups);
                } else {
                    reject(new Error(data.data?.message || 'Failed to list backups'));
                }
            })
            .catch(error => {
                reject(error);
            });
        });
    }
    
    restoreBackup(backupKey) {
        if (!backupKey) {
            this.notificationManager?.error('❌ Invalid backup key', 4000);
            return;
        }
        
        if (!confirm('Are you sure you want to restore this backup? Current settings will be replaced.')) {
            return;
        }
        
        this.showLoadingState(true);
        
        const formData = new FormData();
        formData.append('action', 'mas_v2_restore_backup');
        formData.append('nonce', masV2Admin.nonce || '');
        formData.append('backup_key', backupKey);
        
        fetch(masV2Admin.ajaxUrl || ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            this.showLoadingState(false);
            
            if (data.success) {
                // Show success message
                let message = `✅ ${data.data.message}`;
                if (data.data.restored_count) {
                    message += ` (${data.data.restored_count} settings restored)`;
                }
                this.notificationManager?.success(message, 5000);
                
                // Show warnings if any
                if (data.data.warnings && data.data.warnings.length > 0) {
                    const warningMsg = `⚠️ Restore warnings: ${data.data.warnings.slice(0, 2).join(', ')}`;
                    this.notificationManager?.warning(warningMsg, 6000);
                }
                
                // Reload page to reflect restored settings
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
                
                this.dispatchSettingsEvent('restored', { backupKey });
            } else {
                let errorMsg = '❌ Restore failed: ';
                if (data.data && data.data.message) {
                    errorMsg += data.data.message;
                } else {
                    errorMsg += 'Unknown error';
                }
                this.notificationManager?.error(errorMsg, 8000);
                console.error('Restore error:', data);
            }
        })
        .catch(error => {
            this.showLoadingState(false);
            console.error('Network error during restore:', error);
            this.notificationManager?.error('❌ Network error during restore. Please check your connection.', 5000);
        });
    }
    
    createBackup(backupName = '') {
        this.showLoadingState(true);
        
        const formData = new FormData();
        formData.append('action', 'mas_v2_create_backup');
        formData.append('nonce', masV2Admin.nonce || '');
        if (backupName) {
            formData.append('backup_name', backupName);
        }
        
        fetch(masV2Admin.ajaxUrl || ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            this.showLoadingState(false);
            
            if (data.success) {
                let message = `💾 Backup created successfully!`;
                if (data.data.settings_count) {
                    message += ` (${data.data.settings_count} settings backed up)`;
                }
                this.notificationManager?.success(message, 4000);
                
                this.dispatchSettingsEvent('backup_created', {
                    backupKey: data.data.backup_key,
                    backupName: data.data.backup_name
                });
            } else {
                let errorMsg = '❌ Backup creation failed: ';
                if (data.data && data.data.message) {
                    errorMsg += data.data.message;
                } else {
                    errorMsg += 'Unknown error';
                }
                this.notificationManager?.error(errorMsg, 6000);
                console.error('Backup creation error:', data);
            }
        })
        .catch(error => {
            this.showLoadingState(false);
            console.error('Network error during backup creation:', error);
            this.notificationManager?.error('❌ Network error during backup creation. Please check your connection.', 5000);
        });
    }
    
    deleteBackup(backupKey) {
        if (!backupKey) {
            this.notificationManager?.error('❌ Invalid backup key', 4000);
            return;
        }
        
        if (!confirm('Are you sure you want to delete this backup? This action cannot be undone.')) {
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'mas_v2_delete_backup');
        formData.append('nonce', masV2Admin.nonce || '');
        formData.append('backup_key', backupKey);
        
        fetch(masV2Admin.ajaxUrl || ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.notificationManager?.success('🗑️ Backup deleted successfully', 3000);
                this.dispatchSettingsEvent('backup_deleted', { backupKey });
            } else {
                let errorMsg = '❌ Backup deletion failed: ';
                if (data.data && data.data.message) {
                    errorMsg += data.data.message;
                } else {
                    errorMsg += 'Unknown error';
                }
                this.notificationManager?.error(errorMsg, 6000);
                console.error('Backup deletion error:', data);
            }
        })
        .catch(error => {
            console.error('Network error during backup deletion:', error);
            this.notificationManager?.error('❌ Network error during backup deletion. Please check your connection.', 5000);
        });
    }
    
    // Publiczne API
    getCurrentSettings() {
        return { ...this.settings };
    }
    
    updateSetting(key, value) {
        const input = this.form.querySelector(`[name="${key}"]`);
        if (input) {
            if (input.type === 'checkbox') {
                input.checked = Boolean(value);
            } else {
                input.value = value;
            }
            input.dispatchEvent(new Event('change', { bubbles: true }));
            this.markAsChanged();
        }
    }
    
    getSetting(key) {
        return this.settings[key];
    }
    
    destroy() {
        if (this.autoSaveInterval) {
            clearInterval(this.autoSaveInterval);
        }
        this.isInitialized = false;
    }
}

// Eksport dla użycia w innych modułach
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SettingsManager;
} else {
    window.SettingsManager = SettingsManager;
} 