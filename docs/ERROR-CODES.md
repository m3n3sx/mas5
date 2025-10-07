# Modern Admin Styler V2 - Error Code Reference

## Overview

This document provides a comprehensive reference for all error codes returned by the Modern Admin Styler V2 REST API, including their meanings, causes, and solutions.

---

## Table of Contents

1. [Error Response Format](#error-response-format)
2. [HTTP Status Codes](#http-status-codes)
3. [Authentication Errors](#authentication-errors)
4. [Validation Errors](#validation-errors)
5. [Resource Errors](#resource-errors)
6. [Rate Limiting Errors](#rate-limiting-errors)
7. [Server Errors](#server-errors)
8. [Troubleshooting Guide](#troubleshooting-guide)

---

## Error Response Format

All errors follow this standardized format:

```json
{
  "code": "error_code",
  "message": "Human-readable error message",
  "data": {
    "status": 400,
    "errors": {
      "field_name": "Field-specific error message"
    }
  }
}
```

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `code` | string | Machine-readable error code |
| `message` | string | Human-readable error message |
| `data.status` | integer | HTTP status code |
| `data.errors` | object | Field-specific validation errors (optional) |

---

## HTTP Status Codes

| Code | Status | Meaning |
|------|--------|---------|
| 200 | OK | Request successful |
| 201 | Created | Resource created successfully |
| 304 | Not Modified | Resource hasn't changed (ETag match) |
| 400 | Bad Request | Invalid request parameters |
| 401 | Unauthorized | Authentication required |
| 403 | Forbidden | Insufficient permissions |
| 404 | Not Found | Resource not found |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Server error occurred |

---

## Authentication Errors

### rest_forbidden

**HTTP Status:** 403 Forbidden

**Message:** "You do not have permission to access this resource."

**Cause:** User lacks the required `manage_options` capability.

**Solution:**
- Ensure the user is logged in as an administrator
- Check that the user has the `manage_options` capability
- Verify the user role has not been modified

**Example:**
```json
{
  "code": "rest_forbidden",
  "message": "You do not have permission to access this resource.",
  "data": {
    "status": 403
  }
}
```

**JavaScript Handling:**
```javascript
if (error.code === 'rest_forbidden') {
  alert('You do not have permission to perform this action. Please contact an administrator.');
}
```

---

### rest_cookie_invalid_nonce

**HTTP Status:** 403 Forbidden

**Message:** "Cookie nonce is invalid."

**Cause:** 
- Nonce has expired (typically after 24 hours)
- Nonce is missing from the request
- User session has changed

**Solution:**
- Refresh the page to get a new nonce
- Ensure `X-WP-Nonce` header is included in requests
- Check that `wpApiSettings.nonce` is available

**Example:**
```json
{
  "code": "rest_cookie_invalid_nonce",
  "message": "Cookie nonce is invalid.",
  "data": {
    "status": 403
  }
}
```

**JavaScript Handling:**
```javascript
if (error.code === 'rest_cookie_invalid_nonce') {
  alert('Your session has expired. The page will reload.');
  setTimeout(() => location.reload(), 2000);
}
```

---

### rest_no_route

**HTTP Status:** 404 Not Found

**Message:** "No route was found matching the URL and request method."

**Cause:**
- Incorrect endpoint URL
- API endpoint doesn't exist
- Plugin is not activated

**Solution:**
- Verify the endpoint URL is correct
- Check that the plugin is activated
- Ensure you're using the correct HTTP method

**Example:**
```json
{
  "code": "rest_no_route",
  "message": "No route was found matching the URL and request method.",
  "data": {
    "status": 404
  }
}
```

---

## Validation Errors

### validation_failed

**HTTP Status:** 400 Bad Request

**Message:** "Validation failed" or "Settings validation failed"

**Cause:** One or more fields failed validation.

**Solution:** Check the `data.errors` object for field-specific errors and correct the invalid values.

**Example:**
```json
{
  "code": "validation_failed",
  "message": "Settings validation failed",
  "data": {
    "status": 400,
    "errors": {
      "menu_background": "Must be a valid hex color (#RRGGBB)",
      "menu_width": "Must be between 100 and 400",
      "animation_speed": "Must be between 100 and 1000"
    }
  }
}
```

**JavaScript Handling:**
```javascript
if (error.code === 'validation_failed' && error.data.errors) {
  for (const [field, message] of Object.entries(error.data.errors)) {
    showFieldError(field, message);
  }
}
```

**Common Validation Errors:**

| Field | Error | Solution |
|-------|-------|----------|
| `menu_background` | "Must be a valid hex color" | Use format `#RRGGBB` (e.g., `#1e1e2e`) |
| `menu_width` | "Must be between 100 and 400" | Provide integer between 100-400 |
| `animation_speed` | "Must be between 100 and 1000" | Provide integer between 100-1000 |
| `theme` | "Must be 1-50 characters" | Provide string with 1-50 characters |
| `color_scheme` | "Must be 'light' or 'dark'" | Use only 'light' or 'dark' |

---

### invalid_settings

**HTTP Status:** 400 Bad Request

**Message:** "Invalid settings provided"

**Cause:** Settings data is malformed or missing.

**Solution:**
- Ensure settings is an object/array
- Check JSON syntax is valid
- Verify required fields are present

**Example:**
```json
{
  "code": "invalid_settings",
  "message": "Invalid settings provided",
  "data": {
    "status": 400
  }
}
```

---

### missing_parameters

**HTTP Status:** 400 Bad Request

**Message:** "Missing required parameters: {param1}, {param2}"

**Cause:** One or more required parameters are missing from the request.

**Solution:** Include all required parameters in the request.

**Example:**
```json
{
  "code": "missing_parameters",
  "message": "Missing required parameters: id, name",
  "data": {
    "status": 400,
    "missing_parameters": ["id", "name"]
  }
}
```

---

### invalid_theme_id

**HTTP Status:** 400 Bad Request

**Message:** "Theme ID must contain only lowercase letters, numbers, and hyphens"

**Cause:** Theme ID contains invalid characters.

**Solution:** Use only lowercase letters (a-z), numbers (0-9), and hyphens (-).

**Valid Examples:**
- `my-custom-theme`
- `dark-blue-2024`
- `theme-v2`

**Invalid Examples:**
- `My Theme` (spaces and uppercase)
- `theme_name` (underscores)
- `theme!` (special characters)

---

### invalid_type

**HTTP Status:** 400 Bad Request

**Message:** "Settings must be an object" or similar type error

**Cause:** Parameter has wrong data type.

**Solution:** Ensure the parameter matches the expected type (object, string, integer, boolean, array).

---

## Resource Errors

### not_found

**HTTP Status:** 404 Not Found

**Message:** "{Resource} not found"

**Cause:** The requested resource (theme, backup, etc.) doesn't exist.

**Solution:**
- Verify the resource ID is correct
- Check that the resource hasn't been deleted
- List available resources first

**Examples:**
```json
{
  "code": "not_found",
  "message": "Theme not found",
  "data": {
    "status": 404
  }
}
```

```json
{
  "code": "not_found",
  "message": "Backup not found",
  "data": {
    "status": 404
  }
}
```

---

### theme_readonly

**HTTP Status:** 403 Forbidden

**Message:** "Cannot modify predefined theme"

**Cause:** Attempting to update or delete a predefined (read-only) theme.

**Solution:** Only custom themes can be modified or deleted. Create a new custom theme instead.

**Example:**
```json
{
  "code": "theme_readonly",
  "message": "Cannot modify predefined theme",
  "data": {
    "status": 403
  }
}
```

---

### theme_exists

**HTTP Status:** 400 Bad Request

**Message:** "Theme with this ID already exists"

**Cause:** Attempting to create a theme with an ID that's already in use.

**Solution:** Use a different, unique theme ID.

**Example:**
```json
{
  "code": "theme_exists",
  "message": "Theme with this ID already exists",
  "data": {
    "status": 400
  }
}
```

---

### backup_not_found

**HTTP Status:** 404 Not Found

**Message:** "Backup not found"

**Cause:** The specified backup ID doesn't exist.

**Solution:**
- List available backups first
- Verify the backup ID is correct
- Check if the backup was deleted

---

## Rate Limiting Errors

### rate_limited

**HTTP Status:** 429 Too Many Requests

**Message:** "Too many requests. Please wait before making another request." or "Rate limit exceeded"

**Cause:** Too many requests in a short time period.

**Solution:**
- Wait before making another request
- Check the `Retry-After` header for wait time
- Implement client-side debouncing
- Reduce request frequency

**Example:**
```json
{
  "code": "rate_limited",
  "message": "Rate limit exceeded. Please try again in 60 seconds.",
  "data": {
    "status": 429,
    "retry_after": 60
  }
}
```

**Response Headers:**
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 0
X-RateLimit-Reset: 1704902460
Retry-After: 60
```

**JavaScript Handling:**
```javascript
if (error.code === 'rate_limited') {
  const retryAfter = error.data.retry_after || 60;
  alert(`Too many requests. Please wait ${retryAfter} seconds.`);
  
  // Retry after delay
  setTimeout(() => {
    retryRequest();
  }, retryAfter * 1000);
}
```

---

## Server Errors

### database_error

**HTTP Status:** 500 Internal Server Error

**Message:** "Database error occurred"

**Cause:** Database operation failed.

**Solution:**
- Check database connection
- Verify database tables exist
- Check WordPress debug log
- Contact site administrator

**Example:**
```json
{
  "code": "database_error",
  "message": "Failed to save settings to database",
  "data": {
    "status": 500
  }
}
```

---

### get_settings_failed

**HTTP Status:** 500 Internal Server Error

**Message:** "Failed to retrieve settings"

**Cause:** Error occurred while fetching settings from database.

**Solution:**
- Check database connection
- Verify plugin is properly installed
- Check WordPress debug log

---

### save_settings_failed

**HTTP Status:** 500 Internal Server Error

**Message:** "Failed to save settings"

**Cause:** Error occurred while saving settings to database.

**Solution:**
- Check database write permissions
- Verify disk space is available
- Check WordPress debug log

---

### export_failed

**HTTP Status:** 500 Internal Server Error

**Message:** "Export failed: {reason}"

**Cause:** Error occurred during settings export.

**Solution:**
- Check file system permissions
- Verify sufficient disk space
- Try again

---

### import_failed

**HTTP Status:** 500 Internal Server Error

**Message:** "Import failed: {reason}"

**Cause:** Error occurred during settings import.

**Solution:**
- Verify import data is valid JSON
- Check data format matches expected structure
- Ensure backup creation succeeded (if enabled)

---

### invalid_import_data

**HTTP Status:** 400 Bad Request

**Message:** "Import data must be a valid JSON object"

**Cause:** Import data is not valid JSON or not an object.

**Solution:**
- Verify the import file is valid JSON
- Check the file wasn't corrupted
- Ensure the file was exported from the same plugin

---

### css_generation_failed

**HTTP Status:** 500 Internal Server Error

**Message:** "Failed to generate CSS"

**Cause:** Error occurred during CSS generation.

**Solution:**
- Check settings are valid
- Verify file system permissions
- Check WordPress debug log
- Fallback CSS will be used

---

### diagnostics_error

**HTTP Status:** 500 Internal Server Error

**Message:** "Failed to retrieve diagnostics: {reason}"

**Cause:** Error occurred while collecting diagnostic information.

**Solution:**
- Check system permissions
- Verify plugin files are intact
- Try again

---

### health_check_error

**HTTP Status:** 500 Internal Server Error

**Message:** "Health check failed: {reason}"

**Cause:** Error occurred during health check.

**Solution:**
- Check system status manually
- Verify plugin is properly installed
- Check WordPress debug log

---

### performance_metrics_error

**HTTP Status:** 500 Internal Server Error

**Message:** "Failed to retrieve performance metrics: {reason}"

**Cause:** Error occurred while collecting performance metrics.

**Solution:**
- Check system permissions
- Verify monitoring functions are available
- Try again

---

## Troubleshooting Guide

### Quick Diagnosis

Use this flowchart to diagnose common errors:

```
Error occurred
    ├─ Status 403?
    │   ├─ Code: rest_forbidden → Check user permissions
    │   └─ Code: rest_cookie_invalid_nonce → Refresh page
    │
    ├─ Status 400?
    │   ├─ Code: validation_failed → Check field values
    │   ├─ Code: missing_parameters → Add required parameters
    │   └─ Code: invalid_* → Fix data format
    │
    ├─ Status 404?
    │   └─ Code: not_found → Verify resource exists
    │
    ├─ Status 429?
    │   └─ Code: rate_limited → Wait and retry
    │
    └─ Status 500?
        └─ Check debug log and contact support
```

### Common Solutions

#### 1. Refresh Nonce

```javascript
// Reload page to get fresh nonce
location.reload();
```

#### 2. Validate Before Sending

```javascript
function validateSettings(settings) {
  const errors = {};
  
  // Validate colors
  if (settings.menu_background && !/^#[a-f0-9]{6}$/i.test(settings.menu_background)) {
    errors.menu_background = 'Invalid hex color';
  }
  
  // Validate ranges
  if (settings.menu_width && (settings.menu_width < 100 || settings.menu_width > 400)) {
    errors.menu_width = 'Must be between 100 and 400';
  }
  
  return Object.keys(errors).length === 0 ? null : errors;
}
```

#### 3. Handle Rate Limiting

```javascript
async function requestWithRetry(fn, maxRetries = 3) {
  for (let i = 0; i < maxRetries; i++) {
    try {
      return await fn();
    } catch (error) {
      if (error.code === 'rate_limited' && i < maxRetries - 1) {
        const delay = error.data.retry_after * 1000 || 60000;
        await new Promise(resolve => setTimeout(resolve, delay));
        continue;
      }
      throw error;
    }
  }
}
```

#### 4. Check Resource Exists

```javascript
async function getThemeSafely(themeId) {
  try {
    return await client.getTheme(themeId);
  } catch (error) {
    if (error.code === 'not_found') {
      console.log('Theme not found, listing available themes...');
      const themes = await client.getThemes();
      console.log('Available themes:', themes.data);
    }
    throw error;
  }
}
```

### Debug Mode

Enable WordPress debug mode to see detailed error messages:

```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Check the debug log at `wp-content/debug.log`.

### Testing Errors

Test error handling in the browser console:

```javascript
// Test validation error
fetch('/wp-json/mas-v2/v1/settings', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-WP-Nonce': wpApiSettings.nonce
  },
  credentials: 'same-origin',
  body: JSON.stringify({
    menu_background: 'invalid-color' // Invalid
  })
})
.then(r => r.json())
.then(console.log);

// Test not found error
fetch('/wp-json/mas-v2/v1/themes/nonexistent', {
  headers: { 'X-WP-Nonce': wpApiSettings.nonce },
  credentials: 'same-origin'
})
.then(r => r.json())
.then(console.log);
```

---

## Error Handling Best Practices

### 1. Always Catch Errors

```javascript
try {
  await client.saveSettings(settings);
} catch (error) {
  handleError(error);
}
```

### 2. Provide User-Friendly Messages

```javascript
function getUserMessage(error) {
  const messages = {
    'rest_forbidden': 'You do not have permission to perform this action.',
    'rest_cookie_invalid_nonce': 'Your session has expired. Please refresh the page.',
    'validation_failed': 'Please check your input and try again.',
    'rate_limited': 'Too many requests. Please wait a moment.',
    'not_found': 'The requested resource was not found.'
  };
  
  return messages[error.code] || 'An unexpected error occurred.';
}
```

### 3. Log Errors for Debugging

```javascript
function handleError(error) {
  // Log for debugging
  console.error('API Error:', {
    code: error.code,
    message: error.message,
    data: error.data
  });
  
  // Show user-friendly message
  showNotification(getUserMessage(error), 'error');
}
```

### 4. Implement Retry Logic

```javascript
async function requestWithRetry(fn, maxRetries = 3) {
  let lastError;
  
  for (let i = 0; i < maxRetries; i++) {
    try {
      return await fn();
    } catch (error) {
      lastError = error;
      
      // Don't retry client errors (4xx)
      if (error.data?.status >= 400 && error.data?.status < 500) {
        throw error;
      }
      
      // Wait before retrying
      await new Promise(resolve => setTimeout(resolve, Math.pow(2, i) * 1000));
    }
  }
  
  throw lastError;
}
```

---

## Support

If you encounter an error not listed here or need additional help:

1. Check the WordPress debug log
2. Review the API documentation
3. Test with Postman collection
4. Contact support with:
   - Error code and message
   - Request details (endpoint, method, parameters)
   - Debug log entries
   - Steps to reproduce

---

**Last Updated:** January 10, 2025  
**Version:** 2.2.0
