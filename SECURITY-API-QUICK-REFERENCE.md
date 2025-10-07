# Security API Quick Reference

## Rate Limiting

### Check Rate Limit
```php
$rate_limiter = new MAS_Rate_Limiter_Service();
$result = $rate_limiter->check_rate_limit('/settings', $user_id);

if (is_wp_error($result)) {
    // Rate limit exceeded
    $retry_after = $result->get_error_data()['retry_after'];
}
```

### Get Rate Limit Headers
```php
$headers = $rate_limiter->get_rate_limit_headers('/settings', $user_id);
// Returns: ['X-RateLimit-Limit', 'X-RateLimit-Remaining', 'X-RateLimit-Reset']
```

### Configure Custom Limits
```php
$rate_limiter->configure_limits([
    '/custom-endpoint' => 50,  // 50 requests per window
]);
$rate_limiter->set_window(120);  // 2 minutes
$rate_limiter->set_default_limit(100);
```

### Reset Rate Limit
```php
$rate_limiter->reset_rate_limit('/settings', $user_id);
```

## Input Sanitization

### Color Sanitization
```php
$color = $this->sanitize_color('#ff0000');  // Returns: '#ff0000'
$invalid = $this->sanitize_color('red');     // Returns: ''
```

### CSS Unit Sanitization
```php
$unit = $this->sanitize_css_unit('10px');    // Returns: '10px'
$invalid = $this->sanitize_css_unit('10');   // Returns: ''
```

### Boolean Sanitization
```php
$bool = $this->sanitize_boolean('true');     // Returns: true
$bool = $this->sanitize_boolean(1);          // Returns: true
```

### Integer Sanitization
```php
$int = $this->sanitize_integer('150', 0, 100);  // Returns: 100 (clamped)
$int = $this->sanitize_integer('50', 0, 100);   // Returns: 50
```

### Array Sanitization
```php
$array = $this->sanitize_array(['<script>', 'safe'], 'sanitize_text_field');
// Returns: ['', 'safe']
```

### JSON Sanitization
```php
$json = $this->sanitize_json('{"key":"value"}');  // Returns: valid JSON
$invalid = $this->sanitize_json('invalid');       // Returns: false
```

### Output Escaping
```php
$safe = $this->escape_output($data);  // Recursively escapes HTML
```

## Security Logging

### Log Authentication Failure
```php
$security_logger = new MAS_Security_Logger_Service();
$security_logger->log_auth_failure(
    'username',
    '127.0.0.1',
    '/settings'
);
```

### Log Permission Denied
```php
$security_logger->log_permission_denied(
    $user_id,
    '/settings',
    'manage_options'
);
```

### Log Rate Limit Exceeded
```php
$security_logger->log_rate_limit_exceeded(
    $user_id,
    '/settings',
    $request_count
);
```

### Log Suspicious Activity
```php
$security_logger->log_suspicious_activity(
    'sql_injection_attempt',
    'Detected SQL injection pattern',
    ['pattern' => "' OR '1'='1"]
);
```

### Log Nonce Failure
```php
$security_logger->log_nonce_failure(
    '/settings',
    $nonce_value
);
```

### Log Validation Failure
```php
$security_logger->log_validation_failure(
    '/settings',
    ['field' => 'Invalid value']
);
```

### Get Security Logs
```php
// Get all logs
$logs = $security_logger->get_logs([], 50, 0);

// Filter by event type
$logs = $security_logger->get_logs([
    'event_type' => 'auth_failure'
], 50, 0);

// Filter by severity
$logs = $security_logger->get_logs([
    'severity' => 'critical'
], 50, 0);

// Filter by date range
$logs = $security_logger->get_logs([
    'date_from' => '2025-01-01',
    'date_to' => '2025-01-31'
], 50, 0);
```

### Get Log Statistics
```php
$stats = $security_logger->get_statistics(7);  // Last 7 days

// Returns:
// [
//   'total_events' => 150,
//   'by_severity' => ['error' => 10, 'warning' => 50, ...],
//   'by_type' => ['auth_failure' => 5, ...],
//   'by_user' => [1 => 100, 2 => 50],
//   'recent_critical' => [...]
// ]
```

### Cleanup Old Logs
```php
$deleted = $security_logger->cleanup_old_logs();  // Returns count
```

### Clear All Logs
```php
$security_logger->clear_logs();
```

### Configure Logger
```php
$security_logger->set_retention_days(60);    // Keep logs for 60 days
$security_logger->set_max_entries(2000);     // Keep max 2000 entries
```

## Validation Service

### Validate Color
```php
$validation = new MAS_Validation_Service();
$is_valid = $validation->validate_color('#ff0000');  // Returns: true
```

### Validate CSS Unit
```php
$is_valid = $validation->validate_css_unit('10px');  // Returns: true
```

### Validate Boolean
```php
$is_valid = $validation->validate_boolean(true);     // Returns: true
```

### Validate Numeric
```php
$is_valid = $validation->validate_numeric(50, 0, 100);  // Returns: true
```

### Validate String
```php
$is_valid = $validation->validate_string('test', 1, 100);  // Returns: true
```

### Validate Settings
```php
$result = $validation->validate_settings($data, $schema);

// Returns:
// [
//   'valid' => true/false,
//   'errors' => ['field' => 'error message', ...]
// ]
```

## Rate Limit Response Headers

All successful API responses include:
```
X-RateLimit-Limit: 30
X-RateLimit-Remaining: 29
X-RateLimit-Reset: 1234567890
```

## Rate Limit Error Response

When rate limit is exceeded (HTTP 429):
```json
{
  "code": "rate_limit_exceeded",
  "message": "Rate limit exceeded. Please try again in 45 seconds.",
  "data": {
    "status": 429,
    "retry_after": 45,
    "limit": 30,
    "window": 60
  }
}
```

## Security Event Types

- `auth_failure` - Authentication failed
- `permission_denied` - Permission check failed
- `rate_limit_exceeded` - Rate limit exceeded
- `nonce_failure` - Nonce validation failed
- `validation_failure` - Request validation failed
- `suspicious_activity` - Suspicious activity detected

## Severity Levels

1. **info** - General information
2. **warning** - Potential issues
3. **error** - Errors that need attention
4. **critical** - Critical security events

## Default Rate Limits

| Endpoint | Limit (per minute) |
|----------|-------------------|
| /settings | 30 |
| /preview | 20 |
| /themes | 40 |
| /backups | 20 |
| /import | 10 |
| /export | 20 |
| /diagnostics | 30 |
| Default | 60 |

## Best Practices

1. **Always sanitize input** before processing
2. **Always escape output** before displaying
3. **Check rate limits** for intensive operations
4. **Log security events** for audit trail
5. **Use WordPress functions** for sanitization
6. **Validate data types** before processing
7. **Monitor security logs** regularly
8. **Configure appropriate rate limits** per endpoint

## Testing

Test security features:
```bash
php test-task8-security-hardening.php
```

## Debug Mode

In debug mode (WP_DEBUG = true):
- Rate limiting bypassed for administrators
- Critical events logged to WordPress error log
- Detailed error messages in responses

## Production Recommendations

1. Set appropriate rate limits for your use case
2. Monitor security logs regularly
3. Set up alerts for critical events
4. Configure log retention based on compliance needs
5. Review and adjust rate limits based on usage patterns
6. Implement additional monitoring for suspicious patterns
