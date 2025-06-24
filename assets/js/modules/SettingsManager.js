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
                this.notificationManager?.success('✅ Ustawienia zostały zapisane', 4000);
                this.dispatchSettingsEvent('saved', this.settings);
            } else {
                this.notificationManager?.error('❌ Błąd podczas zapisywania: ' + (data.data || 'Nieznany błąd'), 5000);
            }
        })
        .catch(error => {
            this.showLoadingState(false);
            console.error('Błąd podczas zapisywania:', error);
            this.notificationManager?.error('❌ Błąd sieci podczas zapisywania', 5000);
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
                // Odśwież stronę aby załadować domyślne wartości
                window.location.reload();
            } else {
                this.notificationManager?.error('❌ Błąd podczas resetowania: ' + (data.data || 'Nieznany błąd'), 5000);
            }
        })
        .catch(error => {
            this.showLoadingState(false);
            console.error('Błąd podczas resetowania:', error);
            this.notificationManager?.error('❌ Błąd sieci podczas resetowania', 5000);
        });
    }
    
    exportSettings() {
        this.loadSettings();
        
        const exportData = {
            version: '2.0',
            timestamp: Date.now(),
            settings: this.settings,
            metadata: {
                site_url: window.location.origin,
                exported_by: 'Modern Admin Styler V2',
                date: new Date().toISOString()
            }
        };
        
        const dataStr = JSON.stringify(exportData, null, 2);
        const dataBlob = new Blob([dataStr], { type: 'application/json' });
        
        const link = document.createElement('a');
        link.href = URL.createObjectURL(dataBlob);
        link.download = `mas-v2-settings-${new Date().toISOString().split('T')[0]}.json`;
        link.click();
        
        this.notificationManager?.info('📤 Ustawienia zostały wyeksportowane', 4000);
        this.dispatchSettingsEvent('exported', exportData);
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
        // Walidacja danych
        if (!importData || !importData.settings) {
            this.notificationManager?.error('❌ Nieprawidłowa struktura pliku', 4000);
            return;
        }
        
        if (!confirm('Czy na pewno chcesz zaimportować ustawienia? Obecne ustawienia zostaną nadpisane.')) {
            return;
        }
        
        // Zastosuj importowane ustawienia do formularza
        const settings = importData.settings;
        
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
                
                // Wywołaj change event dla live preview
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
        
        this.loadSettings();
        this.markAsChanged();
        this.notificationManager?.success('📥 Ustawienia zostały zaimportowane', 4000);
        this.dispatchSettingsEvent('imported', settings);
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