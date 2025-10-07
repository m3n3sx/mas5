# Modern Admin Styler V2 - Phase 2 Developer Guide

## Overview

This guide covers the Phase 2 features of Modern Admin Styler V2, including advanced capabilities for enterprise-level WordPress admin customization. Phase 2 builds upon the Phase 1 REST API foundation with enhanced features for monitoring, security, batch operations, webhooks, and analytics.

**Version:** 2.3.0  
**Phase:** 2  
**Last Updated:** January 10, 2025

---

## Table of Contents

1. [What's New in Phase 2](#whats-new-in-phase-2)
2. [Getting Started](#getting-started)
3. [Enhanced Theme Management](#enhanced-theme-management)
4. [Enterprise Backup Management](#enterprise-backup-management)
5. [System Diagnostics](#system-diagnostics)
6. [Advanced Performance](#advanced-performance)
7. [Enhanced Security](#enhanced-security)
8. [Batch Operations](#batch-operations)
9. [Webhook Integration](#webhook-integration)
10. [Analytics & Monitoring](#analytics--monitoring)
11. [Best Practices](#best-practices)
12. [Migration from Phase 1](#migration-from-phase-1)

---

## What's New in Phase 2

### Key Features

- **🎨 Enhanced Theme Management** - Theme presets, preview, and import/export with version compatibility
- **💾 Enterprise Backup Management** - Retention policies, metadata tracking, and batch operations
- **🏥 System Diagnostics** - Comprehensive health monitoring and conflict detection
- **⚡ Advanced Performance** - ETags, Last-Modified headers, and advanced caching
- **🔒 Enhanced Security** - Rate limiting, audit logging, and suspicious activity detection
- **📦 Batch Operations** - Transaction support with automatic rollback
- **🔔 Webhook Support** - Event subscriptions with HMAC signature verification
- **📊 Analytics & Monitoring** - Usage statistics, performance metrics, and error analysis
- **🔄 API Versioning** - Deprecation management and migration guides

### Breaking Changes

Phase 2 is fully backward compatible with Phase 1. No breaking changes were introduced.

---

## Getting Started

### Prerequisites

- WordPress 6.0 or higher
- PHP 7.4 or higher
- Modern Admin Styler V2 plugin version 2.3.0+
- `manage_options` capability for API access

### Installation

Phase 2 features are included in the plugin by default. No additional installation is required.

### Authentication

All Phase 2 endpoints use the same authentication as Phase 1:

```javascript
const client = new MASRestClient();
// Authentication is handled automatically via WordPress nonces
```

---

## Enhanced Theme Management

### Theme Presets

Phase 2 includes 6 predefined professional themes:

```javascript
// Get all theme presets
const response = await client.request('/themes/presets');
const presets = response.data;

console.log(presets);
// [
//   { id: 'dark', name: 'Dark', ... },
//   { id: 'light', name: 'Light', ... },
//   { id: 'ocean', name: 'Ocean', ... },
//   { id: 'sunset', name: 'Sunset', ... },
//   { id: 'forest', name: 'Forest', ... },
//   { id: 'midnight', name: 'Midnight', ... }
// ]
```

### Theme Preview

Preview a theme before applying it:

```javascript
// Preview a theme without saving
const response = await client.request('/themes/preview', {
  method: 'POST',
  body: JSON.stringify({
    theme_id: 'ocean'
  })
});

const previewCSS = response.data.css;

// Apply preview CSS to the page
const styleElement = document.createElement('style');
styleElement.id = 'mas-preview';
styleElement.textContent = previewCSS;
document.head.appendChild(styleElement);

// Remove preview
document.getElementById('mas-preview')?.remove();
```

### Theme Export/Import

Export and import themes with version compatibility checking:

```javascript
// Export a theme
const exportResponse = await client.request('/themes/ocean/export', {
  method: 'POST'
});

const themeData = exportResponse.data;
// {
//   id: 'ocean',
//   name: 'Ocean',
//   version: '1.0',
//   plugin_version: '2.3.0',
//   settings: {...},
//   checksum: 'abc123...'
// }

// Import a theme
const importResponse = await client.request('/themes/import', {
  method: 'POST',
  body: JSON.stringify({
    theme_data: themeData
  })
});

if (importResponse.data.compatible) {
  console.log('Theme imported successfully');
} else {
  console.warn('Version compatibility issues:', importResponse.data.warnings);
}
```

---

## Enterprise Backup Management

### Retention Policies

Phase 2 implements automatic backup retention:

- **Automatic backups:** 30 most recent (created before changes)
- **Manual backups:** 100 most recent (created by user)

```javascript
// Create a manual backup with metadata
const backup = await client.request('/backups', {
  method: 'POST',
  body: JSON.stringify({
    note: 'Before major redesign',
    type: 'manual'
  })
});

console.log('Backup created:', backup.data.id);
```

### Backup Metadata

Backups now include comprehensive metadata:

```javascript
// Get backup with metadata
const response = await client.request('/backups/backup_1704902400');
const backup = response.data;

console.log(backup);
// {
//   id: 'backup_1704902400',
//   timestamp: 1704902400,
//   type: 'manual',
//   settings: {...},
//   metadata: {
//     plugin_version: '2.3.0',
//     wordpress_version: '6.8',
//     user_id: 1,
//     user_login: 'admin',
//     note: 'Before major redesign',
//     size: '15.2 KB',
//     settings_count: 45,
//     checksum: 'abc123...'
//   }
// }
```

### Backup Download

Download backups as JSON files:

```javascript
// Download a backup
const response = await client.request('/backups/backup_1704902400/download');

// Create download link
const blob = new Blob([JSON.stringify(response.data)], { type: 'application/json' });
const url = URL.createObjectURL(blob);
const a = document.createElement('a');
a.href = url;
a.download = `backup-${backup.data.id}.json`;
a.click();
URL.revokeObjectURL(url);
```

### Batch Backup Operations

Delete multiple backups at once:

```javascript
// Delete old automatic backups
const response = await client.request('/backups/batch', {
  method: 'POST',
  body: JSON.stringify({
    operations: [
      { action: 'delete', backup_id: 'backup_1704800000' },
      { action: 'delete', backup_id: 'backup_1704810000' },
      { action: 'delete', backup_id: 'backup_1704820000' }
    ]
  })
});

console.log(`Deleted ${response.data.successful} of ${response.data.total_operations} backups`);
```

### Manual Cleanup

Trigger manual cleanup of old backups:

```javascript
// Clean up old automatic backups
const response = await client.request('/backups/cleanup', {
  method: 'POST'
});

console.log(`Cleaned up ${response.data.deleted_count} old backups`);
```

---

## System Diagnostics

### Health Monitoring

Check overall system health:

```javascript
// Get system health status
const health = await client.request('/system/health');

console.log('System status:', health.data.status);
// 'healthy', 'warning', or 'critical'

// Check individual health checks
health.data.checks.forEach(check => {
  console.log(`${check.name}: ${check.status} - ${check.message}`);
});

// Get recommendations
if (health.data.recommendations.length > 0) {
  console.log('Recommendations:');
  health.data.recommendations.forEach(rec => {
    console.log(`[${rec.severity}] ${rec.message}`);
  });
}
```

### Performance Metrics

Monitor plugin performance:

```javascript
// Get performance metrics
const perf = await client.request('/system/performance');

console.log('Memory usage:', perf.data.memory.current);
console.log('Cache hit rate:', perf.data.cache.hit_rate + '%');
console.log('Database queries:', perf.data.database.queries);
console.log('Avg API response time:', perf.data.api.avg_response_time);
```

### Conflict Detection

Detect conflicting plugins and themes:

```javascript
// Check for conflicts
const conflicts = await client.request('/system/conflicts');

if (conflicts.data.detected) {
  console.warn('Conflicts detected:');
  conflicts.data.conflicts.forEach(conflict => {
    console.warn(`[${conflict.severity}] ${conflict.name}: ${conflict.description}`);
    console.log('Recommendation:', conflict.recommendation);
  });
}
```

### Cache Management

Monitor and clear caches:

```javascript
// Get cache status
const cache = await client.request('/system/cache');
console.log('Cache hit rate:', cache.data.stats.hit_rate + '%');
console.log('Cache size:', cache.data.stats.size);

// Clear all caches
await client.request('/system/cache', { method: 'DELETE' });
console.log('Cache cleared');
```

---

## Advanced Performance

### ETag Support

Leverage ETags for conditional requests:

```javascript
class MASRestClientWithETag extends MASRestClient {
  constructor() {
    super();
    this.etags = new Map();
  }
  
  async request(endpoint, options = {}) {
    // Add If-None-Match header if we have an ETag
    const etag = this.etags.get(endpoint);
    if (etag && options.method === 'GET') {
      options.headers = {
        ...options.headers,
        'If-None-Match': etag
      };
    }
    
    const response = await super.request(endpoint, options);
    
    // Store ETag from response
    if (response.headers && response.headers.get('ETag')) {
      this.etags.set(endpoint, response.headers.get('ETag'));
    }
    
    return response;
  }
}

// Usage
const client = new MASRestClientWithETag();

// First request - full response
const settings1 = await client.getSettings();
console.log('Full response:', settings1);

// Second request - 304 Not Modified if unchanged
const settings2 = await client.getSettings();
console.log('Cached response:', settings2);
```

### Last-Modified Headers

Use Last-Modified for efficient caching:

```javascript
// Check if settings have been modified
const response = await fetch('/wp-json/mas-v2/v1/settings', {
  headers: {
    'If-Modified-Since': 'Wed, 10 Jan 2025 15:00:00 GMT'
  }
});

if (response.status === 304) {
  console.log('Settings not modified, use cached data');
} else {
  const data = await response.json();
  console.log('Settings updated:', data);
}
```

### Cache Warming

Pre-cache frequently accessed data:

```javascript
// Warm the cache on page load
async function warmCache() {
  await Promise.all([
    client.request('/settings'),
    client.request('/themes'),
    client.request('/backups?limit=10')
  ]);
  console.log('Cache warmed');
}

// Call on page load
document.addEventListener('DOMContentLoaded', warmCache);
```

---

## Enhanced Security

### Rate Limiting

Phase 2 implements per-user and per-IP rate limiting:

- **Default:** 60 requests/minute
- **Settings save:** 10 requests/minute
- **Backup create:** 5 requests/5 minutes

```javascript
// Check rate limit status
const status = await client.request('/security/rate-limit/status');

console.log('Rate limits:');
status.data.limits.forEach((limit, endpoint) => {
  console.log(`${endpoint}: ${limit.remaining}/${limit.limit} remaining`);
  console.log(`Resets in: ${limit.reset_in}`);
});

// Handle rate limit errors
try {
  await client.saveSettings(settings);
} catch (error) {
  if (error.status === 429) {
    const retryAfter = error.headers.get('Retry-After');
    console.log(`Rate limited. Retry after ${retryAfter} seconds`);
  }
}
```

### Audit Logging

All operations are logged for security auditing:

```javascript
// Get audit log
const log = await client.request('/security/audit-log', {
  params: {
    action: 'settings.updated',
    start_date: '2025-01-01',
    end_date: '2025-01-10',
    limit: 50,
    page: 1
  }
});

log.data.forEach(entry => {
  console.log(`[${entry.date}] ${entry.user_login}: ${entry.action}`);
  console.log('Details:', entry.details);
  console.log('IP:', entry.ip_address);
});
```

### Suspicious Activity Detection

The system automatically detects suspicious patterns:

```javascript
// Check for suspicious activity
const health = await client.request('/system/health');

const securityCheck = health.data.checks.find(c => c.name === 'security');
if (securityCheck.status === 'warning') {
  console.warn('Suspicious activity detected:', securityCheck.message);
  console.log('Details:', securityCheck.details);
}
```

---

## Batch Operations

### Transaction Support

Perform multiple operations atomically:

```javascript
// Batch update settings with transaction support
const response = await client.request('/settings/batch', {
  method: 'POST',
  body: JSON.stringify({
    operations: [
      {
        action: 'update',
        settings: { menu_background: '#1e1e2e' }
      },
      {
        action: 'update',
        settings: { menu_text_color: '#ffffff' }
      },
      {
        action: 'update',
        settings: { admin_bar_background: '#1e1e2e' }
      }
    ],
    atomic: true // All or nothing
  })
});

if (response.data.successful === response.data.total_operations) {
  console.log('All operations succeeded');
} else {
  console.error('Some operations failed, all rolled back');
  response.data.results.forEach((result, index) => {
    if (!result.success) {
      console.error(`Operation ${index} failed:`, result.error);
    }
  });
}
```

### Batch Theme Application

Apply themes with validation and backup:

```javascript
// Apply theme with automatic backup and validation
const response = await client.request('/themes/batch-apply', {
  method: 'POST',
  body: JSON.stringify({
    theme_id: 'ocean',
    create_backup: true,
    validate: true
  })
});

if (response.data.theme_applied) {
  console.log('Theme applied successfully');
  console.log('Backup created:', response.data.backup_id);
} else {
  console.error('Theme application failed:', response.data.error);
  console.log('No changes made');
}
```

### Error Handling with Rollback

```javascript
// Batch operations with error handling
try {
  const response = await client.request('/settings/batch', {
    method: 'POST',
    body: JSON.stringify({
      operations: [
        { action: 'update', settings: { menu_background: '#1e1e2e' } },
        { action: 'update', settings: { menu_text_color: 'invalid-color' } }, // This will fail
        { action: 'update', settings: { admin_bar_background: '#1e1e2e' } }
      ],
      atomic: true
    })
  });
} catch (error) {
  console.error('Batch operation failed');
  console.log('Rollback performed:', error.data.rollback_performed);
  console.log('Failed operations:', error.data.results.filter(r => !r.success));
}
```

---

## Webhook Integration

### Registering Webhooks

Subscribe to plugin events:

```javascript
// Register a webhook
const webhook = await client.request('/webhooks', {
  method: 'POST',
  body: JSON.stringify({
    url: 'https://myapp.com/webhook',
    events: [
      'settings.updated',
      'theme.applied',
      'backup.created',
      'backup.restored'
    ],
    secret: 'my-webhook-secret-key',
    active: true
  })
});

console.log('Webhook registered:', webhook.data.id);
console.log('Secret:', webhook.data.secret); // Store this securely
```

### Available Events

- `settings.updated` - Settings were updated
- `settings.reset` - Settings were reset to defaults
- `theme.applied` - Theme was applied
- `backup.created` - Backup was created
- `backup.restored` - Backup was restored
- `backup.deleted` - Backup was deleted

### Webhook Payload

When an event occurs, your webhook URL receives:

```json
{
  "event": "settings.updated",
  "timestamp": 1704902400,
  "webhook_id": 1,
  "data": {
    "user_id": 1,
    "user_login": "admin",
    "changed_fields": ["menu_background", "menu_text_color"],
    "old_values": {"menu_background": "#23282d"},
    "new_values": {"menu_background": "#1e1e2e"}
  },
  "site_url": "https://example.com",
  "plugin_version": "2.3.0"
}
```

### Verifying Webhook Signatures

Always verify webhook signatures for security:

```javascript
// Node.js example
const crypto = require('crypto');

function verifyWebhookSignature(payload, signature, secret) {
  const expectedSignature = 'sha256=' + crypto
    .createHmac('sha256', secret)
    .update(JSON.stringify(payload))
    .digest('hex');
  
  return crypto.timingSafeEqual(
    Buffer.from(signature),
    Buffer.from(expectedSignature)
  );
}

// Express.js webhook handler
app.post('/webhook', express.json(), (req, res) => {
  const signature = req.headers['x-mas-signature'];
  const secret = process.env.WEBHOOK_SECRET;
  
  if (!verifyWebhookSignature(req.body, signature, secret)) {
    return res.status(401).send('Invalid signature');
  }
  
  // Process webhook
  console.log('Event received:', req.body.event);
  console.log('Data:', req.body.data);
  
  res.status(200).send('OK');
});
```

### Managing Webhooks

```javascript
// List all webhooks
const webhooks = await client.request('/webhooks');

// Update a webhook
await client.request('/webhooks/1', {
  method: 'PUT',
  body: JSON.stringify({
    events: ['settings.updated', 'backup.created'],
    active: true
  })
});

// Delete a webhook
await client.request('/webhooks/1', { method: 'DELETE' });

// Get delivery history
const deliveries = await client.request('/webhooks/1/deliveries');
deliveries.data.forEach(delivery => {
  console.log(`[${delivery.status}] ${delivery.event} - ${delivery.response_time}`);
});
```

### Retry Mechanism

Webhooks automatically retry on failure with exponential backoff:

- **Attempt 1:** Immediate
- **Attempt 2:** After 1 minute
- **Attempt 3:** After 5 minutes
- **Attempt 4:** After 15 minutes
- **Attempt 5:** After 1 hour

---

## Analytics & Monitoring

### Usage Statistics

Track API usage:

```javascript
// Get usage statistics
const usage = await client.request('/analytics/usage', {
  params: {
    start_date: '2025-01-01',
    end_date: '2025-01-10',
    group_by: 'endpoint'
  }
});

console.log('Total requests:', usage.data.total_requests);

// By endpoint
Object.entries(usage.data.by_endpoint).forEach(([endpoint, stats]) => {
  console.log(`${endpoint}: ${stats.total} requests`);
  console.log(`  GET: ${stats.GET}, POST: ${stats.POST}`);
});

// Top users
usage.data.top_users.forEach(user => {
  console.log(`${user.user_login}: ${user.requests} requests`);
});
```

### Performance Monitoring

Monitor API performance:

```javascript
// Get performance metrics
const perf = await client.request('/analytics/performance', {
  params: {
    start_date: '2025-01-01',
    end_date: '2025-01-10'
  }
});

console.log('Response time percentiles:');
console.log('  P50:', perf.data.response_times.p50);
console.log('  P75:', perf.data.response_times.p75);
console.log('  P90:', perf.data.response_times.p90);
console.log('  P95:', perf.data.response_times.p95);
console.log('  P99:', perf.data.response_times.p99);

// Slow requests
if (perf.data.slow_requests.length > 0) {
  console.warn('Slow requests detected:');
  perf.data.slow_requests.forEach(req => {
    console.warn(`${req.endpoint}: ${req.response_time}`);
  });
}
```

### Error Analysis

Analyze API errors:

```javascript
// Get error statistics
const errors = await client.request('/analytics/errors', {
  params: {
    start_date: '2025-01-01',
    end_date: '2025-01-10'
  }
});

console.log('Total errors:', errors.data.total_errors);
console.log('Error rate:', errors.data.error_rate + '%');

// By status code
console.log('Errors by status code:');
Object.entries(errors.data.by_status_code).forEach(([code, count]) => {
  console.log(`  ${code}: ${count}`);
});

// Recent errors
errors.data.recent_errors.forEach(error => {
  console.error(`[${error.timestamp}] ${error.endpoint}: ${error.message}`);
});
```

### Exporting Analytics

Export analytics data as CSV:

```javascript
// Export usage statistics
const response = await fetch('/wp-json/mas-v2/v1/analytics/export?type=usage&start_date=2025-01-01&end_date=2025-01-10');
const blob = await response.blob();

// Download CSV
const url = URL.createObjectURL(blob);
const a = document.createElement('a');
a.href = url;
a.download = 'analytics-usage.csv';
a.click();
URL.revokeObjectURL(url);
```

---

## Best Practices

### 1. Always Create Backups

Before making significant changes:

```javascript
async function safeSettingsUpdate(settings) {
  // Create backup first
  const backup = await client.request('/backups', {
    method: 'POST',
    body: JSON.stringify({
      note: 'Before settings update'
    })
  });
  
  try {
    // Update settings
    await client.saveSettings(settings);
  } catch (error) {
    // Restore backup on error
    console.error('Update failed, restoring backup');
    await client.request(`/backups/${backup.data.id}/restore`, {
      method: 'POST'
    });
    throw error;
  }
}
```

### 2. Handle Rate Limits Gracefully

```javascript
async function requestWithRetry(endpoint, options, maxRetries = 3) {
  for (let i = 0; i < maxRetries; i++) {
    try {
      return await client.request(endpoint, options);
    } catch (error) {
      if (error.status === 429 && i < maxRetries - 1) {
        const retryAfter = parseInt(error.headers.get('Retry-After') || '60');
        console.log(`Rate limited, retrying in ${retryAfter}s`);
        await new Promise(resolve => setTimeout(resolve, retryAfter * 1000));
      } else {
        throw error;
      }
    }
  }
}
```

### 3. Use Batch Operations for Multiple Changes

```javascript
// Instead of multiple individual requests
// ❌ Bad
await client.saveSettings({ menu_background: '#1e1e2e' });
await client.saveSettings({ menu_text_color: '#ffffff' });
await client.saveSettings({ admin_bar_background: '#1e1e2e' });

// ✅ Good
await client.request('/settings/batch', {
  method: 'POST',
  body: JSON.stringify({
    operations: [
      { action: 'update', settings: { menu_background: '#1e1e2e' } },
      { action: 'update', settings: { menu_text_color: '#ffffff' } },
      { action: 'update', settings: { admin_bar_background: '#1e1e2e' } }
    ],
    atomic: true
  })
});
```

### 4. Monitor System Health

```javascript
// Check health periodically
setInterval(async () => {
  const health = await client.request('/system/health');
  
  if (health.data.status !== 'healthy') {
    console.warn('System health issue detected');
    // Send notification, log to monitoring service, etc.
  }
}, 5 * 60 * 1000); // Every 5 minutes
```

### 5. Verify Webhook Signatures

Always verify webhook signatures to prevent spoofing:

```javascript
// ❌ Bad - No verification
app.post('/webhook', (req, res) => {
  processWebhook(req.body);
  res.send('OK');
});

// ✅ Good - Verify signature
app.post('/webhook', (req, res) => {
  const signature = req.headers['x-mas-signature'];
  if (!verifyWebhookSignature(req.body, signature, secret)) {
    return res.status(401).send('Invalid signature');
  }
  processWebhook(req.body);
  res.send('OK');
});
```

### 6. Use ETags for Caching

```javascript
// Implement ETag caching
class CachedClient extends MASRestClient {
  constructor() {
    super();
    this.cache = new Map();
  }
  
  async getSettings() {
    const cached = this.cache.get('settings');
    if (cached) {
      // Use If-None-Match header
      const response = await this.request('/settings', {
        headers: { 'If-None-Match': cached.etag }
      });
      
      if (response.status === 304) {
        return cached.data;
      }
    }
    
    const response = await this.request('/settings');
    this.cache.set('settings', {
      data: response.data,
      etag: response.headers.get('ETag')
    });
    
    return response.data;
  }
}
```

---

## Migration from Phase 1

### No Breaking Changes

Phase 2 is fully backward compatible with Phase 1. All Phase 1 endpoints continue to work exactly as before.

### Adopting Phase 2 Features

You can adopt Phase 2 features incrementally:

```javascript
// Phase 1 code continues to work
const settings = await client.getSettings();
await client.saveSettings(settings);

// Add Phase 2 features when ready
const health = await client.request('/system/health');
const webhook = await client.request('/webhooks', {
  method: 'POST',
  body: JSON.stringify({
    url: 'https://myapp.com/webhook',
    events: ['settings.updated']
  })
});
```

### New Capabilities

Take advantage of new Phase 2 capabilities:

1. **Replace manual backups with automatic retention**
2. **Add webhook notifications for important events**
3. **Monitor system health and performance**
4. **Use batch operations for efficiency**
5. **Track usage with analytics**

### Example Migration

```javascript
// Phase 1 approach
async function updateTheme(themeId) {
  // Manual backup
  const backup = await client.createBackup();
  
  try {
    await client.applyTheme(themeId);
  } catch (error) {
    await client.restoreBackup(backup.id);
    throw error;
  }
}

// Phase 2 approach
async function updateTheme(themeId) {
  // Automatic backup with validation and rollback
  const response = await client.request('/themes/batch-apply', {
    method: 'POST',
    body: JSON.stringify({
      theme_id: themeId,
      create_backup: true,
      validate: true
    })
  });
  
  return response.data;
}
```

---

## Support & Resources

### Documentation

- [API Documentation](./API-DOCUMENTATION.md)
- [API Migration Guide](./API-MIGRATION-GUIDE.md)
- [API Changelog](./API-CHANGELOG.md)

### Testing

- [Postman Collection](./Modern-Admin-Styler-V2.postman_collection.json)
- [Postman Environment](./Modern-Admin-Styler-V2.postman_environment.json)

### Community

- GitHub Issues: [Report bugs or request features]
- Support Forum: [Get help from the community]
- Documentation: [Browse the full documentation]

---

**Version:** 2.3.0 (Phase 2)  
**Last Updated:** January 10, 2025  
**License:** GPL v2 or later
