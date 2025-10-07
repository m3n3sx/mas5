# AJAX Handler Deprecation Notice

## Overview

As of version 2.2.0, all AJAX handlers in Modern Admin Styler V2 are **deprecated** and will be removed in version 3.0.0 (planned for February 2025). This deprecation is part of our migration to a modern REST API architecture that provides better performance, security, and maintainability.

## Timeline

| Phase | Date | Status | Description |
|-------|------|--------|-------------|
| **Phase 1-3** | Jan 1-15, 2025 | ✅ Completed | REST API infrastructure and endpoints implemented |
| **Phase 4** | Jan 22-31, 2025 | 🔄 In Progress | AJAX handlers marked as deprecated with warnings |
| **Phase 5** | Feb 1, 2025 | 📅 Planned | AJAX handlers removed (optional) |

## What's Changing

### Deprecated AJAX Handlers

All of the following AJAX handlers are now deprecated:

| AJAX Handler | REST API Replacement | HTTP Method |
|--------------|---------------------|-------------|
| `mas_v2_save_settings` | `/wp-json/mas-v2/v1/settings` | POST |
| `mas_v2_reset_settings` | `/wp-json/mas-v2/v1/settings` | DELETE |
| `mas_v2_export_settings` | `/wp-json/mas-v2/v1/export` | GET |
| `mas_v2_import_settings` | `/wp-json/mas-v2/v1/import` | POST |
| `mas_v2_get_preview_css` | `/wp-json/mas-v2/v1/preview` | POST |
| `mas_v2_save_theme` | `/wp-json/mas-v2/v1/themes/{id}/apply` | POST |
| `mas_v2_diagnostics` | `/wp-json/mas-v2/v1/diagnostics` | GET |
| `mas_v2_list_backups` | `/wp-json/mas-v2/v1/backups` | GET |
| `mas_v2_restore_backup` | `/wp-json/mas-v2/v1/backups/{id}/restore` | POST |
| `mas_v2_create_backup` | `/wp-json/mas-v2/v1/backups` | POST |
| `mas_v2_delete_backup` | `/wp-json/mas-v2/v1/backups/{id}` | DELETE |

## Migration Guide

### For Plugin Users

**No action required!** The plugin automatically uses the REST API when available and falls back to AJAX if needed. You'll see deprecation warnings in the admin interface and browser console, but functionality remains unchanged.

### For Developers

If you've built custom integrations or extensions that use the AJAX handlers, you need to migrate to the REST API.

#### Before (AJAX)

```javascript
jQuery.ajax({
    url: ajaxurl,
    type: 'POST',
    data: {
        action: 'mas_v2_save_settings',
        nonce: masV2Data.nonce,
        menu_background: '#1e1e2e',
        menu_text_color: '#ffffff'
    },
    success: function(response) {
        console.log('Settings saved:', response);
    }
});
```

#### After (REST API)

```javascript
fetch('/wp-json/mas-v2/v1/settings', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': wpApiSettings.nonce
    },
    credentials: 'same-origin',
    body: JSON.stringify({
        menu_background: '#1e1e2e',
        menu_text_color: '#ffffff'
    })
})
.then(response => response.json())
.then(data => {
    console.log('Settings saved:', data);
});
```

#### Using the MAS REST Client

For easier migration, use the built-in REST client:

```javascript
// Initialize the client
const client = new MASRestClient();

// Save settings
await client.saveSettings({
    menu_background: '#1e1e2e',
    menu_text_color: '#ffffff'
});

// Get settings
const settings = await client.getSettings();

// Apply theme
await client.applyTheme('dark-blue');

// Create backup
await client.createBackup();

// Generate preview
const preview = await client.generatePreview(settings);
```

### Backward Compatibility

During the transition period (until February 2025), the plugin operates in **dual-mode**:

1. **REST API First**: Attempts to use REST API endpoints
2. **AJAX Fallback**: Falls back to AJAX if REST API fails
3. **No Duplicates**: Request deduplication prevents double operations

This ensures zero downtime during migration.

## Deprecation Warnings

### Admin Notices

When AJAX handlers are used, administrators will see a warning notice in the WordPress admin:

![Deprecation Warning](docs/images/deprecation-warning.png)

The notice includes:
- Clear explanation of the deprecation
- Migration timeline
- Links to migration resources
- Quick access to feature flags and migration status

### Console Warnings

Developers will see detailed warnings in the browser console:

```
🚨 MAS V2 AJAX Deprecation Warning
DEPRECATED: AJAX handler "mas_v2_save_settings" is deprecated and will be removed in version 3.0.0.
Use REST API endpoint "/wp-json/mas-v2/v1/settings" instead.

📅 Timeline:
  • Now - Jan 31, 2025: AJAX handlers work but show warnings
  • Feb 1, 2025: AJAX handlers will be removed (optional)

🔄 Migration Instructions:
  Old AJAX: jQuery.post(ajaxurl, {action: "mas_v2_save_settings", ...})
  New REST: fetch("/wp-json/mas-v2/v1/settings", {method: "POST", ...})

📚 Resources:
  • Migration Guide: https://github.com/your-repo/modern-admin-styler-v2/wiki/REST-API-Migration
  • Feature Flags: /wp-admin/admin.php?page=mas-feature-flags
  • Migration Status: /wp-admin/admin.php?page=mas-v2-settings&tab=migration
```

### HTTP Headers

All AJAX responses include deprecation headers:

```
X-MAS-Deprecated: true
X-MAS-Deprecated-Handler: mas_v2_save_settings
X-MAS-REST-Endpoint: /wp-json/mas-v2/v1/settings
X-MAS-Migration-Guide: https://github.com/your-repo/modern-admin-styler-v2/wiki/REST-API-Migration
```

## Feature Flags

You can control deprecation behavior using feature flags:

### Disable Deprecation Warnings

```php
// In wp-config.php or your theme's functions.php
add_filter('mas_v2_show_deprecation_warnings', '__return_false');
```

### Force AJAX Mode (Not Recommended)

```php
// Temporarily disable REST API and use only AJAX
add_filter('mas_v2_force_ajax_mode', '__return_true');
```

### Enable Debug Mode

```php
// Log detailed deprecation statistics
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Migration Status Dashboard

Check your migration status at:
**WordPress Admin → MAS V2 Settings → Migration Tab**

The dashboard shows:
- Current API mode (REST/AJAX/Dual)
- AJAX handler usage statistics
- Deprecation warnings count
- Migration progress
- Recommended actions

## Benefits of REST API

### For Users
- **Faster Performance**: Optimized caching and response times
- **Better Reliability**: Standardized error handling
- **Improved Security**: Enhanced authentication and validation

### For Developers
- **Modern Standards**: RESTful architecture with JSON
- **Better Documentation**: Self-documenting API with schemas
- **Easier Testing**: Standard HTTP testing tools work
- **Type Safety**: JSON Schema validation
- **Extensibility**: Easy to extend and customize

## Resources

### Documentation
- [Complete API Documentation](docs/API-DOCUMENTATION.md)
- [Developer Guide](docs/DEVELOPER-GUIDE.md)
- [JSON Schemas](docs/JSON-SCHEMAS.md)
- [Error Codes Reference](docs/ERROR-CODES.md)
- [API Changelog](docs/API-CHANGELOG.md)

### Testing Tools
- [Postman Collection](docs/Modern-Admin-Styler-V2.postman_collection.json)
- [REST API Quick Start](REST-API-QUICK-START.md)
- [Testing Guide](tests/TESTING-GUIDE.md)

### Support
- [GitHub Issues](https://github.com/your-repo/modern-admin-styler-v2/issues)
- [Migration Wiki](https://github.com/your-repo/modern-admin-styler-v2/wiki/REST-API-Migration)
- [Community Forum](https://wordpress.org/support/plugin/modern-admin-styler-v2/)

## FAQ

### Q: Do I need to do anything right now?
**A:** No, if you're just using the plugin normally. The migration is automatic. Only developers with custom integrations need to update their code.

### Q: Will my site break when AJAX handlers are removed?
**A:** No, the plugin will continue working with the REST API. The dual-mode ensures a smooth transition.

### Q: Can I keep using AJAX handlers?
**A:** Yes, until February 2025. After that, they will be removed. We recommend migrating to REST API as soon as possible.

### Q: What if I have a custom extension that uses AJAX?
**A:** You need to update your extension to use the REST API. See the migration guide above or contact support for assistance.

### Q: How do I test the REST API before migrating?
**A:** Use the included Postman collection or the REST API Quick Start guide. You can also enable debug mode to see detailed logs.

### Q: Will this affect my existing settings?
**A:** No, your settings are safe. The migration only changes how the plugin communicates with the server, not how data is stored.

### Q: Can I disable deprecation warnings?
**A:** Yes, use the feature flag `mas_v2_show_deprecation_warnings` to disable them. However, we recommend keeping them enabled to stay informed.

### Q: What happens if the REST API fails?
**A:** The plugin automatically falls back to AJAX during the transition period. After February 2025, proper error handling will be in place.

## Version History

- **2.2.0** (January 2025): REST API implemented, AJAX handlers deprecated
- **2.1.0** (December 2024): REST API infrastructure setup
- **2.0.0** (November 2024): Major refactoring and improvements
- **3.0.0** (February 2025): AJAX handlers removed (planned)

## Contact

For questions or assistance with migration:
- Email: support@example.com
- GitHub: https://github.com/your-repo/modern-admin-styler-v2
- WordPress Forum: https://wordpress.org/support/plugin/modern-admin-styler-v2/

---

**Last Updated:** January 10, 2025  
**Plugin Version:** 2.2.0  
**WordPress Compatibility:** 5.0+  
**PHP Compatibility:** 7.4+
