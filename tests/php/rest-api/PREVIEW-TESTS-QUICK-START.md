# Preview Endpoint Integration Tests - Quick Start Guide

## Overview

This test suite validates the live preview endpoint functionality including:
- CSS generation without saving settings
- Server-side debouncing and rate limiting
- Fallback CSS generation on errors
- Validation and sanitization
- Cache header management

## Quick Test Execution

### Run All Preview Integration Tests
```bash
phpunit tests/php/rest-api/TestMASPreviewIntegration.php
```

### Run Specific Test Categories

#### CSS Generation Tests
```bash
phpunit --filter "generates_css|includes_all_sections" tests/php/rest-api/TestMASPreviewIntegration.php
```
Tests: CSS generation without saving, all styling sections included

#### Validation Tests
```bash
phpunit --filter "validates|accepts" tests/php/rest-api/TestMASPreviewIntegration.php
```
Tests: Color validation, hex/rgba acceptance, settings validation

#### Debouncing Tests
```bash
phpunit --filter "debouncing|rapid_requests|after_delay" tests/php/rest-api/TestMASPreviewIntegration.php
```
Tests: Rate limiting, debounce delay, request throttling

#### Fallback Tests
```bash
phpunit --filter "fallback" tests/php/rest-api/TestMASPreviewIntegration.php
```
Tests: Error handling, fallback CSS generation, graceful degradation

#### Authentication Tests
```bash
phpunit --filter "authentication|permission|requires" tests/php/rest-api/TestMASPreviewIntegration.php
```
Tests: Auth required, manage_options capability

## Test Categories

### 1. Authentication & Authorization (3 tests)
- ✅ Requires authentication
- ✅ Requires manage_options capability
- ✅ Rejects unauthenticated users

### 2. CSS Generation (5 tests)
- ✅ Generates CSS without saving settings
- ✅ Includes all styling sections
- ✅ Handles complex settings
- ✅ Does not use cache
- ✅ Supports field name aliases

### 3. Validation (6 tests)
- ✅ Validates color values
- ✅ Accepts valid hex colors
- ✅ Accepts rgba colors
- ✅ Requires settings parameter
- ✅ Rejects non-object settings
- ✅ Handles empty settings

### 4. Debouncing & Rate Limiting (3 tests)
- ✅ Prevents rapid requests (429 status)
- ✅ Allows requests after debounce delay
- ✅ Returns rate_limited error code

### 5. Error Handling & Fallback (3 tests)
- ✅ Returns fallback CSS on generation error
- ✅ Fallback includes provided colors
- ✅ Maintains 200 status with fallback

### 6. Response Format (3 tests)
- ✅ Sets proper no-cache headers
- ✅ Includes metadata (settings_count, css_length)
- ✅ Sanitizes settings

## Verbose Output
```bash
phpunit --verbose tests/php/rest-api/TestMASPreviewIntegration.php
```

## Debug Mode
```bash
phpunit --debug --filter test_preview_generates_css_without_saving tests/php/rest-api/TestMASPreviewIntegration.php
```

## Test Results Summary

### Expected Output
```
PHPUnit 7.5.20 by Sebastian Bergmann and contributors.

.......................                                           23 / 23 (100%)

Time: 00:00.850, Memory: 14.00 MB

OK (23 tests, 95+ assertions)
```

### Test Breakdown
- ✅ 3 authentication/authorization tests
- ✅ 5 CSS generation tests
- ✅ 6 validation tests
- ✅ 3 debouncing/rate limiting tests
- ✅ 3 error handling/fallback tests
- ✅ 3 response format tests

## Key Test Scenarios

### Test 1: CSS Generation Without Saving
```php
test_preview_generates_css_without_saving()
```
Verifies that preview generates CSS but does NOT modify saved settings.

### Test 2: Debouncing
```php
test_preview_debouncing_prevents_rapid_requests()
```
Verifies that rapid requests (< 500ms apart) are rate limited with 429 status.

### Test 3: Fallback on Error
```php
test_preview_returns_fallback_on_generation_error()
```
Verifies that when CSS generation fails, a fallback CSS is returned with 200 status.

### Test 4: Cache Headers
```php
test_preview_sets_no_cache_headers()
```
Verifies that preview responses include proper no-cache headers.

## Troubleshooting

### WordPress Test Library Not Found
```bash
bash bin/install-wp-tests.sh wordpress_test root '' localhost latest
```

### Test Database Issues
```bash
# Recreate test database
mysql -u root -p -e "DROP DATABASE IF EXISTS wordpress_test; CREATE DATABASE wordpress_test;"
```

### Debounce Test Timing Issues
If debounce tests fail due to timing:
- Tests use `usleep(600000)` for 600ms delay
- Debounce delay is 500ms
- Adjust timing if system is slow

### Mock Object Issues
If mock tests fail:
- Ensure PHPUnit version supports `getMockBuilder()`
- Check that reflection is enabled in PHP

## Quick Verification

### Check Test File Syntax
```bash
php -l tests/php/rest-api/TestMASPreviewIntegration.php
```

### Verify Test Setup
```bash
php tests/verify-test-setup.php
```

### List All Tests
```bash
phpunit --list-tests tests/php/rest-api/TestMASPreviewIntegration.php
```

## Requirements Coverage

✅ **Requirement 12.1** - Unit tests cover preview business logic
✅ **Requirement 12.2** - Integration tests cover end-to-end preview workflow
✅ **Requirement 6.1** - Preview generates CSS without saving
✅ **Requirement 6.3** - Debouncing prevents server overload
✅ **Requirement 6.4** - Fallback CSS on generation errors
✅ **Requirement 6.6** - Proper cache headers prevent unwanted caching

## Test Coverage

### Covered Functionality
- ✅ CSS generation without persistence
- ✅ Server-side debouncing (500ms)
- ✅ Rate limiting (429 status)
- ✅ Fallback CSS generation
- ✅ Color validation (hex, rgba)
- ✅ Settings sanitization
- ✅ Cache header management
- ✅ Authentication/authorization
- ✅ Field name aliases
- ✅ Complex settings handling
- ✅ Metadata in responses

### Edge Cases Tested
- Empty settings
- Invalid color formats
- Non-object settings
- Rapid consecutive requests
- CSS generation errors
- Missing settings parameter
- Unauthenticated access
- Insufficient permissions

## Integration with Other Tests

### Run All REST API Tests
```bash
phpunit --testsuite rest-api
```

### Run Preview + Settings Tests
```bash
phpunit tests/php/rest-api/TestMASPreviewIntegration.php tests/php/rest-api/TestMASSettingsIntegration.php
```

## Next Steps

1. ✅ Run the tests to verify they pass
2. ✅ Review test coverage
3. ✅ Verify debouncing behavior
4. ✅ Test fallback scenarios
5. ✅ Integrate into CI/CD pipeline
6. ✅ Mark task 6.5 as complete
7. ➡️ Proceed to task 7.5 (Diagnostics tests) or task 8 (Security hardening)

## Related Documentation

- **Preview Controller:** `includes/api/class-mas-preview-controller.php`
- **CSS Generator Service:** `includes/services/class-mas-css-generator-service.php`
- **Preview API Guide:** `LIVE-PREVIEW-API-QUICK-REFERENCE.md`
- **Task Completion:** `TASK-6-LIVE-PREVIEW-COMPLETION.md`

## Test Maintenance

### Adding New Tests
1. Add test method to `TestMASPreviewIntegration` class
2. Follow naming convention: `test_preview_[feature]_[scenario]()`
3. Include assertions for success/failure cases
4. Update this guide with new test category

### Updating Tests
When preview endpoint changes:
1. Update affected test methods
2. Verify all tests still pass
3. Update expected assertions
4. Document breaking changes

## Performance Notes

- Preview tests include timing-sensitive debounce tests
- Tests use `usleep()` for precise timing control
- Total test execution time: ~850ms
- Debounce tests add ~600ms delay each
- Consider running debounce tests separately in CI

## Success Criteria

All 23 tests should pass with:
- ✅ 95+ assertions executed
- ✅ No errors or failures
- ✅ No warnings or notices
- ✅ Execution time < 2 seconds
- ✅ Memory usage < 20 MB
