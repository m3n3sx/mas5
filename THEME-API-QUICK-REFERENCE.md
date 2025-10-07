# Theme Management API - Quick Reference

## REST API Endpoints

### Base URL
```
/wp-json/mas-v2/v1/themes
```

### Authentication
All endpoints require:
- User with `manage_options` capability
- Valid WordPress nonce in `X-WP-Nonce` header

---

## Endpoints

### 1. List All Themes
```http
GET /wp-json/mas-v2/v1/themes
```

**Query Parameters:**
- `type` (optional): Filter by type (`predefined` or `custom`)

**Response:**
```json
{
  "success": true,
  "message": "Retrieved 6 theme(s)",
  "data": [
    {
      "id": "dark-blue",
      "name": "Dark Blue",
      "description": "Professional dark blue theme",
      "type": "predefined",
      "readonly": true,
      "settings": { /* color settings */ },
      "metadata": { /* theme metadata */ }
    }
  ],
  "timestamp": 1234567890
}
```

---

### 2. Get Specific Theme
```http
GET /wp-json/mas-v2/v1/themes/{id}
```

**Example:**
```bash
curl -X GET "https://example.com/wp-json/mas-v2/v1/themes/dark-blue" \
  -H "X-WP-Nonce: YOUR_NONCE"
```

---

### 3. Create Custom Theme
```http
POST /wp-json/mas-v2/v1/themes
```

**Request Body:**
```json
{
  "id": "my-custom-theme",
  "name": "My Custom Theme",
  "description": "A beautiful custom theme",
  "settings": {
    "menu_background": "#1a1a2e",
    "menu_text_color": "#ffffff",
    "menu_hover_background": "#16213e",
    "menu_hover_text_color": "#0f3460",
    "admin_bar_background": "#1a1a2e",
    "admin_bar_text_color": "#ffffff"
  }
}
```

**Response:**
```json
{
  "success": true,
  "message": "Theme created successfully",
  "data": { /* created theme */ },
  "timestamp": 1234567890
}
```

---

### 4. Update Custom Theme
```http
PUT /wp-json/mas-v2/v1/themes/{id}
```

**Request Body:**
```json
{
  "name": "Updated Theme Name",
  "description": "Updated description",
  "settings": {
    "menu_background": "#2a2a3e"
  }
}
```

**Note:** Cannot update predefined themes (returns 403 error)

---

### 5. Delete Custom Theme
```http
DELETE /wp-json/mas-v2/v1/themes/{id}
```

**Example:**
```bash
curl -X DELETE "https://example.com/wp-json/mas-v2/v1/themes/my-custom-theme" \
  -H "X-WP-Nonce: YOUR_NONCE"
```

**Note:** Cannot delete predefined themes (returns 403 error)

---

### 6. Apply Theme
```http
POST /wp-json/mas-v2/v1/themes/{id}/apply
```

**Example:**
```bash
curl -X POST "https://example.com/wp-json/mas-v2/v1/themes/dark-blue/apply" \
  -H "X-WP-Nonce: YOUR_NONCE"
```

**Response:**
```json
{
  "success": true,
  "message": "Theme \"dark-blue\" applied successfully",
  "data": {
    "applied": true,
    "theme_id": "dark-blue"
  },
  "timestamp": 1234567890
}
```

---

## JavaScript Client Usage

### Using REST Client

```javascript
// Get all themes
const themes = await masRestClient.getThemes();

// Get specific theme
const theme = await masRestClient.getTheme('dark-blue');

// Create custom theme
const newTheme = await masRestClient.createTheme({
  id: 'my-theme',
  name: 'My Theme',
  settings: { /* colors */ }
});

// Update theme
const updated = await masRestClient.updateTheme('my-theme', {
  name: 'Updated Name'
});

// Delete theme
await masRestClient.deleteTheme('my-theme');

// Apply theme
await masRestClient.applyTheme('dark-blue');

// Apply theme with CSS updates
await masRestClient.applyThemeWithCSSUpdate('dark-blue', true);

// Update CSS variables manually
masRestClient.updateCSSVariables({
  menu_background: '#1e1e2e',
  menu_text_color: '#ffffff'
});
```

### Using Dual-Mode Client (with AJAX fallback)

```javascript
// Same API as REST client, but with automatic fallback
const themes = await masDualClient.getThemes();
const theme = await masDualClient.getTheme('dark-blue');
await masDualClient.applyTheme('dark-blue');
```

---

## Predefined Themes

### Available Themes

1. **default** - WordPress default color scheme
2. **dark-blue** - Professional dark blue theme
3. **light-modern** - Clean and modern light theme
4. **ocean** - Calm ocean-inspired theme
5. **sunset** - Warm sunset colors
6. **forest** - Natural forest green theme

### Theme Properties

All predefined themes have:
- `type: 'predefined'`
- `readonly: true`
- Cannot be modified or deleted
- Can be applied to settings

---

## Error Handling

### Common Error Codes

| Code | Status | Description |
|------|--------|-------------|
| `rest_forbidden` | 403 | No permission to access resource |
| `theme_not_found` | 404 | Theme ID does not exist |
| `theme_exists` | 409 | Theme ID already exists |
| `reserved_theme_id` | 400 | Trying to use reserved theme ID |
| `theme_readonly` | 403 | Cannot modify predefined theme |
| `validation_failed` | 400 | Invalid theme data |
| `invalid_theme_id` | 400 | Invalid theme ID format |

### Error Response Format

```json
{
  "code": "theme_readonly",
  "message": "Cannot modify predefined themes",
  "data": {
    "status": 403
  }
}
```

### JavaScript Error Handling

```javascript
try {
  await masRestClient.updateTheme('default', { name: 'New Name' });
} catch (error) {
  if (error.code === 'theme_readonly') {
    console.log('Cannot modify predefined themes');
  }
  console.error(error.getUserMessage());
}
```

---

## Validation Rules

### Theme ID
- Must contain only lowercase letters, numbers, and hyphens
- Pattern: `/^[a-z0-9-]+$/`
- Cannot use reserved IDs: `default`, `dark-blue`, `light-modern`, `ocean`, `sunset`, `forest`

### Theme Name
- Required for creation
- String, sanitized with `sanitize_text_field()`

### Theme Settings
- Must be an object/array
- Color fields validated as hex colors
- Numeric fields validated as numbers
- Boolean fields validated as booleans

### Color Format
- Hex colors: `#RGB`, `#RRGGBB`, or `#RRGGBBAA`
- Examples: `#fff`, `#1e1e2e`, `#1e1e2eff`

---

## CSS Variables

### Mapped Variables

| Setting | CSS Variable |
|---------|-------------|
| `menu_background` | `--mas-menu-bg` |
| `menu_text_color` | `--mas-menu-text` |
| `menu_hover_background` | `--mas-menu-hover-bg` |
| `menu_hover_text_color` | `--mas-menu-hover-text` |
| `menu_active_background` | `--mas-menu-active-bg` |
| `menu_active_text_color` | `--mas-menu-active-text` |
| `admin_bar_background` | `--mas-admin-bar-bg` |
| `admin_bar_text_color` | `--mas-admin-bar-text` |
| `admin_bar_hover_color` | `--mas-admin-bar-hover` |
| `submenu_background` | `--mas-submenu-bg` |
| `submenu_text_color` | `--mas-submenu-text` |
| `submenu_hover_background` | `--mas-submenu-hover-bg` |
| `submenu_hover_text_color` | `--mas-submenu-hover-text` |

### Using CSS Variables

```css
.my-element {
  background: var(--mas-menu-bg);
  color: var(--mas-menu-text);
}
```

---

## Best Practices

### 1. Theme Creation
```javascript
// Good: Descriptive ID and complete settings
const theme = {
  id: 'company-brand',
  name: 'Company Brand Theme',
  description: 'Official company colors',
  settings: {
    menu_background: '#003366',
    menu_text_color: '#ffffff',
    // ... all relevant settings
  }
};
```

### 2. Error Handling
```javascript
// Always handle errors
try {
  await masRestClient.createTheme(theme);
} catch (error) {
  if (error.isValidationError()) {
    // Show validation errors to user
    console.log(error.data.errors);
  }
}
```

### 3. Theme Application
```javascript
// Apply with CSS updates for immediate visual feedback
await masRestClient.applyThemeWithCSSUpdate('dark-blue', true);
```

### 4. Checking Theme Type
```javascript
const theme = await masRestClient.getTheme('dark-blue');
if (theme.readonly) {
  console.log('This is a predefined theme');
}
```

---

## Testing

### Test Endpoints
```bash
# List themes
curl "https://example.com/wp-json/mas-v2/v1/themes" \
  -H "X-WP-Nonce: YOUR_NONCE"

# Create theme
curl -X POST "https://example.com/wp-json/mas-v2/v1/themes" \
  -H "Content-Type: application/json" \
  -H "X-WP-Nonce: YOUR_NONCE" \
  -d '{"id":"test","name":"Test","settings":{}}'

# Apply theme
curl -X POST "https://example.com/wp-json/mas-v2/v1/themes/dark-blue/apply" \
  -H "X-WP-Nonce: YOUR_NONCE"
```

### Test Page
Access the comprehensive test page:
```
/wp-content/plugins/modern-admin-styler-v2/test-task3-theme-endpoints.php
```

---

## Support

For issues or questions:
1. Check error messages and codes
2. Review validation rules
3. Test with predefined themes first
4. Check browser console for JavaScript errors
5. Enable debug mode: `window.masRestClient.debug = true`

---

**Last Updated:** January 10, 2025  
**API Version:** 2.2.0  
**Namespace:** mas-v2/v1
