/**
 * APIClient Tests
 */

// Mock MASRestClient before requiring APIClient
global.MASRestClient = class {
  constructor(config) {
    this.config = config;
  }
  async request(endpoint, options) {
    return { success: true, data: {} };
  }
};

const APIClient = require('../../../assets/js/core/APIClient');

describe('APIClient', () => {
  let apiClient;
  let mockFetch;

  beforeEach(() => {
    // Setup global mocks
    global.wpApiSettings = {
      root: 'http://localhost/wp-json/',
      nonce: 'test-nonce'
    };

    global.ajaxurl = 'http://localhost/wp-admin/admin-ajax.php';

    mockFetch = jest.fn();
    global.fetch = mockFetch;

    apiClient = new APIClient({
      debug: false
    });
  });

  afterEach(() => {
    jest.clearAllMocks();
  });

  describe('constructor', () => {
    it('should initialize with default config', () => {
      expect(apiClient.config).toBeDefined();
      expect(apiClient.config.namespace).toBe('mas-v2/v1');
    });

    it('should check REST API availability', () => {
      expect(apiClient.restAvailable).toBe(true);
    });

    it('should initialize with empty cache', () => {
      expect(apiClient.cache.size).toBe(0);
    });
  });

  describe('request()', () => {
    it('should make REST API request', async () => {
      apiClient.restClient.request = jest.fn().mockResolvedValue({
        success: true,
        data: { test: 'value' }
      });

      const result = await apiClient.request('GET', '/settings');

      expect(result).toEqual({
        success: true,
        data: { test: 'value' }
      });
    });

    it('should deduplicate concurrent requests', async () => {
      apiClient.restClient.request = jest.fn().mockResolvedValue({
        success: true
      });

      const promise1 = apiClient.request('GET', '/settings');
      const promise2 = apiClient.request('GET', '/settings');

      await Promise.all([promise1, promise2]);

      expect(apiClient.restClient.request).toHaveBeenCalledTimes(1);
    });

    it('should cache GET requests', async () => {
      apiClient.restClient.request = jest.fn().mockResolvedValue({
        success: true,
        data: { test: 'value' }
      });

      await apiClient.request('GET', '/settings');
      await apiClient.request('GET', '/settings');

      expect(apiClient.restClient.request).toHaveBeenCalledTimes(1);
    });

    it('should not cache POST requests', async () => {
      apiClient.restClient.request = jest.fn().mockResolvedValue({
        success: true
      });

      await apiClient.request('POST', '/settings', { test: 'value' });
      await apiClient.request('POST', '/settings', { test: 'value' });

      expect(apiClient.restClient.request).toHaveBeenCalledTimes(2);
    });

    it('should invalidate cache on write operations', async () => {
      apiClient.restClient.request = jest.fn()
        .mockResolvedValueOnce({ success: true, data: { test: 'old' } })
        .mockResolvedValueOnce({ success: true })
        .mockResolvedValueOnce({ success: true, data: { test: 'new' } });

      // GET request (cached)
      await apiClient.request('GET', '/settings');
      
      // POST request (invalidates cache)
      await apiClient.request('POST', '/settings', { test: 'new' });
      
      // GET request (should fetch again)
      await apiClient.request('GET', '/settings');

      expect(apiClient.restClient.request).toHaveBeenCalledTimes(3);
    });
  });

  describe('retry logic', () => {
    it('should retry on network error', async () => {
      apiClient.restClient.request = jest.fn()
        .mockRejectedValueOnce(new Error('Network error'))
        .mockRejectedValueOnce(new Error('Network error'))
        .mockResolvedValueOnce({ success: true });

      const result = await apiClient.request('GET', '/settings');

      expect(result).toEqual({ success: true });
      expect(apiClient.restClient.request).toHaveBeenCalledTimes(3);
    });

    it('should not retry on auth error', async () => {
      const authError = new Error('Unauthorized');
      authError.status = 401;

      apiClient.restClient.request = jest.fn().mockRejectedValue(authError);

      await expect(apiClient.request('GET', '/settings')).rejects.toThrow();
      expect(apiClient.restClient.request).toHaveBeenCalledTimes(1);
    });

    it('should not retry on validation error', async () => {
      const validationError = new Error('Validation failed');
      validationError.status = 400;
      validationError.code = 'validation_failed';

      apiClient.restClient.request = jest.fn().mockRejectedValue(validationError);

      await expect(apiClient.request('POST', '/settings', {})).rejects.toThrow();
      expect(apiClient.restClient.request).toHaveBeenCalledTimes(1);
    });

    it('should use exponential backoff', async () => {
      jest.useFakeTimers();

      apiClient.restClient.request = jest.fn()
        .mockRejectedValueOnce(new Error('Network error'))
        .mockRejectedValueOnce(new Error('Network error'))
        .mockResolvedValueOnce({ success: true });

      const promise = apiClient.request('GET', '/settings');

      // Fast-forward through delays
      await jest.runAllTimersAsync();

      await promise;

      jest.useRealTimers();
    });
  });

  describe('timeout handling', () => {
    it('should timeout long requests', async () => {
      jest.useFakeTimers();

      apiClient.restClient.request = jest.fn().mockImplementation(() => {
        return new Promise(() => {}); // Never resolves
      });

      const promise = apiClient.request('GET', '/settings', null, { timeout: 1000 });

      jest.advanceTimersByTime(1001);

      await expect(promise).rejects.toThrow('Request timeout');

      jest.useRealTimers();
    });
  });

  describe('AJAX fallback', () => {
    beforeEach(() => {
      global.jQuery = {
        ajax: jest.fn()
      };
    });

    it('should fallback to AJAX on REST failure', async () => {
      apiClient.restClient.request = jest.fn().mockRejectedValue(new Error('REST failed'));

      global.jQuery.ajax.mockImplementation((options) => {
        options.success({ success: true, data: { test: 'value' } });
      });

      const result = await apiClient.request('GET', '/settings');

      expect(result).toEqual({ test: 'value' });
      expect(global.jQuery.ajax).toHaveBeenCalled();
    });

    it('should not fallback if disabled', async () => {
      apiClient.config.useAjaxFallback = false;
      apiClient.restClient.request = jest.fn().mockRejectedValue(new Error('REST failed'));

      await expect(apiClient.request('GET', '/settings')).rejects.toThrow();
      expect(global.jQuery.ajax).not.toHaveBeenCalled();
    });
  });

  describe('public API methods', () => {
    beforeEach(() => {
      apiClient.request = jest.fn().mockResolvedValue({ success: true });
    });

    it('should call getSettings', async () => {
      await apiClient.getSettings();
      expect(apiClient.request).toHaveBeenCalledWith('GET', '/settings');
    });

    it('should call saveSettings', async () => {
      const settings = { test: 'value' };
      await apiClient.saveSettings(settings);
      expect(apiClient.request).toHaveBeenCalledWith('POST', '/settings', settings);
    });

    it('should call updateSettings', async () => {
      const settings = { test: 'value' };
      await apiClient.updateSettings(settings);
      expect(apiClient.request).toHaveBeenCalledWith('PUT', '/settings', settings);
    });

    it('should call resetSettings', async () => {
      await apiClient.resetSettings();
      expect(apiClient.request).toHaveBeenCalledWith('DELETE', '/settings');
    });

    it('should call getThemes', async () => {
      await apiClient.getThemes();
      expect(apiClient.request).toHaveBeenCalledWith('GET', '/themes');
    });

    it('should call applyTheme', async () => {
      await apiClient.applyTheme('dark');
      expect(apiClient.request).toHaveBeenCalledWith('POST', '/themes/dark/apply');
    });

    it('should call generatePreview', async () => {
      const settings = { test: 'value' };
      await apiClient.generatePreview(settings);
      expect(apiClient.request).toHaveBeenCalledWith('POST', '/preview', { settings });
    });

    it('should call listBackups', async () => {
      await apiClient.listBackups();
      expect(apiClient.request).toHaveBeenCalledWith('GET', '/backups');
    });

    it('should call createBackup', async () => {
      await apiClient.createBackup({ note: 'test' });
      expect(apiClient.request).toHaveBeenCalledWith('POST', '/backups', { note: 'test' });
    });

    it('should call restoreBackup', async () => {
      await apiClient.restoreBackup(123);
      expect(apiClient.request).toHaveBeenCalledWith('POST', '/backups/123/restore');
    });

    it('should call deleteBackup', async () => {
      await apiClient.deleteBackup(123);
      expect(apiClient.request).toHaveBeenCalledWith('DELETE', '/backups/123');
    });
  });

  describe('cache management', () => {
    it('should get cache statistics', () => {
      const stats = apiClient.getCacheStats();
      expect(stats).toHaveProperty('enabled');
      expect(stats).toHaveProperty('size');
      expect(stats).toHaveProperty('maxSize');
    });

    it('should enable cache', () => {
      apiClient.disableCache();
      apiClient.enableCache();
      expect(apiClient.cacheConfig.enabled).toBe(true);
    });

    it('should disable cache', () => {
      apiClient.disableCache();
      expect(apiClient.cacheConfig.enabled).toBe(false);
      expect(apiClient.cache.size).toBe(0);
    });

    it('should invalidate cache by pattern', async () => {
      apiClient.restClient.request = jest.fn().mockResolvedValue({ success: true });

      await apiClient.request('GET', '/settings');
      await apiClient.request('GET', '/themes');

      apiClient.invalidateCache('/settings');

      expect(apiClient.cache.size).toBeLessThan(2);
    });

    it('should enforce cache size limit', async () => {
      apiClient.cacheConfig.maxSize = 2;
      apiClient.restClient.request = jest.fn().mockResolvedValue({ success: true });

      await apiClient.request('GET', '/endpoint1');
      await apiClient.request('GET', '/endpoint2');
      await apiClient.request('GET', '/endpoint3');

      expect(apiClient.cache.size).toBeLessThanOrEqual(2);
    });
  });

  describe('request cancellation', () => {
    it('should cancel pending request', () => {
      apiClient.pendingRequests.set('GET:/settings', Promise.resolve());
      apiClient.cancelRequest('GET', '/settings');

      expect(apiClient.pendingRequests.has('GET:/settings')).toBe(false);
    });

    it('should clear all pending requests', () => {
      apiClient.pendingRequests.set('GET:/settings', Promise.resolve());
      apiClient.pendingRequests.set('GET:/themes', Promise.resolve());

      apiClient.clearPendingRequests();

      expect(apiClient.pendingRequests.size).toBe(0);
    });

    it('should get pending request count', () => {
      apiClient.pendingRequests.set('GET:/settings', Promise.resolve());
      apiClient.pendingRequests.set('GET:/themes', Promise.resolve());

      expect(apiClient.getPendingRequestCount()).toBe(2);
    });
  });
});
