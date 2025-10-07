# Migration Testing Checklist

## Pre-Migration Tests

### Environment Setup
- [ ] WordPress version is 5.8 or higher
- [ ] PHP version is 7.4 or higher
- [ ] Plugin is activated successfully
- [ ] No PHP errors in error log
- [ ] REST API is accessible (`/wp-json/mas-v2/v1/`)

### Baseline Tests (Legacy Mode)
- [ ] Settings page loads without errors
- [ ] All form fields are visible and functional
- [ ] Settings save correctly
- [ ] Live preview works
- [ ] Theme switching works
- [ ] Backup/restore works
- [ ] No JavaScript errors in console
- [ ] No duplicate form submissions

## Migration Tests

### Enable New Frontend
- [ ] Go to **MAS V2 → Feature Flags**
- [ ] Toggle "Use New Frontend" to ON
- [ ] Click "Save Feature Flags"
- [ ] Page reloads without errors
- [ ] Notice appears: "New Frontend Active"

### Verify New Frontend Loaded
- [ ] Check browser console for: `[MASAdminApp] Initialization complete`
- [ ] Check for: `window.MASUseNewFrontend === true`
- [ ] Check for: `window.MASAdminApp` exists
- [ ] Check for: `window.MASLegacyBridge` exists
- [ ] No legacy handler warnings in console

### Settings Functionality (New Mode)
- [ ] Settings page loads without errors
- [ ] All form fields are visible
- [ ] Form fields are populated with current settings
- [ ] Can change text fields
- [ ] Can change color fields
- [ ] Can toggle checkboxes
- [ ] Can select from dropdowns
- [ ] Form validation works
- [ ] Settings save successfully
- [ ] Success notification appears
- [ ] Page doesn't reload on save
- [ ] Settings persist after page reload

### Live Preview (New Mode)
- [ ] Live preview toggle works
- [ ] Color changes apply immediately
- [ ] Text changes apply immediately
- [ ] Checkbox changes apply immediately
- [ ] Preview updates are debounced (not instant)
- [ ] Can disable preview
- [ ] Original styles restore when disabled
- [ ] No errors in console during preview

### Theme Management (New Mode)
- [ ] Theme selector is visible
- [ ] Can view available themes
- [ ] Can apply a theme
- [ ] Theme applies correctly
- [ ] Can create custom theme
- [ ] Can edit custom theme
- [ ] Can delete custom theme
- [ ] Cannot delete predefined themes

### Backup/Restore (New Mode)
- [ ] Backup list loads
- [ ] Can create manual backup
- [ ] Backup appears in list
- [ ] Can restore backup
- [ ] Restore confirmation dialog appears
- [ ] Settings restore correctly
- [ ] Can delete backup
- [ ] Delete confirmation dialog appears

### Error Handling (New Mode)
- [ ] Network error shows user-friendly message
- [ ] Validation error shows field-specific messages
- [ ] Server error shows retry option
- [ ] Errors don't crash the page
- [ ] Can recover from errors

## Rollback Tests

### Switch Back to Legacy
- [ ] Go to **MAS V2 → Feature Flags**
- [ ] Toggle "Use New Frontend" to OFF
- [ ] Click "Save Feature Flags"
- [ ] Page reloads without errors
- [ ] Notice disappears or changes

### Verify Legacy Frontend Loaded
- [ ] Check browser console for legacy handler messages
- [ ] Check for: `window.MASUseNewFrontend === false`
- [ ] Check for: `window.MASAdminApp` does not exist
- [ ] Legacy scripts are loaded

### Settings Preservation After Rollback
- [ ] Settings page loads
- [ ] All settings from new mode are preserved
- [ ] No data loss
- [ ] Settings save correctly in legacy mode
- [ ] Live preview works in legacy mode

## Data Integrity Tests

### Settings Preservation
- [ ] Create test settings in legacy mode
- [ ] Switch to new mode
- [ ] Verify all settings preserved
- [ ] Modify settings in new mode
- [ ] Switch back to legacy mode
- [ ] Verify all settings preserved
- [ ] No data loss at any point

### Complex Settings
- [ ] Test with custom CSS
- [ ] Test with special characters
- [ ] Test with empty values
- [ ] Test with maximum length values
- [ ] Test with all checkboxes checked
- [ ] Test with all checkboxes unchecked
- [ ] Test with custom themes

### Backup Integrity
- [ ] Create backup in legacy mode
- [ ] Switch to new mode
- [ ] Verify backup is accessible
- [ ] Restore backup in new mode
- [ ] Switch back to legacy mode
- [ ] Verify backup still works

## Browser Compatibility Tests

### Modern Browsers
- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Edge (latest)

### Older Browsers
- [ ] Chrome 60+
- [ ] Firefox 55+
- [ ] Safari 11+
- [ ] Edge 79+

### Browser Features
- [ ] Works with JavaScript enabled
- [ ] Degrades gracefully with JavaScript disabled
- [ ] Works with cookies enabled
- [ ] Works with local storage available
- [ ] Works with fetch API
- [ ] Works with Promise support

## Performance Tests

### Load Time
- [ ] Page loads in < 2 seconds
- [ ] Scripts load asynchronously
- [ ] No blocking resources
- [ ] CSS loads quickly

### Interaction Performance
- [ ] Form submission < 500ms
- [ ] Live preview update < 100ms
- [ ] Theme switch < 300ms
- [ ] Backup creation < 1s
- [ ] Backup restore < 2s

### Memory Usage
- [ ] No memory leaks after multiple saves
- [ ] No memory leaks after mode switches
- [ ] Browser doesn't slow down over time

## Security Tests

### Authentication
- [ ] Non-admin cannot access feature flags
- [ ] Non-admin cannot switch modes
- [ ] Nonce validation works
- [ ] Session timeout handled correctly

### Input Validation
- [ ] XSS prevention works
- [ ] SQL injection prevention works
- [ ] Invalid color values rejected
- [ ] Invalid CSS rejected
- [ ] File upload validation works

## Accessibility Tests

### Keyboard Navigation
- [ ] Can navigate form with Tab key
- [ ] Can submit form with Enter key
- [ ] Can toggle checkboxes with Space key
- [ ] Can close modals with Escape key
- [ ] Focus indicators visible

### Screen Reader
- [ ] Form labels read correctly
- [ ] Error messages announced
- [ ] Success messages announced
- [ ] Loading states announced
- [ ] ARIA attributes present

### Color Contrast
- [ ] Text meets WCAG AA standards
- [ ] Buttons meet WCAG AA standards
- [ ] Links meet WCAG AA standards
- [ ] Error messages meet WCAG AA standards

## Edge Cases

### Concurrent Users
- [ ] Two admins can use different modes
- [ ] Settings don't conflict
- [ ] No race conditions

### Network Issues
- [ ] Handles slow network
- [ ] Handles network timeout
- [ ] Handles network disconnect
- [ ] Handles server error
- [ ] Retry mechanism works

### Plugin Conflicts
- [ ] Works with other admin plugins
- [ ] Works with caching plugins
- [ ] Works with security plugins
- [ ] No JavaScript conflicts

## Documentation Tests

### Migration Guide
- [ ] Migration guide is clear
- [ ] Examples are accurate
- [ ] Links work
- [ ] Code samples work

### Admin Notices
- [ ] New frontend notice appears
- [ ] Notice is dismissible
- [ ] Notice doesn't reappear after dismissal
- [ ] Rollback instructions clear

### Console Messages
- [ ] Deprecation warnings clear
- [ ] Error messages helpful
- [ ] Debug messages useful
- [ ] No spam in console

## Final Verification

### Production Readiness
- [ ] All tests passed
- [ ] No critical bugs
- [ ] Performance acceptable
- [ ] Documentation complete
- [ ] Rollback plan tested
- [ ] Support team trained

### Sign-off
- [ ] Developer approval
- [ ] QA approval
- [ ] Product owner approval
- [ ] Ready for production

## Notes

### Issues Found
```
List any issues found during testing:
1. 
2. 
3. 
```

### Performance Metrics
```
Record performance metrics:
- Page load time: 
- Form submission time: 
- Live preview update time: 
- Memory usage: 
```

### Browser Test Results
```
Record browser-specific issues:
- Chrome: 
- Firefox: 
- Safari: 
- Edge: 
```

## Test Environment

- WordPress Version: 
- PHP Version: 
- Plugin Version: 
- Test Date: 
- Tester Name: 
- Environment: (Local/Staging/Production)
