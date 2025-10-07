# Development Setup Guide

This guide will help you set up your development environment for Modern Admin Styler V2 Phase 3.

## Prerequisites

- Node.js 16+ and npm
- PHP 7.4+ (for WordPress)
- WordPress 5.8+ installation
- Git

## Installation

### 1. Clone the Repository

```bash
git clone <repository-url>
cd modern-admin-styler-v2
```

### 2. Install Dependencies

```bash
# Install Node.js dependencies
npm install

# Install Playwright browsers (for E2E testing)
npx playwright install
```

### 3. Install WordPress Plugin

Copy the plugin directory to your WordPress plugins folder:

```bash
# For local WordPress installation
cp -r . /path/to/wordpress/wp-content/plugins/modern-admin-styler-v2

# Or create a symlink for development
ln -s $(pwd) /path/to/wordpress/wp-content/plugins/modern-admin-styler-v2
```

Activate the plugin in WordPress admin.

## Development Tools

### ESLint (Code Quality)

ESLint is configured to enforce code quality standards.

**Run linting:**

```bash
# Check for issues
npm run lint

# Auto-fix issues
npm run lint:fix
```

**VS Code Integration:**

Install the ESLint extension and add to `.vscode/settings.json`:

```json
{
  "eslint.validate": ["javascript"],
  "editor.codeActionsOnSave": {
    "source.fixAll.eslint": true
  }
}
```

### Prettier (Code Formatting)

Prettier ensures consistent code formatting.

**Run formatting:**

```bash
# Format all files
npm run format

# Check formatting without changes
npm run format:check
```

**VS Code Integration:**

Install the Prettier extension and add to `.vscode/settings.json`:

```json
{
  "editor.defaultFormatter": "esbenp.prettier-vscode",
  "editor.formatOnSave": true,
  "[javascript]": {
    "editor.defaultFormatter": "esbenp.prettier-vscode"
  }
}
```

### Source Maps

Source maps are automatically generated for debugging.

**Enable in browser:**

1. Open DevTools
2. Go to Settings → Sources
3. Enable "Enable JavaScript source maps"

### Hot Reload (Development Mode)

For development, you can use browser extensions like LiveReload or set up a watch script.

**Using browser-sync (optional):**

```bash
# Install browser-sync
npm install -D browser-sync

# Add to package.json scripts
"dev": "browser-sync start --proxy 'localhost:8080' --files 'assets/**/*.js, assets/**/*.css'"

# Run development server
npm run dev
```

## Testing

### Unit Tests (Jest)

```bash
# Run all tests
npm test

# Run tests in watch mode
npm run test:watch

# Run with coverage
npm run test:coverage
```

### E2E Tests (Playwright)

```bash
# Run E2E tests
npm run test:playwright

# Run in headed mode (see browser)
npm run test:playwright:headed

# Debug tests
npm run test:playwright:debug

# Interactive UI mode
npm run test:playwright:ui
```

### PHP Tests (PHPUnit)

```bash
# Run PHP tests
npm run test:php

# Run with coverage
npm run test:php:coverage
```

## Debugging

### Browser DevTools

1. Open browser DevTools (F12)
2. Go to Sources tab
3. Find your file in the file tree
4. Set breakpoints by clicking line numbers
5. Refresh page to trigger breakpoints

### VS Code Debugging

Create `.vscode/launch.json`:

```json
{
  "version": "0.2.0",
  "configurations": [
    {
      "type": "chrome",
      "request": "launch",
      "name": "Launch Chrome",
      "url": "http://localhost:8080/wp-admin",
      "webRoot": "${workspaceFolder}",
      "sourceMaps": true
    }
  ]
}
```

### Debug Mode

Enable debug mode in the application:

```javascript
// In browser console
masApp.setDebug(true);

// Or in configuration
window.masAdminConfig = {
    debug: true
};
```

## Code Style Guidelines

### JavaScript

- Use ES6+ features (const, let, arrow functions, classes)
- Use single quotes for strings
- Use 4 spaces for indentation
- Add semicolons
- Use camelCase for variables and functions
- Use PascalCase for classes
- Document public APIs with JSDoc

**Example:**

```javascript
/**
 * Calculate sum of two numbers
 * 
 * @param {number} a - First number
 * @param {number} b - Second number
 * @returns {number} Sum of a and b
 */
function calculateSum(a, b) {
    return a + b;
}
```

### File Organization

- One class per file
- File name matches class name (e.g., `EventBus.js` for `EventBus` class)
- Group related files in directories
- Keep files focused and under 500 lines

### Naming Conventions

- **Files**: kebab-case (e.g., `event-bus.js`)
- **Classes**: PascalCase (e.g., `EventBus`)
- **Functions**: camelCase (e.g., `handleClick`)
- **Constants**: UPPER_SNAKE_CASE (e.g., `MAX_RETRIES`)
- **Private methods**: prefix with underscore (e.g., `_privateMethod`)

## Git Workflow

### Branch Naming

- `feature/` - New features
- `fix/` - Bug fixes
- `refactor/` - Code refactoring
- `docs/` - Documentation updates
- `test/` - Test additions/updates

**Example:**

```bash
git checkout -b feature/add-theme-selector
git checkout -b fix/preview-css-injection
```

### Commit Messages

Follow conventional commits format:

```
<type>(<scope>): <subject>

<body>

<footer>
```

**Types:**
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation
- `style`: Code style (formatting, etc.)
- `refactor`: Code refactoring
- `test`: Tests
- `chore`: Maintenance

**Example:**

```bash
git commit -m "feat(preview): add debounced preview updates"
git commit -m "fix(form): resolve checkbox collection issue"
git commit -m "docs(api): update API client documentation"
```

### Pre-commit Checks

Run before committing:

```bash
# Lint code
npm run lint:fix

# Format code
npm run format

# Run tests
npm test
```

## Continuous Integration

The CI pipeline runs automatically on pull requests:

1. **Linting**: Checks code quality
2. **Formatting**: Verifies code formatting
3. **Unit Tests**: Runs Jest tests
4. **E2E Tests**: Runs Playwright tests

**Run CI checks locally:**

```bash
npm run ci
```

## Performance Profiling

### Chrome DevTools Performance

1. Open DevTools → Performance tab
2. Click Record
3. Perform actions in the app
4. Stop recording
5. Analyze flame graph and timings

### Lighthouse Audit

```bash
# Install Lighthouse CLI
npm install -g lighthouse

# Run audit
lighthouse http://localhost:8080/wp-admin --view
```

### Memory Profiling

1. Open DevTools → Memory tab
2. Take heap snapshot
3. Perform actions
4. Take another snapshot
5. Compare snapshots to find memory leaks

## Troubleshooting

### ESLint Errors

**Issue**: ESLint shows errors for global variables

**Solution**: Add globals to `.eslintrc.json`:

```json
{
  "globals": {
    "MyGlobal": "readonly"
  }
}
```

### Source Maps Not Working

**Issue**: Can't debug original source in DevTools

**Solution**: 
1. Check browser DevTools settings
2. Verify source maps are generated
3. Clear browser cache

### Tests Failing

**Issue**: Tests fail locally but pass in CI

**Solution**:
1. Ensure dependencies are up to date: `npm install`
2. Clear Jest cache: `npx jest --clearCache`
3. Check Node.js version matches CI

### Hot Reload Not Working

**Issue**: Changes don't reflect in browser

**Solution**:
1. Hard refresh browser (Ctrl+Shift+R)
2. Clear browser cache
3. Check file watcher is running
4. Verify file paths in watch configuration

## Resources

- [ESLint Documentation](https://eslint.org/docs/latest/)
- [Prettier Documentation](https://prettier.io/docs/en/)
- [Jest Documentation](https://jestjs.io/docs/getting-started)
- [Playwright Documentation](https://playwright.dev/docs/intro)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)

## Getting Help

- Check existing documentation in `docs/` directory
- Review code examples in `docs/PHASE3-CODE-EXAMPLES.md`
- Consult developer guide in `docs/PHASE3-DEVELOPER-GUIDE.md`
- Search existing issues in the repository
- Ask questions in team chat or create an issue

## Next Steps

After setup, refer to:

1. [Phase 3 Developer Guide](PHASE3-DEVELOPER-GUIDE.md) - Architecture and patterns
2. [Phase 3 Code Examples](PHASE3-CODE-EXAMPLES.md) - Practical examples
3. [API Documentation](API-DOCUMENTATION.md) - REST API reference
4. [Migration Guide](PHASE3-MIGRATION-GUIDE.md) - Migration from old code

Happy coding! 🚀
