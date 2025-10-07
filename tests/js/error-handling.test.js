/**
 * Tests for MAS error handling and fallback mechanisms.
 */

// Mock utilities for error handling tests

// Mock error handler
class MASErrorHandler {
  static handle(error, context = '') {
    console.error(`MAS Error [${context}]:`, error);
    this.showNotice(this.getUserMessage(error), 'error');
    
    if (this.isCritical(error)) {
      this.logToServer(error, context);
    }
  }

  static getUserMessage(error) {
    const messages = {
      'rest_forbidden': 'You do not have permission to perform this action.',
      'validation_failed': 'Please check your input and try again.',
      'database_error': 'A database error occurred. Please try again.',
      'network_error': 'Network error. Please check your connection.',
      'timeout_error': 'Request timed out. Please try again.',
      'server_error': 'Server error occurred. Please try again later.'
    };

    return messages[error.code] || messages[error.type] || 'An unexpected error occurred.';
  }

  static showNotice(message, type = 'error') {
    // Mock implementation
    return { message, type };
  }

  static isCritical(error) {
    const criticalCodes = ['database_error', 'server_error', 'security_error'];
    return criticalCodes.includes(error.code) || criticalCodes.includes(error.type);
  }

  static logToServer(error, context) {
    // Mock server logging
    return fetch('/wp-admin/admin-ajax.php', {
      method: 'POST',
      body: new URLSearchParams({
        action: 'mas_log_error',
        error: JSON.stringify(error),
        context: context,
        nonce: masV2Data.nonce
      })
    });
  }
}

// Mock dual-mode client with fallback
class MASDualModeClient {
  constructor() {
    this.restClient = new MASRestClient();
    this.useRest = this.checkRestAvailability();
    this.fallbackAttempts = 0;
    this.maxFallbackAttempts = 3;
  }

  checkRestAvailability() {
    return typeof wpApiSettings !== 'undefined' && wpApiSettings.root;
  }

  async saveSettings(settings) {
    if (this.useRest && this.fallbackAttempts < this.maxFallbackAttempts) {
      try {
        return await this.restClient.saveSettings(settings);
      } catch (error) {
        console.warn('REST API failed, falling back to AJAX', error);
        this.fallbackAttempts++;
        
        if (this.fallbackAttempts >= this.maxFallbackAttempts) {
          this.useRest = false;
        }
      }
    }

    return this.ajaxSaveSettings(settings);
  }

  async ajaxSaveSettings(settings) {
    return new Promise((resolve, reject) => {
      jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          action: 'mas_v2_save_settings',
          nonce: masV2Data.nonce,
          settings: settings
        },
        success: (response) => {
          if (response.success) {
            resolve(response);
          } else {
            reject(new Error(response.data || 'AJAX request failed'));
          }
        },
        error: (xhr, status, error) => {
          reject(new Error(`AJAX Error: ${error}`));
        }
      });
    });
  }

  async getSettings() {
    if (this.useRest) {
      try {
        return await this.restClient.getSettings();
      } catch (error) {
        console.warn('REST API failed, falling back to AJAX', error);
        this.useRest = false;
      }
    }

    return this.ajaxGetSettings();
  }

  async ajaxGetSettings() {
    return new Promise((resolve, reject) => {
      jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          action: 'mas_v2_get_settings',
          nonce: masV2Data.nonce
        },
        success: resolve,
        error: (xhr, status, error) => {
          reject(new Error(`AJAX Error: ${error}`));
        }
      });
    });
  }

  resetFallbackCounter() {
    this.fallbackAttempts = 0;
    this.useRest = this.checkRestAvailability();
  }
}

// Mock REST client for testing
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

  async saveSettings(settings) {
    return this.request('/settings', {
      method: 'POST',
      body: JSON.stringify(settings)
    });
  }

  async getSettings() {
    return this.request('/settings', { method: 'GET' });
  }
}

describe('MASErrorHandler', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('getUserMessage', () => {
    test('should return specific message for known error codes', () => {
      const error = { code: 'rest_forbidden' };
      const message = MASErrorHandler.getUserMessage(error);
      expect(message).toBe('You do not have permission to perform this action.');
    });

    test('should return specific message for known error types', () => {
      const error = { type: 'network_error' };
      const message = MASErrorHandler.getUserMessage(error);
      expect(message).toBe('Network error. Please check your connection.');
    });

    test('should return generic message for unknown errors', () => {
      const error = { code: 'unknown_error' };
      const message = MASErrorHandler.getUserMessage(error);
      expect(message).toBe('An unexpected error occurred.');
    });
  });

  describe('isCritical', () => {
    test('should identify critical errors by code', () => {
      expect(MASErrorHandler.isCritical({ code: 'database_error' })).toBe(true);
      expect(MASErrorHandler.isCritical({ code: 'server_error' })).toBe(true);
      expect(MASErrorHandler.isCritical({ code: 'security_error' })).toBe(true);
    });

    test('should identify critical errors by type', () => {
      expect(MASErrorHandler.isCritical({ type: 'database_error' })).toBe(true);
      expect(MASErrorHandler.isCritical({ type: 'server_error' })).toBe(true);
    });

    test('should not identify non-critical errors as critical', () => {
      expect(MASErrorHandler.isCritical({ code: 'validation_failed' })).toBe(false);
      expect(MASErrorHandler.isCritical({ code: 'rest_forbidden' })).toBe(false);
    });
  });

  describe('handle', () => {
    test('should log error and show notice', () => {
      const consoleSpy = jest.spyOn(console, 'error');
      const showNoticeSpy = jest.spyOn(MASErrorHandler, 'showNotice');
      
      const error = { code: 'validation_failed', message: 'Invalid data' };
      MASErrorHandler.handle(error, 'test-context');

      expect(consoleSpy).toHaveBeenCalledWith('MAS Error [test-context]:', error);
      expect(showNoticeSpy).toHaveBeenCalledWith(
        'Please check your input and try again.',
        'error'
      );
    });

    test('should log critical errors to server', () => {
      const logToServerSpy = jest.spyOn(MASErrorHandler, 'logToServer');
      
      const error = { code: 'database_error', message: 'DB connection failed' };
      MASErrorHandler.handle(error, 'critical-context');

      expect(logToServerSpy).toHaveBeenCalledWith(error, 'critical-context');
    });
  });
});

describe('MASDualModeClient', () => {
  let client;
  const baseUrl = 'http://localhost/wp-json/mas-v2/v1';

  beforeEach(() => {
    client = new MASDualModeClient();
    global.fetch = jest.fn();
    jest.clearAllMocks();
  });

  afterEach(() => {
    jest.restoreAllMocks();
  });

  describe('constructor', () => {
    test('should initialize with REST API enabled', () => {
      expect(client.useRest).toBe(true);
      expect(client.fallbackAttempts).toBe(0);
    });
  });

  describe('checkRestAvailability', () => {
    test('should return true when wpApiSettings is available', () => {
      expect(client.checkRestAvailability()).toBe(true);
    });

    test('should return false when wpApiSettings is undefined', () => {
      const originalWpApiSettings = global.wpApiSettings;
      global.wpApiSettings = undefined;
      
      const newClient = new MASDualModeClient();
      expect(newClient.checkRestAvailability()).toBe(false);
      
      global.wpApiSettings = originalWpApiSettings;
    });
  });

  describe('saveSettings fallback mechanism', () => {
    test('should use REST API when available', async () => {
      const settings = testUtils.createMockSettings();
      const mockResponse = { success: true, data: settings };
      
      global.fetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(mockResponse)
      });

      const result = await client.saveSettings(settings);

      expect(result).toEqual(mockResponse);
      expect(global.fetch).toHaveBeenCalled();
      expect(client.fallbackAttempts).toBe(0);
    });

    test('should fallback to AJAX when REST API fails', async () => {
      const settings = testUtils.createMockSettings();
      const ajaxResponse = { success: true, data: settings };
      
      // Mock REST API failure
      global.fetch.mockResolvedValue({
        ok: false,
        status: 500,
        json: () => Promise.resolve({ code: 'server_error', message: 'Server error' })
      });

      // Mock successful AJAX
      global.jQuery.ajax.mockImplementation(({ success }) => {
        success(ajaxResponse);
      });

      const result = await client.saveSettings(settings);

      expect(result).toEqual(ajaxResponse);
      expect(client.fallbackAttempts).toBe(1);
      expect(global.jQuery.ajax).toHaveBeenCalledWith({
        url: ajaxurl,
        type: 'POST',
        data: {
          action: 'mas_v2_save_settings',
          nonce: masV2Data.nonce,
          settings: settings
        },
        success: expect.any(Function),
        error: expect.any(Function)
      });
    });

    test('should disable REST API after max fallback attempts', async () => {
      const settings = testUtils.createMockSettings();
      
      // Mock REST API failure
      global.fetch.mockResolvedValue({
        ok: false,
        status: 500,
        json: () => Promise.resolve({ code: 'server_error', message: 'Server error' })
      });

      // Mock AJAX success
      global.jQuery.ajax.mockImplementation(({ success }) => {
        success({ success: true, data: settings });
      });

      // Attempt multiple saves to exceed max fallback attempts
      for (let i = 0; i < 4; i++) {
        await client.saveSettings(settings);
      }

      expect(client.useRest).toBe(false);
      expect(client.fallbackAttempts).toBe(3);
    });

    test('should handle AJAX failure', async () => {
      const settings = testUtils.createMockSettings();
      
      // Disable REST API
      client.useRest = false;

      // Mock AJAX failure
      global.jQuery.ajax.mockImplementation(({ error }) => {
        error({}, 'error', 'Network error');
      });

      await expect(client.saveSettings(settings))
        .rejects.toThrow('AJAX Error: Network error');
    });

    test('should handle AJAX response with success: false', async () => {
      const settings = testUtils.createMockSettings();
      
      // Disable REST API
      client.useRest = false;

      // Mock AJAX failure response
      global.jQuery.ajax.mockImplementation(({ success }) => {
        success({ success: false, data: 'Validation failed' });
      });

      await expect(client.saveSettings(settings))
        .rejects.toThrow('Validation failed');
    });
  });

  describe('getSettings fallback mechanism', () => {
    test('should use REST API when available', async () => {
      const settings = testUtils.createMockSettings();
      const mockResponse = { success: true, data: settings };
      
      global.fetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(mockResponse)
      });

      const result = await client.getSettings();

      expect(result).toEqual(mockResponse);
      expect(global.fetch).toHaveBeenCalled();
    });

    test('should fallback to AJAX when REST API fails', async () => {
      const settings = testUtils.createMockSettings();
      const ajaxResponse = { success: true, data: settings };
      
      // Mock REST API failure
      global.fetch.mockResolvedValue({
        ok: false,
        status: 403,
        json: () => Promise.resolve({ code: 'rest_forbidden', message: 'Forbidden' })
      });

      // Mock successful AJAX
      global.jQuery.ajax.mockImplementation(({ success }) => {
        success(ajaxResponse);
      });

      const result = await client.getSettings();

      expect(result).toEqual(ajaxResponse);
      expect(client.useRest).toBe(false);
      expect(global.jQuery.ajax).toHaveBeenCalledWith({
        url: ajaxurl,
        type: 'POST',
        data: {
          action: 'mas_v2_get_settings',
          nonce: masV2Data.nonce
        },
        success: expect.any(Function),
        error: expect.any(Function)
      });
    });
  });

  describe('resetFallbackCounter', () => {
    test('should reset fallback attempts and re-enable REST API', () => {
      client.fallbackAttempts = 3;
      client.useRest = false;

      client.resetFallbackCounter();

      expect(client.fallbackAttempts).toBe(0);
      expect(client.useRest).toBe(true);
    });
  });
});

describe('Network Error Scenarios', () => {
  let client;
  const baseUrl = 'http://localhost/wp-json/mas-v2/v1';

  beforeEach(() => {
    client = new MASRestClient();
    global.fetch = jest.fn();
  });

  afterEach(() => {
    jest.restoreAllMocks();
  });

  test('should handle network timeout', async () => {
    global.fetch.mockRejectedValue(new Error('Network timeout'));

    await expect(client.getSettings())
      .rejects.toThrow('Network timeout');
  });

  test('should handle connection refused', async () => {
    global.fetch.mockRejectedValue(new Error('Connection refused'));

    await expect(client.getSettings())
      .rejects.toThrow('Connection refused');
  });

  test('should handle malformed JSON response', async () => {
    global.fetch.mockResolvedValue({
      ok: true,
      json: () => Promise.reject(new Error('Unexpected token'))
    });

    await expect(client.getSettings())
      .rejects.toThrow();
  });

  test('should handle empty response', async () => {
    global.fetch.mockResolvedValue({
      ok: true,
      json: () => Promise.reject(new Error('Unexpected end of JSON input'))
    });

    await expect(client.getSettings())
      .rejects.toThrow();
  });
});

describe('Authentication Error Scenarios', () => {
  let client;
  const baseUrl = 'http://localhost/wp-json/mas-v2/v1';

  beforeEach(() => {
    client = new MASRestClient();
    global.fetch = jest.fn();
  });

  afterEach(() => {
    jest.restoreAllMocks();
  });

  test('should handle invalid nonce', async () => {
    global.fetch.mockResolvedValue({
      ok: false,
      status: 403,
      json: () => Promise.resolve({
        code: 'rest_cookie_invalid_nonce',
        message: 'Cookie nonce is invalid'
      })
    });

    const error = await client.saveSettings({}).catch(e => e);
    expect(error.message).toBe('Cookie nonce is invalid');
    expect(error.code).toBe('rest_cookie_invalid_nonce');
    expect(error.status).toBe(403);
  });

  test('should handle insufficient permissions', async () => {
    global.fetch.mockResolvedValue({
      ok: false,
      status: 403,
      json: () => Promise.resolve({
        code: 'rest_forbidden',
        message: 'You do not have permission to access this resource'
      })
    });

    const error = await client.getSettings().catch(e => e);
    expect(error.message).toBe('You do not have permission to access this resource');
    expect(error.code).toBe('rest_forbidden');
  });

  test('should handle expired session', async () => {
    global.fetch.mockResolvedValue({
      ok: false,
      status: 401,
      json: () => Promise.resolve({
        code: 'rest_not_logged_in',
        message: 'You are not currently logged in'
      })
    });

    const error = await client.getSettings().catch(e => e);
    expect(error.message).toBe('You are not currently logged in');
    expect(error.code).toBe('rest_not_logged_in');
  });
});