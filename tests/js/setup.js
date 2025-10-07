/**
 * Jest setup file for Modern Admin Styler V2 tests.
 */

import '@testing-library/jest-dom';

// Mock WordPress globals
global.wp = {
  i18n: {
    __: (text) => text,
    _x: (text) => text,
    _n: (single, plural, number) => number === 1 ? single : plural,
    sprintf: (format, ...args) => {
      return format.replace(/%[sd]/g, () => args.shift());
    }
  },
  hooks: {
    addAction: jest.fn(),
    addFilter: jest.fn(),
    doAction: jest.fn(),
    applyFilters: jest.fn()
  },
  ajax: {
    post: jest.fn(),
    send: jest.fn()
  }
};

global.wpApiSettings = {
  root: 'http://localhost/wp-json/',
  nonce: 'test-nonce-12345',
  versionString: 'wp/v2/'
};

global.ajaxurl = 'http://localhost/wp-admin/admin-ajax.php';

global.masV2Data = {
  nonce: 'test-mas-nonce-12345',
  ajaxUrl: 'http://localhost/wp-admin/admin-ajax.php',
  restUrl: 'http://localhost/wp-json/mas-v2/v1/',
  restNonce: 'test-rest-nonce-12345',
  settings: {
    menu_background: '#1e1e2e',
    menu_text_color: '#ffffff',
    menu_hover_background: '#2d2d44',
    menu_hover_text_color: '#ffffff'
  },
  featureFlags: {
    rest_api_enabled: true,
    dual_mode_enabled: true,
    deprecation_warnings: true
  }
};

// Mock jQuery
global.$ = global.jQuery = {
  ajax: jest.fn(),
  post: jest.fn(),
  get: jest.fn(),
  fn: {
    extend: jest.fn()
  },
  extend: jest.fn(),
  each: jest.fn(),
  map: jest.fn(),
  isFunction: jest.fn(),
  isPlainObject: jest.fn(),
  isArray: Array.isArray,
  ready: jest.fn((callback) => callback()),
  on: jest.fn(),
  off: jest.fn(),
  trigger: jest.fn(),
  find: jest.fn(() => ({
    length: 0,
    addClass: jest.fn(),
    removeClass: jest.fn(),
    toggleClass: jest.fn(),
    attr: jest.fn(),
    prop: jest.fn(),
    val: jest.fn(),
    text: jest.fn(),
    html: jest.fn(),
    css: jest.fn(),
    show: jest.fn(),
    hide: jest.fn(),
    fadeIn: jest.fn(),
    fadeOut: jest.fn(),
    slideUp: jest.fn(),
    slideDown: jest.fn(),
    animate: jest.fn(),
    on: jest.fn(),
    off: jest.fn(),
    click: jest.fn(),
    change: jest.fn(),
    submit: jest.fn()
  }))
};

// Mock console methods for cleaner test output
global.console = {
  ...console,
  log: jest.fn(),
  warn: jest.fn(),
  error: jest.fn(),
  info: jest.fn(),
  debug: jest.fn()
};

// Mock localStorage
const localStorageMock = {
  getItem: jest.fn(),
  setItem: jest.fn(),
  removeItem: jest.fn(),
  clear: jest.fn()
};
global.localStorage = localStorageMock;

// Mock sessionStorage
const sessionStorageMock = {
  getItem: jest.fn(),
  setItem: jest.fn(),
  removeItem: jest.fn(),
  clear: jest.fn()
};
global.sessionStorage = sessionStorageMock;

// Mock fetch
global.fetch = jest.fn();

// Mock DOM methods
global.document.createElement = jest.fn((tagName) => ({
  tagName: tagName.toUpperCase(),
  id: '',
  className: '',
  innerHTML: '',
  textContent: '',
  style: {},
  setAttribute: jest.fn(),
  getAttribute: jest.fn(),
  appendChild: jest.fn(),
  removeChild: jest.fn(),
  addEventListener: jest.fn(),
  removeEventListener: jest.fn(),
  dispatchEvent: jest.fn(),
  querySelector: jest.fn(),
  querySelectorAll: jest.fn(() => [])
}));

global.document.getElementById = jest.fn();
global.document.querySelector = jest.fn();
global.document.querySelectorAll = jest.fn(() => []);
Object.defineProperty(global.document, 'head', {
  value: {
    appendChild: jest.fn(),
    removeChild: jest.fn()
  },
  writable: true
});

// Setup and teardown
beforeEach(() => {
  // Reset all mocks before each test
  jest.clearAllMocks();
  
  // Reset localStorage and sessionStorage
  localStorageMock.getItem.mockClear();
  localStorageMock.setItem.mockClear();
  localStorageMock.removeItem.mockClear();
  localStorageMock.clear.mockClear();
  
  sessionStorageMock.getItem.mockClear();
  sessionStorageMock.setItem.mockClear();
  sessionStorageMock.removeItem.mockClear();
  sessionStorageMock.clear.mockClear();
});

afterEach(() => {
  // Clean up after each test
  jest.restoreAllMocks();
});

// Custom matchers
expect.extend({
  toBeValidHexColor(received) {
    const pass = /^#[0-9A-F]{6}$/i.test(received);
    if (pass) {
      return {
        message: () => `expected ${received} not to be a valid hex color`,
        pass: true
      };
    } else {
      return {
        message: () => `expected ${received} to be a valid hex color`,
        pass: false
      };
    }
  },
  
  toBeValidCSSUnit(received) {
    const pass = /^\d+(px|em|rem|%|vh|vw)$/.test(received);
    if (pass) {
      return {
        message: () => `expected ${received} not to be a valid CSS unit`,
        pass: true
      };
    } else {
      return {
        message: () => `expected ${received} to be a valid CSS unit`,
        pass: false
      };
    }
  }
});

// Test utilities
global.testUtils = {
  createMockResponse: (data, status = 200) => ({
    ok: status >= 200 && status < 300,
    status,
    statusText: status === 200 ? 'OK' : 'Error',
    json: () => Promise.resolve(data),
    text: () => Promise.resolve(JSON.stringify(data))
  }),
  
  createMockSettings: (overrides = {}) => ({
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
    glassmorphism_enabled: true,
    glassmorphism_blur: '10px',
    shadow_effects_enabled: true,
    shadow_intensity: 'medium',
    animations_enabled: true,
    animation_speed: 'normal',
    current_theme: 'default',
    performance_mode: false,
    debug_mode: false,
    ...overrides
  }),
  
  createMockTheme: (id = 'test-theme', overrides = {}) => ({
    id,
    name: 'Test Theme',
    type: 'custom',
    readonly: false,
    settings: {
      menu_background: '#ff5722',
      menu_text_color: '#ffffff'
    },
    metadata: {
      author: 'Test User',
      version: '1.0',
      created: '2025-01-10'
    },
    ...overrides
  }),
  
  createMockBackup: (id = 1234567890, overrides = {}) => ({
    id,
    timestamp: id,
    date: '2025-01-10 15:30:00',
    type: 'manual',
    settings: global.testUtils.createMockSettings(),
    metadata: {
      plugin_version: '2.2.0',
      wordpress_version: '6.8',
      user_id: 1,
      note: 'Test backup'
    },
    ...overrides
  }),
  
  waitFor: (condition, timeout = 1000) => {
    return new Promise((resolve, reject) => {
      const startTime = Date.now();
      const check = () => {
        if (condition()) {
          resolve();
        } else if (Date.now() - startTime > timeout) {
          reject(new Error('Timeout waiting for condition'));
        } else {
          setTimeout(check, 10);
        }
      };
      check();
    });
  }
};