/**
 * Test fixtures and data for testing
 */

export const testFixtures = {
  // Valid settings
  validSettings: {
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
  },

  // Invalid settings
  invalidSettings: {
    menu_background: 'not-a-color',
    menu_text_color: '#zzz',
    menu_width: 'invalid',
    menu_detached: 'not-boolean'
  },

  // Themes
  themes: {
    default: {
      id: 'default',
      name: 'Default Dark',
      type: 'predefined',
      readonly: true,
      settings: {
        menu_background: '#1e1e2e',
        menu_text_color: '#ffffff',
        menu_hover_background: '#2d2d44',
        menu_hover_text_color: '#ffffff'
      },
      metadata: {
        author: 'MAS Team',
        version: '1.0',
        created: '2025-01-01'
      }
    },
    light: {
      id: 'light',
      name: 'Light',
      type: 'predefined',
      readonly: true,
      settings: {
        menu_background: '#ffffff',
        menu_text_color: '#1e1e2e',
        menu_hover_background: '#f5f5f5',
        menu_hover_text_color: '#1e1e2e'
      },
      metadata: {
        author: 'MAS Team',
        version: '1.0',
        created: '2025-01-01'
      }
    },
    custom: {
      id: 'custom-theme-1',
      name: 'My Custom Theme',
      type: 'custom',
      readonly: false,
      settings: {
        menu_background: '#ff5722',
        menu_text_color: '#ffffff',
        menu_hover_background: '#ff7043',
        menu_hover_text_color: '#ffffff'
      },
      metadata: {
        author: 'Test User',
        version: '1.0',
        created: '2025-01-10'
      }
    }
  },

  // Backups
  backups: [
    {
      id: 1704902400,
      timestamp: 1704902400,
      date: '2025-01-10 15:30:00',
      type: 'manual',
      settings: {
        menu_background: '#1e1e2e',
        menu_text_color: '#ffffff'
      },
      metadata: {
        plugin_version: '2.2.0',
        wordpress_version: '6.8',
        user_id: 1,
        note: 'Manual backup before changes'
      }
    },
    {
      id: 1704816000,
      timestamp: 1704816000,
      date: '2025-01-09 15:30:00',
      type: 'automatic',
      settings: {
        menu_background: '#2d2d44',
        menu_text_color: '#ffffff'
      },
      metadata: {
        plugin_version: '2.2.0',
        wordpress_version: '6.8',
        user_id: 1,
        note: 'Automatic backup'
      }
    }
  ],

  // Events
  events: {
    fieldChanged: {
      type: 'field:changed',
      payload: {
        field: 'menu_background',
        value: '#ff5722',
        oldValue: '#1e1e2e'
      }
    },
    settingsSaved: {
      type: 'settings:saved',
      payload: {
        settings: {},
        timestamp: 1704902400
      }
    },
    themeApplied: {
      type: 'theme:applied',
      payload: {
        themeId: 'default',
        settings: {}
      }
    },
    error: {
      type: 'error',
      payload: {
        code: 'validation_failed',
        message: 'Validation failed',
        context: 'settings'
      }
    }
  },

  // State snapshots
  stateSnapshots: {
    initial: {
      settings: {},
      hasUnsavedChanges: false,
      isLoading: false,
      error: null,
      previewActive: false
    },
    loading: {
      settings: {},
      hasUnsavedChanges: false,
      isLoading: true,
      error: null,
      previewActive: false
    },
    withChanges: {
      settings: { menu_background: '#ff5722' },
      hasUnsavedChanges: true,
      isLoading: false,
      error: null,
      previewActive: false
    },
    withError: {
      settings: {},
      hasUnsavedChanges: false,
      isLoading: false,
      error: {
        code: 'validation_failed',
        message: 'Validation failed'
      },
      previewActive: false
    }
  },

  // CSS samples
  css: {
    menu: `
      #adminmenu {
        background: #1e1e2e;
        color: #ffffff;
      }
      #adminmenu li:hover {
        background: #2d2d44;
      }
    `,
    adminBar: `
      #wpadminbar {
        background: #1e1e2e;
        color: #ffffff;
      }
    `,
    effects: `
      .glassmorphism {
        backdrop-filter: blur(10px);
        background: rgba(30, 30, 46, 0.8);
      }
    `
  },

  // Validation errors
  validationErrors: {
    invalidColor: {
      field: 'menu_background',
      message: 'Must be a valid hex color',
      value: 'not-a-color'
    },
    invalidUnit: {
      field: 'menu_width',
      message: 'Must be a valid CSS unit',
      value: 'invalid'
    },
    required: {
      field: 'menu_background',
      message: 'This field is required',
      value: ''
    }
  },

  // Notifications
  notifications: {
    success: {
      type: 'success',
      message: 'Settings saved successfully',
      duration: 3000
    },
    error: {
      type: 'error',
      message: 'Failed to save settings',
      duration: 5000
    },
    warning: {
      type: 'warning',
      message: 'You have unsaved changes',
      duration: 4000
    },
    info: {
      type: 'info',
      message: 'Preview mode active',
      duration: 3000
    }
  }
};

/**
 * Create a deep clone of fixture data
 */
export function cloneFixture(fixture) {
  return JSON.parse(JSON.stringify(fixture));
}

/**
 * Merge fixture with overrides
 */
export function mergeFixture(fixture, overrides) {
  return {
    ...cloneFixture(fixture),
    ...overrides
  };
}
