# Feature Flags Quick Reference

## Quick Actions

### Enable New Frontend
```
Admin → MAS V2 → Feature Flags → Toggle "Use New Frontend" ON → Save
```

### Disable New Frontend
```
Admin → MAS V2 → Feature Flags → Click "Switch to Legacy Frontend"
```

### Emergency Rollback
```php
// Add to wp-config.php
define('MAS_V2_USE_NEW_FRONTEND', false);
```

## Feature Flags

| Flag | Default | Description |
|------|---------|-------------|
| `use_new_frontend` | OFF | Use Phase 3 architecture |
| `enable_live_preview` | ON | Real-time preview |
| `enable_advanced_effects` | ON | Visual effects |
| `enable_theme_presets` | ON | Theme management |
| `enable_backup_system` | ON | Backup/restore |
| `enable_diagnostics` | ON | Diagnostic tools |
| `enable_analytics` | OFF | Usage tracking |
| `enable_webhooks` | OFF | Webhook notifications |
| `debug_mode` | OFF | Debug logging |
| `performance_mode` | OFF | Performance optimizations |

## PHP API

```php
// Get service
$flags = MAS_Feature_Flags_Service::get_instance();

// Check flag
$flags->is_enabled('use_new_frontend');
$flags->use_new_frontend();
$flags->get_frontend_mode(); // 'new' or 'legacy'

// Set flag
$flags->enable('use_new_frontend');
$flags->disable('use_new_frontend');
$flags->set_flag('debug_mode', true);

// Reset
$flags->reset_to_defaults();

// Export for JS
$flags->export_for_js();
```

## JavaScript API

```javascript
// Check mode
window.MASUseNewFrontend // true/false
window.masV2Global.frontendMode // 'new' or 'legacy'
window.masV2Global.featureFlags.useNewFrontend

// Access app (new frontend only)
window.MASAdminApp
window.MASAdminApp.eventBus
window.MASAdminApp.stateManager
window.MASAdminApp.apiClient

// Legacy bridge (new frontend only)
window.MASLegacyBridge
window.masLegacyAjax
window.masLegacyEvents
```

## Override Methods

### Via Constant
```php
// wp-config.php
define('MAS_V2_USE_NEW_FRONTEND', true);
```

### Via Query Parameter (Admins Only)
```
?mas_use_new_frontend=1  // Enable
?mas_use_new_frontend=0  // Disable
```

## Troubleshooting

### Settings Not Saving
1. Check console for errors
2. Verify REST API: `/wp-json/mas-v2/v1/settings`
3. Clear cache
4. Try legacy mode

### JavaScript Errors
1. Clear browser cache
2. Check plugin conflicts
3. Enable debug mode
4. Try different browser

### Dual Handlers
1. Verify feature flag
2. Clear object cache
3. Check `MASDisableModules`

### Performance Issues
1. Enable performance mode
2. Disable advanced effects
3. Check network
4. Verify caching

## Testing Checklist

- [ ] Enable new frontend
- [ ] Test settings save
- [ ] Test live preview
- [ ] Test theme switching
- [ ] Test backup/restore
- [ ] Disable new frontend
- [ ] Verify settings preserved
- [ ] No errors in console

## Support

- **Migration Guide:** `docs/PHASE3-MIGRATION-GUIDE.md`
- **Usage Guide:** `.kiro/specs/rest-api-migration/PHASE3-TASK10-USAGE-GUIDE.md`
- **Test Checklist:** `tests/MIGRATION-TEST-CHECKLIST.md`

## Emergency Contacts

- **Rollback:** Add constant to wp-config.php
- **Support:** Check GitHub issues
- **Documentation:** See docs/ folder
