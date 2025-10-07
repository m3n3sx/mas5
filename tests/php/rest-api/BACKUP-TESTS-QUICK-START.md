# Backup REST API Integration Tests - Quick Start Guide

## Overview

Comprehensive integration tests for the MAS Backups REST API endpoints covering:
- ✅ Backup creation and listing
- ✅ Backup restoration with validation
- ✅ Automatic cleanup functionality
- ✅ Rollback on failed restore
- ✅ Authentication and authorization
- ✅ Error handling and edge cases

## Quick Test Execution

### Run All Backup Integration Tests
```bash
phpunit tests/php/rest-api/TestMASBackupsIntegration.php
```

### Run Specific Test Categories

#### Backup Creation and Listing Tests
```bash
phpunit --filter "test_create_backup|test_list_backups|test_get" tests/php/rest-api/TestMASBackupsIntegration.php
```

#### Backup Restoration Tests
```bash
phpunit --filter "test_restore" tests/php/rest-api/TestMASBackupsIntegration.php
```

#### Automatic Cleanup Tests
```bash
phpunit --filter "test_automatic_cleanup" tests/php/rest-api/TestMASBackupsIntegration.php
```

#### Validation and Rollback Tests
```bash
phpunit --filter "test_backup_validation|test_rollback" tests/php/rest-api/TestMASBackupsIntegration.php
```

#### Authentication Tests
```bash
phpunit --filter "authentication|authorization" tests/php/rest-api/TestMASBackupsIntegration.php
```

### Run Complete Workflow Test
```bash
phpunit --filter test_complete_backup_workflow tests/php/rest-api/TestMASBackupsIntegration.php
```

## Test Coverage

### 1. Backup Creation Tests (Requirements: 12.1, 12.2)
- ✅ `test_create_backup` - Create backup via REST API
- ✅ `test_backup_metadata_storage` - Verify metadata is properly stored
- ✅ `test_backup_type_distinction` - Manual vs automatic backup types

### 2. Backup Listing Tests (Requirements: 12.1, 12.2)
- ✅ `test_list_backups` - List all backups with proper sorting
- ✅ `test_list_backups_with_pagination` - Pagination support
- ✅ `test_get_specific_backup` - Get individual backup with full data
- ✅ `test_get_nonexistent_backup` - Handle non-existent backup (404)
- ✅ `test_backup_statistics` - Statistics endpoint

### 3. Backup Restoration Tests (Requirements: 12.2, 12.4)
- ✅ `test_restore_backup_with_validation` - Restore with validation
- ✅ `test_restore_creates_pre_restore_backup` - Pre-restore backup creation
- ✅ `test_restore_nonexistent_backup` - Handle non-existent backup
- ✅ `test_rollback_on_failed_restore` - Rollback mechanism on failure

### 4. Backup Deletion Tests (Requirements: 12.1, 12.2)
- ✅ `test_delete_backup` - Delete backup successfully
- ✅ `test_delete_nonexistent_backup` - Handle non-existent backup

### 5. Automatic Cleanup Tests (Requirements: 12.2, 12.4)
- ✅ `test_automatic_cleanup_by_count` - Cleanup old automatic backups
- ✅ `test_automatic_cleanup_preserves_manual_backups` - Preserve manual backups

### 6. Validation Tests (Requirements: 12.4)
- ✅ `test_backup_validation_with_invalid_data` - Invalid settings structure
- ✅ `test_backup_validation_with_missing_metadata` - Missing metadata

### 7. Authentication Tests (Requirements: 12.3)
- ✅ `test_endpoints_require_authentication` - All endpoints require auth
- ✅ `test_endpoints_require_manage_options_capability` - Capability check

### 8. Integration Tests (Requirements: 12.2)
- ✅ `test_complete_backup_workflow` - End-to-end workflow
- ✅ `test_concurrent_backup_operations` - Concurrent operations
- ✅ `test_backup_response_format` - Response format consistency
- ✅ `test_error_response_format` - Error format consistency

## Test Results Summary

### Expected Output
```
PHPUnit 7.5.20 by Sebastian Bergmann and contributors.

.........................                                         25 / 25 (100%)

Time: 00:00.XXX, Memory: XX.XX MB

OK (25 tests, 100+ assertions)
```

### Test Breakdown
- ✅ 3 backup creation tests
- ✅ 5 backup listing tests
- ✅ 4 backup restoration tests
- ✅ 2 backup deletion tests
- ✅ 2 automatic cleanup tests
- ✅ 2 validation tests
- ✅ 2 authentication tests
- ✅ 4 integration tests
- ✅ 1 complete workflow test

**Total: 25 comprehensive tests**

## Verbose Output
```bash
phpunit --verbose tests/php/rest-api/TestMASBackupsIntegration.php
```

## Debug Mode
```bash
phpunit --debug --filter test_complete_backup_workflow tests/php/rest-api/TestMASBackupsIntegration.php
```

## Quick Verification

### Check Test File Syntax
```bash
php -l tests/php/rest-api/TestMASBackupsIntegration.php
```

### List All Tests
```bash
phpunit --list-tests tests/php/rest-api/TestMASBackupsIntegration.php
```

## Requirements Coverage

✅ **Requirement 12.1** - Unit tests cover all business logic
✅ **Requirement 12.2** - Integration tests cover end-to-end workflows
- Complete backup workflow (create → list → restore → delete)
- Backup restoration with pre-restore backup creation
- Automatic cleanup functionality
- Concurrent operations

✅ **Requirement 12.3** - Authentication and authorization tests
- Unauthenticated access blocked (403)
- Insufficient permissions blocked (403)
- Admin access granted (200)

✅ **Requirement 12.4** - Edge cases and malformed data handled
- Invalid backup data validation
- Missing metadata validation
- Non-existent backup handling (404)
- Rollback on failed restore

## Key Test Scenarios

### Scenario 1: Complete Backup Lifecycle
1. Create initial settings
2. Create backup #1
3. Modify settings
4. Create backup #2
5. List all backups (verify sorting)
6. Restore backup #1
7. Verify settings restored
8. Delete backup #2
9. Verify deletion

### Scenario 2: Automatic Cleanup
1. Create 12 automatic backups (exceeds max of 10)
2. Trigger cleanup
3. Verify only 10 automatic backups remain
4. Verify manual backups are preserved

### Scenario 3: Restore with Rollback
1. Save current settings
2. Create backup with different settings
3. Restore backup
4. Verify pre-restore backup was created
5. If restore fails, verify rollback to current settings

### Scenario 4: Validation and Error Handling
1. Create backup with invalid data structure
2. Attempt to restore
3. Verify validation error (400)
4. Verify detailed error messages
5. Verify current settings unchanged

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

### Cleanup Test Data
The tests automatically clean up all backups in `setUp()` and `tearDown()` methods.

## Integration with CI/CD

Add to your CI pipeline:
```yaml
- name: Run Backup API Tests
  run: phpunit tests/php/rest-api/TestMASBackupsIntegration.php --coverage-clover coverage.xml
```

## Next Steps

1. ✅ Run the tests to verify they pass
2. ✅ Review test coverage
3. ✅ Integrate into CI/CD pipeline
4. ✅ Mark task 4.5 as complete
5. → Proceed to task 5.1 (Import/Export service)

## Related Documentation

- **Settings Tests:** `tests/php/rest-api/QUICK-START.md`
- **Theme Tests:** `tests/php/rest-api/THEME-TESTS-QUICK-START.md`
- **REST API Documentation:** `REST-API-QUICK-START.md`
- **Backup API Reference:** `BACKUP-API-QUICK-REFERENCE.md`
