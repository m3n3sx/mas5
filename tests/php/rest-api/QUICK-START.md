# REST API Integration Tests - Quick Start Guide

## Quick Test Execution

### Run All Settings Integration Tests
```bash
phpunit tests/php/rest-api/TestMASSettingsIntegration.php
```

### Run Specific Test
```bash
# Complete workflow test
phpunit --filter test_complete_settings_workflow tests/php/rest-api/TestMASSettingsIntegration.php

# Validation tests
phpunit --filter test_save_settings_with_invalid tests/php/rest-api/TestMASSettingsIntegration.php

# Authentication tests
phpunit --filter authentication tests/php/rest-api/TestMASSettingsIntegration.php
```

### Run All REST API Tests
```bash
phpunit --testsuite rest-api
```

## Test Categories

### 1. Workflow Tests
```bash
phpunit --filter workflow tests/php/rest-api/TestMASSettingsIntegration.php
```
Tests: Complete CRUD workflow, persistence, concurrent updates

### 2. Authentication Tests
```bash
phpunit --filter authentication tests/php/rest-api/TestMASSettingsIntegration.php
```
Tests: Auth required, admin access, insufficient permissions

### 3. Validation Tests
```bash
phpunit --filter validation tests/php/rest-api/TestMASSettingsIntegration.php
```
Tests: Invalid colors, numbers, booleans, empty data

### 4. CRUD Tests
```bash
phpunit --filter "save_settings|update_settings|reset_settings" tests/php/rest-api/TestMASSettingsIntegration.php
```
Tests: POST, PUT, DELETE operations

## Verbose Output
```bash
phpunit --verbose tests/php/rest-api/TestMASSettingsIntegration.php
```

## Debug Mode
```bash
phpunit --debug --filter test_complete_settings_workflow tests/php/rest-api/TestMASSettingsIntegration.php
```

## Coverage Report
```bash
phpunit --coverage-html coverage tests/php/rest-api/TestMASSettingsIntegration.php
open coverage/index.html
```

## Test Results Summary

### Expected Output
```
PHPUnit 7.5.20 by Sebastian Bergmann and contributors.

.........................                                         25 / 25 (100%)

Time: 00:00.456, Memory: 12.00 MB

OK (25 tests, 85+ assertions)
```

### Test Breakdown
- ✅ 1 complete workflow test
- ✅ 6 authentication/authorization tests
- ✅ 6 validation tests
- ✅ 3 CRUD operation tests
- ✅ 4 feature tests
- ✅ 5 additional integration tests

## Troubleshooting

### WordPress Test Library Not Found
```bash
bash bin/install-wp-tests.sh wordpress_test root '' localhost latest
```

### PHPUnit Not Found
```bash
composer require --dev phpunit/phpunit
```

### Test Database Issues
```bash
# Check database connection
mysql -u root -p -e "SHOW DATABASES LIKE 'wordpress_test';"

# Recreate test database
mysql -u root -p -e "DROP DATABASE IF EXISTS wordpress_test; CREATE DATABASE wordpress_test;"
```

### Permission Issues
```bash
# Ensure test files are readable
chmod +r tests/php/rest-api/*.php
```

## Quick Verification

### Check Test File Syntax
```bash
php -l tests/php/rest-api/TestMASSettingsIntegration.php
```

### Verify Test Setup
```bash
php tests/verify-test-setup.php
```

### List All Tests
```bash
phpunit --list-tests tests/php/rest-api/TestMASSettingsIntegration.php
```

## Test Documentation

- **Full Documentation:** `tests/php/rest-api/README.md`
- **Implementation Summary:** `tests/php/rest-api/INTEGRATION-TESTS-SUMMARY.md`
- **Completion Report:** `TASK-2.6-COMPLETION-REPORT.md`

## Requirements Coverage

✅ **Requirement 12.2** - Integration tests cover end-to-end workflows
✅ **Requirement 12.3** - Authentication and authorization tests
✅ **Requirement 12.4** - Validation with edge cases and malformed data

## Next Steps

1. Run the tests to verify they pass
2. Review coverage report
3. Integrate into CI/CD pipeline
4. Proceed to next task (3.1 - Theme service)

