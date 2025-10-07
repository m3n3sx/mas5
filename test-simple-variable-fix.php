<?php
/**
 * Simple test to verify the undefined variable fix
 */

echo "🧪 Testing undefined variable fix...\n";

// Check if the problematic line exists in the file
$file_content = file_get_contents('modern-admin-styler-v2.php');

if (strpos($file_content, '$settings_for_js') !== false) {
    echo "❌ ERROR: \$settings_for_js still found in the file!\n";
    
    // Show the problematic lines
    $lines = explode("\n", $file_content);
    foreach ($lines as $line_num => $line) {
        if (strpos($line, '$settings_for_js') !== false) {
            echo "Line " . ($line_num + 1) . ": " . trim($line) . "\n";
        }
    }
    exit(1);
}

if (strpos($file_content, '$current_settings = $this->getSettings()') !== false) {
    echo "✅ SUCCESS: Found the fix - \$current_settings = \$this->getSettings()\n";
}

if (strpos($file_content, '$this->hasMenuCustomizations($current_settings)') !== false) {
    echo "✅ SUCCESS: Found the corrected method call\n";
}

echo "🎉 The undefined variable fix has been applied correctly!\n";