/**
 * Tests for MAS fallback mechanisms and backward compatibility.
 */

// Mock utilities for fallback mechanism tests

// Mock REST client
class MASRestClient {
  constructor() {
    this.baseUrl = wpApiSettings.root + 'mas-v2/v1';
    this.nonce = wpApiSettings.nonce;
  }

  async request(endpoint, options = {}) {
    const url = this.baseUrl + endpoint;
    const response = await fetch(url, {
      ...options,
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': this.nonce,
        ...options.headers
      },
      credentials: 'same-origin'
    });

    const data = await response.json();

    if (!response.ok) {
      const error = new Error(data.message || 'Request failed');
      error.code = data.code;
      error.status = response.status;
      throw error;
    }

    return data;
  }

  async generatePreview(settings) {
    return this.request('/preview', {
      method: 'POST',
      body: JSON.stringify(settings)
    });
  }

  async saveSettings(settings) {
    return this.request('/settings', {
      method: 'POST',
      body: JSON.stringify(settings)
    });
  }
}

// Mock preview manager with debouncing
class MASPreviewManager {
  constructor() {
    this.debounceTimer = null;
    this.debounceDelay = 500;
    this.client = new MASRestClient();
    this.currentRequest = null;
    this.previewCount = 0;
  }

  updatePreview(settings) {
    // Clear existing timer
    if (this.debounceTimer) {
      clearTimeout(this.debounceTimer);
    }

    // Cancel current request if exists
    if (this.currentRequest && this.currentRequest.abort) {
      this.currentRequest.abort();
    }

    // Set new timer
    this.debounceTimer = setTimeout(async () => {
      try {
        this.previewCount++;
        const response = await this.client.generatePreview(settings);
        this.applyPreviewCSS(response.data.css);
        return response;
      } catch (error) {
        console.error('Preview generation failed:', error);
        this.applyFallbackCSS(settings);
        throw error;
      }
    }, this.debounceDelay);

    return this.debounceTimer;
  }

  applyPreviewCSS(css) {
    let styleElement = document.getElementById('mas-preview-styles');
    
    if (!styleElement) {
      styleElement = document.createElement('style');
      styleElement.id = 'mas-preview-styles';
      document.head.appendChild(styleElement);
    }
    
    styleElement.textContent = css;
  }

  applyFallbackCSS(settings) {
    // Generate basic CSS from settings as fallback
    const css = `:root {
      --menu-bg: ${settings.menu_background || '#1e1e2e'};
      --menu-text: ${settings.menu_text_color || '#ffffff'};
    }`;
    this.applyPreviewCSS(css);
  }

  clearPreview() {
    if (this.debounceTimer) {
      clearTimeout(this.debounceTimer);
      this.debounceTimer = null;
    }

    const styleElement = document.getElementById('mas-preview-styles');
    if (styleElement) {
      styleElement.remove();
    }
  }
}

// Mock feature detection utility
class MASFeatureDetection {
  static checkRestApiSupport() {
    return typeof wpApiSettings !== 'undefined' && 
           wpApiSettings.root && 
           wpApiSettings.nonce;
  }

  static checkFetchSupport() {
    return typeof fetch !== 'undefined';
  }

  static checkLocalStorageSupport() {
    try {
      const test = 'test';
      localStorage.setItem(test, test);
      localStorage.removeItem(test);
      return true;
    } catch (e) {
      return false;
    }
  }

  static checkJQuerySupport() {
    return typeof jQuery !== 'undefined' && jQuery.ajax;
  }

  static getCapabilities() {
    return {
      restApi: this.checkRestApiSupport(),
      fetch: this.checkFetchSupport(),
      localStorage: this.checkLocalStorageSupport(),
      jquery: this.checkJQuerySupport()
    };
  }
}

// Mock graceful degradation manager
class MASGracefulDegradation {
  constructor() {
    this.capabilities = MASFeatureDetection.getCapabilities();
    this.fallbackMode = false;
    this.degradationLevel = 0;
  }

  enableFallbackMode(reason = 'unknown') {
    this.fallbackMode = true;
    this.degradationLevel++;
    console.warn(`Fallback mode enabled: ${reason}`);
    
    // Disable advanced features
    this.disableAdvancedFeatures();
  }

  disableAdvancedFeatures() {
    // Mock disabling animations
    document.body.classList.add('mas-reduced-motion');
    
    // Mock disabling complex effects
    document.body.classList.add('mas-basic-mode');
  }

  canUseFeature(feature) {
    const featureRequirements = {
      'live-preview': ['restApi', 'fetch'],
      'auto-save': ['localStorage'],
      'animations': ['fetch'],
      'themes': ['restApi']
    };

    const requirements = featureRequirements[feature] || [];
    return requirements.every(req => this.capabilities[req]);
  }

  getFallbackStrategy(feature) {
    const strategies = {
      'live-preview': 'manual-refresh',
      'auto-save': 'manual-save',
      'animations': 'static-ui',
      'themes': 'basic-colors'
    };

    return strategies[feature] || 'disabled';
  }
}

// Mock backward compatibility layer
class MASBackwardCompatibility {
  constructor() {
    this.legacyMode = false;
    this.migrationWarnings = [];
  }

  checkLegacySupport() {
    // Check for old plugin versions
    const hasLegacyData = localStorage.getItem('mas_legacy_settings');
    const hasOldAjaxHandlers = typeof masV2Data !== 'undefined' && masV2Data.legacyMode;
    
    return hasLegacyData || hasOldAjaxHandlers;
  }

  migrateLegacySettings() {
    const legacySettings = localStorage.getItem('mas_legacy_settings');
    
    if (legacySettings) {
      try {
        const parsed = JSON.parse(legacySettings);
        const migrated = this.transformLegacySettings(parsed);
        
        // Store migrated settings
        localStorage.setItem('mas_v2_settings', JSON.stringify(migrated));
        localStorage.removeItem('mas_legacy_settings');
        
        this.migrationWarnings.push('Legacy settings migrated successfully');
        return migrated;
      } catch (error) {
        this.migrationWarnings.push('Failed to migrate legacy settings');
        return null;
      }
    }
    
    return null;
  }

  transformLegacySettings(legacySettings) {
    // Transform old setting names to new ones
    const fieldMapping = {
      'menu_bg_color': 'menu_background',
      'menu_txt_color': 'menu_text_color',
      'enable_effects': 'glassmorphism_enabled',
      'effect_blur': 'glassmorphism_blur'
    };

    const transformed = {};
    
    Object.keys(legacySettings).forEach(key => {
      const newKey = fieldMapping[key] || key;
      transformed[newKey] = legacySettings[key];
    });

    return transformed;
  }

  showMigrationWarnings() {
    return this.migrationWarnings;
  }
}

describe('MASPreviewManager', () => {
  let previewManager;
  const baseUrl = 'http://localhost/wp-json/mas-v2/v1';

  beforeEach(() => {
    previewManager = new MASPreviewManager();
    global.fetch = jest.fn();
    jest.clearAllMocks();
    jest.useFakeTimers();
  });

  afterEach(() => {
    jest.restoreAllMocks();
    jest.useRealTimers();
    previewManager.clearPreview();
  });

  describe('debouncing mechanism', () => {
    test('should debounce multiple rapid preview requests', async () => {
      const settings1 = testUtils.createMockSettings({ menu_background: '#ff5722' });
      const settings2 = testUtils.createMockSettings({ menu_background: '#2196f3' });
      const settings3 = testUtils.createMockSettings({ menu_background: '#4caf50' });

      const mockResponse = {
        success: true,
        data: { css: ':root { --menu-bg: #4caf50; }', timestamp: 1234567890 }
      };

      global.fetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(mockResponse)
      });

      // Make rapid requests
      previewManager.updatePreview(settings1);
      previewManager.updatePreview(settings2);
      previewManager.updatePreview(settings3);

      // Fast-forward timers
      jest.advanceTimersByTime(500);

      // Wait for async operations
      await new Promise(resolve => setTimeout(resolve, 0));

      // Should only make one request (the last one)
      expect(global.fetch).toHaveBeenCalledTimes(1);
      expect(previewManager.previewCount).toBe(1);
    });

    test('should allow requests after debounce delay', async () => {
      const settings1 = testUtils.createMockSettings({ menu_background: '#ff5722' });
      const settings2 = testUtils.createMockSettings({ menu_background: '#2196f3' });

      const mockResponse = {
        success: true,
        data: { css: ':root { --menu-bg: #ff5722; }', timestamp: 1234567890 }
      };

      global.fetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(mockResponse)
      });

      // First request
      previewManager.updatePreview(settings1);
      jest.advanceTimersByTime(500);
      await new Promise(resolve => setTimeout(resolve, 0));

      // Second request after delay
      previewManager.updatePreview(settings2);
      jest.advanceTimersByTime(500);
      await new Promise(resolve => setTimeout(resolve, 0));

      expect(global.fetch).toHaveBeenCalledTimes(2);
      expect(previewManager.previewCount).toBe(2);
    });

    test('should clear existing timer when new request comes in', () => {
      const settings = testUtils.createMockSettings();
      
      previewManager.updatePreview(settings);
      const firstTimer = previewManager.debounceTimer;
      
      previewManager.updatePreview(settings);
      const secondTimer = previewManager.debounceTimer;
      
      expect(firstTimer).not.toBe(secondTimer);
    });
  });

  describe('fallback CSS generation', () => {
    test('should apply fallback CSS when preview request fails', async () => {
      const settings = testUtils.createMockSettings({ menu_background: '#ff5722' });
      
      global.fetch.mockResolvedValue({
        ok: false,
        status: 500,
        json: () => Promise.resolve({ code: 'server_error', message: 'Server error' })
      });

      const applyFallbackSpy = jest.spyOn(previewManager, 'applyFallbackCSS');

      previewManager.updatePreview(settings);
      jest.advanceTimersByTime(500);

      try {
        await new Promise(resolve => setTimeout(resolve, 0));
      } catch (error) {
        // Expected to fail
      }

      expect(applyFallbackSpy).toHaveBeenCalledWith(settings);
    });

    test('should generate basic CSS from settings', () => {
      const settings = testUtils.createMockSettings({
        menu_background: '#ff5722',
        menu_text_color: '#ffffff'
      });

      const createElementSpy = jest.spyOn(document, 'createElement');
      
      previewManager.applyFallbackCSS(settings);

      expect(createElementSpy).toHaveBeenCalledWith('style');
    });
  });

  describe('CSS application', () => {
    test('should create style element if not exists', () => {
      const css = ':root { --menu-bg: #ff5722; }';
      const createElementSpy = jest.spyOn(document, 'createElement');
      
      previewManager.applyPreviewCSS(css);

      expect(createElementSpy).toHaveBeenCalledWith('style');
    });

    test('should reuse existing style element', () => {
      const css1 = ':root { --menu-bg: #ff5722; }';
      const css2 = ':root { --menu-bg: #2196f3; }';
      
      // Mock existing element
      const mockElement = {
        id: 'mas-preview-styles',
        textContent: ''
      };
      
      document.getElementById = jest.fn().mockReturnValue(mockElement);
      
      previewManager.applyPreviewCSS(css1);
      previewManager.applyPreviewCSS(css2);

      expect(mockElement.textContent).toBe(css2);
    });
  });

  describe('cleanup', () => {
    test('should clear timer and remove style element', () => {
      const settings = testUtils.createMockSettings();
      
      previewManager.updatePreview(settings);
      
      const mockElement = {
        remove: jest.fn()
      };
      
      document.getElementById = jest.fn().mockReturnValue(mockElement);
      
      previewManager.clearPreview();

      expect(previewManager.debounceTimer).toBeNull();
      expect(mockElement.remove).toHaveBeenCalled();
    });
  });
});

describe('MASFeatureDetection', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('checkRestApiSupport', () => {
    test('should return true when wpApiSettings is properly configured', () => {
      expect(MASFeatureDetection.checkRestApiSupport()).toBe(true);
    });

    test('should return false when wpApiSettings is undefined', () => {
      const originalWpApiSettings = global.wpApiSettings;
      global.wpApiSettings = undefined;
      
      expect(MASFeatureDetection.checkRestApiSupport()).toBe(false);
      
      global.wpApiSettings = originalWpApiSettings;
    });

    test('should return false when wpApiSettings lacks required properties', () => {
      const originalWpApiSettings = global.wpApiSettings;
      global.wpApiSettings = { root: 'http://localhost/wp-json/' }; // missing nonce
      
      expect(MASFeatureDetection.checkRestApiSupport()).toBe(false);
      
      global.wpApiSettings = originalWpApiSettings;
    });
  });

  describe('checkFetchSupport', () => {
    test('should return true when fetch is available', () => {
      expect(MASFeatureDetection.checkFetchSupport()).toBe(true);
    });

    test('should return false when fetch is undefined', () => {
      const originalFetch = global.fetch;
      global.fetch = undefined;
      
      expect(MASFeatureDetection.checkFetchSupport()).toBe(false);
      
      global.fetch = originalFetch;
    });
  });

  describe('checkLocalStorageSupport', () => {
    test('should return true when localStorage works', () => {
      expect(MASFeatureDetection.checkLocalStorageSupport()).toBe(true);
    });

    test('should return false when localStorage throws error', () => {
      const originalSetItem = localStorage.setItem;
      localStorage.setItem = jest.fn(() => {
        throw new Error('Storage disabled');
      });
      
      expect(MASFeatureDetection.checkLocalStorageSupport()).toBe(false);
      
      localStorage.setItem = originalSetItem;
    });
  });

  describe('checkJQuerySupport', () => {
    test('should return true when jQuery is available', () => {
      expect(MASFeatureDetection.checkJQuerySupport()).toBe(true);
    });

    test('should return false when jQuery is undefined', () => {
      const originalJQuery = global.jQuery;
      global.jQuery = undefined;
      
      expect(MASFeatureDetection.checkJQuerySupport()).toBe(false);
      
      global.jQuery = originalJQuery;
    });
  });

  describe('getCapabilities', () => {
    test('should return object with all capability checks', () => {
      const capabilities = MASFeatureDetection.getCapabilities();
      
      expect(capabilities).toHaveProperty('restApi');
      expect(capabilities).toHaveProperty('fetch');
      expect(capabilities).toHaveProperty('localStorage');
      expect(capabilities).toHaveProperty('jquery');
      
      expect(typeof capabilities.restApi).toBe('boolean');
      expect(typeof capabilities.fetch).toBe('boolean');
      expect(typeof capabilities.localStorage).toBe('boolean');
      expect(typeof capabilities.jquery).toBe('boolean');
    });
  });
});

describe('MASGracefulDegradation', () => {
  let degradationManager;

  beforeEach(() => {
    degradationManager = new MASGracefulDegradation();
    jest.clearAllMocks();
  });

  describe('constructor', () => {
    test('should initialize with capabilities and default state', () => {
      expect(degradationManager.capabilities).toBeDefined();
      expect(degradationManager.fallbackMode).toBe(false);
      expect(degradationManager.degradationLevel).toBe(0);
    });
  });

  describe('enableFallbackMode', () => {
    test('should enable fallback mode and increment degradation level', () => {
      const consoleSpy = jest.spyOn(console, 'warn');
      
      degradationManager.enableFallbackMode('test reason');

      expect(degradationManager.fallbackMode).toBe(true);
      expect(degradationManager.degradationLevel).toBe(1);
      expect(consoleSpy).toHaveBeenCalledWith('Fallback mode enabled: test reason');
    });

    test('should disable advanced features when enabled', () => {
      const mockClassList = {
        add: jest.fn()
      };
      
      document.body.classList = mockClassList;
      
      degradationManager.enableFallbackMode();

      expect(mockClassList.add).toHaveBeenCalledWith('mas-reduced-motion');
      expect(mockClassList.add).toHaveBeenCalledWith('mas-basic-mode');
    });
  });

  describe('canUseFeature', () => {
    test('should return true when all requirements are met', () => {
      // Mock all capabilities as true
      degradationManager.capabilities = {
        restApi: true,
        fetch: true,
        localStorage: true,
        jquery: true
      };

      expect(degradationManager.canUseFeature('live-preview')).toBe(true);
      expect(degradationManager.canUseFeature('auto-save')).toBe(true);
    });

    test('should return false when requirements are not met', () => {
      // Mock missing capabilities
      degradationManager.capabilities = {
        restApi: false,
        fetch: true,
        localStorage: true,
        jquery: true
      };

      expect(degradationManager.canUseFeature('live-preview')).toBe(false);
      expect(degradationManager.canUseFeature('themes')).toBe(false);
    });

    test('should return true for unknown features', () => {
      expect(degradationManager.canUseFeature('unknown-feature')).toBe(true);
    });
  });

  describe('getFallbackStrategy', () => {
    test('should return appropriate fallback strategy', () => {
      expect(degradationManager.getFallbackStrategy('live-preview')).toBe('manual-refresh');
      expect(degradationManager.getFallbackStrategy('auto-save')).toBe('manual-save');
      expect(degradationManager.getFallbackStrategy('animations')).toBe('static-ui');
      expect(degradationManager.getFallbackStrategy('themes')).toBe('basic-colors');
    });

    test('should return disabled for unknown features', () => {
      expect(degradationManager.getFallbackStrategy('unknown-feature')).toBe('disabled');
    });
  });
});

describe('MASBackwardCompatibility', () => {
  let compatibilityManager;

  beforeEach(() => {
    compatibilityManager = new MASBackwardCompatibility();
    jest.clearAllMocks();
    localStorage.clear();
  });

  describe('checkLegacySupport', () => {
    test('should return true when legacy settings exist', () => {
      localStorage.setItem('mas_legacy_settings', '{"menu_bg_color": "#ff5722"}');
      
      expect(compatibilityManager.checkLegacySupport()).toBe(true);
    });

    test('should return true when legacy mode is enabled', () => {
      global.masV2Data.legacyMode = true;
      
      expect(compatibilityManager.checkLegacySupport()).toBe(true);
      
      delete global.masV2Data.legacyMode;
    });

    test('should return false when no legacy support detected', () => {
      expect(compatibilityManager.checkLegacySupport()).toBe(false);
    });
  });

  describe('migrateLegacySettings', () => {
    test('should migrate legacy settings successfully', () => {
      const legacySettings = {
        menu_bg_color: '#ff5722',
        menu_txt_color: '#ffffff',
        enable_effects: true,
        effect_blur: '10px'
      };
      
      localStorage.setItem('mas_legacy_settings', JSON.stringify(legacySettings));
      
      const migrated = compatibilityManager.migrateLegacySettings();

      expect(migrated).toEqual({
        menu_background: '#ff5722',
        menu_text_color: '#ffffff',
        glassmorphism_enabled: true,
        glassmorphism_blur: '10px'
      });

      expect(localStorage.getItem('mas_legacy_settings')).toBeNull();
      expect(localStorage.getItem('mas_v2_settings')).toBeTruthy();
      expect(compatibilityManager.migrationWarnings).toContain('Legacy settings migrated successfully');
    });

    test('should handle invalid JSON gracefully', () => {
      localStorage.setItem('mas_legacy_settings', 'invalid json{');
      
      const migrated = compatibilityManager.migrateLegacySettings();

      expect(migrated).toBeNull();
      expect(compatibilityManager.migrationWarnings).toContain('Failed to migrate legacy settings');
    });

    test('should return null when no legacy settings exist', () => {
      const migrated = compatibilityManager.migrateLegacySettings();

      expect(migrated).toBeNull();
      expect(compatibilityManager.migrationWarnings).toHaveLength(0);
    });
  });

  describe('transformLegacySettings', () => {
    test('should transform known legacy field names', () => {
      const legacySettings = {
        menu_bg_color: '#ff5722',
        menu_txt_color: '#ffffff',
        enable_effects: true,
        effect_blur: '10px',
        unknown_field: 'value'
      };

      const transformed = compatibilityManager.transformLegacySettings(legacySettings);

      expect(transformed).toEqual({
        menu_background: '#ff5722',
        menu_text_color: '#ffffff',
        glassmorphism_enabled: true,
        glassmorphism_blur: '10px',
        unknown_field: 'value'
      });
    });

    test('should preserve unknown fields', () => {
      const legacySettings = {
        custom_field: 'custom_value',
        another_field: 123
      };

      const transformed = compatibilityManager.transformLegacySettings(legacySettings);

      expect(transformed).toEqual({
        custom_field: 'custom_value',
        another_field: 123
      });
    });
  });

  describe('showMigrationWarnings', () => {
    test('should return array of migration warnings', () => {
      compatibilityManager.migrationWarnings.push('Test warning 1');
      compatibilityManager.migrationWarnings.push('Test warning 2');

      const warnings = compatibilityManager.showMigrationWarnings();

      expect(warnings).toEqual(['Test warning 1', 'Test warning 2']);
    });
  });
});

describe('Integration: Fallback Mechanisms', () => {
  let previewManager;
  let degradationManager;
  let compatibilityManager;

  beforeEach(() => {
    previewManager = new MASPreviewManager();
    degradationManager = new MASGracefulDegradation();
    compatibilityManager = new MASBackwardCompatibility();
    
    fetchMock.reset();
    jest.clearAllMocks();
    localStorage.clear();
  });

  afterEach(() => {
    jest.restoreAllMocks();
  });

  test('should handle complete REST API failure gracefully', async () => {
    // Simulate REST API unavailability
    global.wpApiSettings = undefined;
    
    // Create new managers with disabled REST API
    const newDegradationManager = new MASGracefulDegradation();
    
    expect(newDegradationManager.canUseFeature('live-preview')).toBe(false);
    expect(newDegradationManager.getFallbackStrategy('live-preview')).toBe('manual-refresh');
    
    // Restore wpApiSettings
    global.wpApiSettings = {
      root: 'http://localhost/wp-json/',
      nonce: 'test-nonce-12345'
    };
  });

  test('should migrate legacy settings and apply graceful degradation', () => {
    // Set up legacy settings
    const legacySettings = {
      menu_bg_color: '#ff5722',
      enable_effects: false
    };
    
    localStorage.setItem('mas_legacy_settings', JSON.stringify(legacySettings));
    
    // Migrate settings
    const migrated = compatibilityManager.migrateLegacySettings();
    
    expect(migrated.menu_background).toBe('#ff5722');
    expect(migrated.glassmorphism_enabled).toBe(false);
    
    // Check if effects should be disabled
    if (!migrated.glassmorphism_enabled) {
      degradationManager.enableFallbackMode('effects disabled in legacy settings');
      expect(degradationManager.fallbackMode).toBe(true);
    }
  });

  test('should provide progressive enhancement based on capabilities', () => {
    const capabilities = MASFeatureDetection.getCapabilities();
    
    // Test different capability scenarios
    if (capabilities.restApi && capabilities.fetch) {
      expect(degradationManager.canUseFeature('live-preview')).toBe(true);
    } else {
      expect(degradationManager.getFallbackStrategy('live-preview')).toBe('manual-refresh');
    }
    
    if (capabilities.localStorage) {
      expect(degradationManager.canUseFeature('auto-save')).toBe(true);
    } else {
      expect(degradationManager.getFallbackStrategy('auto-save')).toBe('manual-save');
    }
  });
});
      