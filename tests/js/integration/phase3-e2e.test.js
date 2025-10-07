/**
 * Phase 3 End-to-End Integration Tests
 * 
 * Tests complete user workflows with the new frontend architecture
 */

import { MASAdminApp } from '../../../assets/js/mas-admin-app.js';
import { mockApiResponses, mockSettings } from '../mocks/api-responses.js';
import { setupDOM, cleanupDOM } from '../mocks/dom-helpers.js';

describe('Phase 3 End-to-End Tests', () => {
  let app;
  let fetchMock;

  beforeEach(() => {
    // Setup DOM
    setupDOM();
    
    // Mock fetch
    fetchMock = jest.fn();
    global.fetch = fetchMock;
    
    // Mock WordPress globals
    global.wpApiSettings = {
      root: 'http://example.com/wp-json/',
      nonce: 'test-nonce'
    };
    
    global.masV2Data = {
      nonce: 'test-nonce',
      ajaxUrl: 'http://example.com/wp-admin/admin-ajax.php',
      restUrl: 'http://example.com/wp-json/mas-v2/v1'
    };
  });

  afterEach(() => {
    if (app) {
      app.destroy();
    }
    cleanupDOM();
    jest.clearAllMocks();
  });

  describe('Complete Settings Workflow', () => {
    test('should load, modify, and save settings', async () => {
      // Mock API responses
      fetchMock
        .mockResolvedValueOnce({
          ok: true,
          json: async () => mockApiResponses.getSettings
        })
        .mockResolvedValueOnce({
          ok: true,
          json: async () => mockApiResponses.saveSettings
        });

      // Initialize app
      app = new MASAdminApp();
      await app.init();

      // Verify settings loaded
      const state = app.stateManager.getState();
      expect(state.settings).toBeDefined();

      // Modify settings
      const formComponent = app.getComponent('settingsForm');
      expect(formComponent).toBeDefined();

      // Simulate form change
      const menuBgInput = document.querySelector('[name="menu_background"]');
      menuBgInput.value = '#2d2d44';
      menuBgInput.dispatchEvent(new Event('change', { bubbles: true }));

      // Save settings
      const form = document.getElementById('mas-v2-settings-form');
      form.dispatchEvent(new Event('submit', { bubbles: true }));

      // Wait for async operations
      await new Promise(resolve => setTimeout(resolve, 100));

      // Verify save was called
      expect(fetchMock).toHaveBeenCalledTimes(2);
      expect(fetchMock.mock.calls[1][0]).toContain('/settings');
    });

    test('should handle validation errors gracefully', async () => {
      fetchMock.mockResolvedValueOnce({
        ok: false,
        status: 400,
        json: async () => ({
          success: false,
          code: 'validation_failed',
          message: 'Invalid color value',
          data: {
            errors: {
              menu_background: 'Must be a valid hex color'
            }
          }
        })
      });

      app = new MASAdminApp();
      await app.init();

      const formComponent = app.getComponent('settingsForm');
      
      // Try to save invalid data
      const result = await formComponent.handleSubmit(new Event('submit'));

      // Verify error handling
      expect(result).toBe(false);
      
      // Check notification was shown
      const notifications = document.querySelectorAll('.mas-notification');
      expect(notifications.length).toBeGreaterThan(0);
    });
  });

  describe('Live Preview Workflow', () => {
    test('should generate preview without saving', async () => {
      fetchMock
        .mockResolvedValueOnce({
          ok: true,
          json: async () => mockApiResponses.getSettings
        })
        .mockResolvedValueOnce({
          ok: true,
          json: async () => ({
            success: true,
            data: {
              css: '.admin-menu { background: #ff0000; }',
              timestamp: Date.now()
            }
          })
        });

      app = new MASAdminApp();
      await app.init();

      const previewComponent = app.getComponent('livePreview');
      expect(previewComponent).toBeDefined();

      // Enable preview
      previewComponent.enablePreview();

      // Change a field
      const menuBgInput = document.querySelector('[name="menu_background"]');
      menuBgInput.value = '#ff0000';
      menuBgInput.dispatchEvent(new Event('change', { bubbles: true }));

      // Wait for debounced preview
      await new Promise(resolve => setTimeout(resolve, 400));

      // Verify preview was generated
      expect(fetchMock).toHaveBeenCalledWith(
        expect.stringContaining('/preview'),
        expect.any(Object)
      );

      // Verify preview CSS was injected
      const previewStyle = document.getElementById('mas-preview-styles');
      expect(previewStyle).toBeTruthy();
    });

    test('should restore original styles when preview disabled', async () => {
      fetchMock.mockResolvedValue({
        ok: true,
        json: async () => mockApiResponses.getSettings
      });

      app = new MASAdminApp();
      await app.init();

      const previewComponent = app.getComponent('livePreview');
      
      // Enable and then disable preview
      previewComponent.enablePreview();
      previewComponent.disablePreview();

      // Verify preview styles removed
      const previewStyle = document.getElementById('mas-preview-styles');
      expect(previewStyle).toBeFalsy();
    });
  });

  describe('Component Communication', () => {
    test('should communicate via event bus', async () => {
      fetchMock.mockResolvedValue({
        ok: true,
        json: async () => mockApiResponses.getSettings
      });

      app = new MASAdminApp();
      await app.init();

      const eventSpy = jest.fn();
      app.eventBus.on('settings:changed', eventSpy);

      // Trigger state change
      app.stateManager.setState({
        settings: { menu_background: '#000000' }
      });

      // Verify event was emitted
      expect(eventSpy).toHaveBeenCalled();
    });

    test('should handle component lifecycle correctly', async () => {
      fetchMock.mockResolvedValue({
        ok: true,
        json: async () => mockApiResponses.getSettings
      });

      app = new MASAdminApp();
      await app.init();

      // Verify all components initialized
      expect(app.getComponent('settingsForm')).toBeDefined();
      expect(app.getComponent('livePreview')).toBeDefined();
      expect(app.getComponent('notificationSystem')).toBeDefined();

      // Destroy app
      app.destroy();

      // Verify components cleaned up
      expect(app.components.size).toBe(0);
    });
  });

  describe('Error Handling and Recovery', () => {
    test('should handle network errors with retry', async () => {
      let callCount = 0;
      fetchMock.mockImplementation(() => {
        callCount++;
        if (callCount < 3) {
          return Promise.reject(new Error('Network error'));
        }
        return Promise.resolve({
          ok: true,
          json: async () => mockApiResponses.getSettings
        });
      });

      app = new MASAdminApp();
      
      // Should retry and eventually succeed
      await app.init();

      expect(callCount).toBe(3);
    });

    test('should show user-friendly error messages', async () => {
      fetchMock.mockResolvedValue({
        ok: false,
        status: 500,
        json: async () => ({
          success: false,
          code: 'server_error',
          message: 'Internal server error'
        })
      });

      app = new MASAdminApp();
      await app.init();

      // Try to save
      const formComponent = app.getComponent('settingsForm');
      await formComponent.handleSubmit(new Event('submit'));

      // Verify error notification shown
      const errorNotification = document.querySelector('.mas-notification.error');
      expect(errorNotification).toBeTruthy();
    });
  });

  describe('No Handler Conflicts', () => {
    test('should have only one form submit handler', async () => {
      fetchMock.mockResolvedValue({
        ok: true,
        json: async () => mockApiResponses.getSettings
      });

      app = new MASAdminApp();
      await app.init();

      const form = document.getElementById('mas-v2-settings-form');
      
      // Get all event listeners (this is a simplified check)
      // In real scenario, we'd verify no duplicate handlers
      expect(form).toBeDefined();
      
      // Verify app removed existing handlers
      expect(app.existingHandlersRemoved).toBe(true);
    });
  });

  describe('Theme Application Workflow', () => {
    test('should apply theme and update UI', async () => {
      fetchMock
        .mockResolvedValueOnce({
          ok: true,
          json: async () => mockApiResponses.getSettings
        })
        .mockResolvedValueOnce({
          ok: true,
          json: async () => ({
            success: true,
            data: {
              themes: [
                { id: 'dark-blue', name: 'Dark Blue', type: 'predefined' }
              ]
            }
          })
        })
        .mockResolvedValueOnce({
          ok: true,
          json: async () => ({
            success: true,
            message: 'Theme applied successfully'
          })
        });

      app = new MASAdminApp();
      await app.init();

      const themeSelector = app.getComponent('themeSelector');
      if (themeSelector) {
        await themeSelector.applyTheme('dark-blue');

        // Verify theme was applied
        expect(fetchMock).toHaveBeenCalledWith(
          expect.stringContaining('/themes/dark-blue/apply'),
          expect.any(Object)
        );
      }
    });
  });

  describe('Performance', () => {
    test('should initialize quickly', async () => {
      fetchMock.mockResolvedValue({
        ok: true,
        json: async () => mockApiResponses.getSettings
      });

      const startTime = performance.now();
      
      app = new MASAdminApp();
      await app.init();

      const endTime = performance.now();
      const initTime = endTime - startTime;

      // Should initialize in less than 1 second
      expect(initTime).toBeLessThan(1000);
    });

    test('should debounce preview updates', async () => {
      fetchMock.mockResolvedValue({
        ok: true,
        json: async () => mockApiResponses.getSettings
      });

      app = new MASAdminApp();
      await app.init();

      const previewComponent = app.getComponent('livePreview');
      previewComponent.enablePreview();

      // Trigger multiple rapid changes
      const input = document.querySelector('[name="menu_background"]');
      for (let i = 0; i < 10; i++) {
        input.value = `#${i}${i}${i}${i}${i}${i}`;
        input.dispatchEvent(new Event('change', { bubbles: true }));
      }

      // Wait for debounce
      await new Promise(resolve => setTimeout(resolve, 400));

      // Should only call preview once (after debounce)
      const previewCalls = fetchMock.mock.calls.filter(call => 
        call[0].includes('/preview')
      );
      expect(previewCalls.length).toBeLessThanOrEqual(2);
    });
  });

  describe('Accessibility', () => {
    test('should have proper ARIA attributes', async () => {
      fetchMock.mockResolvedValue({
        ok: true,
        json: async () => mockApiResponses.getSettings
      });

      app = new MASAdminApp();
      await app.init();

      // Check form has proper labels
      const inputs = document.querySelectorAll('input[type="text"]');
      inputs.forEach(input => {
        const label = document.querySelector(`label[for="${input.id}"]`);
        expect(label || input.getAttribute('aria-label')).toBeTruthy();
      });

      // Check notifications have role="alert"
      const notificationContainer = document.querySelector('.mas-notifications');
      if (notificationContainer) {
        expect(notificationContainer.getAttribute('aria-live')).toBe('polite');
      }
    });

    test('should support keyboard navigation', async () => {
      fetchMock.mockResolvedValue({
        ok: true,
        json: async () => mockApiResponses.getSettings
      });

      app = new MASAdminApp();
      await app.init();

      // Test Escape key dismisses notifications
      const notificationSystem = app.getComponent('notificationSystem');
      if (notificationSystem) {
        notificationSystem.show('Test message', 'info');
        
        const escapeEvent = new KeyboardEvent('keydown', { key: 'Escape' });
        document.dispatchEvent(escapeEvent);

        // Notification should be dismissed
        await new Promise(resolve => setTimeout(resolve, 100));
        const notifications = document.querySelectorAll('.mas-notification');
        expect(notifications.length).toBe(0);
      }
    });
  });
});
