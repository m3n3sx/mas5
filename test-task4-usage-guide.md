# Task 4 Testing Guide

## Quick Start

### 1. Run the Test Suite

Access the test file in your browser:
```
http://your-site.local/wp-content/plugins/modern-admin-styler-v2/test-task4-compatibility-checks.php
```

**Note:** You must be logged in as an administrator to run the tests.

### 2. What the Test Checks

#### Task 4.1: Activation Checks
- ✅ WordPress version meets minimum requirement (5.8+)
- ✅ PHP version meets minimum requirement (7.4+)
- ✅ REST API support is available
- ✅ Error messages are clear and actionable

#### Task 4.2: Runtime Verification
- ✅ Required WordPress functions exist (15 functions)
- ✅ Required WordPress classes exist (5 classes)
- ✅ Version warnings display correctly
- ✅ Compatibility monitoring works on load

### 3. Expected Results

#### On Compatible System (WordPress 5.8+, PHP 7.4+)
- All tests should pass (100% pass rate)
- Green success messages throughout
- No critical errors
- May show info warning if WordPress > 6.8

#### On Incompatible System
- Activation would be prevented
- Clear error messages with current versions
- Actionable troubleshooting steps
- Back link to return to plugins page

### 4. Manual Testing

#### Test Activation Checks
1. Temporarily modify the version checks in code
2. Try to activate the plugin
3. Verify error message is clear and helpful
4. Verify back link works

#### Test Runtime Checks
1. Navigate to plugin settings page
2. Check for any admin notices
3. If WordPress > 6.8, verify version warning shows
4. Test dismissing the version warning
5. Verify warning doesn't reappear for same major version

#### Test REST API Check
1. Verify REST API endpoints are accessible
2. Check browser console for any errors
3. Verify plugin features work correctly

### 5. Debug Mode Testing

Enable WordPress debug mode in `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Then check `wp-content/debug.log` for:
- Compatibility check results
- Missing function/class warnings
- REST API initialization status

### 6. Verification Checklist

- [ ] Test suite runs without errors
- [ ] All compatibility checks pass
- [ ] WordPress version check works (5.8+ required)
- [ ] PHP version check works (7.4+ required)
- [ ] REST API support check works
- [ ] Required functions are verified (15 functions)
- [ ] Required classes are verified (5 classes)
- [ ] Version warnings display for untested versions
- [ ] Version warnings can be dismissed
- [ ] Error messages are clear and helpful
- [ ] Debug logging works when WP_DEBUG enabled
- [ ] Admin notices only show to administrators

### 7. Common Issues

#### Test Page Shows "Permission Denied"
- Ensure you're logged in as an administrator
- Check user has `manage_options` capability

#### Some Tests Fail
- Check WordPress version (must be 5.8+)
- Check PHP version (must be 7.4+)
- Verify REST API is not disabled
- Check for plugin conflicts

#### Version Warning Doesn't Show
- Only shows if WordPress > 6.8
- Check if already dismissed for current major version
- Clear option: `delete_option('mas_v2_dismissed_version_warning')`

### 8. Requirements Verification

All requirements from the design document are met:

**Requirement 3.4:** ✅ Prevent activation with clear error message
**Requirement 4.1:** ✅ Activate on WordPress 5.8+
**Requirement 4.2:** ✅ REST API initializes correctly
**Requirement 4.3:** ✅ All features work on latest WordPress
**Requirement 4.4:** ✅ Prevent activation on unsupported versions

### 9. Next Steps

After verifying Task 4:
1. Proceed to Task 5: Test and verify the complete fix
2. Run end-to-end tests
3. Test error scenarios
4. Verify cross-version compatibility

---

## Support

If you encounter any issues:
1. Check the debug log (`wp-content/debug.log`)
2. Review the test output for specific failures
3. Verify system requirements are met
4. Check for plugin conflicts

## Documentation

- Full details: `.kiro/specs/mas3-plugin-repair/TASK-4-COMPLETION-REPORT.md`
- Quick summary: `.kiro/specs/mas3-plugin-repair/TASK-4-SUMMARY.md`
- Requirements: `.kiro/specs/mas3-plugin-repair/requirements.md`
- Design: `.kiro/specs/mas3-plugin-repair/design.md`
