<?php
/**
 * Backup Retention Service Class
 * 
 * Enhanced backup service with comprehensive retention policies,
 * metadata tracking, and download capabilities for Phase 2.
 *
 * @package ModernAdminStylerV2
 * @subpackage Services
 * @since 2.3.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * MAS Backup Retention Service
 * 
 * Provides enterprise-grade backup management with retention policies,
 * metadata tracking, and download capabilities.
 */
class MAS_Backup_Retention_Service {
    
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
     * Maximum number of automatic backups to retain
     * 
     * @var int
     */
    private $max_automatic_backups = 30;
    
    /**
     * Maximum number of manual backups to retain
     * 
     * @var int
     */
    private $max_manual_backups = 100;
    
    /**
     * Retention period for automatic backups in days
     * 
     * @var int
     */
    private $retention_days = 30;
    
    /**
     * Singleton instance
     * 
     * @var MAS_Backup_Retention_Service
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     * 
     * @return MAS_Backup_Retention_Service
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
        // Initialize backup index if it doesn't exist
        if (get_option($this->backup_index_option) === false) {
            update_option($this->backup_index_option, [], false);
        }
    }
    
    /**
     * Create a new backup with enhanced metadata
     * 
     * @param array $settings Settings to backup (if null, uses current settings)
     * @param string $type Backup type: 'manual' or 'automatic'
     * @param string $note Optional note about the backup
     * @param string $name Optional custom name for the backup
     * @return array|WP_Error Backup metadata or error
     */
    public function create_backup($settings = null, $type = 'manual', $note = '', $name = '') {
        // Get current settings if not provided
        if ($settings === null) {
            $settings_service = MAS_Settings_Service::get_instance();
            $settings = $settings_service->get_settings();
        }
        
        // Generate backup ID
        $backup_id = time() . '_' . wp_generate_password(8, false);
        
        // Calculate settings count
        $settings_count = $this->count_settings($settings);
        
        // Calculate checksum for integrity verification
        $checksum = $this->calculate_checksum($settings);
        
        // Calculate size
        $size_bytes = strlen(serialize($settings));
        
        // Get current user info
        $current_user = wp_get_current_user();
        $user_info = [
            'id' => $current_user->ID,
            'login' => $current_user->user_login,
            'display_name' => $current_user->display_name,
        ];
        
        // Create backup data structure with enhanced metadata
        $backup_data = [
            'id' => $backup_id,
            'name' => !empty($name) ? sanitize_text_field($name) : $this->generate_default_name($type),
            'timestamp' => time(),
            'date' => current_time('mysql'),
            'type' => $type,
            'settings' => $settings,
            'metadata' => [
                'plugin_version' => defined('MAS_V2_VERSION') ? MAS_V2_VERSION : '2.3.0',
                'wordpress_version' => get_bloginfo('version'),
                'php_version' => PHP_VERSION,
                'user' => $user_info,
                'note' => sanitize_textarea_field($note),
                'settings_count' => $settings_count,
                'size_bytes' => $size_bytes,
                'size_formatted' => size_format($size_bytes),
                'checksum' => $checksum,
                'created_at' => current_time('mysql'),
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
        
        // Perform automatic cleanup
        $this->cleanup_old_backups();
        
        // Return metadata (without full settings for efficiency)
        return [
            'id' => $backup_data['id'],
            'name' => $backup_data['name'],
            'timestamp' => $backup_data['timestamp'],
            'date' => $backup_data['date'],
            'type' => $backup_data['type'],
            'metadata' => $backup_data['metadata']
        ];
    }
    
    /**
     * Cleanup old backups based on retention policies
     * 
     * Retention policies:
     * - Automatic backups: Keep max 30, delete older than 30 days
     * - Manual backups: Keep max 100, never delete based on age
     * 
     * @return array Cleanup results with counts
     */
    public function cleanup_old_backups() {
        $index = get_option($this->backup_index_option, []);
        $deleted_count = 0;
        $deleted_ids = [];
        
        // Separate automatic and manual backups
        $automatic_backups = array_filter($index, function($item) {
            return $item['type'] === 'automatic';
        });
        
        $manual_backups = array_filter($index, function($item) {
            return $item['type'] === 'manual';
        });
        
        // Sort by timestamp descending (newest first)
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
                $result = $this->delete_backup_internal($backup['id']);
                if (!is_wp_error($result)) {
                    $deleted_count++;
                    $deleted_ids[] = $backup['id'];
                }
            }
        }
        
        // 2. Keep only max number of automatic backups
        if (count($automatic_backups) > $this->max_automatic_backups) {
            $backups_to_delete = array_slice($automatic_backups, $this->max_automatic_backups);
            
            foreach ($backups_to_delete as $backup) {
                // Skip if already deleted by age policy
                if (in_array($backup['id'], $deleted_ids)) {
                    continue;
                }
                
                $result = $this->delete_backup_internal($backup['id']);
                if (!is_wp_error($result)) {
                    $deleted_count++;
                    $deleted_ids[] = $backup['id'];
                }
            }
        }
        
        // Cleanup manual backups - keep only max number (never delete by age)
        if (count($manual_backups) > $this->max_manual_backups) {
            $backups_to_delete = array_slice($manual_backups, $this->max_manual_backups);
            
            foreach ($backups_to_delete as $backup) {
                $result = $this->delete_backup_internal($backup['id']);
                if (!is_wp_error($result)) {
                    $deleted_count++;
                    $deleted_ids[] = $backup['id'];
                }
            }
        }
        
        return [
            'deleted_count' => $deleted_count,
            'deleted_ids' => $deleted_ids,
            'automatic_remaining' => count($automatic_backups) - count(array_intersect($deleted_ids, array_column($automatic_backups, 'id'))),
            'manual_remaining' => count($manual_backups) - count(array_intersect($deleted_ids, array_column($manual_backups, 'id'))),
        ];
    }
    
    /**
     * Download backup as JSON file
     * 
     * @param string $backup_id Backup ID to download
     * @return array|WP_Error Download data or error
     */
    public function download_backup($backup_id) {
        // Get backup data
        $backup = $this->get_backup($backup_id);
        
        if (is_wp_error($backup)) {
            return $backup;
        }
        
        // Prepare download data
        $download_data = [
            'version' => '2.3.0',
            'export_date' => current_time('mysql'),
            'backup' => $backup,
        ];
        
        // Generate filename
        $filename = $this->generate_download_filename($backup);
        
        // Return download data with metadata
        return [
            'filename' => $filename,
            'content' => wp_json_encode($download_data, JSON_PRETTY_PRINT),
            'mime_type' => 'application/json',
            'size' => strlen(wp_json_encode($download_data)),
        ];
    }
    
    /**
     * Get a specific backup by ID
     * 
     * @param string $backup_id Backup ID
     * @return array|WP_Error Backup data or error
     */
    public function get_backup($backup_id) {
        $option_name = $this->backup_prefix . $backup_id;
        $backup = get_option($option_name);
        
        if ($backup === false) {
            return new WP_Error(
                'backup_not_found',
                __('Backup not found', 'modern-admin-styler-v2'),
                ['status' => 404]
            );
        }
        
        // Verify checksum if available
        if (isset($backup['metadata']['checksum'])) {
            $current_checksum = $this->calculate_checksum($backup['settings']);
            if ($current_checksum !== $backup['metadata']['checksum']) {
                return new WP_Error(
                    'backup_corrupted',
                    __('Backup data integrity check failed', 'modern-admin-styler-v2'),
                    ['status' => 500]
                );
            }
        }
        
        return $backup;
    }
    
    /**
     * List all backups with enhanced metadata
     * 
     * @param int $limit Maximum number of backups to return (0 for all)
     * @param int $offset Offset for pagination
     * @param string $type Filter by type ('all', 'manual', 'automatic')
     * @return array Array of backup metadata
     */
    public function list_backups($limit = 0, $offset = 0, $type = 'all') {
        $index = get_option($this->backup_index_option, []);
        
        // Filter by type if specified
        if ($type !== 'all') {
            $index = array_filter($index, function($item) use ($type) {
                return $item['type'] === $type;
            });
        }
        
        // Sort by timestamp descending (newest first)
        usort($index, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });
        
        // Apply pagination if limit is set
        if ($limit > 0) {
            $index = array_slice($index, $offset, $limit);
        }
        
        return array_values($index);
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
        
        // Calculate total size
        $total_size = 0;
        foreach ($index as $backup_meta) {
            if (isset($backup_meta['metadata']['size_bytes'])) {
                $total_size += $backup_meta['metadata']['size_bytes'];
            }
        }
        
        // Get oldest and newest timestamps
        $timestamps = array_column($index, 'timestamp');
        
        return [
            'total_backups' => count($index),
            'automatic_backups' => $automatic_count,
            'manual_backups' => $manual_count,
            'total_size_bytes' => $total_size,
            'total_size_formatted' => size_format($total_size),
            'oldest_backup' => !empty($timestamps) ? min($timestamps) : null,
            'newest_backup' => !empty($timestamps) ? max($timestamps) : null,
            'retention_policy' => [
                'automatic_max' => $this->max_automatic_backups,
                'automatic_days' => $this->retention_days,
                'manual_max' => $this->max_manual_backups,
            ],
        ];
    }
    
    /**
     * Delete a backup (internal method without index update)
     * 
     * @param string $backup_id Backup ID to delete
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    private function delete_backup_internal($backup_id) {
        $option_name = $this->backup_prefix . $backup_id;
        $result = delete_option($option_name);
        
        if (!$result) {
            return new WP_Error(
                'backup_deletion_failed',
                __('Failed to delete backup', 'modern-admin-styler-v2'),
                ['status' => 500]
            );
        }
        
        return true;
    }
    
    /**
     * Calculate checksum for backup integrity verification
     * 
     * @param array $settings Settings data
     * @return string MD5 checksum
     */
    private function calculate_checksum($settings) {
        return md5(serialize($settings));
    }
    
    /**
     * Count settings in backup
     * 
     * @param array $settings Settings data
     * @return int Number of settings
     */
    private function count_settings($settings) {
        $count = 0;
        
        foreach ($settings as $key => $value) {
            if (is_array($value)) {
                $count += count($value);
            } else {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Generate default backup name
     * 
     * @param string $type Backup type
     * @return string Default name
     */
    private function generate_default_name($type) {
        $date = current_time('Y-m-d H:i:s');
        $type_label = $type === 'automatic' ? __('Auto', 'modern-admin-styler-v2') : __('Manual', 'modern-admin-styler-v2');
        
        return sprintf('%s Backup - %s', $type_label, $date);
    }
    
    /**
     * Generate download filename
     * 
     * @param array $backup Backup data
     * @return string Filename
     */
    private function generate_download_filename($backup) {
        $date = date('Y-m-d-His', $backup['timestamp']);
        $name = isset($backup['name']) ? sanitize_file_name($backup['name']) : 'backup';
        
        return sprintf('mas-backup-%s-%s.json', $name, $date);
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
            'name' => $backup_data['name'],
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
}
