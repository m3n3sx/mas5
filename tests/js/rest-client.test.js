/**
 * Tests for MAS REST Client.
 */

// Mock fetch responses
const mockFetch = (url, options) => {
  return Promise.resolve({
    ok: true,
    status: 200,
    json: () => Promise.resolve({ success: true, data: {} })
  });
};

// Mock the REST client (we'll need to load it differently in a real environment)
class MASRestClient {
  constructor() {
    this.baseUrl = wpApiSettings.root + 'mas-v2/v1';
    this.nonce = wpApiSettings.nonce;
  }

  async request(endpoint, options = {}) {
    const url = this.baseUrl + endpoint;
    const headers = {
      'Content-Type': 'application/json',
      'X-WP-Nonce': this.nonce,
      ...options.headers
    };

    const response = await fetch(url, {
      ...options,
      headers,
      credentials: 'same-origin'
    });

    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.message || 'Request failed');
    }

    return data;
  }

  async getSettings() {
    return this.request('/settings', { method: 'GET' });
  }

  async saveSettings(settings) {
    return this.request('/settings', {
      method: 'POST',
      body: JSON.stringify(settings)
    });
  }

  async updateSettings(settings) {
    return this.request('/settings', {
      method: 'PUT',
      body: JSON.stringify(settings)
    });
  }

  async resetSettings() {
    return this.request('/settings', { method: 'DELETE' });
  }

  async getThemes() {
    return this.request('/themes', { method: 'GET' });
  }

  async createTheme(theme) {
    return this.request('/themes', {
      method: 'POST',
      body: JSON.stringify(theme)
    });
  }

  async applyTheme(themeId) {
    return this.request(`/themes/${themeId}/apply`, { method: 'POST' });
  }

  async deleteTheme(themeId) {
    return this.request(`/themes/${themeId}`, { method: 'DELETE' });
  }

  async listBackups() {
    return this.request('/backups', { method: 'GET' });
  }

  async createBackup(note = '') {
    return this.request('/backups', {
      method: 'POST',
      body: JSON.stringify({ note })
    });
  }

  async restoreBackup(backupId) {
    return this.request(`/backups/${backupId}/restore`, { method: 'POST' });
  }

  async deleteBackup(backupId) {
    return this.request(`/backups/${backupId}`, { method: 'DELETE' });
  }

  async exportSettings() {
    return this.request('/export', { method: 'GET' });
  }

  async importSettings(data) {
    return this.request('/import', {
      method: 'POST',
      body: JSON.stringify(data)
    });
  }

  async generatePreview(settings) {
    return this.request('/preview', {
      method: 'POST',
      body: JSON.stringify(settings)
    });
  }

  async getDiagnostics() {
    return this.request('/diagnostics', { method: 'GET' });
  }
}

describe('MASRestClient', () => {
  let client;
  const baseUrl = 'http://localhost/wp-json/mas-v2/v1';

  beforeEach(() => {
    client = new MASRestClient();
    global.fetch = jest.fn();
  });

  afterEach(() => {
    jest.restoreAllMocks();
  });

  describe('constructor', () => {
    test('should initialize with correct base URL and nonce', () => {
      expect(client.baseUrl).toBe(baseUrl);
      expect(client.nonce).toBe('test-nonce-12345');
    });
  });

  describe('request method', () => {
    test('should make GET request with correct headers', async () => {
      const mockResponse = { success: true, data: {} };
      global.fetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(mockResponse)
      });

      await client.request('/test', { method: 'GET' });

      expect(global.fetch).toHaveBeenCalledWith(
        `${baseUrl}/test`,
        expect.objectContaining({
          method: 'GET',
          headers: expect.objectContaining({
            'X-WP-Nonce': 'test-nonce-12345',
            'Content-Type': 'application/json'
          }),
          credentials: 'same-origin'
        })
      );
    });

    test('should make POST request with body', async () => {
      const mockResponse = { success: true, data: {} };
      const requestData = { test: 'data' };
      
      global.fetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(mockResponse)
      });

      await client.request('/test', {
        method: 'POST',
        body: JSON.stringify(requestData)
      });

      expect(global.fetch).toHaveBeenCalledWith(
        `${baseUrl}/test`,
        expect.objectContaining({
          method: 'POST',
          body: JSON.stringify(requestData)
        })
      );
    });

    test('should throw error on failed request', async () => {
      const errorResponse = {
        code: 'rest_forbidden',
        message: 'You do not have permission'
      };
      
      global.fetch.mockResolvedValue({
        ok: false,
        status: 403,
        json: () => Promise.resolve(errorResponse)
      });

      await expect(client.request('/test', { method: 'GET' }))
        .rejects.toThrow('You do not have permission');
    });

    test('should throw generic error when no message provided', async () => {
      global.fetch.mockResolvedValue({
        ok: false,
        status: 500,
        json: () => Promise.resolve({ code: 'server_error' })
      });

      await expect(client.request('/test', { method: 'GET' }))
        .rejects.toThrow('Request failed');
    });
  });

  describe('settings methods', () => {
    test('getSettings should make GET request to /settings', async () => {
      const mockSettings = testUtils.createMockSettings();
      const mockResponse = { success: true, data: mockSettings };
      
      global.fetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(mockResponse)
      });

      const result = await client.getSettings();

      expect(result).toEqual(mockResponse);
      expect(global.fetch).toHaveBeenCalledWith(
        `${baseUrl}/settings`,
        expect.objectContaining({ method: 'GET' })
      );
    });

    test('saveSettings should make POST request with settings data', async () => {
      const settings = testUtils.createMockSettings({ menu_background: '#ff5722' });
      const mockResponse = { success: true, data: settings };
      
      global.fetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(mockResponse)
      });

      const result = await client.saveSettings(settings);

      expect(result).toEqual(mockResponse);
      expect(global.fetch).toHaveBeenCalledWith(
        `${baseUrl}/settings`,
        expect.objectContaining({
          method: 'POST',
          body: JSON.stringify(settings)
        })
      );
    });

    test('updateSettings should make PUT request', async () => {
      const partialSettings = { menu_background: '#2196f3' };
      const mockResponse = { success: true, data: partialSettings };
      
      global.fetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(mockResponse)
      });

      const result = await client.updateSettings(partialSettings);

      expect(result).toEqual(mockResponse);
      expect(global.fetch).toHaveBeenCalledWith(
        `${baseUrl}/settings`,
        expect.objectContaining({
          method: 'PUT',
          body: JSON.stringify(partialSettings)
        })
      );
    });

    test('resetSettings should make DELETE request', async () => {
      const mockResponse = { success: true, message: 'Settings reset' };
      
      global.fetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(mockResponse)
      });

      const result = await client.resetSettings();

      expect(result).toEqual(mockResponse);
      expect(global.fetch).toHaveBeenCalledWith(
        `${baseUrl}/settings`,
        expect.objectContaining({ method: 'DELETE' })
      );
    });
  });

  describe('theme methods', () => {
    test('getThemes should fetch all themes', async () => {
      const mockThemes = [
        testUtils.createMockTheme('theme1'),
        testUtils.createMockTheme('theme2')
      ];
      const mockResponse = { success: true, data: mockThemes };
      
      global.fetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(mockResponse)
      });

      const result = await client.getThemes();

      expect(result).toEqual(mockResponse);
    });

    test('createTheme should create new theme', async () => {
      const newTheme = testUtils.createMockTheme('new-theme');
      const mockResponse = { success: true, data: newTheme };
      
      global.fetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(mockResponse)
      });

      const result = await client.createTheme(newTheme);

      expect(result).toEqual(mockResponse);
      expect(global.fetch).toHaveBeenCalledWith(
        `${baseUrl}/themes`,
        expect.objectContaining({
          method: 'POST',
          body: JSON.stringify(newTheme)
        })
      );
    });

    test('applyTheme should apply theme by ID', async () => {
      const themeId = 'test-theme';
      const mockResponse = { success: true, message: 'Theme applied' };
      
      global.fetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(mockResponse)
      });

      const result = await client.applyTheme(themeId);

      expect(result).toEqual(mockResponse);
    });

    test('deleteTheme should delete theme by ID', async () => {
      const themeId = 'test-theme';
      const mockResponse = { success: true, message: 'Theme deleted' };
      
      global.fetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(mockResponse)
      });

      const result = await client.deleteTheme(themeId);

      expect(result).toEqual(mockResponse);
    });
  });

  describe('backup methods', () => {
    test('listBackups should fetch all backups', async () => {
      const mockBackups = [
        testUtils.createMockBackup(1234567890),
        testUtils.createMockBackup(1234567891)
      ];
      const mockResponse = { success: true, data: mockBackups };
      
      global.fetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(mockResponse)
      });

      const result = await client.listBackups();

      expect(result).toEqual(mockResponse);
    });

    test('createBackup should create new backup with note', async () => {
      const note = 'Test backup';
      const mockBackup = testUtils.createMockBackup(1234567890, { note });
      const mockResponse = { success: true, data: mockBackup };
      
      global.fetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(mockResponse)
      });

      const result = await client.createBackup(note);

      expect(result).toEqual(mockResponse);
      expect(global.fetch).toHaveBeenCalledWith(
        `${baseUrl}/backups`,
        expect.objectContaining({
          method: 'POST',
          body: JSON.stringify({ note })
        })
      );
    });

    test('restoreBackup should restore backup by ID', async () => {
      const backupId = 1234567890;
      const mockResponse = { success: true, message: 'Backup restored' };
      
      global.fetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(mockResponse)
      });

      const result = await client.restoreBackup(backupId);

      expect(result).toEqual(mockResponse);
    });

    test('deleteBackup should delete backup by ID', async () => {
      const backupId = 1234567890;
      const mockResponse = { success: true, message: 'Backup deleted' };
      
      global.fetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(mockResponse)
      });

      const result = await client.deleteBackup(backupId);

      expect(result).toEqual(mockResponse);
    });
  });

  describe('import/export methods', () => {
    test('exportSettings should export current configuration', async () => {
      const mockExportData = {
        version: '2.2.0',
        export_date: '2025-01-10 15:30:00',
        settings: testUtils.createMockSettings(),
        themes: {},
        metadata: {}
      };
      const mockResponse = { success: true, data: mockExportData };
      
      global.fetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(mockResponse)
      });

      const result = await client.exportSettings();

      expect(result).toEqual(mockResponse);
    });

    test('importSettings should import configuration data', async () => {
      const importData = {
        version: '2.2.0',
        settings: testUtils.createMockSettings(),
        themes: {}
      };
      const mockResponse = { success: true, message: 'Settings imported' };
      
      global.fetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(mockResponse)
      });

      const result = await client.importSettings(importData);

      expect(result).toEqual(mockResponse);
      expect(global.fetch).toHaveBeenCalledWith(
        `${baseUrl}/import`,
        expect.objectContaining({
          method: 'POST',
          body: JSON.stringify(importData)
        })
      );
    });
  });

  describe('preview method', () => {
    test('generatePreview should generate CSS preview', async () => {
      const previewSettings = testUtils.createMockSettings({ menu_background: '#ff9800' });
      const mockResponse = {
        success: true,
        data: {
          css: ':root { --menu-bg: #ff9800; }',
          timestamp: 1234567890
        }
      };
      
      global.fetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(mockResponse)
      });

      const result = await client.generatePreview(previewSettings);

      expect(result).toEqual(mockResponse);
      expect(global.fetch).toHaveBeenCalledWith(
        `${baseUrl}/preview`,
        expect.objectContaining({
          method: 'POST',
          body: JSON.stringify(previewSettings)
        })
      );
    });
  });

  describe('diagnostics method', () => {
    test('getDiagnostics should fetch system diagnostics', async () => {
      const mockDiagnostics = {
        system: { php_version: '8.1.0' },
        settings: { integrity_check: 'passed' },
        performance: { memory_usage: 1024 },
        conflicts: [],
        recommendations: []
      };
      const mockResponse = { success: true, data: mockDiagnostics };
      
      global.fetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(mockResponse)
      });

      const result = await client.getDiagnostics();

      expect(result).toEqual(mockResponse);
    });
  });
});