# Modern Admin Styler V2 - REST API Documentation

## Overview

The Modern Admin Styler V2 REST API provides a comprehensive interface for managing plugin settings, themes, backups, and system diagnostics. All endpoints follow RESTful conventions and return JSON responses.

**Base URL:** `/wp-json/mas-v2/v1/`

**Authentication:** WordPress cookie authentication with nonce validation

**Version:** 2.3.0 (Phase 2)

---

## Table of Contents

1. [Authentication](#authentication)
2. [Response Format](#response-format)
3. [Error Handling](#error-handling)
4. [Rate Limiting](#rate-limiting)
5. [Endpoints](#endpoints)
   - [Settings](#settings-endpoints)
   - [Themes](#themes-endpoints)
   - [Backups](#backups-endpoints)
   - [Import/Export](#importexport-endpoints)
   - [Preview](#preview-endpoints)
   - [Diagnostics](#diagnostics-endpoints)
   - [System](#system-endpoints) ⭐ Phase 2
   - [Security](#security-endpoints) ⭐ Phase 2
   - [Batch Operations](#batch-operations-endpoints) ⭐ Phase 2
   - [Webhooks](#webhooks-endpoints) ⭐ Phase 2
   - [Analytics](#analytics-endpoints) ⭐ Phase 2
6. [Examples](#examples)
7. [Phase 2 Features](#phase-2-features)

---

## Authentication

All API endpoints require authentication and the `manage_options` capability.

### Cookie Authentication

The API uses WordPress's built-in cookie authentication. When making requests from JavaScript in the WordPress admin, include the nonce in the request header:

```javascript
fetch('/wp-json/mas-v2/v1/settings', {
  method: 'GET',
  headers: {
    'X-WP-Nonce': wpApiSettings.nonce
  },
  credentials: 'same-origin'
})
```

### Required Headers

- **X-WP-Nonce:** WordPress REST API nonce (required for POST, PUT, DELETE requests)
- **Content-Type:** `application/json` (for requests with body)

---

## Response Format

### Success Response

All successful responses follow this format:

```json
{
  "success": true,
  "data": { ... },
  "message": "Operation completed successfully",
  "timestamp": 1704902400
}
```

### Error Response

Error responses use WordPress's WP_Error format:

```json
{
  "code": "error_code",
  "message": "Human-readable error message",
  "data": {
    "status": 400,
    "errors": { ... }
  }
}
```

---

## Error Handling

### HTTP Status Codes

| Code | Meaning | Description |
|------|---------|-------------|
| 200 | OK | Request successful |
| 201 | Created | Resource created successfully |
| 304 | Not Modified | Resource hasn't changed (ETag match) |
| 400 | Bad Request | Invalid request parameters |
| 401 | Unauthorized | Authentication required |
| 403 | Forbidden | Insufficient permissions |
| 404 | Not Found | Resource not found |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Server error occurred |

### Common Error Codes

| Error Code | Description | Resolution |
|------------|-------------|------------|
| `rest_forbidden` | User lacks required permissions | Ensure user has `manage_options` capability |
| `rest_cookie_invalid_nonce` | Invalid or missing nonce | Refresh page to get new nonce |
| `validation_failed` | Request data failed validation | Check error details for specific field errors |
| `rate_limited` | Too many requests | Wait before making another request |
| `not_found` | Resource doesn't exist | Verify resource ID |

---

## Rate Limiting

The API implements rate limiting to prevent abuse:

- **Default Limit:** 60 requests per minute per user per endpoint
- **Headers:** Rate limit information is included in response headers

### Rate Limit Headers

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1704902460
```

When rate limit is exceeded, the API returns a `429 Too Many Requests` status with a `Retry-After` header.

---

## Endpoints

## Settings Endpoints

### GET /settings

Retrieve current plugin settings.

**Request:**
```http
GET /wp-json/mas-v2/v1/settings
```

**Response:**
```json
{
  "success": true,
  "data": {
    "enable_plugin": true,
    "theme": "modern",
    "color_scheme": "light",
    "admin_bar_background": "#23282d",
    "admin_bar_text_color": "#ffffff",
    "menu_background": "#23282d",
    "menu_text_color": "#ffffff",
    "menu_hover_background": "#32373c",
    "menu_hover_text_color": "#00a0d2",
    "menu_width": 160,
    "enable_animations": true,
    "animation_speed": 300,
    "glassmorphism_effects": false,
    "custom_css": ""
  },
  "message": "Settings retrieved successfully",
  "timestamp": 1704902400
}
```

**Cache Headers:**
- `Cache-Control: private, max-age=300, must-revalidate`
- `ETag: "abc123def456"`

---

### POST /settings

Save complete settings (full replacement).

**Request:**
```http
POST /wp-json/mas-v2/v1/settings
Content-Type: application/json
X-WP-Nonce: abc123def456

{
  "menu_background": "#1e1e2e",
  "menu_text_color": "#ffffff",
  "enable_animations": true
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "settings": { ... },
    "css_generated": true
  },
  "message": "Settings saved successfully",
  "timestamp": 1704902400
}
```

**Side Effects:**
- Regenerates CSS
- Clears settings cache
- Triggers `mas_settings_saved` action hook

---

### PUT /settings

Update settings (partial update - merges with existing).

**Request:**
```http
PUT /wp-json/mas-v2/v1/settings
Content-Type: application/json
X-WP-Nonce: abc123def456

{
  "menu_background": "#2d2d44"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "settings": { ... },
    "css_generated": true
  },
  "message": "Settings updated successfully",
  "timestamp": 1704902400
}
```

---

### DELETE /settings

Reset settings to defaults.

**Request:**
```http
DELETE /wp-json/mas-v2/v1/settings
X-WP-Nonce: abc123def456
```

**Response:**
```json
{
  "success": true,
  "data": {
    "settings": { ... },
    "backup_created": true,
    "css_generated": true
  },
  "message": "Settings reset to defaults successfully",
  "timestamp": 1704902400
}
```

**Side Effects:**
- Creates automatic backup before reset
- Regenerates CSS
- Clears settings cache

---

## Themes Endpoints

### GET /themes

List all available themes (predefined and custom).

**Request:**
```http
GET /wp-json/mas-v2/v1/themes?type=predefined&limit=20&page=1
```

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `type` | string | - | Filter by type: `predefined` or `custom` |
| `limit` | integer | 20 | Number of themes per page |
| `page` | integer | 1 | Page number |

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": "dark-blue",
      "name": "Dark Blue",
      "description": "Professional dark blue theme",
      "type": "predefined",
      "readonly": true,
      "settings": {
        "menu_background": "#1e1e2e",
        "menu_text_color": "#ffffff"
      },
      "metadata": {
        "author": "MAS Team",
        "version": "1.0"
      }
    }
  ],
  "message": "Retrieved 1 of 5 theme(s)",
  "timestamp": 1704902400
}
```

**Pagination Headers:**
```
X-WP-Total: 5
X-WP-TotalPages: 1
Link: <url>; rel="first", <url>; rel="next"
```

---

### GET /themes/{id}

Get a specific theme by ID.

**Request:**
```http
GET /wp-json/mas-v2/v1/themes/dark-blue
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": "dark-blue",
    "name": "Dark Blue",
    "type": "predefined",
    "readonly": true,
    "settings": { ... }
  },
  "message": "Theme retrieved successfully",
  "timestamp": 1704902400
}
```

---

### POST /themes

Create a custom theme.

**Request:**
```http
POST /wp-json/mas-v2/v1/themes
Content-Type: application/json
X-WP-Nonce: abc123def456

{
  "id": "my-custom-theme",
  "name": "My Custom Theme",
  "description": "A custom theme for my site",
  "settings": {
    "menu_background": "#ff6b6b",
    "menu_text_color": "#ffffff"
  }
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": "my-custom-theme",
    "name": "My Custom Theme",
    "type": "custom",
    "readonly": false,
    "settings": { ... }
  },
  "message": "Theme created successfully",
  "timestamp": 1704902400
}
```

**Status Code:** `201 Created`

---

### PUT /themes/{id}

Update a custom theme.

**Request:**
```http
PUT /wp-json/mas-v2/v1/themes/my-custom-theme
Content-Type: application/json
X-WP-Nonce: abc123def456

{
  "name": "Updated Theme Name",
  "settings": {
    "menu_background": "#4ecdc4"
  }
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": "my-custom-theme",
    "name": "Updated Theme Name",
    "settings": { ... }
  },
  "message": "Theme updated successfully",
  "timestamp": 1704902400
}
```

**Note:** Predefined themes cannot be updated (returns `403 Forbidden`).

---

### DELETE /themes/{id}

Delete a custom theme.

**Request:**
```http
DELETE /wp-json/mas-v2/v1/themes/my-custom-theme
X-WP-Nonce: abc123def456
```

**Response:**
```json
{
  "success": true,
  "data": {
    "deleted": true,
    "id": "my-custom-theme"
  },
  "message": "Theme deleted successfully",
  "timestamp": 1704902400
}
```

**Note:** Predefined themes cannot be deleted (returns `403 Forbidden`).

---

### POST /themes/{id}/apply

Apply a theme to current settings.

**Request:**
```http
POST /wp-json/mas-v2/v1/themes/dark-blue/apply
X-WP-Nonce: abc123def456
```

**Response:**
```json
{
  "success": true,
  "data": {
    "applied": true,
    "theme_id": "dark-blue"
  },
  "message": "Theme \"dark-blue\" applied successfully",
  "timestamp": 1704902400
}
```

**Side Effects:**
- Updates current settings with theme settings
- Regenerates CSS
- Clears settings cache

---

## Backups Endpoints

### GET /backups

List all backups with pagination.

**Request:**
```http
GET /wp-json/mas-v2/v1/backups?limit=10&page=1
```

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `limit` | integer | 10 | Number of backups per page |
| `page` | integer | 1 | Page number |
| `offset` | integer | 0 | Offset for pagination (alternative to page) |

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": "backup_1704902400",
      "timestamp": 1704902400,
      "date": "2025-01-10 15:30:00",
      "type": "manual",
      "settings": { ... },
      "metadata": {
        "plugin_version": "2.2.0",
        "wordpress_version": "6.8",
        "user_id": 1,
        "note": "Before major changes"
      }
    }
  ],
  "message": "Backups retrieved successfully",
  "timestamp": 1704902400
}
```

**Pagination Headers:**
```
X-WP-Total: 25
X-WP-TotalPages: 3
Link: <url>; rel="first", <url>; rel="prev", <url>; rel="next", <url>; rel="last"
```

---

### GET /backups/{id}

Get a specific backup by ID.

**Request:**
```http
GET /wp-json/mas-v2/v1/backups/backup_1704902400
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": "backup_1704902400",
    "timestamp": 1704902400,
    "type": "manual",
    "settings": { ... },
    "metadata": { ... }
  },
  "message": "Backup retrieved successfully",
  "timestamp": 1704902400
}
```

---

### POST /backups

Create a new manual backup.

**Request:**
```http
POST /wp-json/mas-v2/v1/backups
Content-Type: application/json
X-WP-Nonce: abc123def456

{
  "note": "Backup before testing new theme"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": "backup_1704902400",
    "timestamp": 1704902400,
    "type": "manual",
    "settings": { ... },
    "metadata": {
      "note": "Backup before testing new theme"
    }
  },
  "message": "Backup created successfully",
  "timestamp": 1704902400
}
```

**Status Code:** `201 Created`

---

### POST /backups/{id}/restore

Restore a backup.

**Request:**
```http
POST /wp-json/mas-v2/v1/backups/backup_1704902400/restore
X-WP-Nonce: abc123def456
```

**Response:**
```json
{
  "success": true,
  "data": {
    "backup_id": "backup_1704902400"
  },
  "message": "Backup restored successfully",
  "timestamp": 1704902400
}
```

**Side Effects:**
- Creates automatic backup of current settings before restore
- Restores settings from backup
- Regenerates CSS
- Clears settings cache

---

### DELETE /backups/{id}

Delete a backup.

**Request:**
```http
DELETE /wp-json/mas-v2/v1/backups/backup_1704902400
X-WP-Nonce: abc123def456
```

**Response:**
```json
{
  "success": true,
  "data": {
    "backup_id": "backup_1704902400"
  },
  "message": "Backup deleted successfully",
  "timestamp": 1704902400
}
```

---

### GET /backups/statistics

Get backup statistics.

**Request:**
```http
GET /wp-json/mas-v2/v1/backups/statistics
```

**Response:**
```json
{
  "success": true,
  "data": {
    "total_backups": 25,
    "manual_backups": 10,
    "automatic_backups": 15,
    "total_size": "2.5 MB",
    "oldest_backup": "2024-12-01 10:00:00",
    "newest_backup": "2025-01-10 15:30:00"
  },
  "message": "Statistics retrieved successfully",
  "timestamp": 1704902400
}
```

---

## Import/Export Endpoints

### GET /export

Export current settings as JSON.

**Request:**
```http
GET /wp-json/mas-v2/v1/export?include_metadata=true
```

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `include_metadata` | boolean | true | Include version and metadata |

**Response:**
```json
{
  "success": true,
  "data": {
    "version": "2.2.0",
    "exported_at": "2025-01-10 15:30:00",
    "settings": { ... }
  },
  "filename": "mas-v2-settings-20250110-153000.json",
  "message": "Settings exported successfully",
  "timestamp": 1704902400
}
```

**Headers:**
```
Content-Disposition: attachment; filename="mas-v2-settings-20250110-153000.json"
Content-Type: application/json
Cache-Control: no-cache, no-store, must-revalidate
```

---

### POST /import

Import settings from JSON.

**Request:**
```http
POST /wp-json/mas-v2/v1/import
Content-Type: application/json
X-WP-Nonce: abc123def456

{
  "data": {
    "version": "2.2.0",
    "settings": { ... }
  },
  "create_backup": true
}
```

**Request Body:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `data` | object/string | Yes | Import data (JSON object or string) |
| `create_backup` | boolean | No | Create backup before import (default: true) |

**Response:**
```json
{
  "success": true,
  "data": {
    "imported": true,
    "backup_created": true
  },
  "message": "Settings imported successfully",
  "timestamp": 1704902400
}
```

**Side Effects:**
- Creates automatic backup before import (if enabled)
- Validates imported data
- Migrates legacy format if needed
- Regenerates CSS
- Clears settings cache

**Validation Errors:**
```json
{
  "code": "validation_failed",
  "message": "Import validation failed",
  "data": {
    "status": 400,
    "errors": {
      "menu_background": "Invalid color value",
      "menu_width": "Must be between 100 and 400"
    }
  }
}
```

---

## Preview Endpoints

### POST /preview

Generate preview CSS without saving settings.

**Request:**
```http
POST /wp-json/mas-v2/v1/preview
Content-Type: application/json
X-WP-Nonce: abc123def456

{
  "settings": {
    "menu_background": "#ff6b6b",
    "menu_text_color": "#ffffff",
    "enable_animations": true
  }
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "css": "#adminmenu { background: #ff6b6b; color: #ffffff; }",
    "settings_count": 3,
    "css_length": 1024
  },
  "message": "Preview CSS generated successfully",
  "timestamp": 1704902400
}
```

**Headers:**
```
Cache-Control: no-store, no-cache, must-revalidate, max-age=0
Pragma: no-cache
Expires: 0
```

**Rate Limiting:**
- Server-side debouncing: 500ms minimum between requests
- Returns `429 Too Many Requests` if requests are too frequent

**Fallback Response:**
If CSS generation fails, a fallback response is returned:
```json
{
  "success": true,
  "data": {
    "css": "/* Fallback CSS */",
    "fallback": true,
    "message": "Using fallback CSS due to generation error"
  },
  "timestamp": 1704902400
}
```

---

## Diagnostics Endpoints

### GET /diagnostics

Get comprehensive system diagnostics.

**Request:**
```http
GET /wp-json/mas-v2/v1/diagnostics?include=system,performance
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `include` | string | Comma-separated list of sections to include |

**Available Sections:**
- `system` - System information (PHP, WordPress versions)
- `plugin` - Plugin information and status
- `settings` - Settings integrity check
- `filesystem` - File permissions and directory structure
- `conflicts` - Plugin/theme conflict detection
- `performance` - Performance metrics
- `recommendations` - Optimization recommendations

**Response:**
```json
{
  "success": true,
  "data": {
    "system": {
      "php_version": "8.1.0",
      "wordpress_version": "6.8",
      "server_software": "Apache/2.4.41",
      "memory_limit": "256M",
      "max_execution_time": 30
    },
    "plugin": {
      "version": "2.2.0",
      "active": true,
      "rest_api_enabled": true
    },
    "settings": {
      "valid": true,
      "total_settings": 25,
      "custom_settings": 5
    },
    "filesystem": {
      "upload_dir_writable": true,
      "plugin_dir_writable": false
    },
    "conflicts": {
      "detected": false,
      "conflicting_plugins": []
    },
    "performance": {
      "memory_usage": "45.2 MB",
      "peak_memory": "48.1 MB",
      "execution_time": "125ms",
      "database_queries": 15
    },
    "recommendations": [
      {
        "type": "performance",
        "severity": "low",
        "message": "Consider enabling object caching"
      }
    ],
    "_metadata": {
      "generated_at": "2025-01-10 15:30:00",
      "generated_timestamp": 1704902400,
      "execution_time": "125.45ms"
    }
  },
  "message": "Diagnostics retrieved successfully",
  "timestamp": 1704902400
}
```

---

### GET /diagnostics/health

Quick health check.

**Request:**
```http
GET /wp-json/mas-v2/v1/diagnostics/health
```

**Response:**
```json
{
  "success": true,
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
  "message": "Health check completed: healthy",
  "timestamp": 1704902400
}
```

**Status Values:**
- `healthy` - All checks passed
- `warning` - Some checks have warnings
- `unhealthy` - One or more checks failed

---

### GET /diagnostics/performance

Get performance metrics only.

**Request:**
```http
GET /wp-json/mas-v2/v1/diagnostics/performance
```

**Response:**
```json
{
  "success": true,
  "data": {
    "memory_usage": "45.2 MB",
    "memory_usage_bytes": 47398912,
    "peak_memory": "48.1 MB",
    "peak_memory_bytes": 50438144,
    "memory_limit": "256M",
    "memory_limit_bytes": 268435456,
    "execution_time": "125.45ms",
    "database_queries": 15,
    "cache_hits": 42,
    "cache_misses": 3
  },
  "message": "Performance metrics retrieved successfully",
  "timestamp": 1704902400
}
```

---

## System Endpoints

⭐ **Phase 2 Feature**

### GET /system/health

Get comprehensive system health status.

**Request:**
```http
GET /wp-json/mas-v2/v1/system/health
```

**Response:**
```json
{
  "success": true,
  "data": {
    "status": "healthy",
    "checks": {
      "php_version": {
        "status": "pass",
        "value": "8.1.0",
        "required": "7.4.0",
        "message": "PHP version is adequate"
      },
      "wordpress_version": {
        "status": "pass",
        "value": "6.8",
        "required": "6.0",
        "message": "WordPress version is adequate"
      },
      "settings_integrity": {
        "status": "pass",
        "message": "All settings are valid"
      },
      "file_permissions": {
        "status": "pass",
        "message": "All required directories are writable"
      },
      "cache_status": {
        "status": "pass",
        "message": "Cache is functioning properly"
      },
      "conflicts": {
        "status": "pass",
        "message": "No conflicts detected"
      }
    },
    "recommendations": []
  },
  "message": "System health check completed",
  "timestamp": 1704902400
}
```

**Status Values:**
- `healthy` - All checks passed
- `warning` - Some checks have warnings but system is functional
- `critical` - One or more critical checks failed

---

### GET /system/info

Get detailed system information.

**Request:**
```http
GET /wp-json/mas-v2/v1/system/info
```

**Response:**
```json
{
  "success": true,
  "data": {
    "php": {
      "version": "8.1.0",
      "memory_limit": "256M",
      "max_execution_time": 30,
      "extensions": ["json", "mbstring", "mysqli"]
    },
    "wordpress": {
      "version": "6.8",
      "multisite": false,
      "debug_mode": false,
      "language": "en_US"
    },
    "plugin": {
      "version": "2.3.0",
      "active": true,
      "rest_api_enabled": true,
      "phase2_enabled": true
    },
    "server": {
      "software": "Apache/2.4.41",
      "os": "Linux",
      "hostname": "example.com"
    }
  },
  "message": "System information retrieved successfully",
  "timestamp": 1704902400
}
```

---

### GET /system/performance

Get performance metrics and statistics.

**Request:**
```http
GET /wp-json/mas-v2/v1/system/performance
```

**Response:**
```json
{
  "success": true,
  "data": {
    "memory": {
      "current": "45.2 MB",
      "peak": "48.1 MB",
      "limit": "256M",
      "usage_percentage": 17.6
    },
    "cache": {
      "enabled": true,
      "hits": 142,
      "misses": 8,
      "hit_rate": 94.7,
      "size": "2.1 MB"
    },
    "database": {
      "queries": 15,
      "query_time": "45.2ms",
      "slow_queries": 0
    },
    "api": {
      "total_requests": 1250,
      "avg_response_time": "125ms",
      "error_rate": 0.8
    }
  },
  "message": "Performance metrics retrieved successfully",
  "timestamp": 1704902400
}
```

---

### GET /system/conflicts

Detect plugin and theme conflicts.

**Request:**
```http
GET /wp-json/mas-v2/v1/system/conflicts
```

**Response:**
```json
{
  "success": true,
  "data": {
    "detected": true,
    "conflicts": [
      {
        "type": "plugin",
        "name": "Another Admin Styler",
        "slug": "another-admin-styler",
        "severity": "high",
        "description": "This plugin modifies the same admin styles",
        "recommendation": "Deactivate one of the admin styling plugins"
      }
    ],
    "total_conflicts": 1
  },
  "message": "Conflict detection completed",
  "timestamp": 1704902400
}
```

---

### GET /system/cache

Get cache status and statistics.

**Request:**
```http
GET /wp-json/mas-v2/v1/system/cache
```

**Response:**
```json
{
  "success": true,
  "data": {
    "enabled": true,
    "type": "redis",
    "stats": {
      "hits": 142,
      "misses": 8,
      "hit_rate": 94.7,
      "size": "2.1 MB",
      "items": 45
    },
    "groups": {
      "mas_v2_settings": {
        "hits": 85,
        "misses": 2,
        "size": "512 KB"
      },
      "mas_v2_css": {
        "hits": 57,
        "misses": 6,
        "size": "1.6 MB"
      }
    }
  },
  "message": "Cache status retrieved successfully",
  "timestamp": 1704902400
}
```

---

### DELETE /system/cache

Clear all plugin caches.

**Request:**
```http
DELETE /wp-json/mas-v2/v1/system/cache
X-WP-Nonce: abc123def456
```

**Response:**
```json
{
  "success": true,
  "data": {
    "cleared": true,
    "groups_cleared": ["mas_v2_settings", "mas_v2_css", "mas_v2_themes"]
  },
  "message": "Cache cleared successfully",
  "timestamp": 1704902400
}
```

---

## Security Endpoints

⭐ **Phase 2 Feature**

### GET /security/audit-log

Get security audit log with filtering.

**Request:**
```http
GET /wp-json/mas-v2/v1/security/audit-log?action=settings.updated&user_id=1&limit=50&page=1
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `action` | string | Filter by action type |
| `user_id` | integer | Filter by user ID |
| `start_date` | string | Start date (YYYY-MM-DD) |
| `end_date` | string | End date (YYYY-MM-DD) |
| `limit` | integer | Results per page (default: 50) |
| `page` | integer | Page number (default: 1) |

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "timestamp": 1704902400,
      "date": "2025-01-10 15:30:00",
      "user_id": 1,
      "user_login": "admin",
      "action": "settings.updated",
      "details": {
        "changed_fields": ["menu_background", "menu_text_color"],
        "old_values": {"menu_background": "#23282d"},
        "new_values": {"menu_background": "#1e1e2e"}
      },
      "ip_address": "192.168.1.1",
      "user_agent": "Mozilla/5.0..."
    }
  ],
  "pagination": {
    "total": 250,
    "pages": 5,
    "current_page": 1,
    "per_page": 50
  },
  "message": "Audit log retrieved successfully",
  "timestamp": 1704902400
}
```

**Pagination Headers:**
```
X-WP-Total: 250
X-WP-TotalPages: 5
```

---

### GET /security/rate-limit/status

Get current rate limit status for the user.

**Request:**
```http
GET /wp-json/mas-v2/v1/security/rate-limit/status
```

**Response:**
```json
{
  "success": true,
  "data": {
    "user_id": 1,
    "limits": {
      "default": {
        "limit": 60,
        "remaining": 45,
        "reset": 1704902460,
        "reset_in": "45 seconds"
      },
      "settings_save": {
        "limit": 10,
        "remaining": 8,
        "reset": 1704902460,
        "reset_in": "45 seconds"
      },
      "backup_create": {
        "limit": 5,
        "remaining": 4,
        "reset": 1704902700,
        "reset_in": "5 minutes"
      }
    }
  },
  "message": "Rate limit status retrieved successfully",
  "timestamp": 1704902400
}
```

---

## Batch Operations Endpoints

⭐ **Phase 2 Feature**

### POST /settings/batch

Perform batch settings updates with transaction support.

**Request:**
```http
POST /wp-json/mas-v2/v1/settings/batch
Content-Type: application/json
X-WP-Nonce: abc123def456

{
  "operations": [
    {
      "action": "update",
      "settings": {
        "menu_background": "#1e1e2e"
      }
    },
    {
      "action": "update",
      "settings": {
        "menu_text_color": "#ffffff"
      }
    }
  ],
  "atomic": true
}
```

**Request Body:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `operations` | array | Yes | Array of operations to perform |
| `atomic` | boolean | No | If true, rollback all on any failure (default: true) |

**Response:**
```json
{
  "success": true,
  "data": {
    "total_operations": 2,
    "successful": 2,
    "failed": 0,
    "results": [
      {
        "operation": 0,
        "success": true,
        "message": "Settings updated successfully"
      },
      {
        "operation": 1,
        "success": true,
        "message": "Settings updated successfully"
      }
    ],
    "rollback_performed": false
  },
  "message": "Batch operation completed successfully",
  "timestamp": 1704902400
}
```

**Error Response (with rollback):**
```json
{
  "success": false,
  "data": {
    "total_operations": 2,
    "successful": 1,
    "failed": 1,
    "results": [
      {
        "operation": 0,
        "success": true,
        "message": "Settings updated successfully"
      },
      {
        "operation": 1,
        "success": false,
        "error": "Invalid color value"
      }
    ],
    "rollback_performed": true
  },
  "message": "Batch operation failed, changes rolled back",
  "timestamp": 1704902400
}
```

---

### POST /backups/batch

Perform batch backup operations.

**Request:**
```http
POST /wp-json/mas-v2/v1/backups/batch
Content-Type: application/json
X-WP-Nonce: abc123def456

{
  "operations": [
    {
      "action": "delete",
      "backup_id": "backup_1704800000"
    },
    {
      "action": "delete",
      "backup_id": "backup_1704810000"
    }
  ]
}
```

**Supported Actions:**
- `delete` - Delete a backup
- `restore` - Restore a backup

**Response:**
```json
{
  "success": true,
  "data": {
    "total_operations": 2,
    "successful": 2,
    "failed": 0,
    "results": [
      {
        "operation": 0,
        "action": "delete",
        "backup_id": "backup_1704800000",
        "success": true
      },
      {
        "operation": 1,
        "action": "delete",
        "backup_id": "backup_1704810000",
        "success": true
      }
    ]
  },
  "message": "Batch backup operations completed",
  "timestamp": 1704902400
}
```

---

### POST /themes/batch-apply

Apply theme with validation in a transaction.

**Request:**
```http
POST /wp-json/mas-v2/v1/themes/batch-apply
Content-Type: application/json
X-WP-Nonce: abc123def456

{
  "theme_id": "dark-blue",
  "create_backup": true,
  "validate": true
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "theme_applied": true,
    "theme_id": "dark-blue",
    "backup_created": true,
    "backup_id": "backup_1704902400",
    "validation_passed": true
  },
  "message": "Theme applied successfully with backup",
  "timestamp": 1704902400
}
```

---

## Webhooks Endpoints

⭐ **Phase 2 Feature**

### GET /webhooks

List all registered webhooks.

**Request:**
```http
GET /wp-json/mas-v2/v1/webhooks?active=true&limit=20&page=1
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `active` | boolean | Filter by active status |
| `event` | string | Filter by event type |
| `limit` | integer | Results per page (default: 20) |
| `page` | integer | Page number (default: 1) |

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "url": "https://example.com/webhook",
      "events": ["settings.updated", "theme.applied"],
      "active": true,
      "secret": "whsec_***",
      "created_at": "2025-01-10 15:30:00",
      "last_triggered": "2025-01-10 16:00:00",
      "delivery_stats": {
        "total": 150,
        "successful": 148,
        "failed": 2,
        "success_rate": 98.7
      }
    }
  ],
  "pagination": {
    "total": 5,
    "pages": 1,
    "current_page": 1,
    "per_page": 20
  },
  "message": "Webhooks retrieved successfully",
  "timestamp": 1704902400
}
```

---

### POST /webhooks

Register a new webhook.

**Request:**
```http
POST /wp-json/mas-v2/v1/webhooks
Content-Type: application/json
X-WP-Nonce: abc123def456

{
  "url": "https://example.com/webhook",
  "events": ["settings.updated", "theme.applied", "backup.created"],
  "secret": "your-webhook-secret",
  "active": true
}
```

**Request Body:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `url` | string | Yes | Webhook URL (must be HTTPS) |
| `events` | array | Yes | Array of event types to subscribe to |
| `secret` | string | No | Secret for HMAC signature (auto-generated if not provided) |
| `active` | boolean | No | Active status (default: true) |

**Available Events:**
- `settings.updated` - Settings were updated
- `settings.reset` - Settings were reset to defaults
- `theme.applied` - Theme was applied
- `backup.created` - Backup was created
- `backup.restored` - Backup was restored
- `backup.deleted` - Backup was deleted

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "url": "https://example.com/webhook",
    "events": ["settings.updated", "theme.applied", "backup.created"],
    "secret": "whsec_abc123def456",
    "active": true,
    "created_at": "2025-01-10 15:30:00"
  },
  "message": "Webhook registered successfully",
  "timestamp": 1704902400
}
```

**Status Code:** `201 Created`

---

### GET /webhooks/{id}

Get a specific webhook.

**Request:**
```http
GET /wp-json/mas-v2/v1/webhooks/1
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "url": "https://example.com/webhook",
    "events": ["settings.updated", "theme.applied"],
    "active": true,
    "secret": "whsec_***",
    "created_at": "2025-01-10 15:30:00",
    "updated_at": "2025-01-10 16:00:00"
  },
  "message": "Webhook retrieved successfully",
  "timestamp": 1704902400
}
```

---

### PUT /webhooks/{id}

Update a webhook.

**Request:**
```http
PUT /wp-json/mas-v2/v1/webhooks/1
Content-Type: application/json
X-WP-Nonce: abc123def456

{
  "events": ["settings.updated", "backup.created"],
  "active": true
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "url": "https://example.com/webhook",
    "events": ["settings.updated", "backup.created"],
    "active": true,
    "updated_at": "2025-01-10 16:30:00"
  },
  "message": "Webhook updated successfully",
  "timestamp": 1704902400
}
```

---

### DELETE /webhooks/{id}

Delete a webhook.

**Request:**
```http
DELETE /wp-json/mas-v2/v1/webhooks/1
X-WP-Nonce: abc123def456
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "deleted": true
  },
  "message": "Webhook deleted successfully",
  "timestamp": 1704902400
}
```

---

### GET /webhooks/{id}/deliveries

Get webhook delivery history.

**Request:**
```http
GET /wp-json/mas-v2/v1/webhooks/1/deliveries?status=failed&limit=50&page=1
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `status` | string | Filter by status: `success`, `failed`, `pending` |
| `limit` | integer | Results per page (default: 50) |
| `page` | integer | Page number (default: 1) |

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "webhook_id": 1,
      "event": "settings.updated",
      "status": "success",
      "response_code": 200,
      "response_time": "125ms",
      "payload": {
        "event": "settings.updated",
        "timestamp": 1704902400,
        "data": {...}
      },
      "response_body": "OK",
      "attempts": 1,
      "created_at": "2025-01-10 15:30:00",
      "delivered_at": "2025-01-10 15:30:01"
    }
  ],
  "pagination": {
    "total": 150,
    "pages": 3,
    "current_page": 1,
    "per_page": 50
  },
  "message": "Webhook deliveries retrieved successfully",
  "timestamp": 1704902400
}
```

---

### Webhook Payload Format

When a webhook is triggered, the following payload is sent:

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

**Headers:**
```
Content-Type: application/json
X-MAS-Signature: sha256=abc123def456...
X-MAS-Event: settings.updated
X-MAS-Webhook-ID: 1
X-MAS-Delivery-ID: 123
```

**Signature Verification:**

The `X-MAS-Signature` header contains an HMAC SHA-256 signature of the payload:

```javascript
const crypto = require('crypto');

function verifySignature(payload, signature, secret) {
  const expectedSignature = 'sha256=' + crypto
    .createHmac('sha256', secret)
    .update(JSON.stringify(payload))
    .digest('hex');
  
  return crypto.timingSafeEqual(
    Buffer.from(signature),
    Buffer.from(expectedSignature)
  );
}
```

---

## Analytics Endpoints

⭐ **Phase 2 Feature**

### GET /analytics/usage

Get API usage statistics.

**Request:**
```http
GET /wp-json/mas-v2/v1/analytics/usage?start_date=2025-01-01&end_date=2025-01-10&group_by=endpoint
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `start_date` | string | Start date (YYYY-MM-DD) |
| `end_date` | string | End date (YYYY-MM-DD) |
| `group_by` | string | Group by: `endpoint`, `method`, `user`, `date` |
| `endpoint` | string | Filter by specific endpoint |

**Response:**
```json
{
  "success": true,
  "data": {
    "period": {
      "start": "2025-01-01",
      "end": "2025-01-10",
      "days": 10
    },
    "total_requests": 1250,
    "by_endpoint": {
      "/settings": {
        "total": 450,
        "GET": 300,
        "POST": 120,
        "PUT": 30
      },
      "/themes": {
        "total": 200,
        "GET": 180,
        "POST": 20
      },
      "/backups": {
        "total": 150,
        "GET": 100,
        "POST": 40,
        "DELETE": 10
      }
    },
    "by_date": {
      "2025-01-01": 120,
      "2025-01-02": 135,
      "2025-01-03": 128
    },
    "top_users": [
      {
        "user_id": 1,
        "user_login": "admin",
        "requests": 850
      }
    ]
  },
  "message": "Usage statistics retrieved successfully",
  "timestamp": 1704902400
}
```

---

### GET /analytics/performance

Get API performance metrics.

**Request:**
```http
GET /wp-json/mas-v2/v1/analytics/performance?start_date=2025-01-01&end_date=2025-01-10
```

**Response:**
```json
{
  "success": true,
  "data": {
    "period": {
      "start": "2025-01-01",
      "end": "2025-01-10"
    },
    "response_times": {
      "p50": "85ms",
      "p75": "125ms",
      "p90": "180ms",
      "p95": "245ms",
      "p99": "420ms",
      "avg": "105ms",
      "min": "45ms",
      "max": "850ms"
    },
    "by_endpoint": {
      "/settings": {
        "avg": "95ms",
        "p95": "220ms"
      },
      "/themes": {
        "avg": "110ms",
        "p95": "250ms"
      },
      "/preview": {
        "avg": "145ms",
        "p95": "320ms"
      }
    },
    "slow_requests": [
      {
        "endpoint": "/preview",
        "method": "POST",
        "response_time": "850ms",
        "timestamp": "2025-01-05 14:30:00"
      }
    ]
  },
  "message": "Performance metrics retrieved successfully",
  "timestamp": 1704902400
}
```

---

### GET /analytics/errors

Get error statistics and analysis.

**Request:**
```http
GET /wp-json/mas-v2/v1/analytics/errors?start_date=2025-01-01&end_date=2025-01-10
```

**Response:**
```json
{
  "success": true,
  "data": {
    "period": {
      "start": "2025-01-01",
      "end": "2025-01-10"
    },
    "total_errors": 25,
    "error_rate": 2.0,
    "by_status_code": {
      "400": 15,
      "403": 5,
      "404": 3,
      "500": 2
    },
    "by_error_code": {
      "validation_failed": 12,
      "rest_forbidden": 5,
      "not_found": 3,
      "database_error": 2
    },
    "by_endpoint": {
      "/settings": {
        "errors": 12,
        "error_rate": 2.7
      },
      "/themes": {
        "errors": 8,
        "error_rate": 4.0
      }
    },
    "recent_errors": [
      {
        "endpoint": "/settings",
        "method": "POST",
        "error_code": "validation_failed",
        "status_code": 400,
        "message": "Invalid color value",
        "timestamp": "2025-01-10 15:30:00"
      }
    ]
  },
  "message": "Error statistics retrieved successfully",
  "timestamp": 1704902400
}
```

---

### GET /analytics/export

Export analytics data as CSV.

**Request:**
```http
GET /wp-json/mas-v2/v1/analytics/export?type=usage&start_date=2025-01-01&end_date=2025-01-10
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `type` | string | Export type: `usage`, `performance`, `errors` |
| `start_date` | string | Start date (YYYY-MM-DD) |
| `end_date` | string | End date (YYYY-MM-DD) |

**Response:**
CSV file download with appropriate headers:

```
Content-Type: text/csv
Content-Disposition: attachment; filename="mas-analytics-usage-20250101-20250110.csv"
```

**CSV Format (usage):**
```csv
Date,Endpoint,Method,Requests,Avg Response Time,Error Rate
2025-01-01,/settings,GET,120,95ms,1.2%
2025-01-01,/settings,POST,45,125ms,2.5%
```

---

## Phase 2 Features

⭐ **New in Version 2.3.0**

### Enhanced Theme Management

- **Theme Presets:** Access predefined professional themes
- **Theme Preview:** Preview themes before applying
- **Theme Export/Import:** Share themes with version compatibility checking
- **Theme Metadata:** Track theme versions and authors

### Enterprise Backup Management

- **Retention Policies:** Automatic cleanup with configurable retention (30 automatic, 100 manual backups)
- **Backup Metadata:** Track user, notes, size, and checksums
- **Backup Download:** Export backups as JSON files
- **Batch Operations:** Delete multiple backups at once

### System Diagnostics

- **Health Monitoring:** Comprehensive system health checks
- **Performance Metrics:** Memory, cache, and database statistics
- **Conflict Detection:** Identify conflicting plugins and themes
- **Recommendations:** Actionable optimization suggestions

### Advanced Performance

- **ETag Support:** Conditional requests with 304 Not Modified responses
- **Last-Modified Headers:** Efficient caching with timestamp validation
- **Advanced Caching:** Object cache with statistics and warming
- **Database Optimization:** Indexed queries and result caching

### Enhanced Security

- **Rate Limiting:** Per-user and per-IP request throttling (60/min default, 10 saves/min, 5 backups/5min)
- **Audit Logging:** Complete audit trail of all operations
- **Suspicious Activity Detection:** Pattern-based threat detection
- **Security Dashboard:** Real-time security monitoring

### Batch Operations

- **Transaction Support:** Atomic operations with automatic rollback
- **Batch Settings Updates:** Update multiple settings in one request
- **Batch Backup Operations:** Perform multiple backup actions
- **Validated Theme Application:** Apply themes with validation and backup

### Webhook Support

- **Event Subscriptions:** Subscribe to plugin events
- **HMAC Signatures:** Secure webhook delivery with signature verification
- **Retry Mechanism:** Automatic retry with exponential backoff
- **Delivery Tracking:** Complete delivery history and statistics

### Analytics & Monitoring

- **Usage Statistics:** Track API usage by endpoint, method, and user
- **Performance Monitoring:** Response time percentiles and slow request tracking
- **Error Analysis:** Error rates and patterns
- **CSV Export:** Export analytics data for external analysis

### API Versioning

- **Version Namespaces:** `/mas-v2/v1/`, `/mas-v2/v2/` support
- **Deprecation Warnings:** Clear deprecation notices with migration guides
- **Backward Compatibility:** Graceful handling of version differences

---

## Examples

### JavaScript Client Example

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
  
  // Get settings
  async getSettings() {
    return this.request('/settings', { method: 'GET' });
  }
  
  // Save settings
  async saveSettings(settings) {
    return this.request('/settings', {
      method: 'POST',
      body: JSON.stringify(settings)
    });
  }
  
  // Apply theme
  async applyTheme(themeId) {
    return this.request(`/themes/${themeId}/apply`, {
      method: 'POST'
    });
  }
  
  // Create backup
  async createBackup(note = '') {
    return this.request('/backups', {
      method: 'POST',
      body: JSON.stringify({ note })
    });
  }
  
  // Generate preview
  async generatePreview(settings) {
    return this.request('/preview', {
      method: 'POST',
      body: JSON.stringify({ settings })
    });
  }
}

// Usage
const client = new MASRestClient();

// Get current settings
const settings = await client.getSettings();
console.log(settings.data);

// Update settings
await client.saveSettings({
  menu_background: '#1e1e2e',
  enable_animations: true
});

// Apply a theme
await client.applyTheme('dark-blue');
```

### cURL Examples

**Get Settings:**
```bash
curl -X GET \
  'https://example.com/wp-json/mas-v2/v1/settings' \
  -H 'Cookie: wordpress_logged_in_...' \
  -H 'X-WP-Nonce: abc123def456'
```

**Save Settings:**
```bash
curl -X POST \
  'https://example.com/wp-json/mas-v2/v1/settings' \
  -H 'Content-Type: application/json' \
  -H 'Cookie: wordpress_logged_in_...' \
  -H 'X-WP-Nonce: abc123def456' \
  -d '{
    "menu_background": "#1e1e2e",
    "menu_text_color": "#ffffff"
  }'
```

**Create Backup:**
```bash
curl -X POST \
  'https://example.com/wp-json/mas-v2/v1/backups' \
  -H 'Content-Type: application/json' \
  -H 'Cookie: wordpress_logged_in_...' \
  -H 'X-WP-Nonce: abc123def456' \
  -d '{
    "note": "Before major changes"
  }'
```

**Export Settings:**
```bash
curl -X GET \
  'https://example.com/wp-json/mas-v2/v1/export' \
  -H 'Cookie: wordpress_logged_in_...' \
  -H 'X-WP-Nonce: abc123def456' \
  -o settings-export.json
```

---

## Best Practices

### 1. Error Handling

Always handle errors gracefully:

```javascript
try {
  const result = await client.saveSettings(settings);
  console.log('Success:', result.message);
} catch (error) {
  console.error('Error:', error.message);
  // Show user-friendly error message
}
```

### 2. Rate Limiting

Implement client-side debouncing for preview requests:

```javascript
let debounceTimer;

function updatePreview(settings) {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(async () => {
    try {
      const result = await client.generatePreview(settings);
      applyPreviewCSS(result.data.css);
    } catch (error) {
      console.error('Preview failed:', error);
    }
  }, 500);
}
```

### 3. Caching

Leverage ETag headers for conditional requests:

```javascript
let cachedETag = null;

async function getSettings() {
  const headers = {};
  if (cachedETag) {
    headers['If-None-Match'] = cachedETag;
  }
  
  const response = await fetch(url, { headers });
  
  if (response.status === 304) {
    // Use cached data
    return cachedSettings;
  }
  
  cachedETag = response.headers.get('ETag');
  return response.json();
}
```

### 4. Backup Before Changes

Always create a backup before major changes:

```javascript
async function applyThemeWithBackup(themeId) {
  // Create backup first
  await client.createBackup('Before applying theme');
  
  // Then apply theme
  await client.applyTheme(themeId);
}
```

---

## Support

For issues, questions, or feature requests:
- GitHub: [repository-url]
- Documentation: [docs-url]
- Support Forum: [forum-url]

### Phase 2 Examples

**Register a Webhook:**
```javascript
const client = new MASRestClient();

// Register webhook for settings changes
const webhook = await client.request('/webhooks', {
  method: 'POST',
  body: JSON.stringify({
    url: 'https://myapp.com/webhook',
    events: ['settings.updated', 'theme.applied'],
    secret: 'my-webhook-secret'
  })
});

console.log('Webhook registered:', webhook.data.id);
```

**Batch Settings Update:**
```javascript
// Update multiple settings atomically
const result = await client.request('/settings/batch', {
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
      }
    ],
    atomic: true
  })
});

console.log('Batch update:', result.data.successful, 'of', result.data.total_operations);
```

**Get Analytics:**
```javascript
// Get usage statistics
const usage = await client.request('/analytics/usage?start_date=2025-01-01&end_date=2025-01-10');
console.log('Total requests:', usage.data.total_requests);

// Get performance metrics
const performance = await client.request('/analytics/performance');
console.log('P95 response time:', performance.data.response_times.p95);
```

**System Health Check:**
```javascript
// Check system health
const health = await client.request('/system/health');
console.log('System status:', health.data.status);

if (health.data.status !== 'healthy') {
  console.log('Recommendations:', health.data.recommendations);
}
```

**Verify Webhook Signature:**
```javascript
// Server-side webhook handler (Node.js example)
const crypto = require('crypto');

app.post('/webhook', (req, res) => {
  const signature = req.headers['x-mas-signature'];
  const payload = JSON.stringify(req.body);
  const secret = 'my-webhook-secret';
  
  const expectedSignature = 'sha256=' + crypto
    .createHmac('sha256', secret)
    .update(payload)
    .digest('hex');
  
  if (signature === expectedSignature) {
    console.log('Webhook verified:', req.body.event);
    // Process webhook...
    res.status(200).send('OK');
  } else {
    res.status(401).send('Invalid signature');
  }
});
```

---

**Last Updated:** January 10, 2025  
**API Version:** 2.3.0 (Phase 2)
