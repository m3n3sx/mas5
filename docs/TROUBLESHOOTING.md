# MAS5 Plugin Troubleshooting Guide

## Quick Diagnostics

Run these commands in your browser console on the plugin settings page:

```javascript
// Check emergency mode is active
console.log('=== MAS5 DIAGNOSTICS ===');
console.log('Emergency Mode:', window.MASEmergencyMode);        // Should be true
console.log('Use New Frontend:', window.MASUseNewFrontend);     // Should be false
console.log('Disable Modules:', window.MASDisableModules);      // Should be true
console.log('Frontend Mode:', masV2Global?.frontendMode);       // Should be 'phase2-stable'
console.log('masV2Global:', typeof masV2Global);                // Should be 'object'

// Check loaded scripts
const scripts = Array.from(document.scripts).map(s => s.src.split('/').pop());
console.log('Loaded Scripts:', scripts.filter(s => s.includes('mas')));

// Expected: mas-rest-client.js, mas-settings-form-handler.js, simple-live-preview.js
// NOT expected: mas-admin-app.js, EventBus.js, StateManager.js
```

## Common Issues

### Issue 1: Settings Not Saving

**Symptoms:**
- Click "Save Settings" but changes don't persist
- Only some settings save, others revert
- No success message appears

**Diagnosis:**

```javascript
// Check AJAX configuration
console.log('AJAX URL:', masV2Global?.ajaxUrl);
console.log('Nonce:', masV2Global?.nonce);
console.log('REST URL:', masV2Global?.restUrl);
```

**Solutions:**

1. **Check browser console for errors**
   ```javascript
   // Look for:
   // - 403 Forbidden (nonce issue)
   // - 500 Server Error (PHP error)
   // - Network errors (connectivity)
   ```

2. **Verify nonce is valid**
   - Refresh the page to get a new nonce
   - Check if you're logged in as admin
   - Verify `manage_options` capability

3. **Test AJAX endpoint directly**
   ```javascript
   fetch(masV2Global.ajaxUrl, {
       method: 'POST',
       headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
       body: new URLSearchParams({
           action: 'mas_v2_save_settings',
           nonce: masV2Global.nonce,
           settings: JSON.stringify({ test: 'value' })
       })
   }).then(r => r.json()).then(console.log);
   ```

4. **Check PHP error logs**
   ```bash
   tail -f /path/to/wordpress/wp-content/debug.log
   ```

5. **Verify form fields have proper names**
   ```javascript
   // All form fields should have name attributes
   jQuery('#mas-v2-settings-form input, #mas-v2-settings-form select').each(function() {
       if (!this.name) console.log('Missing name:', this);
   });
   ```

6. **Check request payload size**
   - Large payloads may be rejected by server
   - Check `post_max_size` and `upload_max_filesize` in php.ini

### Issue 2: Live Preview Not Working

**Symptoms:**
- Color changes don't update preview
- Preview shows errors or blank
- Changes apply only after page reload

**Diagnosis:**

```javascript
// Check live preview is loaded
console.log('Live Preview:', typeof window.masLivePreview);

// Check preview container exists
console.log('Preview Container:', jQuery('#mas-live-preview').length);
```

**Solutions:**

1. **Verify simple-live-preview.js is loaded**
   ```javascript
   const hasLivePreview = Array.from(document.scripts)
       .some(s => s.src.includes('simple-live-preview.js'));
   console.log('Live Preview Loaded:', hasLivePreview);
   ```

2. **Check preview CSS endpoint**
   ```javascript
   fetch(masV2Global.ajaxUrl, {
       method: 'POST',
       headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
       body: new URLSearchParams({
           action: 'mas_v2_get_preview_css',
           nonce: masV2Global.nonce,
           settings: JSON.stringify({ admin_bar_bg: '#2271b1' })
       })
   }).then(r => r.json()).then(console.log);
   ```

3. **Verify no competing preview systems**
   ```javascript
   const competingScripts = Array.from(document.scripts)
       .filter(s => s.src.includes('LivePreviewManager') || 
                    s.src.includes('LivePreviewComponent'));
   console.log('Competing Systems:', competingScripts.length); // Should be 0
   ```

4. **Check CSS injection**
   ```javascript
   // Preview CSS should be in a <style> tag
   const previewStyle = document.querySelector('style#mas-live-preview-css');
   console.log('Preview Style:', previewStyle?.textContent.length, 'chars');
   ```

5. **Test with simple change**
   - Change admin bar background color
   - Wait 300ms (debounce delay)
   - Check if preview updates

### Issue 3: JavaScript Errors in Console

**Symptoms:**
- Console shows errors related to undefined variables
- Errors mention EventBus, StateManager, or APIClient
- Plugin functionality broken

**Diagnosis:**

```javascript
// Check for Phase 3 scripts (should NOT be loaded)
const phase3Scripts = [
    'mas-admin-app.js',
    'EventBus.js',
    'StateManager.js',
    'APIClient.js'
];

const loadedPhase3 = Array.from(document.scripts)
    .filter(s => phase3Scripts.some(ps => s.src.includes(ps)));

console.log('Phase 3 Scripts (should be empty):', loadedPhase3);
```

**Solutions:**

1. **Clear browser cache**
   - Hard reload: Ctrl+Shift+R (Windows/Linux) or Cmd+Shift+R (Mac)
   - Clear all cached files
   - Close and reopen browser

2. **Verify emergency mode is active**
   ```javascript
   if (window.MASEmergencyMode !== true) {
       console.error('Emergency mode not active!');
   }
   ```

3. **Check enqueueAssets() method**
   - Open `modern-admin-styler-v2.php`
   - Verify emergency stabilization code is present
   - Look for inline script setting emergency flags

4. **Clear WordPress object cache**
   ```bash
   wp cache flush
   ```

5. **Deactivate and reactivate plugin**
   - WordPress Admin → Plugins
   - Deactivate "Modern Admin Styler V2"
   - Reactivate

### Issue 4: Phase 3 Scripts Still Loading

**Symptoms:**
- Network tab shows Phase 3 scripts
- Console shows EventBus/StateManager errors
- Emergency mode flags not set

**Diagnosis:**

```bash
# Run PHP diagnostics
php test-emergency-fix-diagnostics.php
```

**Solutions:**

1. **Verify modern-admin-styler-v2.php has emergency fix**
   ```bash
   grep -n "EMERGENCY STABILIZATION" modern-admin-styler-v2.php
   ```
   Should show comments in `enqueueAssets()` method

2. **Check feature flags service**
   ```bash
   grep -n "use_new_frontend" includes/services/class-mas-feature-flags-service.php
   ```
   Should return `false` hardcoded

3. **Clear all caches**
   ```bash
   # WordPress cache
   wp cache flush
   
   # Object cache (if using Redis/Memcached)
   wp cache flush --redis
   
   # Opcache
   wp eval 'opcache_reset();'
   ```

4. **Check for plugin conflicts**
   - Deactivate all other plugins
   - Test if issue persists
   - Reactivate plugins one by one

5. **Verify file permissions**
   ```bash
   ls -la modern-admin-styler-v2.php
   ls -la includes/services/class-mas-feature-flags-service.php
   ```
   Should be readable by web server

### Issue 5: Import/Export Not Working

**Symptoms:**
- Export button doesn't download file
- Import fails with error
- Imported settings don't apply

**Solutions:**

1. **Check AJAX handlers are registered**
   ```php
   // In WordPress, check:
   has_action('wp_ajax_mas_v2_export_settings');
   has_action('wp_ajax_mas_v2_import_settings');
   ```

2. **Verify file permissions**
   - Export requires write permissions to temp directory
   - Import requires read permissions

3. **Check JSON validity**
   ```javascript
   // For import issues, validate JSON:
   try {
       JSON.parse(fileContent);
       console.log('Valid JSON');
   } catch(e) {
       console.error('Invalid JSON:', e);
   }
   ```

4. **Test with small export**
   - Export settings
   - Open file in text editor
   - Verify JSON structure
   - Try importing

### Issue 6: Feature Flags Page Shows Errors

**Symptoms:**
- Feature flags page doesn't load
- Emergency notice not displayed
- PHP errors on page

**Solutions:**

1. **Check class file exists**
   ```bash
   ls -la includes/admin/class-mas-feature-flags-admin.php
   ```

2. **Verify class is loaded**
   ```bash
   grep -n "class-mas-feature-flags-admin" modern-admin-styler-v2.php
   ```

3. **Check PHP error logs**
   ```bash
   tail -f wp-content/debug.log
   ```

4. **Test class instantiation**
   ```php
   // In WordPress:
   require_once 'includes/admin/class-mas-feature-flags-admin.php';
   $admin = new MAS_Feature_Flags_Admin();
   ```

### Issue 7: Performance Issues

**Symptoms:**
- Page loads slowly (> 2 seconds)
- High memory usage
- Browser becomes unresponsive

**Solutions:**

1. **Check script loading**
   ```javascript
   // Measure load time
   performance.getEntriesByType('resource')
       .filter(r => r.name.includes('mas'))
       .forEach(r => console.log(r.name, r.duration + 'ms'));
   ```

2. **Verify only Phase 2 scripts load**
   - Should be 3 scripts total (93KB)
   - If more, Phase 3 may still be loading

3. **Check for memory leaks**
   ```javascript
   // Take heap snapshot before and after interactions
   // Chrome DevTools → Memory → Take Snapshot
   ```

4. **Optimize database queries**
   ```bash
   # Enable query monitor plugin
   wp plugin install query-monitor --activate
   ```

5. **Check server resources**
   ```bash
   # PHP memory limit
   php -i | grep memory_limit
   
   # Should be at least 128M
   ```

## Diagnostic Tools

### PHP Diagnostics

```bash
# Run comprehensive diagnostics
php test-emergency-fix-diagnostics.php

# Expected output:
# Total Tests: 5
# Passed: 4-5
# Failed: 0
# Warnings: 0-1 (acceptable)
```

### Browser Diagnostics

```
Open: test-mas5-functionality.html
Click: "Run All Tests"

Expected:
- Total Tests: 15+
- Passed: 15+
- Failed: 0
- Warnings: 0-2 (acceptable)
```

### Manual Verification

1. **Settings Save Test**
   - Change admin bar background color
   - Click "Save Settings"
   - Reload page
   - Verify color persisted

2. **Live Preview Test**
   - Change a color setting
   - Verify preview updates within 1 second
   - Try multiple rapid changes
   - Verify no errors in console

3. **Import/Export Test**
   - Click "Export Settings"
   - Verify file downloads
   - Click "Import Settings"
   - Select exported file
   - Verify settings apply

## Getting Help

### Before Reporting Issues

1. Run diagnostic tests
2. Check browser console
3. Check PHP error logs
4. Try in different browser
5. Disable other plugins

### Information to Provide

When reporting issues, include:

1. **Diagnostic Output**
   ```bash
   php test-emergency-fix-diagnostics.php > diagnostics.txt
   ```

2. **Browser Console Errors**
   - Screenshot or copy/paste errors
   - Include full error messages

3. **Environment Info**
   - WordPress version
   - PHP version
   - Browser and version
   - Active plugins
   - Active theme

4. **Steps to Reproduce**
   - Exact steps that cause the issue
   - Expected behavior
   - Actual behavior

### Emergency Rollback

If plugin is completely broken:

1. **Via WordPress Admin**
   - Plugins → Deactivate "Modern Admin Styler V2"

2. **Via FTP/SSH**
   ```bash
   # Rename plugin directory
   mv wp-content/plugins/modern-admin-styler-v2 wp-content/plugins/modern-admin-styler-v2.disabled
   ```

3. **Via Database**
   ```sql
   UPDATE wp_options 
   SET option_value = '' 
   WHERE option_name = 'active_plugins';
   ```

## Prevention

### Regular Maintenance

1. **Run diagnostics monthly**
   ```bash
   php test-emergency-fix-diagnostics.php
   ```

2. **Monitor JavaScript console**
   - Check for new errors
   - Verify no Phase 3 scripts load

3. **Test after WordPress updates**
   - Core updates
   - Plugin updates
   - Theme updates

4. **Keep backups**
   - Export settings regularly
   - Keep database backups
   - Keep file backups

### Best Practices

1. ✅ Test changes in staging first
2. ✅ Keep WordPress and PHP updated
3. ✅ Monitor error logs
4. ✅ Use child themes
5. ✅ Document customizations

## Additional Resources

- **Architecture:** See `EMERGENCY-ARCHITECTURE.md`
- **Development:** See `DEVELOPMENT.md`
- **Verification:** See `EMERGENCY-FIX-VERIFICATION-GUIDE.md`
- **Quick Reference:** See `EMERGENCY-STABILIZATION-QUICK-REFERENCE.md`

---

**Last Updated:** 2025-01-07  
**Version:** 2.2.1 (Emergency Fix)
