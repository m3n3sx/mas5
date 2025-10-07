<?php
/**
 * Verify Task 3 Implementation
 * 
 * Simple verification that the error handling methods exist
 */

echo "=== Task 3: Error Handling Implementation Verification ===\n\n";

// Check if the file exists
$file = __DIR__ . '/includes/class-mas-rest-api.php';
if (!file_exists($file)) {
    echo "✗ File not found: $file\n";
    exit(1);
}

echo "✓ File exists: includes/class-mas-rest-api.php\n\n";

// Read the file content
$content = file_get_contents($file);

// Task 3.1: Check for log_error method
echo "--- Task 3.1: Error Logging Helper Method ---\n";

$has_log_error = strpos($content, 'private function log_error($message, $context = [])') !== false;
echo ($has_log_error ? "✓" : "✗") . " log_error() method exists\n";

$has_wp_version = strpos($content, "'WordPress Version: ' . get_bloginfo('version')") !== false;
echo ($has_wp_version ? "✓" : "✗") . " Includes WordPress version in context\n";

$has_php_version = strpos($content, "'PHP Version: ' . PHP_VERSION") !== false;
echo ($has_php_version ? "✓" : "✗") . " Includes PHP version in context\n";

$has_file_context = strpos($content, "!empty(\$context['file'])") !== false;
echo ($has_file_context ? "✓" : "✗") . " Includes file path in context\n";

$has_line_context = strpos($content, "!empty(\$context['line'])") !== false;
echo ($has_line_context ? "✓" : "✗") . " Includes line number in context\n";

$has_debug_check = strpos($content, "if (!defined('WP_DEBUG') || !WP_DEBUG)") !== false;
echo ($has_debug_check ? "✓" : "✗") . " Only logs when WP_DEBUG is enabled\n";

$has_error_storage = strpos($content, '$this->initialization_errors[] =') !== false;
echo ($has_error_storage ? "✓" : "✗") . " Stores errors for admin notice display\n";

// Task 3.2: Check for admin notice method
echo "\n--- Task 3.2: Admin Notice for Initialization Failures ---\n";

$has_display_method = strpos($content, 'public function display_initialization_errors()') !== false;
echo ($has_display_method ? "✓" : "✗") . " display_initialization_errors() method exists\n";

$has_admin_check = strpos($content, "current_user_can('manage_options')") !== false;
echo ($has_admin_check ? "✓" : "✗") . " Displays only to administrators\n";

$has_error_notice = strpos($content, 'notice notice-error') !== false;
echo ($has_error_notice ? "✓" : "✗") . " Creates error notice\n";

$has_troubleshooting = strpos($content, 'Troubleshooting Steps:') !== false;
echo ($has_troubleshooting ? "✓" : "✗") . " Includes troubleshooting information\n";

$has_debug_details = strpos($content, 'Technical Details (Debug Mode)') !== false;
echo ($has_debug_details ? "✓" : "✗") . " Shows technical details in debug mode\n";

$has_admin_hook = strpos($content, "add_action('admin_notices', [\$this, 'display_initialization_errors'])") !== false;
echo ($has_admin_hook ? "✓" : "✗") . " Registered on admin_notices hook\n";

// Check for helper properties
echo "\n--- Helper Properties and Methods ---\n";

$has_errors_property = strpos($content, 'private $initialization_errors = []') !== false;
echo ($has_errors_property ? "✓" : "✗") . " initialization_errors property exists\n";

$has_initialized_property = strpos($content, 'private $initialized = false') !== false;
echo ($has_initialized_property ? "✓" : "✗") . " initialized property exists\n";

$has_is_initialized = strpos($content, 'public function is_initialized()') !== false;
echo ($has_is_initialized ? "✓" : "✗") . " is_initialized() method exists\n";

$has_get_errors = strpos($content, 'public function get_initialization_errors()') !== false;
echo ($has_get_errors ? "✓" : "✗") . " get_initialization_errors() method exists\n";

$has_has_errors = strpos($content, 'public function has_errors()') !== false;
echo ($has_has_errors ? "✓" : "✗") . " has_errors() method exists\n";

// Summary
echo "\n=== Summary ===\n";

$task_3_1_complete = 
    $has_log_error &&
    $has_wp_version &&
    $has_php_version &&
    $has_file_context &&
    $has_line_context &&
    $has_debug_check &&
    $has_error_storage;

$task_3_2_complete = 
    $has_display_method &&
    $has_admin_check &&
    $has_error_notice &&
    $has_troubleshooting &&
    $has_debug_details &&
    $has_admin_hook;

echo "\nTask 3.1 - Error Logging Helper Method: " . ($task_3_1_complete ? "✓ COMPLETE" : "✗ INCOMPLETE") . "\n";
echo "Task 3.2 - Admin Notice for Failures: " . ($task_3_2_complete ? "✓ COMPLETE" : "✗ INCOMPLETE") . "\n";

if ($task_3_1_complete && $task_3_2_complete) {
    echo "\n✓ Task 3: Add comprehensive error handling - COMPLETE\n";
    echo "\nAll requirements met:\n";
    echo "  ✓ Requirement 3.1: Error logging with context information\n";
    echo "  ✓ Requirement 3.2: Admin notices for initialization failures\n";
    echo "  ✓ Requirement 3.3: Debug mode logging\n";
    exit(0);
} else {
    echo "\n✗ Task 3: Some requirements not met\n";
    exit(1);
}
