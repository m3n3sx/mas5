/**
 * Modern Admin Styler V2 - Palette Manager
 * MOOD-BASED COLOR PALETTE SYSTEM 🎨
 * 
 * Zarządza paletami kolorystycznymi i ich dynamiczną zmianą
 */

class PaletteManager {
    constructor(app) {
        this.app = app;
        this.notificationManager = null;
        this.currentPalette = 'professional-blue';
        this.customPalettes = {}; // Store custom user-created palettes
        this.palettes = {
            'professional-blue': {
                name: '🌊 Profesjonalny Błękit',
                description: 'Stabilność i zaufanie - idealny dla biznesu',
                mood: 'professional',
                colors: {
                    primary: '#3B82F6',
                    secondary: '#1E40AF',
                    background: '#F8FAFC',
                    text: '#1E293B'
                }
            },
            'creative-purple': {
                name: '💜 Kreatywny Fiolet',
                description: 'Inspiracja i innowacja - dla twórczych dusz',
                mood: 'creative',
                colors: {
                    primary: '#9333EA',
                    secondary: '#6B21A8',
                    background: '#FEFCFF',
                    text: '#581C87'
                }
            },
            'energetic-green': {
                name: '🌿 Energetyczna Zieleń',
                description: 'Wzrost i vitalność - motywująca energia',
                mood: 'energetic',
                colors: {
                    primary: '#22C55E',
                    secondary: '#15803D',
                    background: '#F7FEF0',
                    text: '#14532D'
                }
            },
            'sunset-orange': {
                name: '🔥 Zachód Słońca',
                description: 'Ciepło i dynamizm - energetyzująca moc',
                mood: 'warm',
                colors: {
                    primary: '#F97316',
                    secondary: '#EA580C',
                    background: '#FFFBEB',
                    text: '#92400E'
                }
            },
            'rose-gold': {
                name: '🌸 Różowe Złoto',
                description: 'Elegancja i luksus - wyrafinowany styl',
                mood: 'elegant',
                colors: {
                    primary: '#F43F5E',
                    secondary: '#BE185D',
                    background: '#FFF1F2',
                    text: '#881337'
                }
            },
            'midnight': {
                name: '🌙 Ciemna Elegancja',
                description: 'Misterium i głębia - dla nocnych marków',
                mood: 'dark',
                colors: {
                    primary: '#94A3B8',
                    secondary: '#64748B',
                    background: '#0F172A',
                    text: '#F1F5F9'
                }
            },
            'ocean-teal': {
                name: '🌊 Ocean',
                description: 'Spokój i równowaga - relaksujące fale',
                mood: 'calm',
                colors: {
                    primary: '#14B8A6',
                    secondary: '#0F766E',
                    background: '#F0FDFA',
                    text: '#134E4A'
                }
            },
            'electric-cyber': {
                name: '⚡ Cyber Elektryczny',
                description: 'Futuryzm i technologia - cyfrowa przyszłość',
                mood: 'cyber',
                colors: {
                    primary: '#00FFFF',
                    secondary: '#0080FF',
                    background: '#0A0A0A',
                    text: '#00FFFF'
                }
            },
            'golden-sunrise': {
                name: '🌅 Złoty Wschód',
                description: 'Optymizm i bogactwo - promienne beginnings',
                mood: 'optimistic',
                colors: {
                    primary: '#FBBF24',
                    secondary: '#D97706',
                    background: '#FFFBEB',
                    text: '#78350F'
                }
            },
            'gaming-neon': {
                name: '🎮 Gaming Neon',
                description: 'Intensywność i akcja - gamingowa energia',
                mood: 'gaming',
                colors: {
                    primary: '#FF00FF',
                    secondary: '#8000FF',
                    background: '#0A0A0F',
                    text: '#FF00FF'
                }
            }
        };
        this.isInitialized = false;
    }
    
    init(settings = {}) {
        if (this.isInitialized) return;
        
        // Load custom palettes from localStorage
        this.loadCustomPalettes();
        
        // Ustaw aktualną paletę z ustawień lub localStorage
        this.currentPalette = settings.color_palette || 
                            localStorage.getItem('mas-v2-palette') || 
                            'professional-blue';
        
        this.applyPalette(this.currentPalette);
        this.setupEventListeners();
        this.createQuickSwitcher();
        this.notificationManager = this.app.getModule('notificationManager');
        
        this.isInitialized = true;
        console.log('🎨 PaletteManager initialized');
    }
    
    setupEventListeners() {
        // Listen for palette change events
        document.addEventListener('mas-palette-change', (e) => {
            this.setPalette(e.detail.palette);
        });
        
        // Keyboard shortcuts for quick palette switching
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.shiftKey) {
                const paletteKeys = {
                    '1': 'professional-blue',
                    '2': 'creative-purple', 
                    '3': 'energetic-green',
                    '4': 'sunset-orange',
                    '5': 'rose-gold',
                    '6': 'midnight',
                    '7': 'ocean-teal',
                    '8': 'electric-cyber',
                    '9': 'golden-sunrise',
                    '0': 'gaming-neon'
                };
                
                if (paletteKeys[e.key]) {
                    e.preventDefault();
                    this.setPalette(paletteKeys[e.key]);
                    this.notificationManager?.theme(`🎨 Paleta: ${this.palettes[paletteKeys[e.key]].name}`, 3000);
                }
            }
        });
    }
    
    setPalette(paletteId) {
        const palette = this.palettes[paletteId] || this.customPalettes[paletteId];
        if (!palette) {
            console.warn(`Palette ${paletteId} not found`);
            return;
        }
        
        this.currentPalette = paletteId;
        this.applyPalette(paletteId);
        this.savePalette(paletteId);
        
        // Dispatch change event
        document.dispatchEvent(new CustomEvent('mas-palette-applied', {
            detail: { 
                palette: paletteId,
                colors: palette.colors,
                custom: palette.custom || false
            }
        }));
    }
    
    applyPalette(paletteId) {
        // Check both predefined and custom palettes
        const palette = this.palettes[paletteId] || this.customPalettes[paletteId];
        if (!palette) {
            console.warn(`Palette ${paletteId} not found`);
            return;
        }
        
        // Apply to document element
        document.documentElement.setAttribute('data-palette', paletteId);
        
        // Apply CSS variables for custom palettes or enhanced predefined palettes
        if (palette.custom || palette.colors) {
            this.applyPaletteVariables(palette);
        }
        
        // Add smooth transition class
        document.body.classList.add('mas-palette-transitioning');
        
        setTimeout(() => {
            document.body.classList.remove('mas-palette-transitioning');
        }, 800);
        
        console.log(`🎨 Palette applied: ${palette.name}`);
    }
    
    savePalette(paletteId) {
        localStorage.setItem('mas-v2-palette', paletteId);
        
        // If we have settings manager, save there too
        if (window.ModernAdminApp) {
            const app = window.ModernAdminApp.getInstance();
            const settingsManager = app.getModule('settingsManager');
            if (settingsManager) {
                settingsManager.updateSetting('color_palette', paletteId);
            }
        }
    }
    
    createQuickSwitcher() {
        // Sprawdź czy już istnieje
        if (document.querySelector('.mas-palette-switcher')) return;
        
        const switcher = document.createElement('div');
        switcher.className = 'mas-palette-switcher';
        switcher.innerHTML = `
            <button class="mas-palette-toggle" aria-label="Zmień paletę kolorów">
                🎨
                <span class="mas-palette-current-name">${this.palettes[this.currentPalette].name}</span>
            </button>
            <div class="mas-palette-dropdown">
                <div class="mas-palette-header">
                    <h3>🎨 Wybierz Paletę Nastrojów</h3>
                    <p>Każda paleta tworzy inny mood i doświadczenie</p>
                </div>
                <div class="mas-palette-grid">
                    ${this.createPaletteGrid()}
                </div>
                <div class="mas-palette-shortcuts">
                    <small>💡 Użyj Ctrl+Shift+1-0 dla szybkiej zmiany</small>
                </div>
            </div>
        `;
        
        // Styling
        Object.assign(switcher.style, {
            position: 'fixed',
            top: '160px',
            right: '20px',
            zIndex: '999996'
        });
        
        document.body.appendChild(switcher);
        this.setupSwitcherEvents(switcher);
    }
    
    createPaletteGrid() {
        let html = '';
        
        // Add predefined palettes
        html += Object.entries(this.palettes).map(([id, palette], index) => `
            <div class="mas-palette-card ${id === this.currentPalette ? 'active' : ''}" 
                 data-palette="${id}"
                 title="${palette.description}">
                <div class="mas-palette-preview" data-palette="${id}"></div>
                <div class="mas-palette-info">
                    <span class="mas-palette-name">${palette.name}</span>
                    <span class="mas-palette-shortcut">Ctrl+Shift+${index === 9 ? '0' : index + 1}</span>
                </div>
                <div class="mas-palette-mood">${palette.mood}</div>
            </div>
        `).join('');
        
        // Add custom palettes section if any exist
        if (Object.keys(this.customPalettes).length > 0) {
            html += '<div class="mas-palette-section-divider">Custom Palettes</div>';
            html += Object.entries(this.customPalettes).map(([id, palette]) => `
                <div class="mas-palette-card custom ${id === this.currentPalette ? 'active' : ''}" 
                     data-palette="${id}"
                     title="${palette.description}">
                    <div class="mas-palette-preview custom" data-palette="${id}" 
                         style="background: linear-gradient(135deg, ${palette.colors.primary}, ${palette.colors.secondary})"></div>
                    <div class="mas-palette-info">
                        <span class="mas-palette-name">${palette.name}</span>
                        <span class="mas-palette-shortcut">Custom</span>
                    </div>
                    <div class="mas-palette-mood">${palette.mood}</div>
                    <div class="mas-palette-actions">
                        <button class="mas-palette-edit" data-palette="${id}" title="Edit">✏️</button>
                        <button class="mas-palette-delete" data-palette="${id}" title="Delete">🗑️</button>
                    </div>
                </div>
            `).join('');
        }
        
        // Add create new palette button
        html += `
            <div class="mas-palette-card create-new" onclick="this.closest('.mas-palette-switcher').dispatchEvent(new CustomEvent('create-palette'))">
                <div class="mas-palette-preview create">
                    <span class="create-icon">+</span>
                </div>
                <div class="mas-palette-info">
                    <span class="mas-palette-name">Create New</span>
                    <span class="mas-palette-shortcut">Custom</span>
                </div>
                <div class="mas-palette-mood">new</div>
            </div>
        `;
        
        return html;
    }
    
    setupSwitcherEvents(switcher) {
        const toggle = switcher.querySelector('.mas-palette-toggle');
        const dropdown = switcher.querySelector('.mas-palette-dropdown');
        
        // Toggle dropdown
        toggle.addEventListener('click', () => {
            dropdown.classList.toggle('show');
        });
        
        // Close on outside click
        document.addEventListener('click', (e) => {
            if (!switcher.contains(e.target)) {
                dropdown.classList.remove('show');
            }
        });
        
        // Palette selection and actions
        dropdown.addEventListener('click', (e) => {
            // Handle edit button
            if (e.target.classList.contains('mas-palette-edit')) {
                e.stopPropagation();
                const paletteId = e.target.dataset.palette;
                this.showEditPaletteDialog(paletteId);
                return;
            }
            
            // Handle delete button
            if (e.target.classList.contains('mas-palette-delete')) {
                e.stopPropagation();
                const paletteId = e.target.dataset.palette;
                this.showDeletePaletteDialog(paletteId);
                return;
            }
            
            // Handle palette selection
            const paletteCard = e.target.closest('.mas-palette-card');
            if (paletteCard && !paletteCard.classList.contains('create-new')) {
                const paletteId = paletteCard.dataset.palette;
                const palette = this.palettes[paletteId] || this.customPalettes[paletteId];
                if (palette) {
                    this.setPalette(paletteId);
                    this.updateSwitcherUI();
                    dropdown.classList.remove('show');
                    this.notificationManager?.theme(`🎨 Paleta: ${palette.name}`, 3000);
                }
            }
        });
        
        // Handle create new palette
        switcher.addEventListener('create-palette', () => {
            this.showCreatePaletteDialog();
            dropdown.classList.remove('show');
        });
    }
    
    updateSwitcherUI() {
        const currentName = document.querySelector('.mas-palette-current-name');
        const cards = document.querySelectorAll('.mas-palette-card');
        
        if (currentName) {
            currentName.textContent = this.palettes[this.currentPalette].name;
        }
        
        cards.forEach(card => {
            card.classList.toggle('active', card.dataset.palette === this.currentPalette);
        });
    }
    
    // Custom Palette Management
    loadCustomPalettes() {
        try {
            const stored = localStorage.getItem('mas-v2-custom-palettes');
            if (stored) {
                this.customPalettes = JSON.parse(stored);
                console.log(`🎨 Loaded ${Object.keys(this.customPalettes).length} custom palettes`);
            }
        } catch (error) {
            console.warn('Failed to load custom palettes:', error);
            this.customPalettes = {};
        }
    }
    
    saveCustomPalettes() {
        try {
            localStorage.setItem('mas-v2-custom-palettes', JSON.stringify(this.customPalettes));
            console.log('🎨 Custom palettes saved');
        } catch (error) {
            console.error('Failed to save custom palettes:', error);
        }
    }
    
    createCustomPalette(id, paletteData) {
        if (!id || !paletteData) {
            throw new Error('Palette ID and data are required');
        }
        
        // Validate palette structure
        const requiredFields = ['name', 'colors'];
        const requiredColors = ['primary', 'secondary', 'background', 'text'];
        
        for (const field of requiredFields) {
            if (!paletteData[field]) {
                throw new Error(`Missing required field: ${field}`);
            }
        }
        
        for (const color of requiredColors) {
            if (!paletteData.colors[color]) {
                throw new Error(`Missing required color: ${color}`);
            }
        }
        
        // Add metadata
        const customPalette = {
            ...paletteData,
            id: id,
            custom: true,
            created: new Date().toISOString(),
            description: paletteData.description || 'Custom user palette',
            mood: paletteData.mood || 'custom'
        };
        
        this.customPalettes[id] = customPalette;
        this.saveCustomPalettes();
        
        // Update UI if switcher exists
        this.updateSwitcherUI();
        
        console.log(`🎨 Created custom palette: ${customPalette.name}`);
        return customPalette;
    }
    
    editCustomPalette(id, updates) {
        if (!this.customPalettes[id]) {
            throw new Error(`Custom palette ${id} not found`);
        }
        
        this.customPalettes[id] = {
            ...this.customPalettes[id],
            ...updates,
            modified: new Date().toISOString()
        };
        
        this.saveCustomPalettes();
        this.updateSwitcherUI();
        
        // If this is the current palette, reapply it
        if (this.currentPalette === id) {
            this.applyPalette(id);
        }
        
        console.log(`🎨 Updated custom palette: ${id}`);
        return this.customPalettes[id];
    }
    
    deleteCustomPalette(id) {
        if (!this.customPalettes[id]) {
            throw new Error(`Custom palette ${id} not found`);
        }
        
        const paletteName = this.customPalettes[id].name;
        delete this.customPalettes[id];
        this.saveCustomPalettes();
        
        // If this was the current palette, switch to default
        if (this.currentPalette === id) {
            this.setPalette('professional-blue');
        }
        
        this.updateSwitcherUI();
        console.log(`🎨 Deleted custom palette: ${paletteName}`);
    }
    
    exportCustomPalettes() {
        const exportData = {
            version: '2.0',
            exported: new Date().toISOString(),
            palettes: this.customPalettes
        };
        
        const blob = new Blob([JSON.stringify(exportData, null, 2)], {
            type: 'application/json'
        });
        
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `mas-custom-palettes-${new Date().toISOString().split('T')[0]}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        
        console.log('🎨 Custom palettes exported');
        return exportData;
    }
    
    importCustomPalettes(jsonData) {
        try {
            let data;
            if (typeof jsonData === 'string') {
                data = JSON.parse(jsonData);
            } else {
                data = jsonData;
            }
            
            if (!data.palettes || typeof data.palettes !== 'object') {
                throw new Error('Invalid palette data format');
            }
            
            let importedCount = 0;
            for (const [id, palette] of Object.entries(data.palettes)) {
                try {
                    // Validate and import each palette
                    this.createCustomPalette(id, palette);
                    importedCount++;
                } catch (error) {
                    console.warn(`Failed to import palette ${id}:`, error);
                }
            }
            
            console.log(`🎨 Imported ${importedCount} custom palettes`);
            return importedCount;
            
        } catch (error) {
            console.error('Failed to import custom palettes:', error);
            throw error;
        }
    }
    
    // Enhanced CSS Variable Updates
    applyPaletteVariables(palette) {
        const root = document.documentElement;
        const colors = palette.colors;
        
        // Apply core color variables
        root.style.setProperty('--mas-accent-start', colors.primary);
        root.style.setProperty('--mas-accent-end', colors.secondary);
        root.style.setProperty('--mas-bg-primary', colors.background);
        root.style.setProperty('--mas-text-primary', colors.text);
        
        // Generate additional variables based on primary colors
        const primaryRgb = this.hexToRgb(colors.primary);
        const secondaryRgb = this.hexToRgb(colors.secondary);
        
        if (primaryRgb) {
            root.style.setProperty('--mas-accent-start-rgb', `${primaryRgb.r}, ${primaryRgb.g}, ${primaryRgb.b}`);
            root.style.setProperty('--mas-accent-glow', `rgba(${primaryRgb.r}, ${primaryRgb.g}, ${primaryRgb.b}, 0.6)`);
            root.style.setProperty('--mas-active-start', `rgba(${primaryRgb.r}, ${primaryRgb.g}, ${primaryRgb.b}, 0.1)`);
        }
        
        if (secondaryRgb) {
            root.style.setProperty('--mas-accent-end-rgb', `${secondaryRgb.r}, ${secondaryRgb.g}, ${secondaryRgb.b}`);
            root.style.setProperty('--mas-active-end', `rgba(${secondaryRgb.r}, ${secondaryRgb.g}, ${secondaryRgb.b}, 0.1)`);
        }
        
        // Generate glass morphism variables
        const isLight = this.isLightColor(colors.background);
        if (isLight) {
            root.style.setProperty('--mas-glass-primary', 'rgba(255, 255, 255, 0.85)');
            root.style.setProperty('--mas-glass-border', `rgba(${primaryRgb?.r || 0}, ${primaryRgb?.g || 0}, ${primaryRgb?.b || 0}, 0.2)`);
            root.style.setProperty('--mas-submenu-glass', 'rgba(255, 255, 255, 0.95)');
        } else {
            root.style.setProperty('--mas-glass-primary', 'rgba(30, 41, 59, 0.85)');
            root.style.setProperty('--mas-glass-border', `rgba(${primaryRgb?.r || 255}, ${primaryRgb?.g || 255}, ${primaryRgb?.b || 255}, 0.2)`);
            root.style.setProperty('--mas-submenu-glass', 'rgba(51, 65, 85, 0.95)');
        }
        
        console.log('🎨 CSS variables updated for palette');
    }
    
    hexToRgb(hex) {
        const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        return result ? {
            r: parseInt(result[1], 16),
            g: parseInt(result[2], 16),
            b: parseInt(result[3], 16)
        } : null;
    }
    
    isLightColor(hex) {
        const rgb = this.hexToRgb(hex);
        if (!rgb) return true;
        
        // Calculate relative luminance
        const luminance = (0.299 * rgb.r + 0.587 * rgb.g + 0.114 * rgb.b) / 255;
        return luminance > 0.5;
    }

    
    // Public API
    getCurrentPalette() {
        const palette = this.palettes[this.currentPalette] || this.customPalettes[this.currentPalette];
        return palette ? {
            id: this.currentPalette,
            ...palette
        } : null;
    }
    
    getAllPalettes() {
        return {
            ...this.palettes,
            ...this.customPalettes
        };
    }
    
    getPredefinedPalettes() {
        return this.palettes;
    }
    
    getCustomPalettes() {
        return this.customPalettes;
    }
    
    cyclePalette() {
        const paletteIds = Object.keys(this.palettes);
        const currentIndex = paletteIds.indexOf(this.currentPalette);
        const nextIndex = (currentIndex + 1) % paletteIds.length;
        this.setPalette(paletteIds[nextIndex]);
    }
    
    randomPalette() {
        const paletteIds = Object.keys(this.palettes);
        const randomId = paletteIds[Math.floor(Math.random() * paletteIds.length)];
        this.setPalette(randomId);
        this.notificationManager?.theme(`🎨 Paleta: ${this.palettes[randomId].name}`, 3000);
    }
    
    showCreatePaletteDialog() {
        const dialog = this.createPaletteDialog();
        document.body.appendChild(dialog);
        
        // Focus first input
        setTimeout(() => {
            const nameInput = dialog.querySelector('input[name="name"]');
            if (nameInput) nameInput.focus();
        }, 100);
    }
    
    showEditPaletteDialog(paletteId) {
        const palette = this.customPalettes[paletteId];
        if (!palette) return;
        
        const dialog = this.createPaletteDialog(palette);
        document.body.appendChild(dialog);
    }
    
    showDeletePaletteDialog(paletteId) {
        const palette = this.customPalettes[paletteId];
        if (!palette) return;
        
        if (confirm(`Are you sure you want to delete the palette "${palette.name}"?`)) {
            try {
                this.deleteCustomPalette(paletteId);
                this.notificationManager?.success(`Deleted palette: ${palette.name}`, 3000);
            } catch (error) {
                this.notificationManager?.error(`Failed to delete palette: ${error.message}`, 5000);
            }
        }
    }
    
    createPaletteDialog(existingPalette = null) {
        const isEdit = !!existingPalette;
        const dialog = document.createElement('div');
        dialog.className = 'mas-palette-dialog-overlay';
        
        const colors = existingPalette?.colors || {
            primary: '#3B82F6',
            secondary: '#1E40AF',
            background: '#F8FAFC',
            text: '#1E293B'
        };
        
        dialog.innerHTML = `
            <div class="mas-palette-dialog">
                <div class="mas-palette-dialog-header">
                    <h3>${isEdit ? '✏️ Edit Palette' : '🎨 Create New Palette'}</h3>
                    <button class="mas-palette-dialog-close">×</button>
                </div>
                <form class="mas-palette-dialog-form">
                    <div class="mas-form-group">
                        <label>Palette Name</label>
                        <input type="text" name="name" value="${existingPalette?.name || ''}" required>
                    </div>
                    <div class="mas-form-group">
                        <label>Description</label>
                        <input type="text" name="description" value="${existingPalette?.description || ''}">
                    </div>
                    <div class="mas-form-group">
                        <label>Mood/Category</label>
                        <input type="text" name="mood" value="${existingPalette?.mood || 'custom'}">
                    </div>
                    <div class="mas-form-group">
                        <label>Primary Color</label>
                        <div class="mas-color-input">
                            <input type="color" name="primary" value="${colors.primary}">
                            <input type="text" name="primary-text" value="${colors.primary}">
                        </div>
                    </div>
                    <div class="mas-form-group">
                        <label>Secondary Color</label>
                        <div class="mas-color-input">
                            <input type="color" name="secondary" value="${colors.secondary}">
                            <input type="text" name="secondary-text" value="${colors.secondary}">
                        </div>
                    </div>
                    <div class="mas-form-group">
                        <label>Background Color</label>
                        <div class="mas-color-input">
                            <input type="color" name="background" value="${colors.background}">
                            <input type="text" name="background-text" value="${colors.background}">
                        </div>
                    </div>
                    <div class="mas-form-group">
                        <label>Text Color</label>
                        <div class="mas-color-input">
                            <input type="color" name="text" value="${colors.text}">
                            <input type="text" name="text-text" value="${colors.text}">
                        </div>
                    </div>
                    <div class="mas-palette-preview-area">
                        <div class="mas-palette-live-preview" id="live-preview">
                            <div class="preview-header">Live Preview</div>
                            <div class="preview-content">Sample content with these colors</div>
                        </div>
                    </div>
                    <div class="mas-form-actions">
                        <button type="button" class="mas-btn-secondary" onclick="this.closest('.mas-palette-dialog-overlay').remove()">Cancel</button>
                        <button type="submit" class="mas-btn-primary">${isEdit ? 'Update' : 'Create'} Palette</button>
                    </div>
                </form>
            </div>
        `;
        
        // Add event listeners
        this.setupDialogEvents(dialog, existingPalette);
        
        return dialog;
    }
    
    setupDialogEvents(dialog, existingPalette) {
        const form = dialog.querySelector('.mas-palette-dialog-form');
        const preview = dialog.querySelector('#live-preview');
        
        // Close dialog
        dialog.querySelector('.mas-palette-dialog-close').addEventListener('click', () => {
            dialog.remove();
        });
        
        // Close on overlay click
        dialog.addEventListener('click', (e) => {
            if (e.target === dialog) {
                dialog.remove();
            }
        });
        
        // Sync color inputs
        const colorInputs = dialog.querySelectorAll('input[type="color"]');
        colorInputs.forEach(input => {
            const textInput = dialog.querySelector(`input[name="${input.name}-text"]`);
            
            input.addEventListener('input', () => {
                textInput.value = input.value;
                this.updateDialogPreview(dialog);
            });
            
            textInput.addEventListener('input', () => {
                if (/^#[0-9A-F]{6}$/i.test(textInput.value)) {
                    input.value = textInput.value;
                    this.updateDialogPreview(dialog);
                }
            });
        });
        
        // Form submission
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handlePaletteFormSubmit(form, existingPalette);
            dialog.remove();
        });
        
        // Initial preview update
        this.updateDialogPreview(dialog);
    }
    
    updateDialogPreview(dialog) {
        const preview = dialog.querySelector('#live-preview');
        const form = dialog.querySelector('.mas-palette-dialog-form');
        const formData = new FormData(form);
        
        const colors = {
            primary: formData.get('primary'),
            secondary: formData.get('secondary'),
            background: formData.get('background'),
            text: formData.get('text')
        };
        
        preview.style.background = `linear-gradient(135deg, ${colors.primary}, ${colors.secondary})`;
        preview.style.color = colors.text;
        
        const content = preview.querySelector('.preview-content');
        if (content) {
            content.style.backgroundColor = colors.background;
            content.style.color = colors.text;
        }
    }
    
    handlePaletteFormSubmit(form, existingPalette) {
        const formData = new FormData(form);
        const isEdit = !!existingPalette;
        
        const paletteData = {
            name: formData.get('name'),
            description: formData.get('description') || 'Custom user palette',
            mood: formData.get('mood') || 'custom',
            colors: {
                primary: formData.get('primary'),
                secondary: formData.get('secondary'),
                background: formData.get('background'),
                text: formData.get('text')
            }
        };
        
        try {
            let paletteId;
            
            if (isEdit) {
                paletteId = existingPalette.id;
                this.editCustomPalette(paletteId, paletteData);
                this.notificationManager?.success(`Updated palette: ${paletteData.name}`, 3000);
            } else {
                // Generate unique ID
                paletteId = 'custom-' + Date.now();
                this.createCustomPalette(paletteId, paletteData);
                this.notificationManager?.success(`Created palette: ${paletteData.name}`, 3000);
            }
            
            // Switch to the new/edited palette
            this.setPalette(paletteId);
            
        } catch (error) {
            this.notificationManager?.error(`Failed to ${isEdit ? 'update' : 'create'} palette: ${error.message}`, 5000);
        }
    }
    
    destroy() {
        const switcher = document.querySelector('.mas-palette-switcher');
        if (switcher) switcher.remove();
        
        const notification = document.querySelector('.mas-palette-notification');
        if (notification) notification.remove();
        
        // Remove any open dialogs
        const dialogs = document.querySelectorAll('.mas-palette-dialog-overlay');
        dialogs.forEach(dialog => dialog.remove());
        
        this.isInitialized = false;
    }
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PaletteManager;
} else {
    window.PaletteManager = PaletteManager;
} 