# Modern Admin Styler V2 - JSON Schemas

## Overview

This document contains JSON Schema definitions for all REST API endpoints. These schemas define the structure, validation rules, and constraints for request and response data.

All schemas conform to JSON Schema Draft 04 specification.

---

## Table of Contents

1. [Settings Schema](#settings-schema)
2. [Theme Schema](#theme-schema)
3. [Backup Schema](#backup-schema)
4. [Import/Export Schema](#importexport-schema)
5. [Preview Schema](#preview-schema)
6. [Diagnostics Schema](#diagnostics-schema)
7. [Common Schemas](#common-schemas)

---

## Settings Schema

### Settings Object

```json
{
  "$schema": "http://json-schema.org/draft-04/schema#",
  "title": "settings",
  "type": "object",
  "properties": {
    "enable_plugin": {
      "description": "Enable plugin functionality",
      "type": "boolean",
      "default": true
    },
    "theme": {
      "description": "Theme name",
      "type": "string",
      "default": "modern",
      "minLength": 1,
      "maxLength": 50
    },
    "color_scheme": {
      "description": "Color scheme (light/dark)",
      "type": "string",
      "enum": ["light", "dark"],
      "default": "light"
    },
    "admin_bar_background": {
      "description": "Admin bar background color",
      "type": "string",
      "format": "hex-color",
      "pattern": "^#[a-fA-F0-9]{6}$",
      "default": "#23282d"
    },
    "admin_bar_text_color": {
      "description": "Admin bar text color",
      "type": "string",
      "format": "hex-color",
      "pattern": "^#[a-fA-F0-9]{6}$",
      "default": "#ffffff"
    },
    "menu_background": {
      "description": "Menu background color",
      "type": "string",
      "format": "hex-color",
      "pattern": "^#[a-fA-F0-9]{6}$",
      "default": "#23282d"
    },
    "menu_text_color": {
      "description": "Menu text color",
      "type": "string",
      "format": "hex-color",
      "pattern": "^#[a-fA-F0-9]{6}$",
      "default": "#ffffff"
    },
    "menu_hover_background": {
      "description": "Menu hover background color",
      "type": "string",
      "format": "hex-color",
      "pattern": "^#[a-fA-F0-9]{6}$",
      "default": "#32373c"
    },
    "menu_hover_text_color": {
      "description": "Menu hover text color",
      "type": "string",
      "format": "hex-color",
      "pattern": "^#[a-fA-F0-9]{6}$",
      "default": "#00a0d2"
    },
    "menu_width": {
      "description": "Menu width in pixels",
      "type": "integer",
      "minimum": 100,
      "maximum": 400,
      "default": 160
    },
    "enable_animations": {
      "description": "Enable animations",
      "type": "boolean",
      "default": true
    },
    "animation_speed": {
      "description": "Animation speed in milliseconds",
      "type": "integer",
      "minimum": 100,
      "maximum": 1000,
      "default": 300
    },
    "glassmorphism_effects": {
      "description": "Enable glassmorphism effects",
      "type": "boolean",
      "default": false
    },
    "custom_css": {
      "description": "Custom CSS code",
      "type": "string",
      "default": "",
      "maxLength": 10000
    }
  },
  "additionalProperties": true
}
```

### Validation Rules

| Field | Type | Constraints | Default |
|-------|------|-------------|---------|
| `enable_plugin` | boolean | - | `true` |
| `theme` | string | 1-50 characters | `"modern"` |
| `color_scheme` | string | Must be "light" or "dark" | `"light"` |
| `admin_bar_background` | string | Valid hex color (#RRGGBB) | `"#23282d"` |
| `admin_bar_text_color` | string | Valid hex color (#RRGGBB) | `"#ffffff"` |
| `menu_background` | string | Valid hex color (#RRGGBB) | `"#23282d"` |
| `menu_text_color` | string | Valid hex color (#RRGGBB) | `"#ffffff"` |
| `menu_hover_background` | string | Valid hex color (#RRGGBB) | `"#32373c"` |
| `menu_hover_text_color` | string | Valid hex color (#RRGGBB) | `"#00a0d2"` |
| `menu_width` | integer | 100-400 | `160` |
| `enable_animations` | boolean | - | `true` |
| `animation_speed` | integer | 100-1000 | `300` |
| `glassmorphism_effects` | boolean | - | `false` |
| `custom_css` | string | Max 10,000 characters | `""` |

---

## Theme Schema

### Theme Object

```json
{
  "$schema": "http://json-schema.org/draft-04/schema#",
  "title": "theme",
  "type": "object",
  "properties": {
    "id": {
      "description": "Unique theme identifier",
      "type": "string",
      "pattern": "^[a-z0-9-]+$",
      "minLength": 3,
      "maxLength": 50
    },
    "name": {
      "description": "Theme display name",
      "type": "string",
      "minLength": 1,
      "maxLength": 100
    },
    "description": {
      "description": "Theme description",
      "type": "string",
      "maxLength": 500
    },
    "type": {
      "description": "Theme type (predefined or custom)",
      "type": "string",
      "enum": ["predefined", "custom"],
      "readonly": true
    },
    "readonly": {
      "description": "Whether theme is read-only",
      "type": "boolean",
      "readonly": true
    },
    "settings": {
      "description": "Theme settings",
      "type": "object",
      "$ref": "#/definitions/settings"
    },
    "metadata": {
      "description": "Theme metadata",
      "type": "object",
      "properties": {
        "author": {
          "type": "string"
        },
        "version": {
          "type": "string",
          "pattern": "^\\d+\\.\\d+(\\.\\d+)?$"
        },
        "created": {
          "type": "string",
          "format": "date-time"
        }
      }
    }
  },
  "required": ["id", "name", "settings"],
  "additionalProperties": false
}
```

### Create Theme Request

```json
{
  "$schema": "http://json-schema.org/draft-04/schema#",
  "title": "create_theme_request",
  "type": "object",
  "properties": {
    "id": {
      "description": "Unique theme identifier (lowercase letters, numbers, and hyphens only)",
      "type": "string",
      "pattern": "^[a-z0-9-]+$",
      "minLength": 3,
      "maxLength": 50
    },
    "name": {
      "description": "Theme display name",
      "type": "string",
      "minLength": 1,
      "maxLength": 100
    },
    "description": {
      "description": "Theme description",
      "type": "string",
      "maxLength": 500
    },
    "settings": {
      "description": "Theme settings (colors, styles, etc.)",
      "type": "object"
    }
  },
  "required": ["id", "name", "settings"],
  "additionalProperties": false
}
```

### Update Theme Request

```json
{
  "$schema": "http://json-schema.org/draft-04/schema#",
  "title": "update_theme_request",
  "type": "object",
  "properties": {
    "name": {
      "description": "Theme display name",
      "type": "string",
      "minLength": 1,
      "maxLength": 100
    },
    "description": {
      "description": "Theme description",
      "type": "string",
      "maxLength": 500
    },
    "settings": {
      "description": "Theme settings (colors, styles, etc.)",
      "type": "object"
    }
  },
  "additionalProperties": false
}
```

---

## Backup Schema

### Backup Object

```json
{
  "$schema": "http://json-schema.org/draft-04/schema#",
  "title": "backup",
  "type": "object",
  "properties": {
    "id": {
      "description": "Unique identifier for the backup",
      "type": "string",
      "pattern": "^backup_\\d+$"
    },
    "timestamp": {
      "description": "Unix timestamp when backup was created",
      "type": "integer",
      "minimum": 0
    },
    "date": {
      "description": "Human-readable date when backup was created",
      "type": "string",
      "format": "date-time"
    },
    "type": {
      "description": "Backup type (manual or automatic)",
      "type": "string",
      "enum": ["manual", "automatic"]
    },
    "settings": {
      "description": "Settings data stored in the backup",
      "type": "object"
    },
    "metadata": {
      "description": "Backup metadata",
      "type": "object",
      "properties": {
        "plugin_version": {
          "description": "Plugin version when backup was created",
          "type": "string",
          "pattern": "^\\d+\\.\\d+(\\.\\d+)?$"
        },
        "wordpress_version": {
          "description": "WordPress version when backup was created",
          "type": "string",
          "pattern": "^\\d+\\.\\d+(\\.\\d+)?$"
        },
        "user_id": {
          "description": "User ID who created the backup",
          "type": "integer",
          "minimum": 1
        },
        "note": {
          "description": "Optional note about the backup",
          "type": "string",
          "maxLength": 500
        }
      }
    }
  },
  "required": ["id", "timestamp", "type", "settings"],
  "additionalProperties": false
}
```

### Create Backup Request

```json
{
  "$schema": "http://json-schema.org/draft-04/schema#",
  "title": "create_backup_request",
  "type": "object",
  "properties": {
    "note": {
      "description": "Optional note about the backup",
      "type": "string",
      "maxLength": 500,
      "default": ""
    }
  },
  "additionalProperties": false
}
```

---

## Import/Export Schema

### Export Response

```json
{
  "$schema": "http://json-schema.org/draft-04/schema#",
  "title": "export_response",
  "type": "object",
  "properties": {
    "version": {
      "description": "Plugin version",
      "type": "string",
      "pattern": "^\\d+\\.\\d+(\\.\\d+)?$"
    },
    "exported_at": {
      "description": "Export timestamp",
      "type": "string",
      "format": "date-time"
    },
    "settings": {
      "description": "Exported settings",
      "type": "object"
    },
    "metadata": {
      "description": "Export metadata",
      "type": "object",
      "properties": {
        "wordpress_version": {
          "type": "string"
        },
        "php_version": {
          "type": "string"
        },
        "site_url": {
          "type": "string",
          "format": "uri"
        }
      }
    }
  },
  "required": ["version", "exported_at", "settings"],
  "additionalProperties": false
}
```

### Import Request

```json
{
  "$schema": "http://json-schema.org/draft-04/schema#",
  "title": "import_request",
  "type": "object",
  "properties": {
    "data": {
      "description": "Import data as JSON string or object",
      "oneOf": [
        {
          "type": "string"
        },
        {
          "type": "object",
          "properties": {
            "version": {
              "type": "string"
            },
            "settings": {
              "type": "object"
            }
          },
          "required": ["settings"]
        }
      ]
    },
    "create_backup": {
      "description": "Create backup before import",
      "type": "boolean",
      "default": true
    }
  },
  "required": ["data"],
  "additionalProperties": false
}
```

---

## Preview Schema

### Preview Request

```json
{
  "$schema": "http://json-schema.org/draft-04/schema#",
  "title": "preview_request",
  "type": "object",
  "properties": {
    "settings": {
      "description": "Settings to preview",
      "type": "object",
      "$ref": "#/definitions/settings"
    }
  },
  "required": ["settings"],
  "additionalProperties": false
}
```

### Preview Response

```json
{
  "$schema": "http://json-schema.org/draft-04/schema#",
  "title": "preview_response",
  "type": "object",
  "properties": {
    "css": {
      "description": "Generated CSS code",
      "type": "string"
    },
    "settings_count": {
      "description": "Number of settings processed",
      "type": "integer",
      "minimum": 0
    },
    "css_length": {
      "description": "Length of generated CSS in bytes",
      "type": "integer",
      "minimum": 0
    },
    "fallback": {
      "description": "Whether fallback CSS was used",
      "type": "boolean",
      "default": false
    }
  },
  "required": ["css"],
  "additionalProperties": false
}
```

---

## Diagnostics Schema

### Diagnostics Response

```json
{
  "$schema": "http://json-schema.org/draft-04/schema#",
  "title": "diagnostics_response",
  "type": "object",
  "properties": {
    "system": {
      "description": "System information",
      "type": "object",
      "properties": {
        "php_version": {
          "type": "string"
        },
        "wordpress_version": {
          "type": "string"
        },
        "server_software": {
          "type": "string"
        },
        "memory_limit": {
          "type": "string"
        },
        "max_execution_time": {
          "type": "integer"
        }
      }
    },
    "plugin": {
      "description": "Plugin information",
      "type": "object",
      "properties": {
        "version": {
          "type": "string"
        },
        "active": {
          "type": "boolean"
        },
        "rest_api_enabled": {
          "type": "boolean"
        }
      }
    },
    "settings": {
      "description": "Settings integrity check",
      "type": "object",
      "properties": {
        "valid": {
          "type": "boolean"
        },
        "total_settings": {
          "type": "integer"
        },
        "custom_settings": {
          "type": "integer"
        }
      }
    },
    "filesystem": {
      "description": "Filesystem checks",
      "type": "object",
      "properties": {
        "upload_dir_writable": {
          "type": "boolean"
        },
        "plugin_dir_writable": {
          "type": "boolean"
        }
      }
    },
    "conflicts": {
      "description": "Conflict detection",
      "type": "object",
      "properties": {
        "detected": {
          "type": "boolean"
        },
        "conflicting_plugins": {
          "type": "array",
          "items": {
            "type": "string"
          }
        }
      }
    },
    "performance": {
      "description": "Performance metrics",
      "type": "object",
      "properties": {
        "memory_usage": {
          "type": "string"
        },
        "peak_memory": {
          "type": "string"
        },
        "execution_time": {
          "type": "string"
        },
        "database_queries": {
          "type": "integer"
        }
      }
    },
    "recommendations": {
      "description": "Optimization recommendations",
      "type": "array",
      "items": {
        "type": "object",
        "properties": {
          "type": {
            "type": "string",
            "enum": ["performance", "security", "compatibility"]
          },
          "severity": {
            "type": "string",
            "enum": ["low", "medium", "high", "critical"]
          },
          "message": {
            "type": "string"
          }
        }
      }
    },
    "_metadata": {
      "description": "Response metadata",
      "type": "object",
      "properties": {
        "generated_at": {
          "type": "string",
          "format": "date-time"
        },
        "generated_timestamp": {
          "type": "integer"
        },
        "execution_time": {
          "type": "string"
        }
      }
    }
  },
  "additionalProperties": false
}
```

### Health Check Response

```json
{
  "$schema": "http://json-schema.org/draft-04/schema#",
  "title": "health_check_response",
  "type": "object",
  "properties": {
    "status": {
      "description": "Overall health status",
      "type": "string",
      "enum": ["healthy", "warning", "unhealthy"]
    },
    "checks": {
      "description": "Individual health checks",
      "type": "object",
      "patternProperties": {
        "^[a-z_]+$": {
          "type": "object",
          "properties": {
            "status": {
              "type": "string",
              "enum": ["pass", "warning", "fail"]
            },
            "message": {
              "type": "string"
            }
          },
          "required": ["status", "message"]
        }
      }
    }
  },
  "required": ["status", "checks"],
  "additionalProperties": false
}
```

---

## Common Schemas

### Success Response

```json
{
  "$schema": "http://json-schema.org/draft-04/schema#",
  "title": "success_response",
  "type": "object",
  "properties": {
    "success": {
      "description": "Whether the request was successful",
      "type": "boolean",
      "enum": [true]
    },
    "data": {
      "description": "Response data",
      "type": ["object", "array", "string", "number", "boolean", "null"]
    },
    "message": {
      "description": "Success message",
      "type": "string"
    },
    "timestamp": {
      "description": "Response timestamp",
      "type": "integer",
      "minimum": 0
    }
  },
  "required": ["success", "data", "timestamp"],
  "additionalProperties": false
}
```

### Error Response

```json
{
  "$schema": "http://json-schema.org/draft-04/schema#",
  "title": "error_response",
  "type": "object",
  "properties": {
    "code": {
      "description": "Error code",
      "type": "string",
      "pattern": "^[a-z_]+$"
    },
    "message": {
      "description": "Human-readable error message",
      "type": "string"
    },
    "data": {
      "description": "Additional error data",
      "type": "object",
      "properties": {
        "status": {
          "description": "HTTP status code",
          "type": "integer",
          "minimum": 400,
          "maximum": 599
        },
        "errors": {
          "description": "Field-specific errors",
          "type": "object",
          "patternProperties": {
            "^[a-z_]+$": {
              "type": "string"
            }
          }
        }
      },
      "required": ["status"]
    }
  },
  "required": ["code", "message", "data"],
  "additionalProperties": false
}
```

### Pagination Parameters

```json
{
  "$schema": "http://json-schema.org/draft-04/schema#",
  "title": "pagination_parameters",
  "type": "object",
  "properties": {
    "page": {
      "description": "Page number (1-indexed)",
      "type": "integer",
      "minimum": 1,
      "default": 1
    },
    "limit": {
      "description": "Number of items per page",
      "type": "integer",
      "minimum": 1,
      "maximum": 100,
      "default": 20
    },
    "offset": {
      "description": "Offset for pagination (alternative to page)",
      "type": "integer",
      "minimum": 0,
      "default": 0
    }
  },
  "additionalProperties": false
}
```

---

## Accessing Schemas via OPTIONS

All endpoints support OPTIONS requests to retrieve their JSON Schema:

### Example: Get Settings Schema

```http
OPTIONS /wp-json/mas-v2/v1/settings
```

**Response:**
```json
{
  "namespace": "mas-v2/v1",
  "methods": ["GET", "POST", "PUT", "DELETE"],
  "endpoints": [
    {
      "methods": ["GET"],
      "args": {}
    },
    {
      "methods": ["POST"],
      "args": {
        "enable_plugin": {
          "description": "Enable plugin functionality",
          "type": "boolean",
          "default": true
        },
        "menu_background": {
          "description": "Menu background color",
          "type": "string",
          "format": "hex-color",
          "pattern": "^#[a-fA-F0-9]{6}$"
        }
        // ... more fields
      }
    }
  ],
  "schema": {
    "$schema": "http://json-schema.org/draft-04/schema#",
    "title": "settings",
    "type": "object",
    "properties": {
      // ... schema definition
    }
  }
}
```

### Example: Get Theme Schema

```http
OPTIONS /wp-json/mas-v2/v1/themes
```

### Example: Get Backup Schema

```http
OPTIONS /wp-json/mas-v2/v1/backups
```

---

## Validation Examples

### Valid Settings Request

```json
{
  "menu_background": "#1e1e2e",
  "menu_text_color": "#ffffff",
  "menu_width": 200,
  "enable_animations": true,
  "animation_speed": 300
}
```

### Invalid Settings Request (Validation Errors)

```json
{
  "menu_background": "invalid-color",  // ❌ Not a valid hex color
  "menu_width": 50,                    // ❌ Below minimum (100)
  "animation_speed": 2000              // ❌ Above maximum (1000)
}
```

**Error Response:**
```json
{
  "code": "validation_failed",
  "message": "Settings validation failed",
  "data": {
    "status": 400,
    "errors": {
      "menu_background": "Must be a valid hex color (#RRGGBB)",
      "menu_width": "Must be between 100 and 400",
      "animation_speed": "Must be between 100 and 1000"
    }
  }
}
```

---

## Schema Validation Tools

### JavaScript Validation

Use [Ajv](https://ajv.js.org/) for client-side validation:

```javascript
import Ajv from 'ajv';

const ajv = new Ajv();
const schema = {
  type: 'object',
  properties: {
    menu_background: {
      type: 'string',
      pattern: '^#[a-fA-F0-9]{6}$'
    }
  }
};

const validate = ajv.compile(schema);
const valid = validate(data);

if (!valid) {
  console.error(validate.errors);
}
```

### PHP Validation

The plugin uses WordPress's built-in validation:

```php
register_rest_route('mas-v2/v1', '/settings', [
    'methods' => 'POST',
    'args' => [
        'menu_background' => [
            'type' => 'string',
            'format' => 'hex-color',
            'validate_callback' => function($value) {
                return preg_match('/^#[a-f0-9]{6}$/i', $value);
            }
        ]
    ]
]);
```

---

**Last Updated:** January 10, 2025  
**Schema Version:** 2.2.0
