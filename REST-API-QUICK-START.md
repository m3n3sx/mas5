# REST API Quick Start Guide

## For Developers: Using the MAS V2 REST API

### Overview

The Modern Admin Styler V2 REST API provides a modern, standardized way to interact with plugin settings and features. This guide shows you how to use the infrastructure that was set up in Phase 1.

## Base URL

All REST API endpoints are available at:
```
https://your-site.com/wp-json/mas-v2/v1/
```

## Authentication

### For JavaScript (in WordPress Admin)

The REST API uses WordPress cookie authentication with nonces:

```javascript
// Get the nonce from wpApiSettings (automatically available in admin)
const nonce = wpApiSettings.nonce;

// Make a request
fetch(wpApiSettings.root + 'mas-v2/v1/settings', {
    method: 'GET',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': nonce
    },
    credentials: 'same-origin'
})
.then(response => response.json())
.then(data => console.log(data));
```

### For External Tools (Postman, cURL)

You'll need to authenticate using WordPress application passwords or other authentication methods.

## Creating a New Controller

### Step 1: Create Controller Class

Create a new file in `includes/api/`:

```php
<?php
// includes/api/class-mas-example-controller.php

class MAS_Example_Controller extends MAS_REST_Controller {
    
    public function register_routes() {
        // GET /example
        register_rest_route($this->namespace, '/example', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_example'],
            'permission_callback' => [$this, 'check_permission'],
        ]);
        
        // POST /example
        register_rest_route($this->namespace, '/example', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'create_example'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'name' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);
    }
    
    public function get_example($request) {
        $data = ['message' => 'Hello from REST API'];
        return $this->success_response($data);
    }
    
    public function create_example($request) {
        $name = $request->get_param('name');
        
        if (empty($name)) {
            return $this->error_response(
                'Name is required',
                'missing_name',
                400
            );
        }
        
        $data = ['created' => $name];
        return $this->success_response($data, 'Example created', 201);
    }
}
```

### Step 2: Register Controller

Add to `includes/class-mas-rest-api.php` in the `load_dependencies()` method:

```php
// Load example controller
if (file_exists($controllers_dir . 'class-mas-example-controller.php')) {
    require_once $controllers_dir . 'class-mas-example-controller.php';
}
```

Add to `register_controllers()` method:

```php
// Example controller
if (class_exists('MAS_Example_Controller')) {
    $this->register_controller('example', 'MAS_Example_Controller');
}
```

## Using the Validation Service

### Basic Validation

```php
// Get validation service
$validator = MAS_REST_API::get_instance()->get_service('validation');

// Validate color
if (!$validator->validate_color($color)) {
    return $this->error_response('Invalid color format', 'invalid_color', 400);
}

// Validate CSS unit
if (!$validator->validate_css_unit($width)) {
    return $this->error_response('Invalid CSS unit', 'invalid_unit', 400);
}

// Validate boolean
if (!$validator->validate_boolean($enabled)) {
    return $this->error_response('Invalid boolean value', 'invalid_boolean', 400);
}
```

### Schema Validation

```php
$schema = [
    'menu_background' => [
        'type' => 'color',
        'required' => true
    ],
    'menu_width' => [
        'type' => 'css_unit',
        'required' => false
    ],
];

$result = $validator->validate_settings($data, $schema);

if (!$result['valid']) {
    return $this->error_response(
        'Validation failed',
        'validation_error',
        400,
        ['errors' => $result['errors']]
    );
}
```

### Field Aliases

```php
// Automatically convert old field names to new ones
$data = [
    'menu_bg' => '#ffffff',      // Old name
    'menu_txt' => '#000000'      // Old name
];

$normalized = $validator->apply_field_aliases($data);
// Result:
// [
//     'menu_background' => '#ffffff',
//     'menu_text_color' => '#000000'
// ]
```

## Response Formats

### Success Response

```php
return $this->success_response(
    ['setting' => 'value'],           // Data
    'Settings saved successfully',     // Message (optional)
    200                                // Status code (default: 200)
);
```

Returns:
```json
{
    "success": true,
    "data": {
        "setting": "value"
    },
    "message": "Settings saved successfully",
    "timestamp": 1234567890
}
```

### Error Response

```php
return $this->error_response(
    'Something went wrong',            // Message
    'error_code',                      // Code
    400,                               // Status code
    ['additional' => 'data']           // Additional data (optional)
);
```

Returns:
```json
{
    "code": "error_code",
    "message": "Something went wrong",
    "data": {
        "status": 400,
        "additional": "data"
    }
}
```

## Common HTTP Status Codes

- `200` - OK (successful GET, PUT)
- `201` - Created (successful POST)
- `204` - No Content (successful DELETE)
- `400` - Bad Request (validation error)
- `401` - Unauthorized (not logged in)
- `403` - Forbidden (insufficient permissions)
- `404` - Not Found (resource doesn't exist)
- `500` - Internal Server Error (server error)

## JavaScript Client Example

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
        
        try {
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
        } catch (error) {
            console.error('REST API Error:', error);
            throw error;
        }
    }
    
    // GET request
    async get(endpoint) {
        return this.request(endpoint, { method: 'GET' });
    }
    
    // POST request
    async post(endpoint, data) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }
    
    // PUT request
    async put(endpoint, data) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    }
    
    // DELETE request
    async delete(endpoint) {
        return this.request(endpoint, { method: 'DELETE' });
    }
}

// Usage
const client = new MASRestClient();

// Get data
const data = await client.get('/settings');

// Create/update data
const result = await client.post('/settings', {
    menu_background: '#1e1e2e',
    menu_text_color: '#ffffff'
});
```

## Testing Your Endpoints

### Using Browser Console

```javascript
// Test GET request
fetch(wpApiSettings.root + 'mas-v2/v1/settings', {
    headers: { 'X-WP-Nonce': wpApiSettings.nonce },
    credentials: 'same-origin'
})
.then(r => r.json())
.then(console.log);

// Test POST request
fetch(wpApiSettings.root + 'mas-v2/v1/settings', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': wpApiSettings.nonce
    },
    credentials: 'same-origin',
    body: JSON.stringify({
        menu_background: '#1e1e2e'
    })
})
.then(r => r.json())
.then(console.log);
```

### Using cURL

```bash
# GET request
curl -X GET \
  'https://your-site.com/wp-json/mas-v2/v1/settings' \
  -H 'Cookie: wordpress_logged_in_...' \
  -H 'X-WP-Nonce: your-nonce'

# POST request
curl -X POST \
  'https://your-site.com/wp-json/mas-v2/v1/settings' \
  -H 'Content-Type: application/json' \
  -H 'Cookie: wordpress_logged_in_...' \
  -H 'X-WP-Nonce: your-nonce' \
  -d '{"menu_background":"#1e1e2e"}'
```

## Debugging

### Enable Debug Mode

Add to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Check Logs

Errors are logged to `wp-content/debug.log` when WP_DEBUG is enabled.

### Common Issues

**403 Forbidden:**
- Check if user has `manage_options` capability
- Verify nonce is being sent correctly
- Check if nonce is valid (not expired)

**404 Not Found:**
- Verify endpoint URL is correct
- Check if controller is registered
- Ensure permalinks are enabled

**500 Internal Server Error:**
- Check debug.log for PHP errors
- Verify all required files are loaded
- Check for syntax errors in controller

## Best Practices

1. **Always validate input** using the validation service
2. **Sanitize data** before saving to database
3. **Use proper HTTP status codes** for responses
4. **Include helpful error messages** for debugging
5. **Log errors** when WP_DEBUG is enabled
6. **Test with different user roles** to verify permissions
7. **Use field aliases** for backward compatibility
8. **Document your endpoints** with PHPDoc comments

## Resources

- [WordPress REST API Handbook](https://developer.wordpress.org/rest-api/)
- [REST API Authentication](https://developer.wordpress.org/rest-api/using-the-rest-api/authentication/)
- [HTTP Status Codes](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status)

---

**Need Help?**
- Check `verify-rest-api-infrastructure.php` for infrastructure tests
- Review `REST-API-PHASE1-IMPLEMENTATION.md` for implementation details
- See existing controllers in `includes/api/` for examples
