# REST API Tests

This directory contains PHPUnit tests for the Modern Admin Styler V2 REST API implementation.

## Test Files

### TestMASRestController.php

Tests for the base `MAS_REST_Controller` class that all REST controllers extend.

### TestMASSettingsIntegration.php

Comprehensive integration tests for the Settings REST API endpoints, covering the complete CRUD workflow.

### TestMASThemesIntegration.php

Comprehensive integration tests for the Themes REST API endpoints, covering theme listing, filtering, creation, validation, application, CSS updates, and predefined theme protection.

**Test Coverage:**

1. **Authentication Tests**
   - `test_check_permission_with_admin_user()` - Verifies admin users have access
   - `test_check_permission_with_editor_user()` - Verifies editors are denied (no manage_options)
   - `test_check_permission_with_subscriber_user()` - Verifies subscribers are denied
   - `test_check_permission_with_unauthenticated_user()` - Verifies unauthenticated users are denied

2. **Response Formatting Tests**
   - `test_error_response_format()` - Tests error response structure
   - `test_error_response_with_defaults()` - Tests default error parameters
   - `test_error_response_status_codes()` - Tests various HTTP error codes (400, 401, 403, 404, 500)
   - `test_success_response_format()` - Tests success response structure
   - `test_success_response_with_defaults()` - Tests default success parameters
   - `test_success_response_with_empty_data()` - Tests success with no data
   - `test_success_response_status_codes()` - Tests various HTTP success codes (200, 201, 204)

3. **Permission Tests**
   - `test_permission_error_message()` - Verifies error messages are descriptive
   - `test_permission_check_with_different_methods()` - Tests GET, POST, PUT, DELETE, PATCH

4. **Configuration Tests**
   - `test_namespace_property()` - Verifies namespace is set to 'mas-v2/v1'

**Test Coverage for Settings Integration:**

1. **Complete Workflow Tests**
   - `test_complete_settings_workflow()` - Tests GET → POST → PUT → DELETE workflow
   - `test_settings_persistence()` - Verifies settings persist across requests
   - `test_concurrent_settings_updates()` - Tests multiple sequential updates

2. **Authentication & Authorization Tests**
   - `test_get_settings_requires_authentication()` - Verifies unauthenticated access is denied
   - `test_get_settings_with_admin_authorization()` - Verifies admin access
   - `test_get_settings_with_insufficient_permissions()` - Tests editor/subscriber denial
   - `test_save_settings_without_authentication()` - Tests POST without auth
   - `test_update_settings_without_authentication()` - Tests PUT without auth
   - `test_reset_settings_without_authentication()` - Tests DELETE without auth

3. **Validation Tests**
   - `test_save_settings_with_invalid_colors()` - Tests color validation
   - `test_save_settings_with_invalid_numeric_values()` - Tests numeric validation
   - `test_save_settings_with_invalid_boolean_values()` - Tests boolean validation
   - `test_save_settings_with_empty_data()` - Tests empty request handling
   - `test_validation_error_response_format()` - Tests error response structure
   - `test_hex_color_validation()` - Tests various hex color formats

4. **CRUD Operation Tests**
   - `test_save_settings_with_valid_data()` - Tests POST with valid data
   - `test_update_settings_partial()` - Tests PUT partial updates
   - `test_reset_settings_to_defaults()` - Tests DELETE reset operation

5. **Feature Tests**
   - `test_css_generation_on_save()` - Verifies CSS regeneration
   - `test_settings_caching()` - Tests cache behavior

## Running These Tests

### Run all REST API tests:
```bash
phpunit --testsuite rest-api
```

### Run only base controller tests:
```bash
phpunit tests/php/rest-api/TestMASRestController.php
```

### Run only settings integration tests:
```bash
phpunit tests/php/rest-api/TestMASSettingsIntegration.php
```

### Run specific test method:
```bash
phpunit --filter test_check_permission_with_admin_user tests/php/rest-api/TestMASRestController.php
```

## Test Requirements Mapping

These tests fulfill the following requirements from the spec:

- **Requirement 12.1**: Unit tests cover all business logic
- **Requirement 12.2**: Integration tests cover end-to-end workflows (TestMASSettingsIntegration)
- **Requirement 12.3**: Authentication and authorization tests (both test files)
- **Requirement 12.4**: Validation tests with edge cases and malformed data (TestMASSettingsIntegration)
- **Requirement 8.1**: Authentication with manage_options capability
- **Requirement 8.2**: Proper permission checks
- **Requirement 1.3**: Appropriate HTTP status codes
- **Requirement 2.1-2.7**: Settings management API endpoints
- **Requirement 10.3**: CSS regeneration on settings save

## Expected Test Results

All tests should pass with the following output:

**Base Controller Tests:**
```
PHPUnit 7.5.20 by Sebastian Bergmann and contributors.

.....................                                             21 / 21 (100%)

Time: 00:00.123, Memory: 10.00 MB

OK (21 tests, 45 assertions)
```

**Settings Integration Tests:**
```
PHPUnit 7.5.20 by Sebastian Bergmann and contributors.

.........................                                         25 / 25 (100%)

Time: 00:00.456, Memory: 12.00 MB

OK (25 tests, 85+ assertions)
```

## Adding New Tests

When adding new REST API controllers, create corresponding test files:

1. Create test file: `TestMAS{Controller}Controller.php`
2. Extend `WP_UnitTestCase`
3. Test all public methods
4. Test authentication and authorization
5. Test error handling
6. Test response formats
7. Aim for 90%+ code coverage

### Example Test Template:

```php
<?php
class TestMASNewController extends WP_UnitTestCase {
    
    protected $controller;
    protected $admin_user;
    
    public function setUp() {
        parent::setUp();
        $this->admin_user = $this->factory->user->create(['role' => 'administrator']);
        $this->controller = new MAS_New_Controller();
    }
    
    public function tearDown() {
        parent::tearDown();
        wp_set_current_user(0);
    }
    
    public function test_endpoint_requires_authentication() {
        // Test implementation
    }
    
    public function test_endpoint_validates_input() {
        // Test implementation
    }
    
    public function test_endpoint_returns_correct_format() {
        // Test implementation
    }
}
```

## Debugging Tests

### Enable WordPress debug mode:

Edit `wp-tests-config.php` and add:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Run tests with verbose output:

```bash
phpunit --verbose tests/php/rest-api/TestMASRestController.php
```

### Run single test with debug output:

```bash
phpunit --debug --filter test_check_permission_with_admin_user
```

## Code Coverage

Generate coverage report for REST API tests:

```bash
phpunit --testsuite rest-api --coverage-html tests/coverage/html
```

View the report:
```bash
open tests/coverage/html/index.html
```

Target coverage: **90%+** for REST API code


## Theme Tests Coverage

### TestMASThemesIntegration.php

**Test Categories:**

1. **Theme Listing and Filtering (5 tests)**
   - `test_get_all_themes()` - Retrieve all themes (predefined + custom)
   - `test_filter_themes_by_predefined_type()` - Filter by predefined type
   - `test_filter_themes_by_custom_type()` - Filter by custom type
   - `test_get_specific_theme()` - Get theme by ID
   - `test_get_nonexistent_theme()` - Handle non-existent theme (404)

2. **Custom Theme Creation and Validation (7 tests)**
   - `test_create_custom_theme_success()` - Create valid custom theme
   - `test_create_theme_missing_required_fields()` - Validate required fields
   - `test_create_theme_invalid_id_format()` - Validate ID format
   - `test_create_theme_duplicate_id()` - Prevent duplicate IDs (409)
   - `test_create_theme_reserved_id()` - Protect reserved IDs (400)
   - `test_create_theme_invalid_colors()` - Validate color values
   - `test_create_theme_valid_color_formats()` - Accept valid colors

3. **Theme Updates (2 tests)**
   - `test_update_custom_theme()` - Update custom theme successfully
   - `test_update_nonexistent_theme()` - Handle non-existent theme (404)

4. **Theme Deletion (2 tests)**
   - `test_delete_custom_theme()` - Delete custom theme successfully
   - `test_delete_nonexistent_theme()` - Handle non-existent theme (404)

5. **Theme Application and CSS Updates (5 tests)**
   - `test_apply_predefined_theme()` - Apply predefined theme
   - `test_apply_custom_theme()` - Apply custom theme
   - `test_apply_nonexistent_theme()` - Handle non-existent theme (404)
   - `test_css_generation_on_theme_apply()` - Verify CSS regeneration
   - `test_theme_apply_preserves_other_settings()` - Preserve non-theme settings

6. **Predefined Theme Protection (2 tests)**
   - `test_update_predefined_theme_protection()` - Prevent updates (403)
   - `test_delete_predefined_theme_protection()` - Prevent deletion (403)

7. **Authentication and Authorization (2 tests)**
   - `test_theme_endpoints_require_authentication()` - All endpoints require auth
   - `test_theme_endpoints_require_manage_options()` - Require manage_options

8. **Additional Integration Tests (3 tests)**
   - `test_theme_data_sanitization()` - XSS prevention
   - `test_theme_caching()` - Cache behavior
   - `test_complete_theme_workflow()` - End-to-end workflow

**Total Theme Tests: 27**

**Requirements Coverage:**
- ✅ Requirement 12.1 - Unit tests cover all business logic
- ✅ Requirement 12.2 - Integration tests cover end-to-end workflows

