# Import/Export API Quick Reference

## REST API Endpoints

### Export Settings
```
GET /wp-json/mas-v2/v1/export
```

**Parameters:**
- `include_metadata` (boolean, optional) - Include metadata in export (default: true)

**Response:**
```json
{
  "success": true,
  "data": {
    "settings": {
      "menu_background": "#1e1e2e",
      "menu_text_color": "#ffffff",
      ...
    },
    "metadata": {
      "export_version": "2.2.0",
      "plugin_version": "2.2.0",
      "wordpress_version": "6.8",
      "export_date": "2025-05-10 12:00:00",
      "export_timestamp": 1715342400,
      "site_url": "https://example.com",
      "exported_by": 1
    }
  },
  "filename": "mas-v2-settings-example-2025-05-10-120000.json",
  "message": "Settings exported successfully",
  "timestamp": 1715342400
}
```

**Headers:**
- `Content-Disposition: attachment; filename="mas-v2-settings-example-2025-05-10-120000.json"`
- `Content-Type: application/json`
- `Cache-Control: no-cache, no-store, must-revalidate`

### Import Settings
```
POST /wp-json/mas-v2/v1/import
```

**Parameters:**
- `data` (object|string, required) - Import data as JSON object or string
- `create_backup` (boolean, optional) - Create backup before import (default: true)

**Request Body:**
```json
{
  "data": {
    "settings": {
      "menu_background": "#2d2d44",
      "menu_text_color": "#ffffff"
    },
    "metadata": {
      "export_version": "2.2.0"
    }
  },
  "create_backup": true
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "imported": true,
    "backup_created": true
  },
  "message": "Settings imported successfully",
  "timestamp": 1715342400
}
```

## JavaScript Client Usage

### Export Settings

```javascript
// Export with automatic download
const exportData = await masRestClient.exportSettings(true, true);

// Export without download (get data only)
const exportData = await masRestClient.exportSettings(true, false);

// Export without metadata
const exportData = await masRestClient.exportSettings(false, true);
```

### Import Settings

```javascript
// Import from file input element
const fileInput = document.getElementById('import-file');
const result = await masRestClient.importSettingsFromFile(fileInput, true);

// Import from data object
const importData = {
  settings: {
    menu_background: '#2d2d44',
    menu_text_color: '#ffffff'
  },
  metadata: {
    export_version: '2.2.0'
  }
};
const result = await masRestClient.importSettings(importData, true);

// Import without creating backup
const result = await masRestClient.importSettings(importData, false);

// Import from File object
const file = fileInput.files[0];
const result = await masRestClient.importSettings(file, true);
```

### Validate Import Data

```javascript
const validation = masRestClient.validateImportData(importData);
if (!validation.valid) {
  console.error('Validation errors:', validation.errors);
}
```

### Manual File Download

```javascript
const data = { settings: {...} };
masRestClient.triggerDownload(
  data,
  'my-settings.json',
  'application/json'
);
```

## PHP Service Usage

### Export Settings

```php
$service = MAS_Import_Export_Service::get_instance();

// Export with metadata
$export_data = $service->export_settings(true);

// Export without metadata
$export_data = $service->export_settings(false);

// Get export filename
$filename = $service->get_export_filename();
// Returns: "mas-v2-settings-sitename-2025-05-10-120000.json"
```

### Import Settings

```php
$service = MAS_Import_Export_Service::get_instance();

$import_data = [
    'settings' => [
        'menu_background' => '#2d2d44',
        'menu_text_color' => '#ffffff',
    ],
    'metadata' => [
        'export_version' => '2.2.0',
    ],
];

// Import with backup
$result = $service->import_settings($import_data, true);

// Import without backup
$result = $service->import_settings($import_data, false);

// Check for errors
if (is_wp_error($result)) {
    $error_message = $result->get_error_message();
    $error_code = $result->get_error_code();
    $error_data = $result->get_error_data();
}
```

### Validate JSON

```php
$service = MAS_Import_Export_Service::get_instance();

$json_string = '{"settings": {"menu_background": "#1e1e2e"}}';
$data = $service->validate_json($json_string);

if (is_wp_error($data)) {
    // Invalid JSON
    echo $data->get_error_message();
} else {
    // Valid JSON, $data contains parsed array
    print_r($data);
}
```

## Error Codes

| Code | Description | HTTP Status |
|------|-------------|-------------|
| `rest_forbidden` | User lacks permission | 403 |
| `rest_cookie_invalid_nonce` | Invalid nonce | 403 |
| `invalid_import_format` | Import data is not valid JSON object | 400 |
| `import_validation_failed` | Import data structure validation failed | 400 |
| `settings_validation_failed` | Settings data validation failed | 400 |
| `incompatible_version` | Import version too old | 400 |
| `invalid_json` | JSON parsing failed | 400 |
| `empty_json` | JSON data is empty | 400 |
| `pre_import_backup_failed` | Failed to create backup before import | 500 |
| `import_failed` | Failed to import settings | 500 |
| `export_failed` | Failed to export settings | 500 |

## Import Data Format

### Current Format (v2.2.0)

```json
{
  "settings": {
    "menu_background": "#1e1e2e",
    "menu_text_color": "#ffffff",
    "menu_hover_background": "#2d2d44",
    "admin_bar_background": "#23282d",
    "enable_animations": true,
    "menu_width": 160,
    ...
  },
  "metadata": {
    "export_version": "2.2.0",
    "plugin_version": "2.2.0",
    "wordpress_version": "6.8",
    "export_date": "2025-05-10 12:00:00",
    "export_timestamp": 1715342400,
    "site_url": "https://example.com",
    "exported_by": 1
  }
}
```

### Legacy Format (v2.0.0)

```json
{
  "settings": {
    "menu_bg": "#1e1e2e",
    "menu_txt": "#ffffff",
    ...
  }
}
```

**Note:** Legacy format is automatically migrated. Field aliases are converted to new names.

## Field Aliases (Backward Compatibility)

| Old Name | New Name |
|----------|----------|
| `menu_bg` | `menu_background` |
| `menu_txt` | `menu_text_color` |
| `menu_hover_bg` | `menu_hover_background` |
| `menu_hover_txt` | `menu_hover_text_color` |
| `menu_active_bg` | `menu_active_background` |
| `menu_active_txt` | `menu_active_text_color` |
| `admin_bar_bg` | `admin_bar_background` |
| `admin_bar_txt` | `admin_bar_text_color` |
| `submenu_bg` | `submenu_background` |
| `submenu_txt` | `submenu_text_color` |

## Version Compatibility

- **Minimum Import Version:** 2.0.0
- **Current Export Version:** 2.2.0
- **Automatic Migration:** Yes, for versions >= 2.0.0
- **Legacy Support:** Yes, imports without metadata are supported

## Security Notes

1. **Authentication Required:** All endpoints require `manage_options` capability
2. **Nonce Validation:** POST requests must include valid WordPress nonce
3. **Input Sanitization:** All imported data is sanitized
4. **Validation:** Comprehensive validation prevents malicious data
5. **File Size Limit:** JavaScript client enforces 5MB maximum
6. **File Type:** Only JSON files accepted

## Example: Complete Export/Import Workflow

```javascript
// 1. Export current settings
const exportData = await masRestClient.exportSettings(true, true);
// File downloads automatically as "mas-v2-settings-sitename-2025-05-10-120000.json"

// 2. Later, import the settings
const fileInput = document.getElementById('import-file');
// User selects the previously exported JSON file

try {
  const result = await masRestClient.importSettingsFromFile(fileInput, true);
  console.log('Import successful:', result);
  // Backup was created automatically before import
} catch (error) {
  if (error instanceof MASRestError) {
    console.error('Import failed:', error.getUserMessage());
    if (error.isValidationError()) {
      console.error('Validation errors:', error.data.errors);
    }
  }
}
```

## Example: Programmatic Import/Export

```javascript
// Export settings programmatically
const exportData = await masRestClient.exportSettings(true, false);
console.log('Exported settings:', exportData.settings);

// Modify settings
exportData.settings.menu_background = '#3d3d5c';

// Import modified settings
const result = await masRestClient.importSettings(exportData, true);
console.log('Import result:', result);
```

## Testing

### cURL Examples

**Export:**
```bash
curl -X GET "http://localhost/wp-json/mas-v2/v1/export?include_metadata=1" \
  -H "X-WP-Nonce: YOUR_NONCE" \
  --cookie "wordpress_logged_in_HASH=YOUR_COOKIE" \
  -o exported-settings.json
```

**Import:**
```bash
curl -X POST "http://localhost/wp-json/mas-v2/v1/import" \
  -H "Content-Type: application/json" \
  -H "X-WP-Nonce: YOUR_NONCE" \
  --cookie "wordpress_logged_in_HASH=YOUR_COOKIE" \
  -d @exported-settings.json
```

### PHPUnit Tests

```bash
cd tests/php/rest-api
phpunit TestMASImportExportIntegration.php
```

## Troubleshooting

### Import Fails with "Invalid JSON"
- Ensure the file is valid JSON
- Check for BOM or encoding issues
- Validate JSON at jsonlint.com

### Import Fails with "Incompatible Version"
- Export version is older than 2.0.0
- Update the plugin or manually migrate the data

### Export Downloads Empty File
- Check user permissions (must have `manage_options`)
- Verify nonce is valid
- Check browser console for errors

### Import Doesn't Apply Settings
- Check validation errors in response
- Verify color values are valid hex colors
- Ensure numeric values are valid numbers

---

**Last Updated:** 2025-05-10
**Version:** 2.2.0
