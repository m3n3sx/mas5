# JavaScript Test Suite

This directory contains the Jest test suite for Modern Admin Styler V2 Phase 3 frontend architecture.

## Directory Structure

```
tests/js/
├── setup.js                    # Jest setup and global mocks
├── README.md                   # This file
├── core/                       # Core class tests
│   ├── EventBus.test.js
│   ├── StateManager.test.js
│   ├── APIClient.test.js
│   └── ErrorHandler.test.js
├── components/                 # Component tests
│   ├── Component.test.js
│   ├── SettingsFormComponent.test.js
│   ├── LivePreviewComponent.test.js
│   ├── NotificationSystem.test.js
│   ├── ThemeSelectorComponent.test.js
│   ├── BackupManagerComponent.test.js
│   └── TabManager.test.js
├── utils/                      # Utility tests
│   ├── Validator.test.js
│   ├── Debouncer.test.js
│   ├── VirtualList.test.js
│   └── LazyLoader.test.js
├── integration/                # Integration tests
│   ├── form-submission.test.js
│   ├── live-preview.test.js
│   ├── error-recovery.test.js
│   └── component-communication.test.js
└── mocks/                      # Mock data and helpers
    ├── api-responses.js
    ├── dom-helpers.js
    └── test-fixtures.js
```

## Running Tests

```bash
# Run all tests
npm test

# Run tests in watch mode
npm run test:watch

# Run tests with coverage
npm run test:coverage

# Run specific test file
npm test -- EventBus.test.js

# Run tests matching pattern
npm test -- --testNamePattern="EventBus"
```

## Writing Tests

### Test Structure

```javascript
describe('ClassName', () => {
  let instance;
  
  beforeEach(() => {
    // Setup before each test
    instance = new ClassName();
  });
  
  afterEach(() => {
    // Cleanup after each test
    jest.clearAllMocks();
  });
  
  describe('methodName', () => {
    it('should do something', () => {
      // Arrange
      const input = 'test';
      
      // Act
      const result = instance.methodName(input);
      
      // Assert
      expect(result).toBe('expected');
    });
  });
});
```

### Custom Matchers

- `toBeValidHexColor()` - Validates hex color format
- `toBeValidCSSUnit()` - Validates CSS unit format

### Test Utilities

Available via `global.testUtils`:

- `createMockResponse(data, status)` - Create mock fetch response
- `createMockSettings(overrides)` - Create mock settings object
- `createMockTheme(id, overrides)` - Create mock theme object
- `createMockBackup(id, overrides)` - Create mock backup object
- `waitFor(condition, timeout)` - Wait for async condition

## Coverage Requirements

Minimum coverage thresholds (configured in jest.config.js):

- Branches: 80%
- Functions: 80%
- Lines: 80%
- Statements: 80%

## Best Practices

1. **Arrange-Act-Assert**: Structure tests with clear setup, execution, and verification
2. **One assertion per test**: Keep tests focused on a single behavior
3. **Descriptive names**: Use clear, descriptive test names
4. **Mock external dependencies**: Isolate units under test
5. **Clean up**: Always clean up after tests to prevent side effects
6. **Test edge cases**: Include tests for error conditions and edge cases
