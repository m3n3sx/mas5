<?php
/**
 * Backup Service Class
 * 
 * Handles all backup and restore operations including CRUD operations,
 * automatic backup creation before major changes, and automatic cleanup
 * of old backups based on retention policy.
 *
 * @package ModernAdminStylerV2
 * @subpackage Services
 * @since 2.2.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * MAS Backup Service
 * 
 * Provides centralized backup management with automatic cleanup.
 */
class MAS_Backup_Service {
    
    /**
     * Backup option prefix in database
     * 
     * @var string
     */
    private $backup_prefix = 'mas_v2_backup_';
    
    /**
     * Backup index option name
     * 
     * @var string
     */
    private $backup_index_option = 'mas_v2_backup_index';
    
    /**
     * Cache service instance
     * 
     * @var MAS_Cache_Service
     */
    private $cache_service;
    
    /**
     * Maximum number of automatic backups to retain
     * 
     * @var int
     */
    private $max_automatic_backups = 10;
    
    /**
     * Maximum number of manual backups to retain
     * 
     * @var int
     */
    private $max_manual_backups = 20;
    
    /**
     * Retention period for automatic backups in days
     * 
     * @var int
     */
    private $retention_days = 30;
    
    /**
     * Singleton instance
     * 
     * @var MAS_Backup_Service
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     * 
     * @return MAS_Backup_Service
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Private constructor to enforce singleton pattern
     */
    private function __construct() {
        $this->cache_service = new MAS_Cache_Service();
        
        // Initialize backup index if it doesn't exist
        if (get_option($this->backup_index_option) === false) {
            update_option($this->backup_index_option, [], false);
        }
    }
    
    /**
     * List all backups
     * 
     * @param int $limit Maximum number of backups to return (0 for all)
     * @param int $offset Offset for pagination
     * @return array Array of backup metadata
     */
    public function list_backups($limit = 0, $offset = 0) {
        // Use cache for backup index
        $cache_key = 'backup_index';
        $index = $this->cache_service->remember($cache_key, function() {
            $index = get_option($this->backup_index_option, []);
            
            // Sort by timestamp descending (newest first)
            usort($index, function($a, $b) {
                return $b['timestamp'] - $a['timestamp'];
            });
            
            return $index;
        }, 300); // Cache for 5 minutes
        
        // Apply pagination if limit is set
        if ($limit > 0) {
            $index = array_slice($index, $offset, $limit);
        }
        
        return $index;
    }
    
    /**
     * Get a specific backup by ID
     * 
     * @param string $backup_id Backup ID
     * @return array|WP_Error Backup data or error
     */
    public function get_backup($backup_id) {
        // Use cache for individual backups
        $cache_key = 'backup_' . $backup_id;
        
        return $this->cache_service->remember($cache_key, function() use ($backup_id) {
            $option_name = $this->backup_prefix . $backup_id;
            $backup = get_option($option_name);
            
            if ($backup === false) {
                return new WP_Error(
                    'backup_not_found',
                    __('Backup not found', 'modern-admin-styler-v2'),
                    ['status' => 404]
                );
            }
            
            return $backup;
        }, 600); // Cache for 10 minutes
    }
    
    /**
     * Create a new backup
     * 
     * @param array $settings Settings to backup (if null, uses current settings)
     * @param string $type Backup type: 'manual' or 'automatic'
     * @param string $note Optional note about the backup
     * @return array|WP_Error Backup metadata or error
     */
    public function create_backup($settings = null, $type = 'manual', $note = '') {
        // Get current settings if not provided
        if ($settings === null) {
            $settings_service = MAS_Settings_Service::get_instance();
            $settings = $settings_service->get_settings();
        }
        
        // Generate backup ID
        $backup_id = time() . '_' . wp_generate_password(8, false);
        
        // Create backup data structure
        $backup_data = [
            'id' => $backup_id,
            'timestamp' => time(),
            'date' => current_time('mysql'),
            'type' => $type,
            'settings' => $settings,
            'metadata' => [
                'plugin_version' => defined('MAS_V2_VERSION') ? MAS_V2_VERSION : '2.2.0',
                'wordpress_version' => get_bloginfo('version'),
                'user_id' => get_current_user_id(),
                'note' => $note,
            ]
        ];
        
        // Save backup
        $option_name = $this->backup_prefix . $backup_id;
        $result = update_option($option_name, $backup_data, false);
        
        if (!$result && get_option($option_name) !== $backup_data) {
            return new WP_Error(
                'backup_creation_failed',
                __('Failed to create backup', 'modern-admin-styler-v2'),
                ['status' => 500]
            );
        }
        
        // Update backup index
        $this->add_to_index($backup_data);
        
        // Invalidate backup cache
        $this->invalidate_backup_cache();
        
        // Perform automatic cleanup
        $this->cleanup_old_backups();
        
        // Return metadata (without full settings for efficiency)
        return [
            'id' => $backup_data['id'],
            'timestamp' => $backup_data['timestamp'],
            'date' => $backup_data['date'],
            'type' => $backup_data['type'],
            'metadata' => $backup_data['metadata']
        ];
    }
    
    /**
     * Restore a backup
     * 
     * @param string $backup_id Backup ID to restore
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function restore_backup($backup_id) {
        // Get backup data
        $backup = $this->get_backup($backup_id);
        
        if (is_wp_error($backup)) {
            return $backup;
        }
        
        // Validate backup data
        $validation_result = $this->validate_backup($backup);
        if (is_wp_error($validation_result)) {
            return $validation_result;
        }
        
        // Create automatic backup of current state before restoring
        $current_backup = $this->create_backup(null, 'automatic', 'Before restore of backup ' . $backup_id);
        
        if (is_wp_error($current_backup)) {
            return new WP_Error(
                'pre_restore_backup_failed',
                __('Failed to create backup before restore', 'modern-admin-styler-v2'),
                ['status' => 500]
            );
        }
        
        // Restore settings
        $settings_service = MAS_Settings_Service::get_instance();
        $result = $settings_service->save_settings($backup['settings']);
        
        if (is_wp_error($result)) {
            // Rollback: restore the pre-restore backup
            $rollback_result = $settings_service->save_settings($current_backup['settings']);
            
            return new WP_Error(
                'restore_failed',
                __('Failed to restore backup. Settings have been rolled back.', 'modern-admin-styler-v2'),
                ['status' => 500, 'original_error' => $result]
            );
        }
        
        return true;
    }
    
    /**
     * Delete a backup
     * 
     * @param string $backup_id Backup ID to delete
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function delete_backup($backup_id) {
        // Check if backup exists
        $backup = $this->get_backup($backup_id);
        
        if (is_wp_error($backup)) {
            return $backup;
        }
        
        // Delete backup option
        $option_name = $this->backup_prefix . $backup_id;
        $result = delete_option($option_name);
        
        if (!$result) {
            return new WP_Error(
                'backup_deletion_failed',
                __('Failed to delete backup', 'modern-admin-styler-v2'),
                ['status' => 500]
            );
        }
        
        // Remove from index
        $this->remove_from_index($backup_id);
        
        // Invalidate backup cache
        $this->invalidate_backup_cache();
        
        return true;
    }
    
    /**
     * Create automatic backup before major changes
     * 
     * @param string $note Note describing the change
     * @return array|WP_Error Backup metadata or error
     */
    public function create_automatic_backup($note = '') {
        return $this->create_backup(null, 'automatic', $note);
    }
    
    /**
     * Validate backup data
     * 
     * @param array $backup Backup data to validate
     * @return bool|WP_Error True if valid, WP_Error if invalid
     */
    private function validate_backup($backup) {
        $errors = [];
        
        // Check required fields
        if (!isset($backup['settings']) || !is_array($backup['settings'])) {
            $errors[] = __('Backup does not contain valid settings data', 'modern-admin-styler-v2');
        }
        
        if (!isset($backup['metadata']) || !is_array($backup['metadata'])) {
            $errors[] = __('Backup does not contain valid metadata', 'modern-admin-styler-v2');
        }
        
        // Check version compatibility
        if (isset($backup['metadata']['plugin_version'])) {
            $backup_version = $backup['metadata']['plugin_version'];
            $current_version = defined('MAS_V2_VERSION') ? MAS_V2_VERSION : '2.2.0';
            
            // Simple version check - could be enhanced with version_compare
            if (version_compare($backup_version, '2.0.0', '<')) {
                $errors[] = sprintf(
                    __('Backup version %s is too old and may not be compatible', 'modern-admin-styler-v2'),
                    $backup_version
                );
            }
        }
        
        if (!empty($errors)) {
            return new WP_Error(
                'backup_validation_failed',
                __('Backup validation failed', 'modern-admin-styler-v2'),
                ['status' => 400, 'errors' => $errors]
            );
        }
        
        return true;
    }
    
    /**
     * Add backup to index
     * 
     * @param array $backup_data Backup data
     * @return void
     */
    private function add_to_index($backup_data) {
        $index = get_option($this->backup_index_option, []);
        
        // Add metadata to index (without full settings)
        $index[] = [
            'id' => $backup_data['id'],
            'timestamp' => $backup_data['timestamp'],
            'date' => $backup_data['date'],
            'type' => $backup_data['type'],
            'metadata' => $backup_data['metadata']
        ];
        
        update_option($this->backup_index_option, $index, false);
    }
    
    /**
     * Remove backup from index
     * 
     * @param string $backup_id Backup ID
     * @return void
     */
    private function remove_from_index($backup_id) {
        $index = get_option($this->backup_index_option, []);
        
        $index = array_filter($index, function($item) use ($backup_id) {
            return $item['id'] !== $backup_id;
        });
        
        // Re-index array
        $index = array_values($index);
        
        update_option($this->backup_index_option, $index, false);
    }
    
    /**
     * Cleanup old backups based on retention policy
     * 
     * @return int Number of backups deleted
     */
    public function cleanup_old_backups() {
        $index = get_option($this->backup_index_option, []);
        $deleted_count = 0;
        
        // Separate automatic and manual backups
        $automatic_backups = array_filter($index, function($item) {
            return $item['type'] === 'automatic';
        });
        
        $manual_backups = array_filter($index, function($item) {
            return $item['type'] === 'manual';
        });
        
        // Sort by timestamp descending
        usort($automatic_backups, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });
        
        usort($manual_backups, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });
        
        // Cleanup automatic backups
        // 1. Remove backups older than retention period
        $cutoff_time = time() - ($this->retention_days * DAY_IN_SECONDS);
        
        foreach ($automatic_backups as $backup) {
            if ($backup['timestamp'] < $cutoff_time) {
                $this->delete_backup($backup['id']);
                $deleted_count++;
            }
        }
        
        // 2. Keep only max number of automatic backups
        if (count($automatic_backups) > $this->max_automatic_backups) {
            $backups_to_delete = array_slice($automatic_backups, $this->max_automatic_backups);
            
            foreach ($backups_to_delete as $backup) {
                $this->delete_backup($backup['id']);
                $deleted_count++;
            }
        }
        
        // Cleanup manual backups - keep only max number
        if (count($manual_backups) > $this->max_manual_backups) {
            $backups_to_delete = array_slice($manual_backups, $this->max_manual_backups);
            
            foreach ($backups_to_delete as $backup) {
                $this->delete_backup($backup['id']);
                $deleted_count++;
            }
        }
        
        return $deleted_count;
    }
    
    /**
     * Get backup statistics
     * 
     * @return array Statistics about backups
     */
    public function get_statistics() {
        $index = get_option($this->backup_index_option, []);
        
        $automatic_count = count(array_filter($index, function($item) {
            return $item['type'] === 'automatic';
        }));
        
        $manual_count = count(array_filter($index, function($item) {
            return $item['type'] === 'manual';
        }));
        
        // Calculate total size (approximate)
        $total_size = 0;
        foreach ($index as $backup_meta) {
            $backup = $this->get_backup($backup_meta['id']);
            if (!is_wp_error($backup)) {
                $total_size += strlen(serialize($backup));
            }
        }
        
        return [
            'total_backups' => count($index),
            'automatic_backups' => $automatic_count,
            'manual_backups' => $manual_count,
            'total_size_bytes' => $total_size,
            'total_size_formatted' => size_format($total_size),
            'oldest_backup' => !empty($index) ? min(array_column($index, 'timestamp')) : null,
            'newest_backup' => !empty($index) ? max(array_column($index, 'timestamp')) : null,
        ];
    }
    
    /**
     * Invalidate backup cache
     * 
     * Called when backups are created, deleted, or modified.
     * 
     * @return void
     */
    private function invalidate_backup_cache() {
        $this->cache_service->delete('backup_index');
        
        // Also invalidate individual backup caches
        $index = get_option($this->backup_index_option, []);
        foreach ($index as $backup_meta) {
            $this->cache_service->delete('backup_' . $backup_meta['id']);
        }
    }
}
