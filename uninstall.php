<?php
/**
 * Modern Admin Styler V2 - Uninstall Script
 * Task 13: WordPress Compatibility Testing and Fixes
 * 
 * This file is executed when the plugin is deleted via WordPress admin
 */

// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Security check
if (!current_user_can('delete_plugins')) {
    exit;
}

/**
 * Clean up all plugin data on uninstall
 */
function mas_v2_uninstall_cleanup() {
    global $wpdb;
    
    // Log uninstall start
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('MAS V2: Starting plugin uninstall cleanup');
    }
    
    // 1. Remove all plugin options
    $plugin_options = [
        'mas_v2_settings',
        'mas_v2_version',
        'mas_v2_activation_time',
        'mas_v2_last_backup_cleanup'
    ];
    
    foreach ($plugin_options as $option) {
        delete_option($option);
        delete_site_option($option); // For multisite
    }
    
    // 2. Remove all settings backups
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'mas_v2_settings_backup_%'");
    
    // 3. Remove all plugin transients
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_mas_v2_%' OR option_name LIKE '_transient_mas_v2_%'");
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_site_transient_timeout_mas_v2_%' OR option_name LIKE '_site_transient_mas_v2_%'");
    
    // 4. Remove user meta related to plugin
    $wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key LIKE 'mas_v2_%'");
    
    // 5. Clean up temporary files
    $upload_dir = wp_upload_dir();
    $temp_dir = $upload_dir['basedir'] . '/mas-v2-temp/';
    
    if (is_dir($temp_dir)) {
        $files = glob($temp_dir . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir($temp_dir);
    }
    
    // 6. Clear any scheduled events
    wp_clear_scheduled_hook('mas_v2_cleanup_backups');
    wp_clear_scheduled_hook('mas_v2_cache_cleanup');
    
    // 7. Clear WordPress cache
    if (function_exists('wp_cache_flush') && !wp_using_ext_object_cache()) {
        wp_cache_flush();
    }
    
    // 8. For multisite - clean up network options
    if (is_multisite()) {
        $sites = get_sites();
        foreach ($sites as $site) {
            switch_to_blog($site->blog_id);
            
            // Remove site-specific options
            foreach ($plugin_options as $option) {
                delete_option($option);
            }
            
            // Remove site-specific backups
            $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'mas_v2_settings_backup_%'");
            
            restore_current_blog();
        }
    }
    
    // Log uninstall completion
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('MAS V2: Plugin uninstall cleanup completed');
    }
}

/**
 * Create uninstall backup before cleanup
 */
function mas_v2_create_uninstall_backup() {
    $settings = get_option('mas_v2_settings');
    if ($settings) {
        $backup_data = [
            'settings' => $settings,
            'timestamp' => time(),
            'wp_version' => get_bloginfo('version'),
            'plugin_version' => defined('MAS_V2_VERSION') ? MAS_V2_VERSION : '2.2.0',
            'uninstall_reason' => 'plugin_deletion'
        ];
        
        // Store backup in uploads directory as JSON file
        $upload_dir = wp_upload_dir();
        $backup_file = $upload_dir['basedir'] . '/mas-v2-uninstall-backup-' . time() . '.json';
        
        file_put_contents($backup_file, json_encode($backup_data, JSON_PRETTY_PRINT));
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MAS V2: Uninstall backup created at ' . $backup_file);
        }
    }
}

// Execute uninstall process
try {
    // Create backup before cleanup
    mas_v2_create_uninstall_backup();
    
    // Perform cleanup
    mas_v2_uninstall_cleanup();
    
} catch (Exception $e) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('MAS V2: Uninstall error - ' . $e->getMessage());
    }
}