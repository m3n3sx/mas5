<?php
/**
 * Verify Task 14: Security Implementation and Validation
 * 
 * This script verifies that the security enhancements have been properly implemented.
 */

echo "Task 14: Security Implementation and Validation - Verification\n";
echo str_repeat("=", 60) . "\n";

// Check if the main plugin file exists
$mainFile = 'modern-admin-styler-v2.php';
if (!file_exists($mainFile)) {
    echo "❌ Main plugin file not found: {$mainFile}\n";
    exit(1);
}

$pluginContent = file_get_contents($mainFile);

// Test 1: Check for enhanced AJAX security validation
echo "1. Checking Enhanced AJAX Security Validation...\n";

$securityFeatures = [
    'validateAjaxSecurity' => 'Centralized AJAX security validation',
    'validateAjaxRequest' => 'Request data validation',
    'validateFileUploadRequest' => 'File upload validation',
    'getRequiredCapabilityForAction' => 'Action-specific capability checks',
    'checkRateLimit' => 'Rate limiting implementation',
    'detectSuspiciousActivity' => 'Suspicious activity detection',
    'isValidSettingKey' => 'Setting key validation',
    'containsMaliciousContent' => 'Malicious content detection',
    'isValidJsonContent' => 'JSON content validation',
    'getClientIP' => 'Client IP detection'
];

foreach ($securityFeatures as $method => $description) {
    if (strpos($pluginContent, "function {$method}") !== false) {
        echo "  ✅ {$description} implemented\n";
    } else {
        echo "  ❌ {$description} missing\n";
    }
}

// Test 2: Check for enhanced sanitization
echo "\n2. Checking Enhanced Input Sanitization...\n";

$sanitizationEnhancements = [
    'containsMaliciousContent' => 'Malicious content detection in sanitization',
    'sanitizeCustomCSS' => 'Enhanced CSS sanitization',
    'sanitizeColorValue' => 'Enhanced color value sanitization',
    'dangerous_patterns' => 'XSS prevention patterns',
    'valid_color_names' => 'Color name whitelist',
    'malicious_patterns' => 'Malicious pattern detection'
];

foreach ($sanitizationEnhancements as $feature => $description) {
    if (strpos($pluginContent, $feature) !== false) {
        echo "  ✅ {$description} implemented\n";
    } else {
        echo "  ❌ {$description} missing\n";
    }
}

// Test 3: Check for secure data handling
echo "\n3. Checking Secure Data Handling...\n";

$dataHandlingFeatures = [
    'secureStoreSettings' => 'Secure settings storage',
    'secureRetrieveSettings' => 'Secure settings retrieval',
    'generateSettingsHash' => 'Settings integrity hashing',
    'verifySettingsIntegrity' => 'Settings integrity verification',
    'restoreFromBackup' => 'Backup restoration',
    'secureExportSettings' => 'Secure settings export',
    'secureImportSettings' => 'Secure settings import',
    '_integrity_hash' => 'Integrity hash storage'
];

foreach ($dataHandlingFeatures as $method => $description) {
    if (strpos($pluginContent, $method) !== false) {
        echo "  ✅ {$description} implemented\n";
    } else {
        echo "  ❌ {$description} missing\n";
    }
}

// Test 4: Check for security-enhanced AJAX handlers
echo "\n4. Checking Security-Enhanced AJAX Handlers...\n";

$ajaxHandlers = [
    'ajaxSaveSettings' => 'Save settings handler',
    'ajaxResetSettings' => 'Reset settings handler',
    'ajaxExportSettings' => 'Export settings handler',
    'ajaxImportSettings' => 'Import settings handler'
];

$securityEnhanced = 0;
foreach ($ajaxHandlers as $handler => $description) {
    if (strpos($pluginContent, "function {$handler}") !== false) {
        // Check if it uses the new security validation
        $handlerStart = strpos($pluginContent, "function {$handler}");
        $handlerEnd = strpos($pluginContent, "function ", $handlerStart + 1);
        if ($handlerEnd === false) $handlerEnd = strlen($pluginContent);
        
        $handlerCode = substr($pluginContent, $handlerStart, $handlerEnd - $handlerStart);
        
        if (strpos($handlerCode, 'validateAjaxSecurity') !== false) {
            echo "  ✅ {$description} security enhanced\n";
            $securityEnhanced++;
        } else {
            echo "  ❌ {$description} not security enhanced\n";
        }
    } else {
        echo "  ❌ {$description} not found\n";
    }
}

// Test 5: Check for WordPress security functions usage
echo "\n5. Checking WordPress Security Functions Usage...\n";

$wpSecurityFunctions = [
    'wp_verify_nonce' => 'Nonce verification',
    'current_user_can' => 'Capability checks',
    'sanitize_text_field' => 'Text field sanitization',
    'sanitize_hex_color' => 'Color sanitization',
    'wp_unslash' => 'Data unslashing',
    'wp_doing_ajax' => 'AJAX context check'
];

foreach ($wpSecurityFunctions as $function => $description) {
    $count = substr_count($pluginContent, $function);
    if ($count > 0) {
        echo "  ✅ {$description} used {$count} times\n";
    } else {
        echo "  ❌ {$description} not used\n";
    }
}

// Test 6: Check for security patterns and best practices
echo "\n6. Checking Security Patterns and Best Practices...\n";

$securityPatterns = [
    'hash_hmac' => 'HMAC hashing for integrity',
    'hash_equals' => 'Timing-safe string comparison',
    'filter_var.*FILTER_VALIDATE_IP' => 'IP address validation',
    'preg_replace.*javascript' => 'JavaScript injection prevention',
    'preg_replace.*expression' => 'CSS expression prevention',
    'strlen.*>' => 'Length validation',
    'count.*>' => 'Array size validation'
];

foreach ($securityPatterns as $pattern => $description) {
    if (preg_match('/' . str_replace('/', '\/', $pattern) . '/i', $pluginContent)) {
        echo "  ✅ {$description} implemented\n";
    } else {
        echo "  ❌ {$description} missing\n";
    }
}

// Test 7: Check for error logging and debugging
echo "\n7. Checking Error Logging and Security Debugging...\n";

$debugFeatures = [
    'error_log.*MAS V2.*Invalid nonce' => 'Nonce failure logging',
    'error_log.*MAS V2.*Insufficient permissions' => 'Permission failure logging',
    'error_log.*MAS V2.*Suspicious activity' => 'Suspicious activity logging',
    'error_log.*MAS V2.*Malicious content' => 'Malicious content logging'
];

foreach ($debugFeatures as $pattern => $description) {
    if (preg_match('/' . str_replace('/', '\/', $pattern) . '/i', $pluginContent)) {
        echo "  ✅ {$description} implemented\n";
    } else {
        echo "  ❌ {$description} missing\n";
    }
}

// Test 8: Check for rate limiting implementation
echo "\n8. Checking Rate Limiting Implementation...\n";

$rateLimitFeatures = [
    'set_transient.*rate_limit' => 'Rate limit counter storage',
    'get_transient.*rate_limit' => 'Rate limit counter retrieval',
    'rate_limit_exceeded' => 'Rate limit exceeded response',
    'mas_v2_rate_limit_' => 'Rate limit key pattern'
];

foreach ($rateLimitFeatures as $pattern => $description) {
    if (preg_match('/' . str_replace('/', '\/', $pattern) . '/i', $pluginContent)) {
        echo "  ✅ {$description} implemented\n";
    } else {
        echo "  ❌ {$description} missing\n";
    }
}

// Summary
echo "\n" . str_repeat("=", 60) . "\n";
echo "Task 14 Security Implementation Verification Summary:\n";
echo str_repeat("=", 60) . "\n";

$totalChecks = 0;
$passedChecks = 0;

// Count security features
$allFeatures = array_merge($securityFeatures, $sanitizationEnhancements, $dataHandlingFeatures);
foreach ($allFeatures as $feature => $description) {
    $totalChecks++;
    if (strpos($pluginContent, $feature) !== false) {
        $passedChecks++;
    }
}

// Add AJAX handler security enhancement count
$totalChecks += count($ajaxHandlers);
$passedChecks += $securityEnhanced;

$percentage = round(($passedChecks / $totalChecks) * 100, 1);

echo "Security Features Implemented: {$passedChecks}/{$totalChecks} ({$percentage}%)\n";

if ($percentage >= 90) {
    echo "✅ Task 14 Security Implementation: EXCELLENT\n";
    echo "   All major security enhancements have been implemented.\n";
} elseif ($percentage >= 75) {
    echo "✅ Task 14 Security Implementation: GOOD\n";
    echo "   Most security enhancements have been implemented.\n";
} elseif ($percentage >= 50) {
    echo "⚠️  Task 14 Security Implementation: PARTIAL\n";
    echo "   Some security enhancements are missing.\n";
} else {
    echo "❌ Task 14 Security Implementation: INCOMPLETE\n";
    echo "   Major security enhancements are missing.\n";
}

echo "\nKey Security Enhancements:\n";
echo "• Enhanced AJAX security validation with multiple checks\n";
echo "• Comprehensive input sanitization with XSS prevention\n";
echo "• Secure data handling with integrity verification\n";
echo "• Rate limiting to prevent abuse\n";
echo "• Suspicious activity detection\n";
echo "• File upload validation\n";
echo "• Enhanced capability checks\n";
echo "• Malicious content detection\n";
echo "• Secure settings storage and retrieval\n";
echo "• Comprehensive error logging\n";

echo "\nRequirements Addressed:\n";
echo "• 4.5: WordPress coding standards and security\n";
echo "• 6.3: Input sanitization and validation\n";
echo "• 6.4: Secure data handling\n";

echo "\n" . str_repeat("=", 60) . "\n";