# Diagnostics API - Quick Reference

## Overview

The Diagnostics API provides comprehensive system health checks, performance monitoring, and optimization recommendations for the Modern Admin Styler V2 plugin.

## REST API Endpoints

### 1. Get Full Diagnostics

**Endpoint:** `GET /wp-json/mas-v2/v1/diagnostics`

**Description:** Retrieves comprehensive system diagnostics including system info, plugin info, settings integrity, filesystem checks, conflict detection, performance metrics, and recommendations.

**Parameters:**
- `include` (optional): Comma-separated list of sections to include
  - Valid values: `system`, `plugin`, `settings`, `filesystem`, `conflicts`, `performance`, `recommendations`

**Example Request:**
```bash
curl -X GET "https://yoursite.com/wp-json/mas-v2/v1/diagnostics" \
  -H "X-WP-Nonce: YOUR_NONCE" \
  --cookie "wordpress_logged_in_cookie=YOUR_COOKIE"
```

**Example Request (Specific Sections):**
```bash
curl -X GET "https://yoursite.com/wp-json/mas-v2/v1/diagnostics?include=system,performance" \
  -H "X-WP-Nonce: YOUR_NONCE" \
  --cookie "wordpress_logged_in_cookie=YOUR_COOKIE"
```

**Example Response:**
```json
{
  "success": true,
  "message": "Diagnostics retrieved successfully",
  "data": {
    "system": {
      "php_version": "8.1.0",
      "php_version_check": true,
      "wordpress_version": "6.4.0",
      "wordpress_version_check": true,
      "mysql_version": "8.0.32",
      "server_software": "Apache/2.4.54",
      "php_memory_limit": "256M",
      "php_max_execution_time": "30",
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
      "invalid_values": {},
      "total_settings": 45,
      "expected_settings": 45
    },
    "filesystem": {
      "upload_dir_writable": true,
      "plugin_dir_readable": true,
      "required_directories": {
        "includes": {
          "exists": true,
          "readable": true
        }
      }
    },
    "conflicts": {
      "potential_conflicts": [],
      "admin_menu_plugins": [],
      "rest_api_conflicts": []
    },
    "performance": {
      "memory_usage": {
        "current": "45.2 MB",
        "current_bytes": 47398912,
        "peak": "48.5 MB",
        "peak_bytes": 50855936,
        "limit": "256M"
      },
      "execution_time": {
        "diagnostics": "125.45ms",
        "diagnostics_seconds": 0.1254
      },
      "database": {
        "queries": 15,
        "query_time": "0.0234s"
      },
      "cache": {
        "object_cache_enabled": false,
        "cache_type": "Database",
        "transients_count": 3
      }
    },
    "recommendations": [
      {
        "severity": "low",
        "category": "performance",
        "title": "Object Cache Not Enabled",
        "description": "External object cache is not enabled. Performance could be improved.",
        "action": "Consider installing Redis or Memcached for better performance"
      }
    ],
    "_metadata": {
      "generated_at": "2025-05-10 15:30:00",
      "generated_timestamp": 1715356200,
      "execution_time": "125.45ms"
    }
  },
  "timestamp": 1715356200
}
```

### 2. Get Health Check

**Endpoint:** `GET /wp-json/mas-v2/v1/diagnostics/health`

**Description:** Quick health check that returns overall system status and key checks.

**Example Request:**
```bash
curl -X GET "https://yoursite.com/wp-json/mas-v2/v1/diagnostics/health" \
  -H "X-WP-Nonce: YOUR_NONCE" \
  --cookie "wordpress_logged_in_cookie=YOUR_COOKIE"
```

**Example Response:**
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
  },
  "timestamp": 1715356200
}
```

**Health Status Values:**
- `healthy`: All checks passed
- `warning`: Some checks have warnings
- `unhealthy`: One or more checks failed

### 3. Get Performance Metrics

**Endpoint:** `GET /wp-json/mas-v2/v1/diagnostics/performance`

**Description:** Returns only performance-related metrics.

**Example Request:**
```bash
curl -X GET "https://yoursite.com/wp-json/mas-v2/v1/diagnostics/performance" \
  -H "X-WP-Nonce: YOUR_NONCE" \
  --cookie "wordpress_logged_in_cookie=YOUR_COOKIE"
```

**Example Response:**
```json
{
  "success": true,
  "message": "Performance metrics retrieved successfully",
  "data": {
    "memory_usage": {
      "current": "45.2 MB",
      "current_bytes": 47398912,
      "peak": "48.5 MB",
      "peak_bytes": 50855936,
      "limit": "256M"
    },
    "execution_time": {
      "diagnostics": "25.12ms",
      "diagnostics_seconds": 0.0251
    },
    "database": {
      "queries": 8,
      "query_time": "0.0123s",
      "prefix": "wp_"
    },
    "cache": {
      "object_cache_enabled": false,
      "cache_type": "Database",
      "transients_count": 3
    }
  },
  "timestamp": 1715356200
}
```

## JavaScript Client Usage

### Using MASRestClient

```javascript
// Get full diagnostics
const diagnostics = await masRestClient.getDiagnostics();
console.log('Diagnostics:', diagnostics);

// Get specific sections only
const systemInfo = await masRestClient.getDiagnostics(['system', 'performance']);
console.log('System Info:', systemInfo);

// Get health check
const health = await masRestClient.getHealthCheck();
console.log('Health Status:', health.status);

// Get performance metrics
const performance = await masRestClient.getPerformanceMetrics();
console.log('Memory Usage:', performance.memory_usage);
```

### Using DiagnosticsManager

```javascript
// Initialize diagnostics manager
const diagnosticsManager = new DiagnosticsManager(masRestClient, {
    container: '#diagnostics-container',
    autoRefresh: true,
    refreshInterval: 60000, // 1 minute
    debug: true
});

// Initialize and load diagnostics
await diagnosticsManager.init();

// Load diagnostics manually
await diagnosticsManager.loadDiagnostics();

// Get health check
const health = await diagnosticsManager.getHealthCheck();

// Get performance metrics
const metrics = await diagnosticsManager.getPerformanceMetrics();

// Render diagnostics display
diagnosticsManager.render();

// Fix settings integrity issues (one-click fix)
await diagnosticsManager.fixSettingsIntegrity();

// Start/stop auto-refresh
diagnosticsManager.startAutoRefresh();
diagnosticsManager.stopAutoRefresh();

// Cleanup
diagnosticsManager.destroy();
```

### HTML Container Example

```html
<div id="diagnostics-container"></div>

<script>
    // Initialize and render diagnostics
    const diagnosticsManager = new DiagnosticsManager(masRestClient, {
        container: '#diagnostics-container',
        autoRefresh: true
    });
    
    diagnosticsManager.init();
</script>
```

## Diagnostics Data Structure

### System Information
- PHP version and compatibility check
- WordPress version and compatibility check
- MySQL version
- Server software
- PHP configuration (memory limit, execution time, etc.)
- WordPress configuration
- Multisite status
- Debug mode status
- REST API availability

### Plugin Information
- Plugin version
- Plugin name and author
- Active status
- REST API namespace
- REST API availability

### Settings Integrity
- Validation status
- Missing settings keys
- Invalid values with error messages
- Total settings count
- Expected settings count

### Filesystem Checks
- Upload directory writability
- Plugin directory readability
- Required directories existence and permissions

### Conflict Detection
- Potential conflicting plugins
- Admin menu related plugins
- REST API namespace conflicts

### Performance Metrics
- Current and peak memory usage
- Execution time
- Database query count and time
- Cache status and type
- Transients count

### Recommendations
Each recommendation includes:
- `severity`: `high`, `medium`, or `low`
- `category`: `system`, `performance`, `settings`, `filesystem`, or `conflicts`
- `title`: Short description
- `description`: Detailed explanation
- `action`: Recommended action to take

## Error Handling

### Common Error Codes

- `rest_forbidden` (403): User lacks permission
- `diagnostics_error` (500): Failed to retrieve diagnostics
- `health_check_error` (500): Health check failed
- `performance_metrics_error` (500): Failed to retrieve performance metrics

### Error Response Example

```json
{
  "code": "rest_forbidden",
  "message": "You do not have permission to access this resource.",
  "data": {
    "status": 403
  }
}
```

## Testing

### Test File Location
`test-task7-diagnostics.php`

### Running Tests

1. Access the test file in your browser:
   ```
   https://yoursite.com/wp-content/plugins/modern-admin-styler-v2/test-task7-diagnostics.php
   ```

2. The test will verify:
   - Diagnostics service class existence and functionality
   - Diagnostics controller class and registration
   - REST API endpoint registration and responses
   - JavaScript client methods
   - DiagnosticsManager module

### Interactive Testing

The test file includes interactive buttons to test:
- Full diagnostics retrieval
- Health check
- Performance metrics

## Best Practices

1. **Caching**: Diagnostics data can be cached for short periods (1-5 minutes) to reduce overhead
2. **Selective Loading**: Use the `include` parameter to load only needed sections
3. **Health Checks**: Use the health check endpoint for quick status monitoring
4. **Auto-Refresh**: Enable auto-refresh for real-time monitoring dashboards
5. **Error Handling**: Always wrap diagnostics calls in try-catch blocks
6. **Performance**: Use performance metrics endpoint for lightweight monitoring

## Security

- All endpoints require `manage_options` capability
- Nonce validation for all requests
- No sensitive data exposed in responses
- File paths are sanitized in responses

## Requirements Met

This implementation satisfies all requirements from Requirement 7:

✅ 7.1 - System health information returned via GET /diagnostics  
✅ 7.2 - PHP, WordPress, and plugin versions included  
✅ 7.3 - Settings integrity validation  
✅ 7.4 - File permissions and directory structure verification  
✅ 7.5 - Conflict detection with detailed information  
✅ 7.6 - Performance metrics (memory usage, execution time)  
✅ 7.7 - Optimization recommendations provided  

## Related Documentation

- [REST API Phase 1 Implementation](REST-API-PHASE1-IMPLEMENTATION.md)
- [REST API Phase 2 Implementation](REST-API-PHASE2-IMPLEMENTATION.md)
- [REST API Quick Start](REST-API-QUICK-START.md)
- [Testing Quick Start](TESTING-QUICK-START.md)
