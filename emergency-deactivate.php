<?php
/**
 * Emergency Plugin Deactivation Script
 * 
 * If you're seeing a critical error and can't access WordPress admin,
 * this script will deactivate the Modern Admin Styler V2 plugin.
 * 
 * USAGE:
 * 1. Upload this file to your WordPress root directory
 * 2. Visit: http://your-site.com/emergency-deactivate.php
 * 3. The plugin will be deactivated
 * 4. Delete this file after use for security
 */

// Load WordPress
define('WP_USE_THEMES', false);
require_once('./wp-load.php');

// Security check - only allow if user is logged in as admin
if (!is_user_logged_in() || !current_user_can('activate_plugins')) {
    wp_die('You must be logged in as an administrator to use this script.');
}

echo '<html><head><title>Emergency Plugin Deactivation</title></head><body>';
echo '<h1>Modern Admin Styler V2 - Emergency Deactivation</h1>';

// Get the plugin file
$plugin_file = 'modern-admin-styler-v2/modern-admin-styler-v2.php';

// Check if plugin is active
if (is_plugin_active($plugin_file)) {
    echo '<p>Deactivating Modern Admin Styler V2...</p>';
    
    // Deactivate the plugin
    deactivate_plugins($plugin_file);
    
    echo '<p style="color: green;"><strong>✓ Plugin deactivated successfully!</strong></p>';
    echo '<p>You can now access your WordPress admin panel.</p>';
    echo '<p><a href="' . admin_url() . '">Go to WordPress Admin</a></p>';
    
    // Log the deactivation
    error_log('MAS V2: Emergency deactivation performed by user ' . get_current_user_id());
} else {
    echo '<p style="color: orange;">Plugin is not currently active.</p>';
    echo '<p><a href="' . admin_url() . '">Go to WordPress Admin</a></p>';
}

echo '<hr>';
echo '<p><strong>IMPORTANT:</strong> Delete this file (emergency-deactivate.php) immediately for security reasons!</p>';
echo '</body></html>';
