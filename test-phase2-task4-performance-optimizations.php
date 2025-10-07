<?php
/**
 * Test Phase 2 Task 4: Advanced Performance Optimizations
 * 
 * Tests ETag support, Last-Modified headers, cache service enhancements,
 * database query optimization, and JavaScript conditional requests.
 * 
 * @package ModernAdminStylerV2
 * @since 2.3.0
 */

// Load WordPress
require_once dirname(__FILE__) . '/../../../wp-load.php';

// Ensure user is logged in as admin
if (!current_user_can('manage_options')) {
    wp_die('You must be an administrator to run this test.');
}

// Load required files
require_once plugin_dir_path(__FILE__) . 'includes/services/class-mas-cache-service.php';
require_once plugin_dir_path(__FILE__) . 'includes/services/class-mas-settings-service.php';
require_once plugin_dir_path(__FILE__) . 'includes/services/class-mas-database-optimizer.php';
require_once plugin_dir_path(__FILE__) . 'includes/api/class-mas-rest-controller.php';
require_once plugin_dir_path(__FILE__) . 'includes/api/class-mas-settings-controller.php';

echo "<h1>Phase 2 Task 4: Advanced Performance Optimizations Test</h1>\n";
echo "<pre>\n";

$test_results = [];
$all_passed = true;

/**
 * Test 1: ETag Support in Settings Controller
 */
echo "\n=== Test 1: ETag Support ===\n";
try {
    $settings_controller = new MAS_Settings_Controller();
    $request = new WP_REST_Request('GET', '/mas-v2/v1/settings');
    $request->set_header('X-WP-Nonce', wp_create_nonce('wp_rest'));
    
    $response = $settings_controller->get_settings($request);
    
    if ($response instanceof WP_REST_Response) {
        $etag = $response->get_headers()['ETag'] ?? null;
        $x_cache = $response->get_headers()['X-Cache'] ?? null;
        
        if ($etag) {
            echo "✓ ETag header present: $etag\n";
            $test_results['etag_present'] = true;
        } else {
            echo "✗ ETag header missing\n";
            $test_results['etag_present'] = false;
            $all_passed = false;
        }
        
        if ($x_cache) {
            echo "✓ X-Cache header present: $x_cache\n";
            $test_results['x_cache_present'] = true;
        } else {
            echo "✗ X-Cache header missing\n";
            $test_results['x_cache_present'] = false;
            $all_passed = false;
        }
        
        // Test If-None-Match (304 response)
        $request2 = new WP_REST_Request('GET', '/mas-v2/v1/settings');
        $request2->set_header('X-WP-Nonce', wp_create_nonce('wp_rest'));
        $request2->set_header('If-None-Match', $etag);
        
        $response2 = $settings_controller->get_settings($request2);
        
        if ($response2->get_status() === 304) {
            echo "✓ 304 Not Modified returned for matching ETag\n";
            $test_results['etag_304'] = true;
        } else {
            echo "✗ Expected 304 but got: " . $response2->get_status() . "\n";
            $test_results['etag_304'] = false;
            $all_passed = false;
        }
    } else {
        echo "✗ Failed to get settings response\n";
        $test_results['etag_present'] = false;
        $all_passed = false;
    }
} catch (Exception $e) {
    echo "✗ Exception: " . $e->getMessage() . "\n";
    $test_results['etag_test'] = false;
    $all_passed = false;
}

/**
 * Test 2: Last-Modified Header Support
 */
echo "\n=== Test 2: Last-Modified Header ===\n";
try {
    $settings_service = MAS_Settings_Service::get_instance();
    $last_modified = $settings_service->get_last_modified_time();
    
    if ($last_modified > 0) {
        echo "✓ Last modified time retrieved: " . date('Y-m-d H:i:s', $last_modified) . "\n";
        $test_results['last_modified_time'] = true;
    } else {
        echo "✗ Invalid last modified time\n";
        $test_results['last_modified_time'] = false;
        $all_passed = false;
    }
    
    // Test Last-Modified header in response
    $request = new WP_REST_Request('GET', '/mas-v2/v1/settings');
    $request->set_header('X-WP-Nonce', wp_create_nonce('wp_rest'));
    
    $response = $settings_controller->get_settings($request);
    $last_modified_header = $response->get_headers()['Last-Modified'] ?? null;
    
    if ($last_modified_header) {
        echo "✓ Last-Modified header present: $last_modified_header\n";
        $test_results['last_modified_header'] = true;
        
        // Test If-Modified-Since (304 response)
        $request2 = new WP_REST_Request('GET', '/mas-v2/v1/settings');
        $request2->set_header('X-WP-Nonce', wp_create_nonce('wp_rest'));
        $request2->set_header('If-Modified-Since', $last_modified_header);
        
        $response2 = $settings_controller->get_settings($request2);
        
        if ($response2->get_status() === 304) {
            echo "✓ 304 Not Modified returned for If-Modified-Since\n";
            $test_results['last_modified_304'] = true;
        } else {
            echo "✗ Expected 304 but got: " . $response2->get_status() . "\n";
            $test_results['last_modified_304'] = false;
            $all_passed = false;
        }
    } else {
        echo "✗ Last-Modified header missing\n";
        $test_results['last_modified_header'] = false;
        $all_passed = false;
    }
} catch (Exception $e) {
    echo "✗ Exception: " . $e->getMessage() . "\n";
    $test_results['last_modified_test'] = false;
    $all_passed = false;
}

/**
 * Test 3: Advanced Cache Service
 */
echo "\n=== Test 3: Advanced Cache Service ===\n";
try {
    $cache_service = new MAS_Cache_Service();
    
    // Test cache operations
    $test_key = 'test_cache_key';
    $test_data = ['test' => 'data', 'timestamp' => time()];
    
    $cache_service->set($test_key, $test_data, 300);
    $cached_data = $cache_service->get($test_key);
    
    if ($cached_data === $test_data) {
        echo "✓ Cache set and get working\n";
        $test_results['cache_operations'] = true;
    } else {
        echo "✗ Cache operations failed\n";
        $test_results['cache_operations'] = false;
        $all_passed = false;
    }
    
    // Test cache statistics
    $stats = $cache_service->get_stats();
    
    if (isset($stats['hits']) && isset($stats['misses']) && isset($stats['hit_rate'])) {
        echo "✓ Cache statistics available:\n";
        echo "  - Hits: {$stats['hits']}\n";
        echo "  - Misses: {$stats['misses']}\n";
        echo "  - Hit Rate: {$stats['hit_rate_percentage']}\n";
        $test_results['cache_stats'] = true;
    } else {
        echo "✗ Cache statistics incomplete\n";
        $test_results['cache_stats'] = false;
        $all_passed = false;
    }
    
    // Test cache warming
    $warm_results = $cache_service->warm_cache();
    
    if (is_array($warm_results)) {
        echo "✓ Cache warming executed:\n";
        foreach ($warm_results as $key => $result) {
            echo "  - $key: $result\n";
        }
        $test_results['cache_warming'] = true;
    } else {
        echo "✗ Cache warming failed\n";
        $test_results['cache_warming'] = false;
        $all_passed = false;
    }
    
    // Cleanup
    $cache_service->delete($test_key);
} catch (Exception $e) {
    echo "✗ Exception: " . $e->getMessage() . "\n";
    $test_results['cache_service_test'] = false;
    $all_passed = false;
}

/**
 * Test 4: Database Optimizer
 */
echo "\n=== Test 4: Database Optimizer ===\n";
try {
    $db_optimizer = MAS_Database_Optimizer::get_instance();
    
    // Test database statistics
    $db_stats = $db_optimizer->get_stats();
    
    if (isset($db_stats['total_options']) && isset($db_stats['backup_count'])) {
        echo "✓ Database statistics available:\n";
        echo "  - Total Options: {$db_stats['total_options']}\n";
        echo "  - Backup Count: {$db_stats['backup_count']}\n";
        echo "  - Transient Count: {$db_stats['transient_count']}\n";
        echo "  - Table Size: {$db_stats['options_table_size_mb']} MB\n";
        $test_results['db_stats'] = true;
    } else {
        echo "✗ Database statistics incomplete\n";
        $test_results['db_stats'] = false;
        $all_passed = false;
    }
    
    // Test options table optimization
    $optimize_result = $db_optimizer->optimize_options_table();
    
    if ($optimize_result['status'] === 'success') {
        echo "✓ Options table optimization check passed\n";
        echo "  - Indexes: " . count($optimize_result['indexes']) . "\n";
        $test_results['db_optimization'] = true;
    } else {
        echo "✗ Options table optimization failed\n";
        $test_results['db_optimization'] = false;
        $all_passed = false;
    }
    
    // Test transient cleanup
    $deleted_count = $db_optimizer->cleanup_expired_transients();
    echo "✓ Expired transients cleaned up: $deleted_count\n";
    $test_results['transient_cleanup'] = true;
} catch (Exception $e) {
    echo "✗ Exception: " . $e->getMessage() . "\n";
    $test_results['db_optimizer_test'] = false;
    $all_passed = false;
}

/**
 * Test 5: JavaScript Client Conditional Requests
 */
echo "\n=== Test 5: JavaScript Client Conditional Requests ===\n";
echo "Note: JavaScript client tests should be run in browser console:\n";
echo "\n";
echo "// Test ETag caching\n";
echo "const client = new MASRestClient({ debug: true });\n";
echo "const settings1 = await client.getSettings(); // Should be cache MISS\n";
echo "const settings2 = await client.getSettings(); // Should be 304 or cache HIT\n";
echo "\n";
echo "// Check cache stats\n";
echo "console.log(client.getCacheStats());\n";
echo "\n";
echo "// Clear cache\n";
echo "client.clearCache('/settings');\n";
echo "\n";
echo "// Test after save (cache should be cleared)\n";
echo "await client.saveSettings(settings1);\n";
echo "const settings3 = await client.getSettings(); // Should be cache MISS\n";
echo "\n";

/**
 * Summary
 */
echo "\n=== Test Summary ===\n";
echo "Total Tests: " . count($test_results) . "\n";
echo "Passed: " . count(array_filter($test_results)) . "\n";
echo "Failed: " . (count($test_results) - count(array_filter($test_results))) . "\n";
echo "\n";

if ($all_passed) {
    echo "✓ ALL TESTS PASSED!\n";
    echo "\nPhase 2 Task 4 implementation is complete and working correctly.\n";
} else {
    echo "✗ SOME TESTS FAILED\n";
    echo "\nPlease review the failed tests above.\n";
}

echo "\n</pre>\n";
