# Manual QA Testing Checklist

## Test Environment Setup

### Prerequisites
- [ ] WordPress 5.9+ installed
- [ ] PHP 7.4+ configured
- [ ] Plugin activated successfully
- [ ] Test user accounts created (admin, editor, subscriber)
- [ ] Browser testing tools available (Chrome DevTools, Firefox Developer Tools)

### Test Data
- [ ] Sample settings configurations prepared
- [ ] Test themes created
- [ ] Backup files ready for import testing
- [ ] Invalid data samples for error testing

## User Role Testing

### Administrator Role
- [ ] Can access all REST API endpoints
- [ ] Can save settings via REST API
- [ ] Can create/apply themes via REST API
- [ ] Can create/restore backups via REST API
- [ ] Can import/export settings via REST API
- [ ] Can generate live previews via REST API
- [ ] Can access diagnostics via REST API

### Editor Role
- [ ] Cannot access REST API endpoints (403 Forbidden)
- [ ] Fallback to AJAX works correctly
- [ ] Error messages are user-friendly

### Subscriber Role
- [ ] Cannot access REST API endpoints (403 Forbidden)
- [ ] Cannot access admin interface
- [ ] No JavaScript errors in console

## Endpoint Testing

### Settings Endpoints
- [ ] GET /settings returns current settings
- [ ] POST /settings saves new settings
- [ ] PUT /settings updates partial settings
- [ ] DELETE /settings resets to defaults
- [ ] Invalid color values rejected with proper error
- [ ] Field name aliases work correctly
- [ ] CSS regeneration occurs after save

### Theme Endpoints
- [ ] GET /themes lists all available themes
- [ ] POST /themes creates custom theme
- [ ] PUT /themes/{id} updates custom theme
- [ ] DELETE /themes/{id} removes custom theme
- [ ] POST /themes/{id}/apply applies theme correctly
- [ ] Predefined themes are read-only
- [ ] Theme validation works properly### Backup
 Endpoints
- [ ] GET /backups lists all backups with metadata
- [ ] POST /backups creates backup successfully
- [ ] POST /backups/{id}/restore restores backup correctly
- [ ] DELETE /backups/{id} removes backup
- [ ] Automatic cleanup removes old backups
- [ ] Backup validation prevents corrupt restores
- [ ] Rollback works on failed restore

### Import/Export Endpoints
- [ ] GET /export downloads settings as JSON
- [ ] POST /import validates and imports settings
- [ ] Content-Disposition headers trigger download
- [ ] Import validation catches invalid data
- [ ] Automatic backup created before import
- [ ] Legacy format migration works
- [ ] Version metadata included in exports

### Preview Endpoint
- [ ] POST /preview generates CSS without saving
- [ ] Preview includes all current settings
- [ ] Debouncing prevents server overload
- [ ] Fallback CSS returned on errors
- [ ] Preview doesn't affect saved settings
- [ ] Cache headers prevent unwanted caching
- [ ] Multiple requests handled correctly

### Diagnostics Endpoint
- [ ] GET /diagnostics returns system information
- [ ] PHP/WordPress versions included
- [ ] Settings integrity validated
- [ ] File permissions checked
- [ ] Conflict detection works
- [ ] Performance metrics included
- [ ] Optimization recommendations provided

## Error Scenario Testing

### Authentication Errors
- [ ] 401 returned for unauthenticated requests
- [ ] 403 returned for insufficient permissions
- [ ] Nonce validation works correctly
- [ ] Session timeout handled gracefully

### Validation Errors
- [ ] Invalid color values rejected
- [ ] Invalid CSS units rejected
- [ ] Required fields validated
- [ ] Array structure validation works
- [ ] Detailed error messages provided

### Network Errors
- [ ] Connection timeout handled
- [ ] Server errors (500) handled
- [ ] Malformed responses handled
- [ ] Fallback to AJAX works

### Edge Cases
- [ ] Empty settings object handled
- [ ] Very large settings objects handled
- [ ] Special characters in theme names
- [ ] Concurrent requests handled properly
- [ ] Rate limiting works correctly

## Performance Testing

### Response Times
- [ ] GET /settings < 200ms
- [ ] POST /settings < 500ms
- [ ] Theme application < 300ms
- [ ] Preview generation < 400ms
- [ ] Backup creation < 1000ms

### Load Testing
- [ ] 10 concurrent requests handled
- [ ] 50 concurrent requests handled
- [ ] Memory usage remains stable
- [ ] No memory leaks detected
- [ ] Database queries optimized

### Caching
- [ ] Settings cached properly
- [ ] CSS generation cached
- [ ] Cache invalidation works
- [ ] ETag headers work correctly
- [ ] Cache-Control headers set

## Cross-Browser Compatibility

### Chrome
- [ ] All endpoints work correctly
- [ ] JavaScript client functions properly
- [ ] Error handling works
- [ ] Fallback mechanisms work

### Firefox
- [ ] All endpoints work correctly
- [ ] JavaScript client functions properly
- [ ] Error handling works
- [ ] Fallback mechanisms work

### Safari
- [ ] All endpoints work correctly
- [ ] JavaScript client functions properly
- [ ] Error handling works
- [ ] Fallback mechanisms work

### Edge
- [ ] All endpoints work correctly
- [ ] JavaScript client functions properly
- [ ] Error handling works
- [ ] Fallback mechanisms work

## Backward Compatibility

### Dual-Mode Operation
- [ ] REST API and AJAX both work
- [ ] No duplicate operations occur
- [ ] Fallback from REST to AJAX works
- [ ] Feature flags control behavior
- [ ] Deprecation warnings shown

### Migration Testing
- [ ] Existing settings preserved
- [ ] Custom themes preserved
- [ ] Backups remain accessible
- [ ] No data loss during migration
- [ ] Rollback possible if needed

## Security Testing

### Input Sanitization
- [ ] XSS attempts blocked
- [ ] SQL injection attempts blocked
- [ ] File upload restrictions work
- [ ] Path traversal blocked
- [ ] Script injection blocked

### Rate Limiting
- [ ] Excessive requests throttled
- [ ] 429 status returned when limited
- [ ] Rate limits reset correctly
- [ ] Per-user tracking works
- [ ] Bypass attempts blocked

### Authorization
- [ ] Capability checks enforced
- [ ] Nonce validation required
- [ ] Session validation works
- [ ] CSRF protection active
- [ ] Privilege escalation blocked

## Documentation Testing

### API Documentation
- [ ] All endpoints documented
- [ ] Request/response examples accurate
- [ ] Error codes documented
- [ ] Authentication requirements clear
- [ ] Rate limits documented

### Developer Guide
- [ ] Integration examples work
- [ ] Code samples accurate
- [ ] Migration guide helpful
- [ ] Troubleshooting guide useful
- [ ] Postman collection works

## Test Results Summary

### Pass/Fail Counts
- Total Tests: ___
- Passed: ___
- Failed: ___
- Skipped: ___

### Critical Issues Found
1. ___
2. ___
3. ___

### Performance Issues
1. ___
2. ___
3. ___

### Browser Compatibility Issues
1. ___
2. ___
3. ___

### Recommendations
1. ___
2. ___
3. ___

## Sign-off

- [ ] QA Testing Complete
- [ ] All critical issues resolved
- [ ] Performance meets requirements
- [ ] Security testing passed
- [ ] Documentation verified

**Tester:** _______________  
**Date:** _______________  
**Version:** _______________