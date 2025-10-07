/**
 * Mock API responses for testing
 */

export const mockApiResponses = {
  // Settings responses
  getSettings: {
    success: true,
    message: 'Settings retrieved successfully',
    data: {
      menu_background: '#1e1e2e',
      menu_text_color: '#ffffff',
      menu_hover_background: '#2d2d44',
      menu_hover_text_color: '#ffffff',
      menu_active_background: '#3d3d5c',
      menu_active_text_color: '#ffffff',
      menu_width: '280px',
      menu_item_height: '48px',
      menu_border_radius: '12px',
      menu_detached: false,
      admin_bar_background: '#1e1e2e',
      admin_bar_text_color: '#ffffff',
      admin_bar_floating: false,
      glassmorphism_enabled: true,
      glassmorphism_blur: '10px',
      shadow_effects_enabled: true,
      shadow_intensity: 'medium',
      animations_enabled: true,
      animation_speed: 'normal',
      current_theme: 'default',
      performance_mode: false,
      debug_mode: false
    }
  },

  saveSettings: {
    success: true,
    message: 'Settings saved successfully',
    data: {
      css_generated: true,
      timestamp: 1704902400
    }
  },

  // Theme responses
  getThemes: {
    success: true,
    message: 'Themes retrieved successfully',
    data: [
      {
        id: 'default',
        name: 'Default',
        type: 'predefined',
        readonly: true,
        settings: {
          menu_background: '#1e1e2e',
          menu_text_color: '#ffffff'
        }
      },
      {
        id: 'light',
        name: 'Light',
        type: 'predefined',
        readonly: true,
        settings: {
          menu_background: '#ffffff',
          menu_text_color: '#1e1e2e'
        }
      }
    ]
  },

  applyTheme: {
    success: true,
    message: 'Theme applied successfully',
    data: {
      theme_id: 'default',
      settings_updated: true
    }
  },

  // Backup responses
  listBackups: {
    success: true,
    message: 'Backups retrieved successfully',
    data: [
      {
        id: 1704902400,
        timestamp: 1704902400,
        date: '2025-01-10 15:30:00',
        type: 'manual',
        metadata: {
          plugin_version: '2.2.0',
          wordpress_version: '6.8',
          user_id: 1,
          note: 'Manual backup'
        }
      }
    ]
  },

  createBackup: {
    success: true,
    message: 'Backup created successfully',
    data: {
      id: 1704902400,
      timestamp: 1704902400
    }
  },

  restoreBackup: {
    success: true,
    message: 'Backup restored successfully',
    data: {
      restored: true,
      settings_updated: true
    }
  },

  // Preview responses
  generatePreview: {
    success: true,
    message: 'Preview generated successfully',
    data: {
      css: '#adminmenu { background: #1e1e2e; color: #ffffff; }',
      timestamp: 1704902400
    }
  },

  // Error responses
  validationError: {
    code: 'validation_failed',
    message: 'Validation failed',
    data: {
      status: 400,
      errors: {
        menu_background: 'Must be a valid hex color'
      }
    }
  },

  authError: {
    code: 'rest_forbidden',
    message: 'You do not have permission to access this resource',
    data: {
      status: 403
    }
  },

  notFoundError: {
    code: 'not_found',
    message: 'Resource not found',
    data: {
      status: 404
    }
  },

  serverError: {
    code: 'internal_error',
    message: 'An internal server error occurred',
    data: {
      status: 500
    }
  }
};

/**
 * Create a mock fetch response
 */
export function createMockFetchResponse(data, status = 200) {
  return Promise.resolve({
    ok: status >= 200 && status < 300,
    status,
    statusText: status === 200 ? 'OK' : 'Error',
    json: () => Promise.resolve(data),
    text: () => Promise.resolve(JSON.stringify(data)),
    headers: new Map([
      ['content-type', 'application/json']
    ])
  });
}

/**
 * Create a mock fetch error
 */
export function createMockFetchError(message = 'Network error') {
  return Promise.reject(new Error(message));
}

/**
 * Create a mock AJAX response
 */
export function createMockAjaxResponse(data, success = true) {
  return {
    success,
    data: success ? data : undefined,
    error: success ? undefined : data
  };
}
