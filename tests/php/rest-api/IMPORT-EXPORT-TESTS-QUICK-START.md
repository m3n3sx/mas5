# Import/Export Tests Quick Start

This guide covers the comprehensive test suite for the Modern Admin Styler V2 Import/Export REST API endpoints.

## Overview

The `TestMASImportExportIntegration.php` file contains 19 comprehensive tests covering all aspects of the import/export functionality, including:

- Export with proper headers and format
- Import with valid and invalid data
- Automatic backup creation on import
- Legacy format migration

## Test Coverage

### 1. Export with Proper Headers and Format (4 tests)

#### `test_export_requires_authentication()`
Verifies that unauthenticated users cannot export settings.

**Expected Result:** 403 Forbidden

```php
// Unauthenticated request
$request = new WP_REST_Request('GET', '/mas-v2/v1/export');
$response = rest_do_request($request);
// Returns 403
```

#### `test_export_with_admin_user()`
Tests successful export with admin user, verifying data structure.

**Expected Result:** 200 OK with settings and metadata

```php
// Admin user exports settings
wp_set_current_user($admin_user);
$request = new WP_REST_Request('GET', '/mas-v2/v1/export');
$response = rest_do_request($request);

// Response includes:
// - success: true
// - data.settings: {...}
// - data.metadata: {...}
// - filename: "mas-v2-settings-*.json"
```

#### `test_export_has_proper_headers()`
Verifies that export response includes proper HTTP headers for file download.

**Expected Headers:**
- `Content-Disposition: attachment; filename="*.json"`
- `Content-Type: application/json`
- `Cache-Control: no-cache, no-store, must-revalidate`

#### `test_export_includes_version_metadata()`
Ensures export includes version information for compatibility checking.

**Metadata Fields:**
- `export_version`: Current export format version
- `plugin_version`: Plugin version
- `wordpress_version`: WordPress version
- `export_date`: Export date/time
- `export_timestamp`: Unix timestamp
- `site_url`: Site URL
- `exported_by`: User ID

### 2. Import with Valid and Invalid Data (8 tests)

#### `test_import_requires_authentication()`
Verifies that unauthenticated users cannot import settings.

**Expected Result:** 403 Forbidden

#### `test_import_with_valid_data()`
Tests successful import with valid data structure.

**Valid Import Data:**
```json
{
  "settings": {
    "menu_background": "#2d2d44",
    "menu_text_color": "#00a0d2"
  },
  "metadata": {
    "export_version": "2.2.0",
    "plugin_version": "2.2.0"
  }
}
```

**Expected Result:** 200 OK, settings applied

#### `test_import_with_invalid_data_structure()`
Tests rejection of data missing required fields.

**Invalid Data:**
```json
{
  "metadata": {
    "export_version": "2.2.0"
  }
  // Missing "settings" key
}
```

**Expected Result:** 400 Bad Request

#### `test_import_with_invalid_json_string()`
Tests rejection of malformed JSON strings.

**Invalid JSON:**
```
"invalid json {"
```

**Expected Result:** 400 Bad Request with error code `invalid_json`

#### `test_import_with_valid_json_string()`
Tests that import accepts JSON as a string parameter.

**Valid JSON String:**
```json
"{\"settings\":{\"menu_background\":\"#3d3d5c\"},\"metadata\":{\"export_version\":\"2.2.0\"}}"
```

**Expected Result:** 200 OK, settings applied

#### `test_import_with_invalid_color_values()`
Tests validation of color values during import.

**Invalid Data:**
```json
{
  "settings": {
    "menu_background": "not-a-color"
  }
}
```

**Expected Result:** 400 Bad Request with error code `settings_validation_failed`

#### `test_import_with_empty_settings()`
Tests rejection of empty settings object.

**Invalid Data:**
```json
{
  "settings": {},
  "metadata": {"export_version": "2.2.0"}
}
```

**Expected Result:** 400 Bad Request

#### `test_import_with_incompatible_version()`
Tests version compatibility checking.

**Incompatible Data:**
```json
{
  "settings": {...},
  "metadata": {
    "export_version": "1.0.0"  // Too old
  }
}
```

**Expected Result:** 400 Bad Request with error code `incompatible_version`

### 3. Automatic Backup Creation on Import (2 tests)

#### `test_import_creates_automatic_backup()`
Verifies that import creates a backup when requested.

**Test Flow:**
1. Set initial settings
2. Count existing backups
3. Import new settings with `create_backup: true`
4. Verify backup count increased by 1
5. Verify backup contains old settings

**Expected Result:** Backup created with previous settings

#### `test_import_without_backup()`
Verifies that import can skip backup creation.

**Test Flow:**
1. Count existing backups
2. Import settings with `create_backup: false`
3. Verify backup count unchanged

**Expected Result:** No backup created

### 4. Legacy Format Migration (2 tests)

#### `test_import_with_legacy_format()`
Tests migration of legacy format without metadata.

**Legacy Format:**
```json
{
  "settings": {
    "menu_background": "#777777",
    "menu_text_color": "#888888"
  }
  // No metadata field
}
```

**Expected Result:** 200 OK, settings migrated and applied

#### `test_import_with_field_aliases()`
Tests handling of old field names (aliases).

**Data with Aliases:**
```json
{
  "settings": {
    "menu_bg": "#999999",    // Old alias
    "menu_txt": "#aaaaaa"    // Old alias
  }
}
```

**Expected Result:** 200 OK, aliases converted to new field names

### 5. Additional Integration Tests (3 tests)

#### `test_full_export_import_workflow()`
Tests complete export-import cycle.

**Test Flow:**
1. Set initial settings
2. Export settings
3. Change settings
4. Import exported settings
5. Verify settings restored to initial state

**Expected Result:** Settings successfully restored

#### `test_editor_cannot_export()`
Verifies that editor users cannot export settings.

**Expected Result:** 403 Forbidden

#### `test_editor_cannot_import()`
Verifies that editor users cannot import settings.

**Expected Result:** 403 Forbidden

## Running the Tests

### Run all import/export tests:
```bash
phpunit tests/php/rest-api/TestMASImportExportIntegration.php
```

### Run specific test:
```bash
phpunit --filter test_import_with_valid_data tests/php/rest-api/TestMASImportExportIntegration.php
```

### Run with verbose output:
```bash
phpunit --verbose tests/php/rest-api/TestMASImportExportIntegration.php
```

## Quick Verification

Use the verification script to check test implementation:

```bash
php verify-task5.5-completion.php
```

This will verify:
- All 19 required tests are implemented
- Implementation files exist
- Key methods are present
- Test coverage is complete

## Expected Test Results

All tests should pass:

```
PHPUnit 7.5.20 by Sebastian Bergmann and contributors.

...................                                               19 / 19 (100%)

Time: 00:00.500, Memory: 12.00 MB

OK (19 tests, 75+ assertions)
```

## Test Requirements Mapping

These tests fulfill the following requirements:

- ✅ **Requirement 12.1**: Unit tests cover all business logic
- ✅ **Requirement 12.2**: Integration tests cover end-to-end workflows
- ✅ **Requirement 12.4**: Validation tests with edge cases and malformed data
- ✅ **Requirement 5.1**: Export settings as JSON
- ✅ **Requirement 5.2**: Import settings from JSON
- ✅ **Requirement 5.3**: Proper Content-Disposition headers
- ✅ **Requirement 5.4**: Import validation and error messages
- ✅ **Requirement 5.5**: Automatic backup before import
- ✅ **Requirement 5.6**: Version metadata for compatibility
- ✅ **Requirement 5.7**: Legacy format migration

## API Endpoints Tested

### Export Endpoint
```
GET /wp-json/mas-v2/v1/export
```

**Parameters:**
- `include_metadata` (boolean, optional): Include metadata in export (default: true)

**Response:**
```json
{
  "success": true,
  "data": {
    "settings": {...},
    "metadata": {...}
  },
  "filename": "mas-v2-settings-sitename-2025-01-10-123456.json",
  "message": "Settings exported successfully",
  "timestamp": 1234567890
}
```

### Import Endpoint
```
POST /wp-json/mas-v2/v1/import
```

**Parameters:**
- `data` (string|object, required): Import data as JSON string or object
- `create_backup` (boolean, optional): Create backup before import (default: true)

**Response:**
```json
{
  "success": true,
  "data": {
    "imported": true,
    "backup_created": true
  },
  "message": "Settings imported successfully"
}
```

## Common Test Patterns

### Testing Authentication
```php
// Test without authentication
$request = new WP_REST_Request('GET', '/mas-v2/v1/export');
$response = rest_do_request($request);
$this->assertEquals(403, $response->get_status());

// Test with admin user
wp_set_current_user($this->admin_user);
$request = new WP_REST_Request('GET', '/mas-v2/v1/export');
$response = rest_do_request($request);
$this->assertEquals(200, $response->get_status());
```

### Testing Validation
```php
$import_data = [
    'settings' => [
        'menu_background' => 'invalid-color'
    ]
];

$request = new WP_REST_Request('POST', '/mas-v2/v1/import');
$request->set_param('data', $import_data);
$response = rest_do_request($request);

$this->assertEquals(400, $response->get_status());
$error = $response->as_error();
$this->assertEquals('settings_validation_failed', $error->get_error_code());
```

### Testing Backup Creation
```php
// Count backups before
$backups_before = $this->backup_service->list_backups();
$count_before = count($backups_before);

// Import with backup
$request = new WP_REST_Request('POST', '/mas-v2/v1/import');
$request->set_param('data', $import_data);
$request->set_param('create_backup', true);
$response = rest_do_request($request);

// Count backups after
$backups_after = $this->backup_service->list_backups();
$count_after = count($backups_after);

// Verify backup was created
$this->assertEquals($count_before + 1, $count_after);
```

## Debugging Failed Tests

### Enable debug mode:
```php
// In test setUp()
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Add debug output:
```php
public function test_import_with_valid_data() {
    // ... test code ...
    
    // Debug output
    error_log('Response: ' . print_r($response->get_data(), true));
    
    // ... assertions ...
}
```

### Check WordPress logs:
```bash
tail -f wp-content/debug.log
```

## Test Data Examples

### Minimal Valid Import
```json
{
  "settings": {
    "menu_background": "#1e1e2e"
  },
  "metadata": {
    "export_version": "2.2.0"
  }
}
```

### Complete Import with All Fields
```json
{
  "settings": {
    "menu_background": "#1e1e2e",
    "menu_text_color": "#ffffff",
    "menu_hover_background": "#2d2d44",
    "admin_bar_background": "#23282d",
    "glassmorphism_enabled": true,
    "animation_speed": 300
  },
  "metadata": {
    "export_version": "2.2.0",
    "plugin_version": "2.2.0",
    "wordpress_version": "6.8",
    "export_date": "2025-01-10 12:34:56",
    "export_timestamp": 1234567890
  }
}
```

### Legacy Format (No Metadata)
```json
{
  "settings": {
    "menu_bg": "#1e1e2e",
    "menu_txt": "#ffffff"
  }
}
```

## Troubleshooting

### Test fails with "Class not found"
Ensure all required files are loaded in `setUp()`:
```php
require_once MAS_V2_PLUGIN_DIR . 'includes/api/class-mas-import-export-controller.php';
require_once MAS_V2_PLUGIN_DIR . 'includes/services/class-mas-import-export-service.php';
```

### Test fails with "Permission denied"
Ensure user is set before making request:
```php
wp_set_current_user($this->admin_user);
```

### Test fails with "Settings not saved"
Check that settings service is properly initialized:
```php
$this->settings_service = MAS_Settings_Service::get_instance();
```

### Backup tests fail
Ensure backups are cleaned up in `tearDown()`:
```php
public function tearDown() {
    $this->cleanup_all_backups();
    parent::tearDown();
}
```

## Next Steps

After verifying all tests pass:

1. ✅ Mark task 5.5 as complete
2. ✅ Update task status in tasks.md
3. ✅ Proceed to task 6.1 (Live Preview Endpoint)

## Related Documentation

- [REST API Quick Start](./QUICK-START.md)
- [Integration Tests Summary](./INTEGRATION-TESTS-SUMMARY.md)
- [Import/Export API Reference](../../../IMPORT-EXPORT-API-QUICK-REFERENCE.md)
- [Task 5 Completion Report](../../../TASK-5-IMPORT-EXPORT-COMPLETION.md)
