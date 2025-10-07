# Modern Admin Styler V2 - API Documentation

Welcome to the Modern Admin Styler V2 REST API documentation! This directory contains comprehensive documentation for integrating with and using the plugin's REST API.

---

## 📚 Documentation Index

### Getting Started

1. **[API Documentation](API-DOCUMENTATION.md)** - Complete REST API reference
   - All endpoints with examples
   - Request/response formats
   - Authentication guide
   - Rate limiting information

2. **[Developer Guide](DEVELOPER-GUIDE.md)** - Integration guide for developers
   - JavaScript client implementation
   - Common use cases
   - Error handling
   - Migration from AJAX
   - Best practices

### Reference

3. **[JSON Schemas](JSON-SCHEMAS.md)** - JSON Schema definitions
   - Request/response schemas
   - Validation rules
   - Data structures
   - Schema access via OPTIONS

4. **[Error Codes](ERROR-CODES.md)** - Complete error reference
   - All error codes and meanings
   - Causes and solutions
   - Troubleshooting guide
   - Best practices

5. **[API Changelog](API-CHANGELOG.md)** - Version history and changes
   - Breaking changes
   - Deprecation notices
   - Migration guides
   - Future roadmap

### Testing

6. **[Postman Collection](Modern-Admin-Styler-V2.postman_collection.json)** - Ready-to-use API collection
   - All endpoints configured
   - Example requests
   - Response examples

7. **[Postman Environment](Modern-Admin-Styler-V2.postman_environment.json)** - Environment variables
   - Base URL configuration
   - Nonce management
   - Variable templates

---

## 🚀 Quick Start

### 1. Import Postman Collection

The fastest way to explore the API:

1. Open Postman
2. Import `Modern-Admin-Styler-V2.postman_collection.json`
3. Import `Modern-Admin-Styler-V2.postman_environment.json`
4. Set your `base_url` and `nonce` in the environment
5. Start making requests!

### 2. JavaScript Client

Use the provided JavaScript client:

```javascript
// Initialize client
const client = new MASRestClient();

// Get settings
const settings = await client.getSettings();
console.log(settings.data);

// Update settings
await client.updateSettings({
  menu_background: '#1e1e2e',
  enable_animations: true
});

// Apply theme
await client.applyTheme('dark-blue');
```

### 3. cURL Examples

Test endpoints with cURL:

```bash
# Get settings
curl -X GET \
  'https://your-site.com/wp-json/mas-v2/v1/settings' \
  -H 'X-WP-Nonce: your-nonce'

# Save settings
curl -X POST \
  'https://your-site.com/wp-json/mas-v2/v1/settings' \
  -H 'Content-Type: application/json' \
  -H 'X-WP-Nonce: your-nonce' \
  -d '{"menu_background": "#1e1e2e"}'
```

---

## 📖 Documentation Structure

### For First-Time Users

Start here if you're new to the API:

1. Read [API Documentation](API-DOCUMENTATION.md) - Overview and basics
2. Review [Developer Guide](DEVELOPER-GUIDE.md) - Integration examples
3. Import [Postman Collection](Modern-Admin-Styler-V2.postman_collection.json) - Test endpoints
4. Check [Error Codes](ERROR-CODES.md) - Understand error handling

### For Experienced Developers

Quick reference materials:

- [JSON Schemas](JSON-SCHEMAS.md) - Data structures and validation
- [Error Codes](ERROR-CODES.md) - Error reference
- [API Changelog](API-CHANGELOG.md) - Version changes

### For Migrating from AJAX

If you're migrating from the legacy AJAX system:

1. Read [API Changelog](API-CHANGELOG.md) - Breaking changes
2. Follow [Developer Guide - Migration](DEVELOPER-GUIDE.md#migration-from-ajax) - Step-by-step guide
3. Review [Error Codes](ERROR-CODES.md) - New error handling

---

## 🔑 Key Concepts

### Authentication

All endpoints require WordPress cookie authentication with nonce:

```javascript
headers: {
  'X-WP-Nonce': wpApiSettings.nonce
}
```

### Base URL

All endpoints are accessed via:

```
https://your-site.com/wp-json/mas-v2/v1/
```

### Response Format

All successful responses follow this format:

```json
{
  "success": true,
  "data": { ... },
  "message": "Operation completed successfully",
  "timestamp": 1704902400
}
```

### Error Format

All errors follow this format:

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

## 📋 Available Endpoints

### Settings
- `GET /settings` - Get current settings
- `POST /settings` - Save settings (full)
- `PUT /settings` - Update settings (partial)
- `DELETE /settings` - Reset to defaults

### Themes
- `GET /themes` - List all themes
- `GET /themes/{id}` - Get specific theme
- `POST /themes` - Create custom theme
- `PUT /themes/{id}` - Update theme
- `DELETE /themes/{id}` - Delete theme
- `POST /themes/{id}/apply` - Apply theme

### Backups
- `GET /backups` - List backups
- `GET /backups/{id}` - Get backup
- `POST /backups` - Create backup
- `POST /backups/{id}/restore` - Restore backup
- `DELETE /backups/{id}` - Delete backup
- `GET /backups/statistics` - Get statistics

### Import/Export
- `GET /export` - Export settings
- `POST /import` - Import settings

### Preview
- `POST /preview` - Generate preview CSS

### Diagnostics
- `GET /diagnostics` - Get diagnostics
- `GET /diagnostics/health` - Health check
- `GET /diagnostics/performance` - Performance metrics

---

## 🛠️ Tools and Resources

### Postman

- **Collection:** Pre-configured requests for all endpoints
- **Environment:** Variable templates for easy configuration
- **Examples:** Sample requests and responses

### JavaScript Client

Complete client implementation with:
- All endpoint methods
- Error handling
- Rate limiting support
- Caching support

### JSON Schemas

- Request validation
- Response validation
- Data structure documentation
- Available via OPTIONS requests

---

## 🔍 Common Use Cases

### 1. Save Settings

```javascript
const settings = {
  menu_background: '#1e1e2e',
  menu_text_color: '#ffffff',
  enable_animations: true
};

await client.saveSettings(settings);
```

### 2. Apply Theme

```javascript
await client.applyTheme('dark-blue');
```

### 3. Create Backup

```javascript
await client.createBackup('Before major changes');
```

### 4. Live Preview

```javascript
const preview = await client.generatePreview({
  menu_background: '#ff6b6b'
});
console.log(preview.data.css);
```

### 5. Export/Import

```javascript
// Export
const exported = await client.exportSettings();

// Import
await client.importSettings(exported.data, true);
```

---

## ⚠️ Important Notes

### Rate Limiting

- **Limit:** 60 requests per minute per user per endpoint
- **Headers:** Check `X-RateLimit-*` headers
- **Status:** 429 when exceeded

### Caching

- **ETag:** Use for conditional requests
- **Cache-Control:** Respect cache headers
- **304:** Not Modified response when cached

### Security

- **Nonce:** Required for all write operations
- **Permissions:** Requires `manage_options` capability
- **Validation:** All input is validated and sanitized

### Deprecation

- **AJAX Handlers:** Deprecated in v2.2.0, removed in v3.0.0
- **Legacy Fields:** Deprecated in v2.2.0, removed in v3.0.0
- **Migration:** See [API Changelog](API-CHANGELOG.md)

---

## 🐛 Troubleshooting

### Common Issues

1. **"Cookie nonce is invalid"**
   - Solution: Refresh the page to get a new nonce

2. **"You do not have permission"**
   - Solution: Ensure user has `manage_options` capability

3. **"Rate limit exceeded"**
   - Solution: Wait before making another request

4. **"Validation failed"**
   - Solution: Check field values against validation rules

See [Error Codes](ERROR-CODES.md) for complete troubleshooting guide.

### Debug Mode

Enable WordPress debug mode:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Check `wp-content/debug.log` for detailed errors.

---

## 📞 Support

### Documentation

- [API Documentation](API-DOCUMENTATION.md)
- [Developer Guide](DEVELOPER-GUIDE.md)
- [Error Codes](ERROR-CODES.md)

### Testing

- [Postman Collection](Modern-Admin-Styler-V2.postman_collection.json)
- [Postman Environment](Modern-Admin-Styler-V2.postman_environment.json)

### Community

- GitHub Issues: [repository-url]/issues
- Discussions: [repository-url]/discussions
- Support Forum: [forum-url]

---

## 📝 Version Information

- **Plugin Version:** 2.2.0
- **API Version:** v1
- **Last Updated:** January 10, 2025

---

## 🎯 Next Steps

1. **Read** [API Documentation](API-DOCUMENTATION.md) for complete reference
2. **Import** [Postman Collection](Modern-Admin-Styler-V2.postman_collection.json) to test
3. **Follow** [Developer Guide](DEVELOPER-GUIDE.md) for integration
4. **Check** [Error Codes](ERROR-CODES.md) for error handling
5. **Review** [API Changelog](API-CHANGELOG.md) for updates

---

Happy coding! 🚀
