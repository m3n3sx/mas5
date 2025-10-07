# Phase 3 Frontend Migration Guide

## Overview

This guide helps you migrate from the legacy AJAX-based frontend to the new Phase 3 component-based architecture.

## What Changed?

### Phase 2 (Legacy)
- AJAX-based handlers (`admin-settings-simple.js`, `mas-settings-form-handler.js`)
- jQuery-dependent
- Dual handler conflicts
- Limited error handling
- No component architecture

### Phase 3 (New)
- Component-based architecture
- Unified entry point (`mas-admin-app.js`)
- Event bus for conflict-free communication
- Centralized state management
- Progressive enhancement with fallbacks
- Comprehensive error handling
- Better testability

## Migration Steps

### For Plugin Users

1. **Enable New Frontend**
   - Go to **MAS V2 → Feature Flags**
   - Toggle "Use New Frontend" to ON
   - Click "Save Feature Flags"

2. **Test Your Settings**
   - Go to **MAS V2 → Settings**
   - Make changes and save
   - Verify all settings work correctly

3. **Rollback if Needed**
   - Go to **MAS V2 → Feature Flags**
   - Toggle "Use New Frontend" to OFF
   - Or click "Switch to Legacy Frontend" button

### For Developers

#### 1. Update Script Dependencies

**Old (Phase 2):**
```php
wp_enqueue_script(
    'my-custom-script',
    'path/to/script.js',
    ['jquery', 'mas-v2-settings-form-handler'],
    '1.0.0',
    true
);
```

**New (Phase 3):**
```php
wp_enqueue_script(
    'my-custom-script',
    'path/to/script.js',
    ['mas-v2-admin-app'],
    '1.0.0',
    true
);
```

#### 2. Update Event Listeners

**Old (Phase 2):**
```javascript
jQuery(document).on('mas:settings:changed', function(e, data) {
    console.log('Settings changed:', data);
});
```

**New (Phase 3):**
```javascript
// Access the global app instance
if (window.MASAdminApp) {
    window.MASAdminApp.eventBus.on('settings:changed', function(event) {
        console.log('Settings changed:', event.data);
    });
}
```

#### 3. Update API Calls

**Old (Phase 2):**
```javascript
jQuery.ajax({
    url: ajaxurl,
    type: 'POST',
    data: {
        action: 'mas_v2_save_settings',
        nonce: masV2Global.nonce,
        settings: settings
    },
    success: function(response) {
        console.log('Saved:', response);
    }
});
```

**New (Phase 3):**
```javascript
// Access the API client
if (window.MASAdminApp) {
    window.MASAdminApp.apiClient.saveSettings(settings)
        .then(response => {
            console.log('Saved:', response);
        })
        .catch(error => {
            console.error('Error:', error);
        });
}
```

#### 4. Update State Access

**Old (Phase 2):**
```javascript
// Settings were stored in global variable
const settings = window.masV2Global.settings;
```

**New (Phase 3):**
```javascript
// Access centralized state
if (window.MASAdminApp) {
    const state = window.MASAdminApp.stateManager.getState();
    const settings = state.settings;
}
```

## Feature Flags

### Available Flags

| Flag | Description | Default |
|------|-------------|---------|
| `use_new_frontend` | Use Phase 3 frontend architecture | `false` |
| `enable_live_preview` | Enable real-time preview | `true` |
| `enable_advanced_effects` | Enable advanced visual effects | `true` |
| `debug_mode` | Enable debug logging | `false` |

### Programmatic Access

```php
// PHP
$flags_service = MAS_Feature_Flags_Service::get_instance();
$use_new = $flags_service->use_new_frontend();
```

```javascript
// JavaScript
const useNew = window.masV2Global.featureFlags.useNewFrontend;
```

### Override via Constant

```php
// In wp-config.php
define('MAS_V2_USE_NEW_FRONTEND', true);
```

### Override via Query Parameter (Admins Only)

```
?mas_use_new_frontend=1  // Enable
?mas_use_new_frontend=0  // Disable
```

## Compatibility

### Browser Support

**Phase 3 Requirements:**
- Modern browsers (Chrome 60+, Firefox 55+, Safari 11+, Edge 79+)
- ES6 support
- Promise support
- Fetch API support

**Fallback:**
- Legacy browsers automatically use Phase 2 frontend
- Polyfills provided via LegacyBridge.js

### WordPress Compatibility

- WordPress 5.8+
- PHP 7.4+
- REST API enabled

## Troubleshooting

### Issue: Settings Not Saving

**Solution:**
1. Check browser console for errors
2. Verify REST API is accessible: `/wp-json/mas-v2/v1/settings`
3. Check nonce is valid
4. Try switching to legacy frontend temporarily

### Issue: JavaScript Errors

**Solution:**
1. Clear browser cache
2. Check for plugin conflicts
3. Enable debug mode in Feature Flags
4. Check browser console for specific errors

### Issue: Dual Handlers Still Active

**Solution:**
1. Verify feature flag is set correctly
2. Clear WordPress object cache
3. Check that old scripts are not being enqueued
4. Look for `MASDisableModules` in console

### Issue: Performance Issues

**Solution:**
1. Enable performance mode in Feature Flags
2. Disable advanced effects temporarily
3. Check for slow network requests
4. Verify caching is working

## Testing Checklist

Before deploying to production:

- [ ] Test all settings save correctly
- [ ] Test live preview works
- [ ] Test theme switching
- [ ] Test backup/restore
- [ ] Test with different user roles
- [ ] Test in different browsers
- [ ] Test with JavaScript disabled (should fallback)
- [ ] Test rollback to legacy frontend
- [ ] Check browser console for errors
- [ ] Verify no duplicate handlers

## Rollback Plan

If issues occur:

1. **Immediate Rollback:**
   ```php
   // In wp-config.php
   define('MAS_V2_USE_NEW_FRONTEND', false);
   ```

2. **Via Admin UI:**
   - Go to **MAS V2 → Feature Flags**
   - Click "Switch to Legacy Frontend"

3. **Via Database:**
   ```sql
   UPDATE wp_options 
   SET option_value = 'a:1:{s:17:"use_new_frontend";b:0;}' 
   WHERE option_name = 'mas_v2_feature_flags';
   ```

## Support

- **Documentation:** `/docs/`
- **GitHub Issues:** [Report a bug](https://github.com/modern-admin-team/modern-admin-styler-v2/issues)
- **Developer Guide:** `docs/PHASE3-DEVELOPER-GUIDE.md`

## Timeline

- **Phase 2 (Current):** Stable, maintained
- **Phase 3 (New):** Beta, opt-in via feature flags
- **Phase 2 Deprecation:** TBD (minimum 6 months notice)
- **Phase 2 Removal:** TBD (minimum 12 months notice)

## FAQ

### Q: Do I need to migrate immediately?
**A:** No, Phase 2 will be supported for at least 12 months.

### Q: Will my custom code break?
**A:** Not if you use the compatibility layer. Test thoroughly before deploying.

### Q: Can I use both systems?
**A:** No, only one frontend system is active at a time.

### Q: What if I find a bug?
**A:** Report it on GitHub and switch back to legacy frontend.

### Q: How do I test without affecting users?
**A:** Use the query parameter override: `?mas_use_new_frontend=1`

## Additional Resources

- [Phase 3 Architecture Guide](PHASE3-ARCHITECTURE.md)
- [Component Development Guide](PHASE3-COMPONENT-GUIDE.md)
- [API Documentation](API-DOCUMENTATION.md)
- [Testing Guide](TESTING-GUIDE.md)
