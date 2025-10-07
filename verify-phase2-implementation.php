<?php
/**
 * Verify Phase 2 Implementation
 * 
 * This script verifies that all Phase 2 components are properly installed
 * and can be loaded without errors.
 * 
 * @package ModernAdminStylerV2
 * @since 2.2.0
 */

// Define WordPress constants if not already defined
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/../../../');
}

if (!defined('MAS_V2_PLUGIN_DIR')) {
    define('MAS_V2_PLUGIN_DIR', dirname(__FILE__) . '/');
}

echo "=== MAS REST API Phase 2 Verification ===\n\n";

// Check file existence
$files = [
    'Settings Service' => 'includes/services/class-mas-settings-service.php',
    'Settings Controller' => 'includes/api/class-mas-settings-controller.php',
    'REST API Bootstrap' => 'includes/class-mas-rest-api.php',
    'Validation Service' => 'includes/services/class-mas-validation-service.php',
    'REST Client JS' => 'assets/js/mas-rest-client.js',
    'Dual Mode Client JS' => 'assets/js/mas-dual-mode-client.js',
    'Test File' => 'test-rest-api-settings.php'
];

echo "1. Checking File Existence:\n";
$allFilesExist = true;
foreach ($files as $name => $path) {
    $fullPath = MAS_V2_PLUGIN_DIR . $path;
    $exists = file_exists($fullPath);
    $allFilesExist = $allFilesExist && $exists;
    
    echo sprintf(
        "   %s %s: %s\n",
        $exists ? '✓' : '✗',
        $name,
        $exists ? 'Found' : 'Missing'
    );
}

echo "\n2. Checking PHP Syntax:\n";
$phpFiles = [
    'Settings Service' => 'includes/services/class-mas-settings-service.php',
    'Settings Controller' => 'includes/api/class-mas-settings-controller.php',
    'REST API Bootstrap' => 'includes/class-mas-rest-api.php',
    'Validation Service' => 'includes/services/class-mas-validation-service.php'
];

$allSyntaxValid = true;
foreach ($phpFiles as $name => $path) {
    $fullPath = MAS_V2_PLUGIN_DIR . $path;
    
    if (!file_exists($fullPath)) {
        echo "   ✗ $name: File not found\n";
        $allSyntaxValid = false;
        continue;
    }
    
    $output = [];
    $returnVar = 0;
    exec("php -l " . escapeshellarg($fullPath) . " 2>&1", $output, $returnVar);
    
    $valid = $returnVar === 0;
    $allSyntaxValid = $allSyntaxValid && $valid;
    
    echo sprintf(
        "   %s %s: %s\n",
        $valid ? '✓' : '✗',
        $name,
        $valid ? 'Valid' : 'Syntax Error'
    );
}

echo "\n3. Checking Class Definitions:\n";

// Try to load classes (only non-WordPress dependent ones)
$classChecks = [
    'MAS_Settings_Service' => 'includes/services/class-mas-settings-service.php',
    'MAS_Validation_Service' => 'includes/services/class-mas-validation-service.php'
];

$allClassesLoaded = true;
foreach ($classChecks as $className => $path) {
    $fullPath = MAS_V2_PLUGIN_DIR . $path;
    
    if (!file_exists($fullPath)) {
        echo "   ✗ $className: File not found\n";
        $allClassesLoaded = false;
        continue;
    }
    
    // Try to include the file
    try {
        require_once $fullPath;
        $exists = class_exists($className);
        $allClassesLoaded = $allClassesLoaded && $exists;
        
        echo sprintf(
            "   %s %s: %s\n",
            $exists ? '✓' : '✗',
            $className,
            $exists ? 'Loaded' : 'Not Found'
        );
    } catch (Exception $e) {
        echo "   ✗ $className: Error loading - " . $e->getMessage() . "\n";
        $allClassesLoaded = false;
    }
}

// Check WordPress-dependent classes (just verify they exist and have valid syntax)
$wpDependentClasses = [
    'MAS_Settings_Controller' => 'includes/api/class-mas-settings-controller.php',
    'MAS_REST_API' => 'includes/class-mas-rest-api.php',
    'MAS_REST_Controller' => 'includes/api/class-mas-rest-controller.php'
];

foreach ($wpDependentClasses as $className => $path) {
    $fullPath = MAS_V2_PLUGIN_DIR . $path;
    
    if (!file_exists($fullPath)) {
        echo "   ✗ $className: File not found\n";
        continue;
    }
    
    // Check if class is defined in the file
    $content = file_get_contents($fullPath);
    $hasClass = preg_match('/class\s+' . preg_quote($className, '/') . '\s+/', $content);
    
    echo sprintf(
        "   %s %s: %s (requires WordPress)\n",
        $hasClass ? '✓' : '✗',
        $className,
        $hasClass ? 'Defined' : 'Not Found'
    );
}

echo "\n4. Checking Service Functionality:\n";

try {
    // Test Settings Service
    if (class_exists('MAS_Settings_Service')) {
        $settingsService = MAS_Settings_Service::get_instance();
        $defaults = $settingsService->get_defaults();
        $defaultCount = count($defaults);
        
        echo sprintf(
            "   ✓ Settings Service: Initialized (%d default settings)\n",
            $defaultCount
        );
    } else {
        echo "   ✗ Settings Service: Class not found\n";
    }
    
    // Test Validation Service
    if (class_exists('MAS_Validation_Service')) {
        $validationService = new MAS_Validation_Service();
        
        // Test color validation
        $validColor = $validationService->validate_color('#ff0000');
        $invalidColor = $validationService->validate_color('invalid');
        
        echo sprintf(
            "   %s Validation Service: Color validation %s\n",
            ($validColor && !$invalidColor) ? '✓' : '✗',
            ($validColor && !$invalidColor) ? 'working' : 'failed'
        );
        
        // Test field aliases
        $aliases = $validationService->get_field_aliases();
        $aliasCount = count($aliases);
        
        echo sprintf(
            "   ✓ Validation Service: %d field aliases configured\n",
            $aliasCount
        );
    } else {
        echo "   ✗ Validation Service: Class not found\n";
    }
} catch (Exception $e) {
    echo "   ✗ Service Test: Error - " . $e->getMessage() . "\n";
}

echo "\n5. Checking JavaScript Files:\n";

$jsFiles = [
    'REST Client' => 'assets/js/mas-rest-client.js',
    'Dual Mode Client' => 'assets/js/mas-dual-mode-client.js'
];

foreach ($jsFiles as $name => $path) {
    $fullPath = MAS_V2_PLUGIN_DIR . $path;
    
    if (!file_exists($fullPath)) {
        echo "   ✗ $name: File not found\n";
        continue;
    }
    
    $content = file_get_contents($fullPath);
    $size = strlen($content);
    
    // Check for key classes/functions
    $hasClass = false;
    if ($name === 'REST Client') {
        $hasClass = strpos($content, 'class MASRestClient') !== false;
    } elseif ($name === 'Dual Mode Client') {
        $hasClass = strpos($content, 'class MASDualModeClient') !== false;
    }
    
    echo sprintf(
        "   %s %s: %s (%s bytes)\n",
        $hasClass ? '✓' : '✗',
        $name,
        $hasClass ? 'Valid' : 'Invalid',
        number_format($size)
    );
}

echo "\n=== Summary ===\n";
echo sprintf("Files Exist: %s\n", $allFilesExist ? '✓ All present' : '✗ Some missing');
echo sprintf("PHP Syntax: %s\n", $allSyntaxValid ? '✓ All valid' : '✗ Some errors');
echo sprintf("Classes: %s\n", $allClassesLoaded ? '✓ All loaded' : '✗ Some failed');

if ($allFilesExist && $allSyntaxValid && $allClassesLoaded) {
    echo "\n✅ Phase 2 implementation is complete and ready for testing!\n";
    echo "\nNext steps:\n";
    echo "1. Test REST API endpoints: test-rest-api-settings.php\n";
    echo "2. Test JavaScript client in browser console\n";
    echo "3. Test dual-mode fallback functionality\n";
    echo "4. Proceed to Phase 2 continuation (Theme Management)\n";
} else {
    echo "\n⚠️ Some issues detected. Please review the errors above.\n";
}

echo "\n";
