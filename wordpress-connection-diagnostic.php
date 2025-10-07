<?php
/**
 * WordPress Connection Diagnostic Tool
 * Helps troubleshoot WordPress.org connection issues
 */

echo "🔍 WordPress Connection Diagnostic Tool\n";
echo "=====================================\n\n";

// Test basic connectivity
echo "1. Testing basic connectivity...\n";

$test_urls = [
    'https://wordpress.org',
    'https://api.wordpress.org',
    'https://downloads.wordpress.org'
];

foreach ($test_urls as $url) {
    echo "   Testing: $url\n";
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'user_agent' => 'WordPress Connection Test'
        ]
    ]);
    
    $result = @file_get_contents($url, false, $context);
    
    if ($result !== false) {
        echo "   ✅ SUCCESS: Connection established\n";
    } else {
        echo "   ❌ FAILED: Cannot connect\n";
        $error = error_get_last();
        if ($error) {
            echo "   Error: " . $error['message'] . "\n";
        }
    }
    echo "\n";
}

// Test cURL if available
echo "2. Testing cURL functionality...\n";
if (function_exists('curl_init')) {
    echo "   ✅ cURL is available\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://wordpress.org');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For testing only
    
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($result && $http_code == 200) {
        echo "   ✅ cURL connection successful\n";
    } else {
        echo "   ❌ cURL connection failed\n";
        echo "   HTTP Code: $http_code\n";
        if ($error) {
            echo "   Error: $error\n";
        }
    }
} else {
    echo "   ❌ cURL is not available\n";
}

echo "\n3. Recommendations:\n";
echo "   • If connections fail, check your server's firewall settings\n";
echo "   • Ensure outbound HTTPS connections are allowed\n";
echo "   • Contact your hosting provider if issues persist\n";
echo "   • These warnings don't affect plugin functionality\n";

echo "\n🎯 Quick Fix for WordPress Warnings:\n";
echo "   Add this to your wp-config.php to disable update checks:\n";
echo "   define('AUTOMATIC_UPDATER_DISABLED', true);\n";
echo "   define('WP_AUTO_UPDATE_CORE', false);\n";