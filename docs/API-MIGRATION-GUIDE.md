# API Migration Guide

## Overview

This guide helps developers migrate between different versions of the Modern Admin Styler V2 REST API. Each version introduces new features, improvements, and occasionally breaking changes that require code updates.

## Table of Contents

- [Version History](#version-history)
- [Migration from v1 to v2](#migration-from-v1-to-v2)
- [Deprecation Policy](#deprecation-policy)
- [Version Detection](#version-detection)
- [Breaking Changes](#breaking-changes)
- [Code Examples](#code-examples)

## Version History

### v2 (Beta) - Released June 10, 2025

**Status:** Beta  
**Stability:** Stable for testing, not recommended for production  
**Support:** Active development

**New Features:**
- Enhanced theme management with preview capabilities
- Enterprise backup system with retention policies
- System diagnostics and health monitoring
- Advanced performance optimizations (ETags, conditional requests)
- Rate limiting and security enhancements
- Batch operations and transaction support
- Webhook system for external integrations
- Analytics and monitoring capabilities
- API versioning and deprecation management

**Breaking Changes:**
- None (v1 remains fully functional)

### v1 (Stable) - Released January 1, 2024

**Status:** Stable  
**Stability:** Production-ready  
**Support:** Long-term support until December 31, 2026

**Features:**
- Core settings management (GET, POST, PUT, DELETE)
- Basic theme management
- Backup and restore functionality
- Import/export capabilities
- Live preview generation
- Basic diagnostics

## Migration from v1 to v2

### Overview

Version 2 is fully backward compatible with v1. All v1 endpoints continue to work without modification. However, v2 introduces enhanced features and improved performance that you may want to adopt.

### Key Differences

#### 1. Enhanced Theme Management

**v1 Approach:**
```javascript
// Basic theme application
const response = await fetch('/wp-json/mas-v2/v1/themes/dark-blue/apply', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': wpApiSettings.nonce
    }
});
```

**v2 Approach:**
```javascript
// Preview before applying
const previewResponse = await fetch('/wp-json/mas-v2/v2/themes/preview', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': wpApiSettings.nonce
    },
    body: JSON.stringify({ theme_id: 'dark-blue' })
});

// Apply after preview
const applyResponse = await fetch('/wp-json/mas-v2/v2/themes/dark-blue/apply', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': wpApiSettings.nonce
    }
});
```

#### 2. Backup System Enhancements

**v1 Approach:**
```javascript
// Simple backup creation
const response = await fetch('/wp-json/mas-v2/v1/backups', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': wpApiSettings.nonce
    }
});
```

**v2 Approach:**
```javascript
// Backup with metadata and notes
const response = await fetch('/wp-json/mas-v2/v2/backups', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': wpApiSettings.nonce
    },
    body: JSON.stringify({
        name: 'Before major update',
        note: 'Backup created before migrating to new theme system',
        type: 'manual'
    })
});

// Download backup
const downloadResponse = await fetch(`/wp-json/mas-v2/v2/backups/${backupId}/download`, {
    headers: {
        'X-WP-Nonce': wpApiSettings.nonce
    }
});
```

#### 3. Conditional Requests (Performance)

**v1 Approach:**
```javascript
// Always fetches full data
const response = await fetch('/wp-json/mas-v2/v1/settings', {
    headers: {
        'X-WP-Nonce': wpApiSettings.nonce
    }
});
```

**v2 Approach:**
```javascript
// Use ETags for conditional requests
let etag = localStorage.getItem('settings_etag');

const response = await fetch('/wp-json/mas-v2/v2/settings', {
    headers: {
        'X-WP-Nonce': wpApiSettings.nonce,
        'If-None-Match': etag
    }
});

if (response.status === 304) {
    // Use cached data
    console.log('Settings unchanged, using cache');
} else {
    // Update cache with new data
    etag = response.headers.get('ETag');
    localStorage.setItem('settings_etag', etag);
}
```

#### 4. Batch Operations

**v1 Approach:**
```javascript
// Multiple individual requests
for (const setting of settings) {
    await fetch('/wp-json/mas-v2/v1/settings', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': wpApiSettings.nonce
        },
        body: JSON.stringify(setting)
    });
}
```

**v2 Approach:**
```javascript
// Single batch request with transaction support
const response = await fetch('/wp-json/mas-v2/v2/settings/batch', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': wpApiSettings.nonce
    },
    body: JSON.stringify({
        operations: settings.map(setting => ({
            action: 'update',
            data: setting
        }))
    })
});
```

#### 5. System Diagnostics

**v2 Only Feature:**
```javascript
// Get comprehensive system health
const healthResponse = await fetch('/wp-json/mas-v2/v2/system/health', {
    headers: {
        'X-WP-Nonce': wpApiSettings.nonce
    }
});

const health = await healthResponse.json();
console.log('System Status:', health.data.status); // healthy, warning, critical
console.log('Recommendations:', health.data.recommendations);
```

#### 6. Webhooks

**v2 Only Feature:**
```javascript
// Register webhook for settings changes
const response = await fetch('/wp-json/mas-v2/v2/webhooks', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': wpApiSettings.nonce
    },
    body: JSON.stringify({
        url: 'https://your-app.com/webhook',
        events: ['settings.updated', 'theme.applied'],
        secret: 'your-webhook-secret'
    })
});
```

## Deprecation Policy

### Timeline

When an endpoint or feature is deprecated:

1. **Announcement:** Deprecation is announced in release notes
2. **Warning Period:** Minimum 12 months of warning headers
3. **Removal:** Feature removed in next major version

### Deprecation Warnings

Deprecated endpoints return a `Warning` header:

```
Warning: 299 - "This endpoint is deprecated and will be removed on December 31, 2026. Please use /settings instead. See migration guide: https://..."
```

Additional headers:
```
X-API-Deprecated: true
X-API-Deprecation-Date: 2025-01-01
X-API-Removal-Date: 2026-12-31
X-API-Replacement: /settings
X-API-Migration-Guide: https://...
```

### Handling Deprecation Warnings

```javascript
const response = await fetch('/wp-json/mas-v2/v1/settings/legacy', {
    headers: {
        'X-WP-Nonce': wpApiSettings.nonce
    }
});

// Check for deprecation
const deprecated = response.headers.get('X-API-Deprecated');
if (deprecated === 'true') {
    const removalDate = response.headers.get('X-API-Removal-Date');
    const replacement = response.headers.get('X-API-Replacement');
    const guide = response.headers.get('X-API-Migration-Guide');
    
    console.warn(`Endpoint deprecated! Will be removed on ${removalDate}`);
    console.warn(`Use ${replacement} instead. Guide: ${guide}`);
}
```

## Version Detection

### Specifying API Version

You can specify the API version in multiple ways:

#### 1. URL Namespace (Recommended)
```javascript
// Use v1
fetch('/wp-json/mas-v2/v1/settings')

// Use v2
fetch('/wp-json/mas-v2/v2/settings')
```

#### 2. X-API-Version Header
```javascript
fetch('/wp-json/mas-v2/settings', {
    headers: {
        'X-API-Version': 'v2',
        'X-WP-Nonce': wpApiSettings.nonce
    }
});
```

#### 3. Accept Header
```javascript
fetch('/wp-json/mas-v2/settings', {
    headers: {
        'Accept': 'application/json; version=v2',
        'X-WP-Nonce': wpApiSettings.nonce
    }
});
```

#### 4. Query Parameter
```javascript
fetch('/wp-json/mas-v2/settings?version=v2', {
    headers: {
        'X-WP-Nonce': wpApiSettings.nonce
    }
});
```

### Default Version

If no version is specified, the API defaults to **v1** (latest stable).

### Version Response Headers

All responses include version information:

```
X-API-Version: v1
```

## Breaking Changes

### v2 Breaking Changes

**None.** Version 2 is fully backward compatible with v1.

### Future Breaking Changes

Breaking changes will only be introduced in major versions (v3, v4, etc.) and will be announced at least 12 months in advance.

## Code Examples

### Complete Migration Example

Here's a complete example of migrating a settings management component from v1 to v2:

**Before (v1):**
```javascript
class SettingsManager {
    constructor() {
        this.baseUrl = '/wp-json/mas-v2/v1';
        this.nonce = wpApiSettings.nonce;
    }
    
    async getSettings() {
        const response = await fetch(`${this.baseUrl}/settings`, {
            headers: {
                'X-WP-Nonce': this.nonce
            }
        });
        return response.json();
    }
    
    async saveSettings(settings) {
        const response = await fetch(`${this.baseUrl}/settings`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': this.nonce
            },
            body: JSON.stringify(settings)
        });
        return response.json();
    }
}
```

**After (v2 with enhancements):**
```javascript
class SettingsManager {
    constructor() {
        this.baseUrl = '/wp-json/mas-v2/v2';
        this.nonce = wpApiSettings.nonce;
        this.etag = localStorage.getItem('settings_etag');
    }
    
    async getSettings() {
        const headers = {
            'X-WP-Nonce': this.nonce
        };
        
        // Add ETag for conditional request
        if (this.etag) {
            headers['If-None-Match'] = this.etag;
        }
        
        const response = await fetch(`${this.baseUrl}/settings`, { headers });
        
        // Handle 304 Not Modified
        if (response.status === 304) {
            return JSON.parse(localStorage.getItem('settings_cache'));
        }
        
        // Update ETag and cache
        this.etag = response.headers.get('ETag');
        localStorage.setItem('settings_etag', this.etag);
        
        const data = await response.json();
        localStorage.setItem('settings_cache', JSON.stringify(data));
        
        return data;
    }
    
    async saveSettings(settings) {
        // Create automatic backup before saving
        await this.createBackup('Auto backup before settings save');
        
        const response = await fetch(`${this.baseUrl}/settings`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': this.nonce
            },
            body: JSON.stringify(settings)
        });
        
        // Clear cache on successful save
        if (response.ok) {
            this.etag = null;
            localStorage.removeItem('settings_etag');
            localStorage.removeItem('settings_cache');
        }
        
        return response.json();
    }
    
    async createBackup(note) {
        const response = await fetch(`${this.baseUrl}/backups`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': this.nonce
            },
            body: JSON.stringify({
                type: 'automatic',
                note: note
            })
        });
        return response.json();
    }
    
    async getSystemHealth() {
        const response = await fetch(`${this.baseUrl}/system/health`, {
            headers: {
                'X-WP-Nonce': this.nonce
            }
        });
        return response.json();
    }
}
```

### PHP Migration Example

**Before (v1):**
```php
// Direct API call
$response = wp_remote_get(
    rest_url('mas-v2/v1/settings'),
    [
        'headers' => [
            'X-WP-Nonce' => wp_create_nonce('wp_rest')
        ]
    ]
);
```

**After (v2 with version detection):**
```php
// Use version manager
$version_manager = new MAS_Version_Manager();
$namespace = $version_manager->get_namespace('v2');

$response = wp_remote_get(
    rest_url($namespace . '/settings'),
    [
        'headers' => [
            'X-WP-Nonce' => wp_create_nonce('wp_rest'),
            'X-API-Version' => 'v2'
        ]
    ]
);

// Check for deprecation warnings
$headers = wp_remote_retrieve_headers($response);
if (isset($headers['X-API-Deprecated']) && $headers['X-API-Deprecated'] === 'true') {
    error_log('Warning: Using deprecated API endpoint');
    error_log('Replacement: ' . $headers['X-API-Replacement']);
    error_log('Migration guide: ' . $headers['X-API-Migration-Guide']);
}
```

## Support and Resources

### Documentation
- [API Documentation](./API-DOCUMENTATION.md)
- [Developer Guide](./DEVELOPER-GUIDE.md)
- [Error Codes Reference](./ERROR-CODES.md)

### Getting Help
- GitHub Issues: https://github.com/yourusername/modern-admin-styler-v2/issues
- Documentation: https://github.com/yourusername/modern-admin-styler-v2/wiki

### Changelog
- [API Changelog](./API-CHANGELOG.md)

## Best Practices

### 1. Always Specify Version
```javascript
// Good: Explicit version
fetch('/wp-json/mas-v2/v2/settings')

// Avoid: Relying on default
fetch('/wp-json/mas-v2/settings')
```

### 2. Handle Deprecation Warnings
```javascript
async function apiCall(endpoint) {
    const response = await fetch(endpoint);
    
    // Check for deprecation
    if (response.headers.get('X-API-Deprecated') === 'true') {
        console.warn('Deprecated endpoint:', endpoint);
        console.warn('Replacement:', response.headers.get('X-API-Replacement'));
    }
    
    return response.json();
}
```

### 3. Use Conditional Requests
```javascript
// Store ETags for better performance
const etag = response.headers.get('ETag');
localStorage.setItem('etag_' + endpoint, etag);

// Use in subsequent requests
headers['If-None-Match'] = localStorage.getItem('etag_' + endpoint);
```

### 4. Monitor Version Headers
```javascript
// Log version information
const version = response.headers.get('X-API-Version');
console.log('API Version:', version);
```

### 5. Plan for Migration
- Monitor deprecation warnings in development
- Update code before removal dates
- Test thoroughly with new versions
- Keep dependencies updated

## Conclusion

The Modern Admin Styler V2 API versioning system ensures smooth transitions between versions while maintaining backward compatibility. By following this guide and best practices, you can confidently migrate to new API versions and take advantage of enhanced features.

For questions or issues, please refer to the documentation or open an issue on GitHub.
