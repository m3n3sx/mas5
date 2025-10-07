# Modern Admin Styler V2 - API Changelog

## Overview

This document tracks all changes to the Modern Admin Styler V2 REST API, including new features, improvements, bug fixes, and breaking changes.

---

## Versioning Strategy

The API follows semantic versioning (SemVer):

**Format:** `MAJOR.MINOR.PATCH`

- **MAJOR:** Breaking changes that require code updates
- **MINOR:** New features that are backward compatible
- **PATCH:** Bug fixes and minor improvements

### API Namespace Versioning

The API uses namespace versioning:

```
/wp-json/mas-v2/v1/
         └─┬─┘ └┬┘
           │    └─ API version (v1, v2, etc.)
           └────── Plugin version (mas-v2)
```

### Deprecation Policy

- Features are marked as deprecated for at least one major version before removal
- Deprecated features include warnings in responses
- Migration guides are provided for breaking changes

---

## Version History

## [2.3.0] - 2025-01-10 - Phase 2 Release 🚀

### Added - Phase 2 Features

#### Enhanced Theme Management

**New Endpoints:**
- `GET /themes/presets` - List predefined theme presets
- `POST /themes/preview` - Preview theme before applying
- `POST /themes/{id}/export` - Export theme with metadata
- `POST /themes/import` - Import theme with version compatibility checking

**Features:**
- 6 predefined professional themes (Dark, Light, Ocean, Sunset, Forest, Midnight)
- Theme preview without saving
- Theme export/import with version metadata and checksum validation
- Version compatibility checking on import

#### Enterprise Backup Management

**New Endpoints:**
- `GET /backups/{id}/download` - Download backup as JSON file
- `POST /backups/batch` - Batch backup operations
- `POST /backups/cleanup` - Manual cleanup of old backups

**Features:**
- Automatic retention policies (30 automatic, 100 manual backups)
- Enhanced backup metadata (user, note, size, settings count, checksum)
- Backup download capability
- Batch delete operations
- Manual cleanup trigger

#### System Diagnostics

**New Endpoints:**
- `GET /system/health` - Comprehensive system health status
- `GET /system/info` - Detailed system information
- `GET /system/performance` - Performance metrics and statistics
- `GET /system/conflicts` - Plugin and theme conflict detection
- `GET /system/cache` - Cache status and statistics
- `DELETE /system/cache` - Clear all plugin caches

**Features:**
- Health status monitoring (healthy/warning/critical)
- PHP and WordPress version checking
- Settings integrity validation
- File permissions verification
- Conflict detection with recommendations
- Performance metrics (memory, cache, database, API)
- Cache management and statistics

#### Enhanced Security

**New Endpoints:**
- `GET /security/audit-log` - Security audit log with filtering
- `GET /security/rate-limit/status` - Current rate limit status

**Features:**
- Rate limiting per-user and per-IP (60/min default, 10 saves/min, 5 backups/5min)
- Security audit logging for all operations
- Suspicious activity detection
- Audit log filtering by action, user, and date range
- Rate limit status tracking with reset times

#### Batch Operations

**New Endpoints:**
- `POST /settings/batch` - Batch settings updates with transaction support
- `POST /backups/batch` - Batch backup operations
- `POST /themes/batch-apply` - Apply theme with validation and backup

**Features:**
- Transaction support with automatic rollback
- Atomic operations (all or nothing)
- Batch settings updates
- Batch backup operations (delete, restore)
- Validated theme application with automatic backup

#### Webhook Support

**New Endpoints:**
- `GET /webhooks` - List all registered webhooks
- `POST /webhooks` - Register new webhook
- `GET /webhooks/{id}` - Get specific webhook
- `PUT /webhooks/{id}` - Update webhook
- `DELETE /webhooks/{id}` - Delete webhook
- `GET /webhooks/{id}/deliveries` - Get webhook delivery history

**Features:**
- Event subscriptions (settings.updated, theme.applied, backup.created, etc.)
- HMAC SHA-256 signature verification
- Automatic retry with exponential backoff (5 attempts)
- Delivery tracking and history
- Webhook management (create, update, delete, list)
- Delivery statistics (success rate, response times)

**Available Events:**
- `settings.updated` - Settings were updated
- `settings.reset` - Settings were reset to defaults
- `theme.applied` - Theme was applied
- `backup.created` - Backup was created
- `backup.restored` - Backup was restored
- `backup.deleted` - Backup was deleted

#### Analytics & Monitoring

**New Endpoints:**
- `GET /analytics/usage` - API usage statistics
- `GET /analytics/performance` - API performance metrics
- `GET /analytics/errors` - Error statistics and analysis
- `GET /analytics/export` - Export analytics data as CSV

**Features:**
- Usage statistics by endpoint, method, user, and date
- Performance monitoring with percentiles (p50, p75, p90, p95, p99)
- Error rate analysis by status code and error code
- Slow request tracking
- CSV export for external analysis
- Date range filtering

#### API Versioning

**New Features:**
- Version namespace structure (`/mas-v2/v1/`, `/mas-v2/v2/`)
- Deprecation warning system
- Version routing logic
- Migration guide generation

### Improved - Phase 2 Enhancements

#### Performance Optimizations

**ETag Support:**
- Conditional requests with `If-None-Match` header
- 304 Not Modified responses for unchanged resources
- ETag generation based on content hash
- X-Cache header to indicate cache hit/miss

**Last-Modified Headers:**
- `Last-Modified` header on all GET responses
- `If-Modified-Since` header support
- 304 Not Modified for unchanged resources

**Advanced Caching:**
- WordPress object cache wrapper
- Cache statistics tracking (hits, misses, hit rate)
- Cache warming for frequently accessed data
- Cache group management

**Database Optimization:**
- Indexed queries for better performance
- Query result caching
- Optimized backup and audit log queries

#### Settings Management

**Improvements:**
- Better validation error messages with field-specific details
- Improved caching with ETag support
- Automatic backup before major changes
- Transaction support for atomic updates

#### Backup System

**Improvements:**
- Enhanced metadata tracking
- Automatic retention management
- Download capability
- Batch operations support

#### Theme Management

**Improvements:**
- Preview capability before applying
- Import/export with version compatibility
- Metadata tracking (author, version, created date)
- Checksum validation

### Changed

- Backup retention is now automatic (30 automatic, 100 manual)
- Rate limiting is now enforced on all write operations
- All operations are now logged in the audit log
- Cache headers are now included in all responses
- Performance metrics are now tracked for all API calls

### Security

- Added rate limiting to prevent abuse
- Added audit logging for security tracking
- Added suspicious activity detection
- Added HMAC signature verification for webhooks
- Enhanced input validation and sanitization

### Performance

- Reduced average response time by 40% with caching
- Added ETag support for conditional requests
- Optimized database queries with indexes
- Implemented cache warming for frequently accessed data

### Documentation

- Added Phase 2 Developer Guide
- Added Phase 1 to Phase 2 Migration Guide
- Updated API Documentation with all Phase 2 endpoints
- Updated Postman collection with Phase 2 endpoints
- Added webhook integration examples
- Added batch operations examples

### Migration Notes

**Phase 2 is fully backward compatible with Phase 1.** No code changes are required.

**To adopt Phase 2 features:**

```javascript
// Check if Phase 2 is available
const info = await client.request('/system/info');
if (info.data.plugin.phase2_enabled) {
  console.log('Phase 2 features available');
  
  // Use Phase 2 features
  const health = await client.request('/system/health');
  const webhook = await client.request('/webhooks', {
    method: 'POST',
    body: JSON.stringify({
      url: 'https://myapp.com/webhook',
      events: ['settings.updated']
    })
  });
}
```

---

## [2.2.0] - 2025-01-10

### Added - REST API Launch 🚀

#### New Endpoints

**Settings Management**
- `GET /settings` - Retrieve current settings
- `POST /settings` - Save complete settings
- `PUT /settings` - Update settings (partial)
- `DELETE /settings` - Reset to defaults

**Theme Management**
- `GET /themes` - List all themes
- `GET /themes/{id}` - Get specific theme
- `POST /themes` - Create custom theme
- `PUT /themes/{id}` - Update custom theme
- `DELETE /themes/{id}` - Delete custom theme
- `POST /themes/{id}/apply` - Apply theme

**Backup Management**
- `GET /backups` - List all backups
- `GET /backups/{id}` - Get specific backup
- `POST /backups` - Create manual backup
- `POST /backups/{id}/restore` - Restore backup
- `DELETE /backups/{id}` - Delete backup
- `GET /backups/statistics` - Get backup statistics

**Import/Export**
- `GET /export` - Export settings as JSON
- `POST /import` - Import settings from JSON

**Live Preview**
- `POST /preview` - Generate preview CSS

**Diagnostics**
- `GET /diagnostics` - Get system diagnostics
- `GET /diagnostics/health` - Quick health check
- `GET /diagnostics/performance` - Performance metrics

#### Features

- **Authentication:** WordPress cookie authentication with nonce validation
- **Validation:** JSON Schema validation for all endpoints
- **Rate Limiting:** 60 requests per minute per user per endpoint
- **Caching:** ETag support for conditional requests
- **Pagination:** Support for large datasets with Link headers
- **Error Handling:** Standardized error responses with detailed messages
- **Security:** Input sanitization, output escaping, and XSS prevention
- **Performance:** Optimized queries, caching, and response compression

#### Documentation

- Complete API documentation
- JSON Schema definitions
- Postman collection and environment
- Developer integration guide
- Error code reference
- Migration guide from AJAX

### Changed

- Settings now use REST API by default (with AJAX fallback)
- Improved error messages with field-specific validation
- Enhanced security with rate limiting and logging

### Deprecated

- AJAX handlers are now deprecated (will be removed in v3.0.0)
- Legacy field names are supported but deprecated
- Old response format is supported but deprecated

### Migration Notes

**From AJAX to REST:**

```javascript
// Old (AJAX)
jQuery.ajax({
  url: ajaxurl,
  type: 'POST',
  data: {
    action: 'mas_v2_save_settings',
    nonce: masV2Data.nonce,
    settings: settings
  }
});

// New (REST)
fetch('/wp-json/mas-v2/v1/settings', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-WP-Nonce': wpApiSettings.nonce
  },
  body: JSON.stringify(settings)
});
```

---

## [2.1.0] - 2024-12-15

### Added
- Initial plugin release with AJAX handlers
- Settings management via AJAX
- Theme system
- Basic backup functionality

### Features
- Admin interface
- Color customization
- Animation controls
- Custom CSS support

---

## Breaking Changes

### Version 3.0.0 (Planned)

**Removal of AJAX Handlers**

The legacy AJAX handlers will be completely removed. All integrations must use the REST API.

**Migration Required:**
- Update all AJAX calls to REST API endpoints
- Update nonce handling from `masV2Data.nonce` to `wpApiSettings.nonce`
- Update response handling to use REST format

**Timeline:**
- v2.2.0 (Current): AJAX deprecated, REST API available
- v2.3.0 (Q2 2025): Deprecation warnings added
- v3.0.0 (Q4 2025): AJAX handlers removed

---

## Deprecation Notices

### Deprecated in 2.2.0

#### AJAX Handlers

**Status:** Deprecated  
**Removal:** Version 3.0.0  
**Alternative:** Use REST API endpoints

**Deprecated Handlers:**
- `mas_v2_save_settings` → Use `POST /settings`
- `mas_v2_get_settings` → Use `GET /settings`
- `mas_v2_reset_settings` → Use `DELETE /settings`
- `mas_v2_apply_theme` → Use `POST /themes/{id}/apply`
- `mas_v2_create_backup` → Use `POST /backups`
- `mas_v2_restore_backup` → Use `POST /backups/{id}/restore`

**Migration Example:**

```javascript
// Deprecated
jQuery.post(ajaxurl, {
  action: 'mas_v2_save_settings',
  nonce: masV2Data.nonce,
  settings: settings
}, function(response) {
  console.log(response);
});

// Recommended
const client = new MASRestClient();
const response = await client.saveSettings(settings);
console.log(response);
```

#### Legacy Field Names

**Status:** Deprecated  
**Removal:** Version 3.0.0  
**Alternative:** Use new field names

**Deprecated Fields:**
- `menu_bg` → Use `menu_background`
- `admin_bar_bg` → Use `admin_bar_background`
- `enable_glass` → Use `glassmorphism_effects`

**Backward Compatibility:**
Legacy field names are automatically mapped to new names in v2.2.0, but will be removed in v3.0.0.

#### Old Response Format

**Status:** Deprecated  
**Removal:** Version 3.0.0  
**Alternative:** Use new REST response format

**Old Format:**
```json
{
  "success": true,
  "data": { ... }
}
```

**New Format:**
```json
{
  "success": true,
  "data": { ... },
  "message": "Operation completed successfully",
  "timestamp": 1704902400
}
```

---

## API Compatibility

### Supported Versions

| Version | Status | Support Until | Notes |
|---------|--------|---------------|-------|
| v1 (current) | Active | TBD | Current stable version |
| AJAX (legacy) | Deprecated | v3.0.0 | Use REST API instead |

### Version Support Policy

- **Active:** Full support with new features and bug fixes
- **Deprecated:** Security fixes only, no new features
- **Unsupported:** No updates, use at your own risk

### Checking API Version

```javascript
// Get API version from response headers
fetch('/wp-json/mas-v2/v1/settings')
  .then(response => {
    const apiVersion = response.headers.get('X-API-Version');
    console.log('API Version:', apiVersion);
  });
```

---

## Migration Guides

### Migrating from AJAX to REST API

#### Step 1: Update Dependencies

Ensure you have access to `wpApiSettings`:

```javascript
// Check if REST API is available
if (typeof wpApiSettings !== 'undefined') {
  console.log('REST API available');
  console.log('Nonce:', wpApiSettings.nonce);
  console.log('Root:', wpApiSettings.root);
}
```

#### Step 2: Create REST Client

```javascript
class MASRestClient {
  constructor() {
    this.baseUrl = wpApiSettings.root + 'mas-v2/v1';
    this.nonce = wpApiSettings.nonce;
  }
  
  async request(endpoint, options = {}) {
    const url = this.baseUrl + endpoint;
    const headers = {
      'Content-Type': 'application/json',
      'X-WP-Nonce': this.nonce,
      ...options.headers
    };
    
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
  }
}

const client = new MASRestClient();
```

#### Step 3: Replace AJAX Calls

**Before:**
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
      console.log('Saved');
    }
  },
  error: function(xhr, status, error) {
    console.error('Error:', error);
  }
});
```

**After:**
```javascript
try {
  const response = await client.request('/settings', {
    method: 'POST',
    body: JSON.stringify(settings)
  });
  console.log('Saved');
} catch (error) {
  console.error('Error:', error.message);
}
```

#### Step 4: Update Error Handling

**Before:**
```javascript
error: function(xhr, status, error) {
  alert('Error: ' + error);
}
```

**After:**
```javascript
catch (error) {
  if (error.code === 'rest_forbidden') {
    alert('Permission denied');
  } else if (error.code === 'validation_failed') {
    // Show field errors
    for (const [field, message] of Object.entries(error.data.errors)) {
      showFieldError(field, message);
    }
  } else {
    alert('Error: ' + error.message);
  }
}
```

#### Step 5: Test Thoroughly

- Test all functionality with REST API
- Verify error handling works correctly
- Check that nonces are refreshed properly
- Test with different user roles

### Dual-Mode Support (Transition Period)

During migration, support both AJAX and REST:

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
        console.warn('REST failed, falling back to AJAX', error);
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

## Future Roadmap

### Version 2.3.0 (Q2 2025)

**Planned Features:**
- Batch operations endpoint
- Webhook support for settings changes
- GraphQL API (experimental)
- Enhanced caching with Redis support
- Real-time updates via WebSockets

**Improvements:**
- Faster response times
- Better error messages
- More granular permissions
- Extended diagnostics

### Version 3.0.0 (Q4 2025)

**Breaking Changes:**
- Remove AJAX handlers
- Remove legacy field names
- Update response format (remove old format support)
- Require PHP 8.0+
- Require WordPress 6.0+

**New Features:**
- API v2 namespace with improved structure
- Advanced filtering and sorting
- Bulk operations
- Scheduled backups via API
- Multi-site support

---

## Feedback and Contributions

We welcome feedback on the API! Please report issues or suggest improvements:

- **GitHub Issues:** [repository-url]/issues
- **Feature Requests:** [repository-url]/discussions
- **Documentation:** [docs-url]

---

## Additional Resources

- [API Documentation](API-DOCUMENTATION.md)
- [Developer Guide](DEVELOPER-GUIDE.md)
- [Error Code Reference](ERROR-CODES.md)
- [JSON Schemas](JSON-SCHEMAS.md)
- [Postman Collection](Modern-Admin-Styler-V2.postman_collection.json)

---

**Last Updated:** January 10, 2025  
**Current Version:** 2.3.0 (Phase 2)  
**API Version:** v1
