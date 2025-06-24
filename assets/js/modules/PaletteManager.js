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
        if (!this.palettes[paletteId]) {
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
                colors: this.palettes[paletteId].colors
            }
        }));
    }
    
    applyPalette(paletteId) {
        const palette = this.palettes[paletteId];
        if (!palette) return;
        
        // Apply to document element
        document.documentElement.setAttribute('data-palette', paletteId);
        
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
        return Object.entries(this.palettes).map(([id, palette], index) => `
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
        
        // Palette selection
        dropdown.addEventListener('click', (e) => {
            const paletteCard = e.target.closest('.mas-palette-card');
            if (paletteCard) {
                const paletteId = paletteCard.dataset.palette;
                this.setPalette(paletteId);
                this.updateSwitcherUI();
                dropdown.classList.remove('show');
                this.notificationManager?.theme(`🎨 Paleta: ${this.palettes[paletteId].name}`, 3000);
            }
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
    

    
    // Public API
    getCurrentPalette() {
        return {
            id: this.currentPalette,
            ...this.palettes[this.currentPalette]
        };
    }
    
    getAllPalettes() {
        return this.palettes;
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
    
    destroy() {
        const switcher = document.querySelector('.mas-palette-switcher');
        if (switcher) switcher.remove();
        
        const notification = document.querySelector('.mas-palette-notification');
        if (notification) notification.remove();
        
        this.isInitialized = false;
    }
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PaletteManager;
} else {
    window.PaletteManager = PaletteManager;
} 