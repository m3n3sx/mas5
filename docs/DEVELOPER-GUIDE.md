# Modern Admin Styler V2 - Developer Integration Guide

## Table of Contents

1. [Introduction](#introduction)
2. [Getting Started](#getting-started)
3. [Authentication](#authentication)
4. [JavaScript Client](#javascript-client)
5. [Common Use Cases](#common-use-cases)
6. [Error Handling](#error-handling)
7. [Migration from AJAX](#migration-from-ajax)
8. [Best Practices](#best-practices)
9. [Advanced Topics](#advanced-topics)
10. [Troubleshooting](#troubleshooting)

---

## Introduction

The Modern Admin Styler V2 REST API provides a modern, RESTful interface for managing plugin settings, themes, backups, and more. This guide will help you integrate with the API in your WordPress plugins or themes.

### Why Use the REST API?

- **Standardized:** RESTful conventions with proper HTTP methods
- **Well-Documented:** Comprehensive documentation and JSON schemas
- **Testable:** Easy to test with tools like Postman
- **Secure:** Built-in authentication and validation
- **Performant:** Caching, rate limiting, and optimization

### Prerequisites

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Modern Admin Styler V2 plugin installed and activated
- Basic understanding of REST APIs and JavaScript

---

## Getting Started

### Base URL

All API endpoints are accessed via:

```
https://your-site.com/wp-json/mas-v2/v1/
```

### Quick Example

```javascript
// Get current settings
fetch('/wp-json/mas-v2/v1/settings', {
  headers: {
    'X-WP-Nonce': wpApiSettings.nonce
  },
  credentials: 'same-origin'
})
.then(response => response.json())
.then(data => console.log(data))
.catch(error => console.error(error));
```

---

## Frontend Integration (Updated in v2.2.0)

### Unified Form Handler

As of version 2.2.0, the plugin uses a **unified form handler** that eliminates dual handler conflicts and provides seamless REST API integration with AJAX fallback.

**Key Features**:
- ✅ Single handler for all form submissions (no conflicts)
- ✅ REST API by default with automatic AJAX fallback
- ✅ Comprehensive form data collection (all fields, including unchecked checkboxes)
- ✅ Duplicate submission prevention
- ✅ Loading states and user feedback
- ✅ Graceful error handling

**Automatic Loading**:
The handler is automatically loaded on the settings page. No manual initialization required.

**Custom Events**:
```javascript
// Listen for settings saved
document.addEventListener('mas-settings-saved', (e) => {
    console.log('Settings saved:', e.detail);
    console.log('Method used:', e.detail.method); // 'REST' or 'AJAX'
    console.log('Data:', e.detail.data);
});

// Listen for settings errors
document.addEventListener('mas-settings-error', (e) => {
    console.error('Settings error:', e.detail.error);
});
```

**Debug Mode**:
Enable `WP_DEBUG` in `wp-config.php` to see detailed console logging:
```javascript
[MAS Form Handler] Initializing...
[MAS Form Handler] Form found
[MAS Form Handler] Using REST API
[MAS Form Handler] Submitting settings: { fieldCount: 25, useRest: true }
[MAS Form Handler] REST API success
[MAS Form Handler] Save successful: { method: 'REST' }
```

**Migration Note**:
If you were using the old `admin-settings-simple.js` or `SettingsManager.js` directly, those are now deprecated. Use the unified handler or listen to custom events instead.

---

## Authentication

The API uses WordPress cookie authentication. When making requests from the WordPress admin, you need to include the REST API nonce.

### Getting the Nonce

WordPress provides the nonce via the `wpApiSettings` global:

```javascript
const nonce = wpApiSettings.nonce;
const apiRoot = wpApiSettings.root;
```

### Including the Nonce

Add the nonce to your request headers:

```javascript
headers: {
  'X-WP-Nonce': wpApiSettings.nonce
}
```

### Required Permissions

All endpoints require the `manage_options` capability. Users without this capability will receive a `403 Forbidden` response.

### Example: Authenticated Request

```javascript
async function getSettings() {
  const response = await fetch('/wp-json/mas-v2/v1/settings', {
    method: 'GET',
    headers: {
      'X-WP-Nonce': wpApiSettings.nonce
    },
    credentials: 'same-origin'
  });
  
  if (!response.ok) {
    throw new Error(`HTTP error! status: ${response.status}`);
  }
  
  return response.json();
}
```

---

## JavaScript Client

### Basic Client Implementation

Here's a complete JavaScript client for the REST API:

```javascript
class MASRestClient {
  constructor() {
    this.baseUrl = wpApiSettings.root + 'mas-v2/v1';
    this.nonce = wpApiSettings.nonce;
  }
  
  /**
   * Make a request to the API
   * @param {string} endpoint - API endpoint (e.g., '/settings')
   * @param {object} options - Fetch options
   * @returns {Promise} Response data
   */
  async request(endpoint, options = {}) {
    const url = this.baseUrl + endpoint;
    const headers = {
      'Content-Type': 'application/json',
      'X-WP-Nonce': this.nonce,
      ...options.headers
    };
    
    try {
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
    } catch (error) {
      console.error('REST API Error:', error);
      throw error;
    }
  }
  
  // Settings methods
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
  
  // Theme methods
  async getThemes(type = null) {
    const query = type ? `?type=${type}` : '';
    return this.request(`/themes${query}`, { method: 'GET' });
  }
  
  async getTheme(themeId) {
    return this.request(`/themes/${themeId}`, { method: 'GET' });
  }
  
  async createTheme(themeData) {
    return this.request('/themes', {
      method: 'POST',
      body: JSON.stringify(themeData)
    });
  }
  
  async updateTheme(themeId, themeData) {
    return this.request(`/themes/${themeId}`, {
      method: 'PUT',
      body: JSON.stringify(themeData)
    });
  }
  
  async deleteTheme(themeId) {
    return this.request(`/themes/${themeId}`, { method: 'DELETE' });
  }
  
  async applyTheme(themeId) {
    return this.request(`/themes/${themeId}/apply`, { method: 'POST' });
  }
  
  // Backup methods
  async listBackups(limit = 10, page = 1) {
    return this.request(`/backups?limit=${limit}&page=${page}`, {
      method: 'GET'
    });
  }
  
  async getBackup(backupId) {
    return this.request(`/backups/${backupId}`, { method: 'GET' });
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
  
  async getBackupStatistics() {
    return this.request('/backups/statistics', { method: 'GET' });
  }
  
  // Import/Export methods
  async exportSettings(includeMetadata = true) {
    return this.request(`/export?include_metadata=${includeMetadata}`, {
      method: 'GET'
    });
  }
  
  async importSettings(data, createBackup = true) {
    return this.request('/import', {
      method: 'POST',
      body: JSON.stringify({ data, create_backup: createBackup })
    });
  }
  
  // Preview method
  async generatePreview(settings) {
    return this.request('/preview', {
      method: 'POST',
      body: JSON.stringify({ settings })
    });
  }
  
  // Diagnostics methods
  async getDiagnostics(include = null) {
    const query = include ? `?include=${include}` : '';
    return this.request(`/diagnostics${query}`, { method: 'GET' });
  }
  
  async getHealthCheck() {
    return this.request('/diagnostics/health', { method: 'GET' });
  }
  
  async getPerformanceMetrics() {
    return this.request('/diagnostics/performance', { method: 'GET' });
  }
}

// Usage
const client = new MASRestClient();
```

### Using the Client

```javascript
// Initialize client
const masClient = new MASRestClient();

// Get settings
const settings = await masClient.getSettings();
console.log('Current settings:', settings.data);

// Update settings
await masClient.updateSettings({
  menu_background: '#1e1e2e',
  enable_animations: true
});

// Apply a theme
await masClient.applyTheme('dark-blue');

// Create a backup
await masClient.createBackup('Before major changes');

// Generate preview
const preview = await masClient.generatePreview({
  menu_background: '#ff6b6b'
});
console.log('Preview CSS:', preview.data.css);
```

---

## Common Use Cases

### 1. Save Settings with Validation

```javascript
async function saveSettingsWithValidation(settings) {
  try {
    // Validate locally first
    if (!settings.menu_background || !isValidHexColor(settings.menu_background)) {
      throw new Error('Invalid menu background color');
    }
    
    // Save via API
    const result = await masClient.saveSettings(settings);
    
    // Show success message
    showNotification('Settings saved successfully', 'success');
    
    return result;
  } catch (error) {
    // Show error message
    showNotification(error.message, 'error');
    throw error;
  }
}

function isValidHexColor(color) {
  return /^#[a-f0-9]{6}$/i.test(color);
}
```

### 2. Live Preview with Debouncing

```javascript
class PreviewManager {
  constructor(client) {
    this.client = client;
    this.debounceTimer = null;
    this.debounceDelay = 500;
  }
  
  updatePreview(settings) {
    // Clear existing timer
    if (this.debounceTimer) {
      clearTimeout(this.debounceTimer);
    }
    
    // Set new timer
    this.debounceTimer = setTimeout(async () => {
      try {
        const response = await this.client.generatePreview(settings);
        this.applyPreviewCSS(response.data.css);
      } catch (error) {
        console.error('Preview failed:', error);
      }
    }, this.debounceDelay);
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
  
  clearPreview() {
    const styleElement = document.getElementById('mas-preview-styles');
    if (styleElement) {
      styleElement.remove();
    }
  }
}

// Usage
const previewManager = new PreviewManager(masClient);

// Update preview when settings change
document.getElementById('menu-background').addEventListener('input', (e) => {
  const settings = {
    menu_background: e.target.value
  };
  previewManager.updatePreview(settings);
});
```

### 3. Backup Before Major Changes

```javascript
async function applyThemeWithBackup(themeId) {
  try {
    // Create backup first
    const backup = await masClient.createBackup(`Before applying theme: ${themeId}`);
    console.log('Backup created:', backup.data.id);
    
    // Apply theme
    await masClient.applyTheme(themeId);
    
    showNotification('Theme applied successfully', 'success');
  } catch (error) {
    showNotification('Failed to apply theme: ' + error.message, 'error');
    throw error;
  }
}
```

### 4. Import/Export Settings

```javascript
// Export settings
async function exportSettings() {
  try {
    const result = await masClient.exportSettings(true);
    
    // Create download link
    const blob = new Blob([JSON.stringify(result.data, null, 2)], {
      type: 'application/json'
    });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = result.filename || 'mas-v2-settings.json';
    a.click();
    URL.revokeObjectURL(url);
    
    showNotification('Settings exported successfully', 'success');
  } catch (error) {
    showNotification('Export failed: ' + error.message, 'error');
  }
}

// Import settings
async function importSettings(file) {
  try {
    const text = await file.text();
    const data = JSON.parse(text);
    
    // Confirm with user
    if (!confirm('This will replace your current settings. Continue?')) {
      return;
    }
    
    // Import with backup
    await masClient.importSettings(data, true);
    
    showNotification('Settings imported successfully', 'success');
    
    // Reload page to apply changes
    location.reload();
  } catch (error) {
    showNotification('Import failed: ' + error.message, 'error');
  }
}
```

### 5. Health Check Dashboard

```javascript
async function displayHealthStatus() {
  try {
    const health = await masClient.getHealthCheck();
    
    const statusElement = document.getElementById('health-status');
    statusElement.className = `health-status health-${health.data.status}`;
    statusElement.textContent = health.data.status.toUpperCase();
    
    // Display individual checks
    const checksContainer = document.getElementById('health-checks');
    checksContainer.innerHTML = '';
    
    for (const [name, check] of Object.entries(health.data.checks)) {
      const checkElement = document.createElement('div');
      checkElement.className = `health-check health-check-${check.status}`;
      checkElement.innerHTML = `
        <span class="check-name">${name}</span>
        <span class="check-status">${check.status}</span>
        <span class="check-message">${check.message}</span>
      `;
      checksContainer.appendChild(checkElement);
    }
  } catch (error) {
    console.error('Health check failed:', error);
  }
}

// Run health check every 5 minutes
setInterval(displayHealthStatus, 5 * 60 * 1000);
displayHealthStatus(); // Initial check
```

---

## Error Handling

### Error Response Format

All errors follow this format:

```json
{
  "code": "error_code",
  "message": "Human-readable error message",
  "data": {
    "status": 400,
    "errors": {
      "field_name": "Field-specific error"
    }
  }
}
```

### Handling Different Error Types

```javascript
async function handleApiRequest(requestFn) {
  try {
    return await requestFn();
  } catch (error) {
    // Parse error response
    const errorData = error.response?.data || {};
    
    switch (errorData.code) {
      case 'rest_forbidden':
        showNotification('You do not have permission to perform this action', 'error');
        break;
        
      case 'rest_cookie_invalid_nonce':
        showNotification('Your session has expired. Please refresh the page.', 'error');
        // Optionally reload page
        setTimeout(() => location.reload(), 2000);
        break;
        
      case 'validation_failed':
        // Show field-specific errors
        if (errorData.data?.errors) {
          for (const [field, message] of Object.entries(errorData.data.errors)) {
            showFieldError(field, message);
          }
        }
        break;
        
      case 'rate_limited':
        showNotification('Too many requests. Please wait a moment.', 'warning');
        break;
        
      default:
        showNotification(errorData.message || 'An error occurred', 'error');
    }
    
    throw error;
  }
}
```

### Retry Logic

```javascript
async function requestWithRetry(requestFn, maxRetries = 3) {
  let lastError;
  
  for (let i = 0; i < maxRetries; i++) {
    try {
      return await requestFn();
    } catch (error) {
      lastError = error;
      
      // Don't retry on client errors (4xx)
      if (error.response?.status >= 400 && error.response?.status < 500) {
        throw error;
      }
      
      // Wait before retrying (exponential backoff)
      const delay = Math.pow(2, i) * 1000;
      await new Promise(resolve => setTimeout(resolve, delay));
    }
  }
  
  throw lastError;
}

// Usage
const settings = await requestWithRetry(() => masClient.getSettings());
```

---

## Migration from AJAX

If you're migrating from the legacy AJAX handlers, here's a comparison:

### AJAX (Old Way)

```javascript
jQuery.ajax({
  url: ajaxurl,
  type: 'POST',
  data: {
    action: 'mas_v2_save_settings',
    nonce: masV2Data.nonce,
    settings: settings
  },
  success: function(response) {
    if (response.success) {
      console.log('Settings saved');
    }
  },
  error: function(xhr, status, error) {
    console.error('Error:', error);
  }
});
```

### REST API (New Way)

```javascript
try {
  const result = await masClient.saveSettings(settings);
  console.log('Settings saved');
} catch (error) {
  console.error('Error:', error);
}
```

### Migration Checklist

- [ ] Replace `jQuery.ajax` with `fetch` or REST client
- [ ] Update nonce from `masV2Data.nonce` to `wpApiSettings.nonce`
- [ ] Change from `action` parameter to REST endpoint
- [ ] Update response handling (REST uses `data` property)
- [ ] Update error handling (REST uses standard HTTP status codes)
- [ ] Test all functionality thoroughly

### Dual-Mode Support

During migration, you can support both AJAX and REST:

```javascript
class MASClient {
  constructor() {
    this.restClient = new MASRestClient();
    this.useRest = this.checkRestAvailability();
  }
  
  checkRestAvailability() {
    return typeof wpApiSettings !== 'undefined' && wpApiSettings.root;
  }
  
  async saveSettings(settings) {
    if (this.useRest) {
      try {
        return await this.restClient.saveSettings(settings);
      } catch (error) {
        console.warn('REST API failed, falling back to AJAX', error);
        this.useRest = false;
      }
    }
    
    // Fallback to AJAX
    return this.ajaxSaveSettings(settings);
  }
  
  ajaxSaveSettings(settings) {
    return new Promise((resolve, reject) => {
      jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          action: 'mas_v2_save_settings',
          nonce: masV2Data.nonce,
          settings: settings
        },
        success: resolve,
        error: reject
      });
    });
  }
}
```

---

## Best Practices

### 1. Always Handle Errors

```javascript
try {
  await masClient.saveSettings(settings);
} catch (error) {
  // Always handle errors
  console.error('Failed to save settings:', error);
  showNotification(error.message, 'error');
}
```

### 2. Use Debouncing for Frequent Updates

```javascript
let debounceTimer;

function debouncedUpdate(settings) {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    masClient.updateSettings(settings);
  }, 500);
}
```

### 3. Validate Before Sending

```javascript
function validateSettings(settings) {
  const errors = {};
  
  if (settings.menu_background && !isValidHexColor(settings.menu_background)) {
    errors.menu_background = 'Invalid color format';
  }
  
  if (settings.menu_width && (settings.menu_width < 100 || settings.menu_width > 400)) {
    errors.menu_width = 'Width must be between 100 and 400';
  }
  
  return Object.keys(errors).length === 0 ? null : errors;
}

async function saveWithValidation(settings) {
  const errors = validateSettings(settings);
  if (errors) {
    throw new Error('Validation failed: ' + JSON.stringify(errors));
  }
  
  return masClient.saveSettings(settings);
}
```

### 4. Create Backups Before Major Changes

```javascript
async function safeOperation(operationFn, backupNote) {
  // Create backup first
  await masClient.createBackup(backupNote);
  
  // Perform operation
  return operationFn();
}

// Usage
await safeOperation(
  () => masClient.applyTheme('new-theme'),
  'Before applying new theme'
);
```

### 5. Use Caching for Read Operations

```javascript
class CachedMASClient extends MASRestClient {
  constructor() {
    super();
    this.cache = new Map();
    this.cacheDuration = 5 * 60 * 1000; // 5 minutes
  }
  
  async getSettings() {
    const cacheKey = 'settings';
    const cached = this.cache.get(cacheKey);
    
    if (cached && Date.now() - cached.timestamp < this.cacheDuration) {
      return cached.data;
    }
    
    const data = await super.getSettings();
    this.cache.set(cacheKey, {
      data,
      timestamp: Date.now()
    });
    
    return data;
  }
  
  clearCache() {
    this.cache.clear();
  }
}
```

---

## Advanced Topics

### Custom Endpoints

You can extend the API with custom endpoints:

```php
add_action('rest_api_init', function() {
    register_rest_route('mas-v2/v1', '/custom-endpoint', [
        'methods' => 'GET',
        'callback' => 'my_custom_endpoint_handler',
        'permission_callback' => function() {
            return current_user_can('manage_options');
        }
    ]);
});

function my_custom_endpoint_handler($request) {
    return new WP_REST_Response([
        'success' => true,
        'data' => ['custom' => 'data']
    ], 200);
}
```

### Webhooks

Trigger actions when settings change:

```php
add_action('mas_settings_saved', function($settings) {
    // Send webhook
    wp_remote_post('https://example.com/webhook', [
        'body' => json_encode([
            'event' => 'settings_saved',
            'data' => $settings
        ]),
        'headers' => [
            'Content-Type' => 'application/json'
        ]
    ]);
});
```

### Rate Limit Handling

```javascript
class RateLimitedClient extends MASRestClient {
  async request(endpoint, options = {}) {
    try {
      return await super.request(endpoint, options);
    } catch (error) {
      if (error.response?.status === 429) {
        // Get retry-after header
        const retryAfter = error.response.headers.get('Retry-After');
        const delay = retryAfter ? parseInt(retryAfter) * 1000 : 60000;
        
        // Wait and retry
        await new Promise(resolve => setTimeout(resolve, delay));
        return this.request(endpoint, options);
      }
      throw error;
    }
  }
}
```

---

## Troubleshooting

### Common Issues

#### 0. Settings Not Saving or Only Partial Save (v2.2.0+)

**Cause:** Dual handler conflict (resolved in v2.2.0) or form data collection issue.

**Solution:**
1. Verify you're using v2.2.0 or higher
2. Check browser console for errors
3. Verify only one handler is attached:
   ```javascript
   // Should see only one handler
   console.log('[MAS Form Handler] Initializing...');
   ```
4. Check form data collection:
   ```javascript
   // Enable debug mode to see field count
   // Should show all fields, not just one
   [MAS Form Handler] Submitting settings: { fieldCount: 25, ... }
   ```
5. If using custom code, listen to events instead of attaching handlers:
   ```javascript
   document.addEventListener('mas-settings-saved', (e) => {
       // Your code here
   });
   ```

**Verification:**
- Open browser DevTools → Console
- Submit form
- Check for: `[MAS Form Handler] Save successful: { method: 'REST' }`
- Verify field count is > 20 (not just 1-2)

#### 1. "Cookie nonce is invalid"

**Cause:** Nonce has expired or is missing.

**Solution:**
```javascript
// Refresh the page to get a new nonce
location.reload();

// Or fetch a new nonce via AJAX
jQuery.post(ajaxurl, {
  action: 'mas_refresh_nonce'
}, function(response) {
  wpApiSettings.nonce = response.nonce;
});
```

#### 2. "You do not have permission"

**Cause:** User lacks `manage_options` capability.

**Solution:** Ensure the user is an administrator or has the required capability.

#### 3. "Rate limit exceeded"

**Cause:** Too many requests in a short time.

**Solution:** Implement client-side debouncing and respect the `Retry-After` header.

#### 4. CORS Errors

**Cause:** Making requests from a different origin.

**Solution:** The API is designed for same-origin requests. If you need cross-origin access, add CORS headers:

```php
add_filter('rest_pre_serve_request', function($served, $result, $request) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
    header('Access-Control-Allow-Headers: X-WP-Nonce, Content-Type');
    return $served;
}, 10, 3);
```

### Debug Mode

Enable debug mode to see detailed error messages:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Check the debug log at `wp-content/debug.log`.

### Testing Endpoints

Use the browser console to test endpoints:

```javascript
// Test GET request
fetch('/wp-json/mas-v2/v1/settings', {
  headers: { 'X-WP-Nonce': wpApiSettings.nonce },
  credentials: 'same-origin'
})
.then(r => r.json())
.then(console.log);

// Test POST request
fetch('/wp-json/mas-v2/v1/settings', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-WP-Nonce': wpApiSettings.nonce
  },
  credentials: 'same-origin',
  body: JSON.stringify({ menu_background: '#1e1e2e' })
})
.then(r => r.json())
.then(console.log);
```

---

## Support and Resources

- **API Documentation:** See `API-DOCUMENTATION.md`
- **JSON Schemas:** See `JSON-SCHEMAS.md`
- **Postman Collection:** Import `Modern-Admin-Styler-V2.postman_collection.json`
- **Error Reference:** See `ERROR-CODES.md`

---

**Last Updated:** January 10, 2025  
**Version:** 2.2.0
