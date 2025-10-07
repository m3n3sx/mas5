# Diagnostics REST API Tests - Quick Start Guide

## Overview

This guide covers the integration tests for the MAS Diagnostics REST API endpoints, including system information collection, settings integrity validation, conflict detection, health checks, and performance metrics.

## Test File

- **Location**: `tests/php/rest-api/TestMASDiagnosticsIntegration.php`
- **Class**: `TestMASDiagnosticsIntegration`
- **Extends**: `WP_UnitTestCase`

## Running the Tests

### Run All Diagnostics Tests

```bash
cd /path/to/wordpress/wp-content/plugins/modern-admin-styler-v2
vendor/bin/phpunit tests/php/rest-api/TestMASDiagnosticsIntegration.php
```

### Run Specific Test

```bash
vendor/bin/phpunit --filter test_get_system_information tests/php/rest-api/TestMASDiagnosticsIntegration.php
```

### Run with Verbose Output

```bash
vendor/bin/phpunit --verbose tests/php/rest-api/TestMASDiagnosticsIntegration.php
```

## Test Coverage

### System Information Collection (Requirements: 12.1, 12.2)

- ✅ `test_get_system_information` - Verifies PHP, WordPress, MySQL versions
- ✅ `test_get_plugin_information` - Verifies plugin metadata and REST API availability

### Settings Integrity Validation (Requirements: 12.1, 12.2)

- ✅ `test_settings_integrity_validation` - Checks settings structure and validity
- ✅ `test_settings_integrity_with_invalid_data` - Detects invalid color/unit values
- ✅ `test_settings_integrity_with_missing_keys` - Detects missing required settings

### Conflict Detection (Requirements: 12.1, 12.2)

- ✅ `test_conflict_detection` - Checks for plugin conflicts
- ✅ `test_conflict_detection_with_plugins` - Detects admin menu plugin conflicts

### Health Checks (Requirements: 12.1, 12.2)

- ✅ `test_health_check_endpoint` - Verifies health check endpoint
- ✅ `test_health_check_status_determination` - Tests overall health status

### Performance Metrics (Requirements: 12.1, 12.2)

- ✅ `test_performance_metrics_collection` - Verifies memory and execution metrics
- ✅ `test_performance_metrics_endpoint` - Tests dedicated performance endpoint
- ✅ `test_diagnostics_performance` - Ensures diagnostics complete quickly

### Authentication & Authorization (Requirements: 12.3)

- ✅ `test_diagnostics_requires_authentication` - Blocks unauthenticated access
- ✅ `test_diagnostics_requires_proper_authorization` - Requires manage_options capability
- ✅ `test_health_check_requires_authentication` - Protects health check endpoint
- ✅ `test_performance_metrics_requires_authentication` - Protects performance endpoint

### Additional Features

- ✅ `test_diagnostics_with_include_parameter` - Tests selective section retrieval
- ✅ `test_diagnostics_with_invalid_include_parameter` - Validates include parameter
- ✅ `test_recommendations_generation` - Verifies optimization recommendations
- ✅ `test_diagnostics_metadata` - Checks metadata timestamps
- ✅ `test_diagnostics_error_handling` - Tests exception handling
- ✅ `test_complete_diagnostics_workflow` - End-to-end workflow test

## Endpoints Tested

### GET /mas-v2/v1/diagnostics

**Purpose**: Retrieve comprehensive system diagnostics

**Test Coverage**:
- System information (PHP, WordPress, MySQL versions)
- Plugin information and REST API availability
- Settings integrity validation
- Filesystem permissions and structure
- Conflict detection with other plugins
- Performance metrics (memory, execution time)
- Optimization recommendations
- Metadata (timestamps, execution time)

**Parameters**:
- `include` (optional): Comma-separated list of sections to include

**Example Request**:
```bash
curl -X GET "http://localhost/wp-json/mas-v2/v1/diagnostics" \
  -H "X-WP-Nonce: YOUR_NONCE" \
  --cookie "wordpress_logged_in_cookie=YOUR_COOKIE"
```

**Example with Include Parameter**:
```bash
curl -X GET "http://localhost/wp-json/mas-v2/v1/diagnostics?include=system,plugin" \
  -H "X-WP-Nonce: YOUR_NONCE" \
  --cookie "wordpress_logged_in_cookie=YOUR_COOKIE"
```

### GET /mas-v2/v1/diagnostics/health

**Purpose**: Quick health check with pass/fail status

**Test Coverage**:
- REST API availability check
- Settings integrity check
- Filesystem permissions check
- PHP version check
- Overall health status determination

**Example Request**:
```bash
curl -X GET "http://localhost/wp-json/mas-v2/v1/diagnostics/health" \
  -H "X-WP-Nonce: YOUR_NONCE" \
  --cookie "wordpress_logged_in_cookie=YOUR_COOKIE"
```

### GET /mas-v2/v1/diagnostics/performance

**Purpose**: Retrieve performance metrics only

**Test Coverage**:
- Memory usage (current, peak, limit)
- Execution time
- Database query metrics
- Cache metrics

**Example Request**:
```bash
curl -X GET "http://localhost/wp-json/mas-v2/v1/diagnostics/performance" \
  -H "X-WP-Nonce: YOUR_NONCE" \
  --cookie "wordpress_logged_in_cookie=YOUR_COOKIE"
```

## Expected Response Formats

### Full Diagnostics Response

```json
{
  "success": true,
  "message": "Diagnostics retrieved successfully",
  "data": {
    "system": {
      "php_version": "8.1.0",
      "wordpress_version": "6.8",
      "mysql_version": "8.0.30",
      "rest_api_enabled": true
    },
    "plugin": {
      "version": "2.2.0",
      "name": "Modern Admin Styler V2",
      "rest_api_namespace": "mas-v2/v1",
      "rest_api_available": true
    },
    "settings": {
      "valid": true,
      "missing_keys": [],
      "invalid_values": [],
      "total_settings": 25,
      "expected_settings": 25
    },
    "filesystem": {
      "upload_dir_writable": true,
      "plugin_dir_readable": true,
      "required_directories": {}
    },
    "conflicts": {
      "potential_conflicts": [],
      "admin_menu_plugins": [],
      "rest_api_conflicts": []
    },
    "performance": {
      "memory_usage": {
        "current": "32 MB",
        "peak": "35 MB",
        "limit": "256M"
      },
      "execution_time": {
        "diagnostics": "45.23ms"
      }
    },
    "recommendations": [],
    "_metadata": {
      "generated_at": "2025-05-10 12:00:00",
      "generated_timestamp": 1715342400,
      "execution_time": "45.23ms"
    }
  }
}
```

### Health Check Response

```json
{
  "success": true,
  "message": "Health check completed: healthy",
  "data": {
    "status": "healthy",
    "checks": {
      "rest_api": {
        "status": "pass",
        "message": "REST API is available"
      },
      "settings": {
        "status": "pass",
        "message": "Settings are valid"
      },
      "filesystem": {
        "status": "pass",
        "message": "Filesystem is writable"
      },
      "php_version": {
        "status": "pass",
        "message": "PHP version is adequate"
      }
    }
  }
}
```

## Common Test Scenarios

### Testing System Information Collection

```php
public function test_get_system_information() {
    wp_set_current_user( $this->admin_user );
    
    $request = new WP_REST_Request( 'GET', $this->route );
    $response = rest_do_request( $request );
    
    $data = $response->get_data();
    $system = $data['data']['system'];
    
    $this->assertArrayHasKey( 'php_version', $system );
    $this->assertArrayHasKey( 'wordpress_version', $system );
}
```

### Testing Settings Integrity

```php
public function test_settings_integrity_with_invalid_data() {
    wp_set_current_user( $this->admin_user );
    
    // Save invalid settings
    update_option( 'mas_v2_settings', array(
        'menu_background' => 'invalid-color',
    ) );
    
    $request = new WP_REST_Request( 'GET', $this->route );
    $response = rest_do_request( $request );
    
    $data = $response->get_data();
    $settings = $data['data']['settings'];
    
    $this->assertFalse( $settings['valid'] );
    $this->assertNotEmpty( $settings['invalid_values'] );
}
```

### Testing Conflict Detection

```php
public function test_conflict_detection_with_plugins() {
    wp_set_current_user( $this->admin_user );
    
    // Simulate active plugins
    update_option( 'active_plugins', array(
        'admin-menu-editor/admin-menu-editor.php',
    ) );
    
    $request = new WP_REST_Request( 'GET', $this->route );
    $response = rest_do_request( $request );
    
    $data = $response->get_data();
    $conflicts = $data['data']['conflicts'];
    
    $this->assertNotEmpty( $conflicts['potential_conflicts'] );
}
```

## Troubleshooting

### Tests Fail with "Class not found"

Ensure all required files are loaded in `setUp()`:

```php
require_once MAS_V2_PLUGIN_DIR . 'includes/api/class-mas-rest-controller.php';
require_once MAS_V2_PLUGIN_DIR . 'includes/services/class-mas-diagnostics-service.php';
require_once MAS_V2_PLUGIN_DIR . 'includes/api/class-mas-diagnostics-controller.php';
```

### Tests Fail with Permission Errors

Ensure test user has admin role:

```php
$this->admin_user = $this->factory->user->create( array( 'role' => 'administrator' ) );
wp_set_current_user( $this->admin_user );
```

### Diagnostics Return Empty Data

Check that the plugin is properly initialized and routes are registered:

```php
$this->controller->register_routes();
```

## Requirements Coverage

- ✅ **Requirement 12.1**: Unit tests cover all business logic
- ✅ **Requirement 12.2**: Integration tests cover all endpoints end-to-end
- ✅ **Requirement 12.3**: Authentication tests cover success and failure cases

## Next Steps

After running these tests successfully:

1. Review test coverage report
2. Add any missing edge case tests
3. Integrate tests into CI/CD pipeline
4. Document any discovered issues

## Related Documentation

- [REST API Quick Start](REST-API-QUICK-START.md)
- [Diagnostics API Quick Reference](../../../DIAGNOSTICS-API-QUICK-REFERENCE.md)
- [Integration Tests Summary](INTEGRATION-TESTS-SUMMARY.md)
