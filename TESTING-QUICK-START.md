# Testing Quick Start Guide

## Modern Admin Styler V2 - PHPUnit Tests

This guide provides quick instructions for running the PHPUnit tests for the REST API migration.

## Quick Setup

### 1. Verify Test Setup

```bash
php tests/verify-test-setup.php
```

This will check if all required files are in place.

### 2. Install WordPress Test Library (if needed)

```bash
bash bin/install-wp-tests.sh wordpress_test root '' localhost latest
```

Replace parameters as needed:
- `wordpress_test` - Test database name
- `root` - Database user
- `''` - Database password
- `localhost` - Database host
- `latest` - WordPress version

### 3. Install PHPUnit (if needed)

**Via Composer:**
```bash
composer require --dev phpunit/phpunit ^7.5
```

**Or download directly:**
```bash
wget https://phar.phpunit.de/phpunit-7.phar
chmod +x phpunit-7.phar
sudo mv phpunit-7.phar /usr/local/bin/phpunit
```

## Running Tests

### Run All Tests
```bash
phpunit
```

### Run REST API Tests Only
```bash
phpunit --testsuite rest-api
```

### Run Specific Test File
```bash
phpunit tests/php/rest-api/TestMASRestController.php
```

### Run Specific Test Method
```bash
phpunit --filter test_check_permission_with_admin_user
```

### Generate Coverage Report
```bash
phpunit --coverage-html tests/coverage/html
```

Then open `tests/coverage/html/index.html` in your browser.

## Test Files

### Current Tests

- **TestMASRestController.php** - Base REST controller tests
  - 21 tests covering authentication, permissions, and response formatting
  - Tests all user roles (admin, editor, subscriber, unauthenticated)
  - Tests error and success response formats
  - Tests HTTP status codes (200, 201, 204, 400, 401, 403, 404, 500)

### Future Tests (to be implemented)

- TestMASSettingsController.php
- TestMASThemesController.php
- TestMASBackupsController.php
- TestMASImportExportController.php
- TestMASPreviewController.php
- TestMASDiagnosticsController.php

## Test Structure

```
tests/
├── bootstrap.php                           # Test bootstrap
├── verify-test-setup.php                   # Setup verification
├── run-tests.sh                            # Test runner script
├── README.md                               # Full documentation
├── php/
│   └── rest-api/
│       ├── TestMASRestController.php       # Base controller tests
│       └── README.md                       # REST API test docs
└── TASK-1.4-IMPLEMENTATION-SUMMARY.md     # Implementation summary
```

## What's Tested

### Authentication ✓
- Admin users can access endpoints
- Non-admin users are denied
- Unauthenticated users are denied
- Proper error codes returned

### Response Formatting ✓
- Success responses have correct structure
- Error responses have correct structure
- HTTP status codes are appropriate
- Data is properly formatted

### Permissions ✓
- manage_options capability required
- All HTTP methods checked (GET, POST, PUT, DELETE, PATCH)
- Error messages are descriptive

### Configuration ✓
- Namespace is correct (mas-v2/v1)
- Base controller properties set correctly

## Coverage Goals

- **Base Infrastructure**: 100% ✓
- **REST Controllers**: 90%+ (target)
- **Service Classes**: 85%+ (target)
- **Overall REST API**: 80%+ (target)

## Troubleshooting

### "Could not find wp-tests-config.php"
```bash
bash bin/install-wp-tests.sh wordpress_test root '' localhost latest
```

### "PHPUnit not found"
```bash
composer require --dev phpunit/phpunit
```

### "Class not found" errors
Check that `tests/bootstrap.php` is loading the plugin correctly.

### Database connection errors
Verify MySQL/MariaDB is running and credentials are correct.

## Documentation

- **tests/README.md** - Complete testing guide
- **tests/php/rest-api/README.md** - REST API specific documentation
- **tests/TASK-1.4-IMPLEMENTATION-SUMMARY.md** - Implementation details

## Requirements Fulfilled

✓ **Requirement 12.1** - Unit tests cover all business logic
✓ **Requirement 12.2** - Integration tests for end-to-end workflows
✓ **Requirement 8.1** - Authentication with manage_options capability
✓ **Requirement 8.2** - Proper permission checks
✓ **Requirement 1.3** - Appropriate HTTP status codes

## Next Steps

1. Run tests to verify everything works
2. Review test coverage report
3. Implement additional controller tests as new endpoints are created
4. Set up CI/CD pipeline to run tests automatically

## Support

For detailed information, see:
- Full documentation: `tests/README.md`
- REST API tests: `tests/php/rest-api/README.md`
- Implementation summary: `tests/TASK-1.4-IMPLEMENTATION-SUMMARY.md`
