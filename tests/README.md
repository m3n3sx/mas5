# Modern Admin Styler V2 - PHPUnit Tests

This directory contains PHPUnit tests for the Modern Admin Styler V2 plugin, with a focus on the REST API implementation.

## Setup

### Prerequisites

- PHP 7.4 or higher
- PHPUnit 7.x or higher
- WordPress test library
- MySQL/MariaDB

### Installation

1. Install the WordPress test library:

```bash
bash bin/install-wp-tests.sh wordpress_test root '' localhost latest
```

Replace the parameters as needed:
- `wordpress_test` - Database name for tests
- `root` - Database user
- `''` - Database password (empty in this example)
- `localhost` - Database host
- `latest` - WordPress version (or specific version like 6.4)

2. Install PHPUnit if not already installed:

```bash
composer require --dev phpunit/phpunit ^7.5
```

Or globally:

```bash
wget https://phar.phpunit.de/phpunit-7.phar
chmod +x phpunit-7.phar
sudo mv phpunit-7.phar /usr/local/bin/phpunit
```

## Running Tests

### Run all tests:

```bash
phpunit
```

### Run specific test suite:

```bash
phpunit --testsuite rest-api
```

### Run specific test file:

```bash
phpunit tests/php/rest-api/TestMASRestController.php
```

### Run with coverage report:

```bash
phpunit --coverage-html tests/coverage/html
```

Then open `tests/coverage/html/index.html` in your browser.

## Test Structure

```
tests/
├── bootstrap.php                           # Test bootstrap file
├── php/
│   └── rest-api/
│       ├── TestMASRestController.php       # Base controller tests
│       ├── TestMASSettingsController.php   # Settings endpoint tests (future)
│       ├── TestMASThemesController.php     # Themes endpoint tests (future)
│       └── ...
└── README.md                               # This file
```

## Writing Tests

### Test Class Structure

```php
<?php
class TestMyFeature extends WP_UnitTestCase {
    
    protected $admin_user;
    
    public function setUp() {
        parent::setUp();
        $this->admin_user = $this->factory->user->create(['role' => 'administrator']);
    }
    
    public function tearDown() {
        parent::tearDown();
        wp_set_current_user(0);
    }
    
    public function test_my_feature() {
        // Test implementation
        $this->assertTrue(true);
    }
}
```

### Best Practices

1. **Isolation**: Each test should be independent and not rely on other tests
2. **Setup/Teardown**: Use `setUp()` and `tearDown()` to prepare and clean up test environment
3. **Assertions**: Use descriptive assertion messages
4. **Naming**: Test method names should clearly describe what they test
5. **Coverage**: Aim for 80%+ code coverage for REST API code

## Test Coverage Goals

- **Base Infrastructure**: 100% coverage
- **REST Controllers**: 90%+ coverage
- **Service Classes**: 85%+ coverage
- **Overall REST API**: 80%+ coverage

## Continuous Integration

Tests should be run automatically on:
- Pull requests
- Commits to main branch
- Before releases

## Troubleshooting

### "Could not find wp-tests-config.php"

Run the installation script again:
```bash
bash bin/install-wp-tests.sh wordpress_test root '' localhost latest
```

### "Class not found" errors

Make sure the bootstrap file is loading the plugin correctly. Check `tests/bootstrap.php`.

### Database connection errors

Verify your database credentials and that MySQL/MariaDB is running.

### Permission errors

Ensure the test database user has proper permissions:
```sql
GRANT ALL PRIVILEGES ON wordpress_test.* TO 'root'@'localhost';
FLUSH PRIVILEGES;
```

## Resources

- [WordPress Plugin Unit Tests](https://make.wordpress.org/cli/handbook/misc/plugin-unit-tests/)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [WordPress REST API Handbook](https://developer.wordpress.org/rest-api/)
