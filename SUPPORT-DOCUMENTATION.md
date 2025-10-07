# Support Documentation - Modern Admin Styler V2 (v2.2.0)

## Quick Reference Guide for Support Team

This document provides the support team with essential information for assisting users with the REST API migration release.

---

## Table of Contents

1. [What's New in v2.2.0](#whats-new-in-v220)
2. [Common Issues & Solutions](#common-issues--solutions)
3. [Troubleshooting Guide](#troubleshooting-guide)
4. [Support Scripts](#support-scripts)
5. [Escalation Procedures](#escalation-procedures)
6. [FAQ](#faq)

---

## What's New in v2.2.0

### Major Changes

**REST API Migration**
- All AJAX handlers replaced with modern REST API endpoints
- Improved performance and reliability
- Better error handling and validation
- Enhanced security features

**Backward Compatibility**
- Legacy AJAX handlers still functional (deprecated)
- Automatic fallback if REST API unavailable
- No breaking changes for existing users

**New Features**
- Enhanced diagnostics endpoint
- Improved backup/restore system
- Better import/export functionality
- Real-time live preview improvements

### User Impact

**Positive Changes:**
- Faster settings saves (up to 60% faster)
- More reliable operations
- Better error messages
- Improved admin interface responsiveness

**Potential Issues:**
- Some caching plugins may need configuration
- Permalink flush may be required
- Browser cache should be cleared

---

## Common Issues & Solutions

### Issue 1: "REST API Not Found" Error

**Symptoms:**
- Error message: "REST API endpoint not found"
- 404 errors in browser console
- Settings not saving

**Cause:**
- Permalink structure not updated
- REST API disabled
- .htaccess issues

**Solution:**
```
1. Go to Settings → Permalinks
2. Click "Save Changes" (no changes needed)
3. Clear browser cache
4. Test again
```

**WP-CLI Solution:**
```bash
wp rewrite flush
wp cache flush
```

**Success Criteria:**
- No 404 errors in console
- Settings save successfully
- REST API endpoints accessible

---

### Issue 2: Settings Not Saving

**Symptoms:**
- Changes don't persist after save
- "Save successful" message but no changes
- Settings revert to previous values

**Cause:**
- Caching conflict
- Permission issues
- Database connection problem

**Solution:**

**Step 1: Clear All Caches**
```
1. Clear browser cache (Ctrl+Shift+Delete)
2. Disable caching plugins temporarily
3. Clear WordPress object cache
4. Try saving again
```

**Step 2: Check Permissions**
```bash
# Check file permissions
ls -la wp-content/plugins/modern-admin-styler-v2/

# Should be 755 for directories, 644 for files
chmod -R 755 wp-content/plugins/modern-admin-styler-v2/
```

**Step 3: Check Database**
```bash
# Verify database connection
wp db check

# Test settings write
wp option update mas_v2_settings '{"test":"value"}'
wp option get mas_v2_settings
```

**Success Criteria:**
- Settings persist after save
- No error messages
- Changes visible immediately

---

### Issue 3: JavaScript Errors in Console

**Symptoms:**
- Console errors mentioning "mas-rest-client"
- Broken admin interface
- Buttons not working

**Cause:**
- JavaScript file not loaded
- Browser cache issue
- Plugin conflict

**Solution:**

**Step 1: Clear Browser Cache**
```
1. Press Ctrl+Shift+Delete (Cmd+Shift+Delete on Mac)
2. Select "Cached images and files"
3. Clear cache
4. Hard refresh page (Ctrl+F5)
```

**Step 2: Check for Conflicts**
```
1. Deactivate all other plugins
2. Test if issue persists
3. Reactivate plugins one by one
4. Identify conflicting plugin
```

**Step 3: Verify File Integrity**
```bash
# Check if JavaScript files exist
ls -la wp-content/plugins/modern-admin-styler-v2/assets/js/

# Should see:
# - mas-rest-client.js
# - mas-dual-mode-client.js
# - admin-settings-page.js
```

**Success Criteria:**
- No console errors
- All buttons functional
- Interface responsive

---

### Issue 4: Performance Issues

**Symptoms:**
- Slow admin interface
- Long save times
- Timeout errors

**Cause:**
- No object caching
- Database not optimized
- Server resource constraints

**Solution:**

**Step 1: Enable Object Caching**
```bash
# Install Redis or Memcached
# Then enable object cache
wp cache flush
```

**Step 2: Optimize Database**
```bash
# Optimize tables
wp db optimize

# Clean up transients
wp transient delete --all --expired
```

**Step 3: Check Server Resources**
```bash
# Check memory usage
free -m

# Check CPU usage
top

# Check disk space
df -h
```

**Success Criteria:**
- Settings save in < 500ms
- Admin interface responsive
- No timeout errors

---

### Issue 5: Backup/Restore Not Working

**Symptoms:**
- Backup creation fails
- Restore doesn't apply changes
- "Backup not found" errors

**Cause:**
- Insufficient disk space
- Permission issues
- Database size limits

**Solution:**

**Step 1: Check Disk Space**
```bash
# Check available space
df -h

# Should have at least 100MB free
```

**Step 2: Check Permissions**
```bash
# Uploads directory should be writable
ls -la wp-content/uploads/

# Fix permissions if needed
chmod -R 755 wp-content/uploads/
```

**Step 3: Manual Backup**
```bash
# Create manual backup
wp option get mas_v2_settings > manual-backup.json

# Restore from manual backup
wp option update mas_v2_settings "$(cat manual-backup.json)"
```

**Success Criteria:**
- Backups created successfully
- Restore applies changes
- No error messages

---

## Troubleshooting Guide

### Diagnostic Information Collection

When a user reports an issue, collect this information:

**1. System Information**
```
WordPress Version: [Check in Dashboard → Updates]
PHP Version: [Check in Tools → Site Health]
Plugin Version: [Check in Plugins page]
Active Theme: [Check in Appearance → Themes]
Other Active Plugins: [List from Plugins page]
```

**2. Error Messages**
```
- Exact error message text
- When error occurs (during what action)
- Browser console errors (F12 → Console tab)
- PHP error log entries
```

**3. Browser Information**
```
Browser: [Chrome/Firefox/Safari/Edge]
Version: [Browser version number]
Operating System: [Windows/Mac/Linux]
```

**4. Steps to Reproduce**
```
1. [First step]
2. [Second step]
3. [Error occurs]
```

### Using the Diagnostics Endpoint

The plugin includes a diagnostics endpoint for troubleshooting:

**Access via Browser:**
```
1. Log in to WordPress admin
2. Navigate to: Modern Admin Styler V2 → Diagnostics
3. Click "Run Diagnostics"
4. Copy the report
```

**Access via WP-CLI:**
```bash
wp rest get /mas-v2/v1/diagnostics --user=admin
```

**Diagnostic Report Includes:**
- System information (PHP, WordPress, plugin versions)
- Health checks (settings integrity, file permissions)
- Performance metrics (memory usage, execution time)
- Conflict detection (incompatible plugins)
- Recommendations (optimization suggestions)

### Debug Mode

Enable debug mode for detailed logging:

**wp-config.php:**
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

**Check Debug Log:**
```bash
tail -f wp-content/debug.log
```

---

## Support Scripts

### Script 1: Quick Health Check

```bash
#!/bin/bash
# health-check.sh

echo "=== Modern Admin Styler V2 Health Check ==="

# Check plugin is active
if wp plugin is-active modern-admin-styler-v2; then
    echo "✓ Plugin is active"
else
    echo "✗ Plugin is not active"
fi

# Check version
VERSION=$(wp plugin get modern-admin-styler-v2 --field=version)
echo "Plugin Version: $VERSION"

# Check REST API
if wp rest list | grep -q "mas-v2/v1"; then
    echo "✓ REST API namespace registered"
else
    echo "✗ REST API namespace not found"
fi

# Check settings
if wp option get mas_v2_settings > /dev/null 2>&1; then
    echo "✓ Settings exist"
else
    echo "✗ Settings not found"
fi

# Check file permissions
PERMS=$(stat -c %a wp-content/plugins/modern-admin-styler-v2/)
if [ "$PERMS" = "755" ]; then
    echo "✓ File permissions correct"
else
    echo "⚠ File permissions: $PERMS (should be 755)"
fi

echo "=== Health Check Complete ==="
```

### Script 2: Clear All Caches

```bash
#!/bin/bash
# clear-caches.sh

echo "Clearing all caches..."

# WordPress cache
wp cache flush
echo "✓ WordPress cache cleared"

# Transients
wp transient delete --all
echo "✓ Transients cleared"

# Rewrite rules
wp rewrite flush
echo "✓ Rewrite rules flushed"

# Object cache (if available)
if wp cache type | grep -q "redis\|memcached"; then
    wp cache flush
    echo "✓ Object cache cleared"
fi

echo "✓ All caches cleared successfully"
```

### Script 3: Reset to Defaults

```bash
#!/bin/bash
# reset-to-defaults.sh

echo "Resetting Modern Admin Styler V2 to defaults..."

# Backup current settings
wp option get mas_v2_settings > settings-backup-$(date +%Y%m%d-%H%M%S).json
echo "✓ Current settings backed up"

# Reset via REST API
wp rest delete /mas-v2/v1/settings --user=admin
echo "✓ Settings reset to defaults"

# Clear caches
wp cache flush
echo "✓ Caches cleared"

echo "✓ Reset complete"
```

---

## Escalation Procedures

### Level 1: Support Team (First Response)

**Handle:**
- Common issues with documented solutions
- User guidance and education
- Basic troubleshooting

**Escalate if:**
- Issue not in documentation
- Requires code changes
- Affects multiple users
- Security concern

**Response Time:** Within 4 hours

### Level 2: Senior Support / QA

**Handle:**
- Complex troubleshooting
- Configuration issues
- Plugin conflicts
- Performance problems

**Escalate if:**
- Bug confirmed
- Feature request
- Requires development
- Critical issue

**Response Time:** Within 24 hours

### Level 3: Development Team

**Handle:**
- Bug fixes
- Code issues
- Feature development
- Security patches

**Escalate if:**
- Critical security issue
- Data loss risk
- System-wide impact

**Response Time:** Within 48 hours (critical: immediate)

### Critical Issue Escalation

**Immediate escalation required for:**
- Data loss or corruption
- Security vulnerabilities
- Complete plugin failure
- Widespread user impact

**Escalation Process:**
1. Create urgent ticket
2. Email dev team lead
3. Call on-call engineer
4. Update status page

---

## FAQ

### Q1: Is the REST API migration mandatory?

**A:** No, the plugin maintains backward compatibility. Legacy AJAX handlers still work, but REST API is recommended for better performance and reliability.

### Q2: Will my settings be lost during the update?

**A:** No, all settings are preserved. The plugin automatically migrates settings to the new format if needed.

### Q3: Do I need to change any configuration?

**A:** In most cases, no. The plugin works out of the box. You may need to flush permalinks (Settings → Permalinks → Save).

### Q4: What if the REST API doesn't work?

**A:** The plugin automatically falls back to AJAX if REST API is unavailable. No manual intervention needed.

### Q5: How do I know if REST API is working?

**A:** Check the browser console (F12). If you see requests to `/wp-json/mas-v2/v1/`, REST API is working.

### Q6: Can I disable the REST API and use only AJAX?

**A:** Yes, use the feature flags in plugin settings to control which system is active.

### Q7: Is this update compatible with my WordPress version?

**A:** The plugin requires WordPress 5.8 or higher. Check your version in Dashboard → Updates.

### Q8: Will this affect my site's performance?

**A:** Performance should improve. REST API is generally faster than AJAX. If you experience issues, enable object caching.

### Q9: How do I report a bug?

**A:** Use the support forum or GitHub issues. Include diagnostic information and steps to reproduce.

### Q10: Can I rollback to the previous version?

**A:** Yes, you can safely rollback. See ROLLBACK-PLAN.md for detailed instructions.

---

## Support Resources

### Documentation
- **API Documentation**: `docs/API-DOCUMENTATION.md`
- **Developer Guide**: `docs/DEVELOPER-GUIDE.md`
- **Migration Guide**: `docs/MIGRATION-GUIDE.md`
- **Error Codes**: `docs/ERROR-CODES.md`

### Tools
- **Postman Collection**: `docs/Modern-Admin-Styler-V2.postman_collection.json`
- **Diagnostic Endpoint**: `/wp-json/mas-v2/v1/diagnostics`
- **Health Check Script**: `health-check.sh`

### Contact
- **Support Forum**: [URL]
- **GitHub Issues**: [URL]
- **Email**: support@example.com
- **Emergency**: [Phone Number]

---

## Support Ticket Template

```markdown
**Issue Summary:**
[Brief description]

**WordPress Version:**
[Version number]

**Plugin Version:**
[Version number]

**PHP Version:**
[Version number]

**Browser:**
[Browser and version]

**Steps to Reproduce:**
1. [Step 1]
2. [Step 2]
3. [Error occurs]

**Expected Behavior:**
[What should happen]

**Actual Behavior:**
[What actually happens]

**Error Messages:**
[Any error messages]

**Console Errors:**
[Browser console errors]

**Diagnostic Report:**
[Paste diagnostic report]

**Screenshots:**
[Attach screenshots if applicable]

**Additional Information:**
[Any other relevant details]
```

---

**Document Version**: 1.0
**Last Updated**: 2025-06-10
**Next Review**: 2025-07-10
