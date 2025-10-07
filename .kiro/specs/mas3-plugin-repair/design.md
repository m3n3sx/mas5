# Design Document

## Overview

This design addresses the critical fatal error in the Modern Admin Styler V2 plugin by implementing a lazy-loading pattern for REST API controllers. The core issue is that controller classes extending `WP_REST_Controller` are being loaded before WordPress has initialized the REST API framework, causing a "Class not found" fatal error.

The solution implements deferred class loading, proper error handling, and WordPress compatibility checks to ensure the plugin loads safely across all supported WordPress versions.

## Architecture

### Current Architecture (Problematic)

```
Plugin Load
  └─> __construct()
      └─> init()
          └─> add_action('rest_api_init', 'init_rest_api')
              └─> init_rest_api() [called when rest_api_init fires]
                  └─> MAS_REST_API::get_instance()
                      └─> __construct()
                          └─> load_dependencies()
                              └─> require_once 'class-mas-rest-controller.php'
                                  └─> ❌ FATAL: class extends WP_REST_Controller (doesn't exist yet)
```

### New Architecture (Fixed)

```
Plugin Load
  └─> __construct()
      └─> init()
          └─> add_action('rest_api_init', 'init_rest_api')
              └─> init_rest_api() [called when rest_api_init fires]
                  └─> ✅ Check: class_exists('WP_REST_Controller')
                  └─> MAS_REST_API::get_instance()
                      └─> __construct()
                          └─> init()
                              └─> add_action('rest_api_init', 'register_controllers')
                                  └─> register_controllers() [deferred]
                                      └─> load_controller_files()
                                          └─> ✅ Safe: WP_REST_Controller exists
```

## Components and Interfaces

### 1. MAS_REST_API Class (Modified)

**Responsibilities:**
- Defer controller file loading until `register_controllers()` is called
- Verify WordPress REST API is available before loading controllers
- Provide error handling and logging for initialization failures

**Key Methods:**

```php
class MAS_REST_API {
    private function __construct() {
        // Don't load dependencies here
        $this->init();
    }
    
    private function init() {
        // Only register the hook, don't load files yet
        add_action('rest_api_init', [$this, 'register_controllers']);
    }
    
    public function register_controllers() {
        // Verify WP_REST_Controller exists
        if (!class_exists('WP_REST_Controller')) {
            $this->log_error('WP_REST_Controller not available');
            return;
        }
        
        // Now it's safe to load controller files
        $this->load_controller_files();
        
        // Initialize and register each controller
        $this->init_controllers();
    }
    
    private function load_controller_files() {
        // Load base controller first
        require_once MAS_V2_PLUGIN_DIR . 'includes/api/class-mas-rest-controller.php';
        
        // Load specific controllers
        // ... (existing code)
    }
}
```

### 2. Main Plugin Class (Modified)

**Responsibilities:**
- Add safety checks before initializing REST API
- Provide graceful degradation if REST API unavailable
- Log initialization steps in debug mode

**Key Methods:**

```php
class ModernAdminStylerV2 {
    public function init_rest_api() {
        // Safety check
        if (!class_exists('WP_REST_Controller')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MAS V2: WP_REST_Controller not available, skipping REST API init');
            }
            return;
        }
        
        // Load REST API bootstrap class
        require_once MAS_V2_PLUGIN_DIR . 'includes/class-mas-rest-api.php';
        
        // Initialize REST API
        MAS_REST_API::get_instance();
    }
}
```

### 3. Error Handler Component (New)

**Responsibilities:**
- Centralize error logging
- Provide admin notices for critical errors
- Track initialization failures

**Interface:**

```php
class MAS_Error_Handler {
    public static function log_error($message, $context = []);
    public static function add_admin_notice($message, $type = 'error');
    public static function is_fatal_error($error);
}
```

## Data Models

### Error Log Entry

```php
[
    'timestamp' => '2025-06-10 12:00:00',
    'level' => 'fatal|error|warning|info',
    'message' => 'Error description',
    'context' => [
        'file' => 'path/to/file.php',
        'line' => 123,
        'function' => 'function_name',
        'wp_version' => '6.4.0',
        'php_version' => '8.1.0'
    ]
]
```

### Initialization State

```php
[
    'rest_api_available' => true|false,
    'controllers_loaded' => true|false,
    'services_initialized' => true|false,
    'errors' => []
]
```

## Error Handling

### 1. Class Not Found Errors

**Detection:** Check `class_exists()` before loading files that depend on WordPress classes

**Handling:**
- Log the error with context
- Skip REST API initialization
- Display admin notice if in admin context
- Allow plugin to continue with degraded functionality

### 2. File Loading Errors

**Detection:** Use `file_exists()` before `require_once`

**Handling:**
- Log missing file path
- Continue loading other components
- Track which features are unavailable

### 3. WordPress Version Incompatibility

**Detection:** Check `get_bloginfo('version')` during activation

**Handling:**
- Prevent activation with clear error message
- Provide minimum version requirement
- Link to WordPress update documentation

## Testing Strategy

### Unit Tests

1. **Test REST API Initialization**
   - Verify `WP_REST_Controller` check works correctly
   - Test behavior when class doesn't exist
   - Verify controllers load in correct order

2. **Test Error Handling**
   - Verify errors are logged correctly
   - Test admin notice display
   - Verify graceful degradation

3. **Test WordPress Compatibility**
   - Mock different WordPress versions
   - Verify activation checks work
   - Test minimum version enforcement

### Integration Tests

1. **Test Plugin Activation**
   - Activate plugin on fresh WordPress install
   - Verify no fatal errors occur
   - Check that REST API endpoints are registered

2. **Test REST API Endpoints**
   - Verify all endpoints are accessible
   - Test authentication and permissions
   - Verify responses are correctly formatted

3. **Test Error Scenarios**
   - Simulate missing WordPress classes
   - Test with incompatible WordPress version
   - Verify error messages are helpful

### Manual Testing Checklist

- [ ] Activate plugin on WordPress 5.8
- [ ] Activate plugin on WordPress 6.0
- [ ] Activate plugin on WordPress 6.4+
- [ ] Verify REST API endpoints work
- [ ] Check error logs for warnings
- [ ] Test with WP_DEBUG enabled
- [ ] Test with WP_DEBUG disabled
- [ ] Verify admin notices display correctly
- [ ] Test deactivation and reactivation
- [ ] Verify no conflicts with other plugins

## Implementation Notes

### Critical Changes

1. **Move `load_dependencies()` call** from `__construct()` to `register_controllers()`
2. **Add `class_exists()` check** before loading controller files
3. **Wrap file loading** in try-catch blocks for better error handling
4. **Add initialization logging** for debugging

### Backward Compatibility

- Existing REST API endpoints will continue to work
- No changes to endpoint URLs or response formats
- AJAX handlers remain unchanged
- Settings and data structures unchanged

### Performance Considerations

- Lazy loading reduces initial plugin load time
- Controllers only loaded when REST API is actually used
- No performance impact on non-REST requests
- Error logging only active in debug mode

### Security Considerations

- No changes to authentication or authorization
- Error messages don't expose sensitive information
- File paths in logs are sanitized
- Admin notices only shown to administrators

## Rollback Plan

If the fix causes issues:

1. **Immediate Rollback:** Deactivate plugin via WordPress admin or wp-cli
2. **File-Level Rollback:** Restore previous version of files from git
3. **Database Rollback:** No database changes, so no rollback needed

## Success Criteria

- ✅ Plugin activates without fatal errors
- ✅ WordPress site loads normally
- ✅ REST API endpoints are accessible
- ✅ No errors in WordPress debug log
- ✅ Admin panel is accessible
- ✅ All existing features continue to work
- ✅ Error messages are clear and helpful
