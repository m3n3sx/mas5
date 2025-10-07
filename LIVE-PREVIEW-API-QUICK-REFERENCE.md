# Live Preview API - Quick Reference

## REST API Endpoint

### Generate Preview CSS

**Endpoint:** `POST /wp-json/mas-v2/v1/preview`

**Authentication:** WordPress cookie + nonce

**Headers:**
```
Content-Type: application/json
X-WP-Nonce: <wp_rest_nonce>
```

**Request Body:**
```json
{
  "settings": {
    "menu_background": "#2c3e50",
    "menu_text_color": "#ecf0f1",
    "admin_bar_background": "#34495e",
    "enable_animations": true,
    "animation_speed": 300,
    "glassmorphism_effects": true,
    "glassmorphism_blur": 10
  }
}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "css": "/* Modern Admin Styler V2 - Generated CSS */\n\n/* Admin Bar Styles */\n...",
    "settings_count": 7,
    "css_length": 2456
  },
  "message": "Preview CSS generated successfully",
  "timestamp": 1704931200
}
```

**Error Response (400):**
```json
{
  "code": "validation_failed",
  "message": "Settings validation failed",
  "data": {
    "status": 400,
    "errors": {
      "menu_background": "Invalid color value for menu_background"
    }
  }
}
```

**Rate Limited Response (429):**
```json
{
  "code": "rate_limited",
  "message": "Too many requests. Please wait before generating another preview.",
  "data": {
    "status": 429
  }
}
```

## JavaScript API

### MASRestClient

```javascript
// Initialize client
const client = new MASRestClient({
    debug: true
});

// Generate preview
const response = await client.generatePreview({
    menu_background: '#2c3e50',
    menu_text_color: '#ecf0f1'
});

console.log(response.css); // Generated CSS
```

### PreviewManager

```javascript
// Initialize with REST client
const previewManager = new PreviewManager(masRestClient, {
    debounceDelay: 500,  // ms
    styleElementId: 'mas-preview-styles',
    debug: true
});

// Update preview (debounced)
previewManager.updatePreview({
    menu_background: '#2c3e50',
    menu_text_color: '#ecf0f1'
});

// Clear preview
previewManager.clearPreview();

// Cancel pending request
previewManager.cancelPreview();

// Check if active
if (previewManager.isActive()) {
    console.log('Preview is generating...');
}

// Get statistics
const stats = previewManager.getStats();
console.log('Requests:', stats.requestCount);
console.log('Cancelled:', stats.cancelledCount);
console.log('Errors:', stats.errorCount);

// Change debounce delay
previewManager.setDebounceDelay(300);

// Cleanup
previewManager.destroy();
```

### Events

```javascript
// Preview updated successfully
document.addEventListener('mas-preview-updated', (event) => {
    console.log('CSS applied:', event.detail.css.length, 'chars');
    console.log('Duration:', event.detail.duration, 'ms');
    console.log('Fallback:', event.detail.fallback);
});

// Preview error
document.addEventListener('mas-preview-error', (event) => {
    console.error('Preview failed:', event.detail.error);
});

// Preview cleared
document.addEventListener('mas-preview-cleared', (event) => {
    console.log('Preview removed');
});
```

## PHP API

### CSS Generator Service

```php
// Get instance
$css_generator = MAS_CSS_Generator_Service::get_instance();

// Generate CSS with caching
$css = $css_generator->generate($settings, true);

// Generate CSS without caching (for preview)
$css = $css_generator->generate($settings, false);

// Clear cache
$css_generator->clear_cache();
```

### Preview Controller

```php
// Controller is automatically registered via REST API bootstrap
// Access via REST API endpoint: POST /wp-json/mas-v2/v1/preview

// Manual usage (not typical)
$controller = new MAS_Preview_Controller();
$request = new WP_REST_Request('POST', '/mas-v2/v1/preview');
$request->set_param('settings', $settings);
$response = $controller->generate_preview($request);
```

## Common Use Cases

### 1. Real-time Color Preview

```javascript
// Listen to color picker changes
colorPicker.addEventListener('change', (event) => {
    previewManager.updatePreview({
        menu_background: event.target.value
    });
});
```

### 2. Form Field Preview

```javascript
// Preview on any form field change
document.querySelectorAll('.mas-setting-field').forEach(field => {
    field.addEventListener('input', () => {
        const settings = getFormSettings(); // Your function
        previewManager.updatePreview(settings);
    });
});
```

### 3. Theme Preview

```javascript
// Preview theme before applying
async function previewTheme(themeId) {
    const theme = await masRestClient.getTheme(themeId);
    await previewManager.updatePreview(theme.settings);
}
```

### 4. Batch Settings Preview

```javascript
// Preview multiple settings at once
previewManager.updatePreview({
    menu_background: '#2c3e50',
    menu_text_color: '#ecf0f1',
    menu_hover_background: '#34495e',
    admin_bar_background: '#2c3e50',
    enable_animations: true,
    animation_speed: 300
});
```

### 5. Preview with Loading State

```javascript
async function updatePreviewWithLoading(settings) {
    showLoadingIndicator();
    
    try {
        await previewManager.updatePreview(settings);
        hideLoadingIndicator();
    } catch (error) {
        hideLoadingIndicator();
        showError(error.message);
    }
}
```

## Configuration

### Server-Side

```php
// In class-mas-preview-controller.php
private $debounce_delay = 500; // Minimum ms between requests
```

### Client-Side

```javascript
// Configure PreviewManager
const previewManager = new PreviewManager(restClient, {
    debounceDelay: 500,           // Debounce delay in ms
    styleElementId: 'mas-preview', // Style element ID
    debug: false                   // Enable debug logging
});
```

## Performance Tips

1. **Use Debouncing:** Default 500ms is optimal for most cases
2. **Batch Updates:** Update multiple settings at once instead of individually
3. **Clear When Done:** Call `clearPreview()` when user navigates away
4. **Monitor Stats:** Use `getStats()` to track performance
5. **Cache on Server:** CSS Generator uses WordPress object cache

## Troubleshooting

### Preview Not Updating

```javascript
// Check if preview manager is active
console.log('Active:', previewManager.isActive());

// Check statistics
console.log('Stats:', previewManager.getStats());

// Enable debug mode
previewManager.debug = true;
```

### Rate Limiting Issues

```javascript
// Increase debounce delay
previewManager.setDebounceDelay(1000); // 1 second

// Check request count
const stats = previewManager.getStats();
if (stats.requestCount > 100) {
    previewManager.resetStats();
}
```

### CSS Not Applying

```javascript
// Check if style element exists
const styleEl = document.getElementById('mas-preview-styles');
console.log('Style element:', styleEl);
console.log('CSS length:', styleEl?.textContent.length);

// Manually apply CSS
previewManager.applyPreviewCSS('/* test */ body { color: red; }');
```

### Validation Errors

```javascript
// Catch validation errors
try {
    await previewManager.updatePreview(settings);
} catch (error) {
    if (error.code === 'validation_failed') {
        console.error('Validation errors:', error.data.errors);
    }
}
```

## Browser Compatibility

- ✅ Chrome 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Edge 90+

**Requirements:**
- Fetch API
- Promises/async-await
- AbortController
- CustomEvent

## Security

- ✅ Requires `manage_options` capability
- ✅ Nonce validation for all requests
- ✅ Input sanitization and validation
- ✅ Rate limiting (429 responses)
- ✅ No settings saved during preview
- ✅ XSS prevention via sanitization

## Related Documentation

- [Task 6 Implementation Complete](TASK-6-LIVE-PREVIEW-COMPLETION.md)
- [REST API Quick Start](REST-API-QUICK-START.md)
- [Settings API Reference](SETTINGS-API-QUICK-REFERENCE.md)
- [Theme API Reference](THEME-API-QUICK-REFERENCE.md)
