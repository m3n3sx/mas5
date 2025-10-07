<?php
/**
 * Database Schema Service
 *
 * Manages database table creation, migrations, and schema versioning for Phase 2 features.
 *
 * @package ModernAdminStyler
 * @subpackage Services
 * @since 2.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database Schema Service Class
 *
 * Centralizes database schema management and migrations.
 */
class MAS_Database_Schema {
    
    /**
     * Current schema version
     *
     * @var string
     */
    const SCHEMA_VERSION = '2.3.0';
    
    /**
     * Option name for storing schema version
     *
     * @var string
     */
    const VERSION_OPTION = 'mas_v2_schema_version';
    
    /**
     * Table definitions
     *
     * @var array
     */
    private $tables = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->define_tables();
    }
    
    /**
     * Define all Phase 2 database tables
     *
     * @return void
     */
    private function define_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Audit Log Table
        $this->tables['mas_v2_audit_log'] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}mas_v2_audit_log (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            username varchar(60) NOT NULL,
            action varchar(50) NOT NULL,
            description text,
            ip_address varchar(45) NOT NULL,
            user_agent varchar(255),
            old_value longtext,
            new_value longtext,
            status varchar(20) DEFAULT 'success',
            timestamp datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY action (action),
            KEY timestamp (timestamp),
            KEY ip_address (ip_address),
            KEY status (status)
        ) $charset_collate;";
        
        // Webhooks Table
        $this->tables['mas_v2_webhooks'] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}mas_v2_webhooks (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            url varchar(500) NOT NULL,
            events text NOT NULL,
            secret varchar(64) NOT NULL,
            active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY active (active),
            KEY url (url(191))
        ) $charset_collate;";
        
        // Webhook Deliveries Table
        $this->tables['mas_v2_webhook_deliveries'] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}mas_v2_webhook_deliveries (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            webhook_id bigint(20) unsigned NOT NULL,
            event varchar(100) NOT NULL,
            payload longtext NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            response_code int(11) DEFAULT NULL,
            response_body text DEFAULT NULL,
            error_message text DEFAULT NULL,
            attempt_count int(11) NOT NULL DEFAULT 0,
            next_retry_at datetime DEFAULT NULL,
            delivered_at datetime DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY webhook_id (webhook_id),
            KEY status (status),
            KEY next_retry_at (next_retry_at),
            KEY event (event)
        ) $charset_collate;";
        
        // Metrics Table
        $this->tables['mas_v2_metrics'] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}mas_v2_metrics (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            endpoint varchar(255) NOT NULL,
            method varchar(10) NOT NULL,
            response_time int(11) NOT NULL,
            status_code int(11) NOT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            timestamp datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY endpoint (endpoint(191)),
            KEY status_code (status_code),
            KEY timestamp (timestamp),
            KEY user_id (user_id),
            KEY method (method)
        ) $charset_collate;";
    }
    
    /**
     * Create all Phase 2 database tables
     *
     * @return array Results of table creation
     */
    public function create_tables() {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $results = [];
        
        foreach ($this->tables as $table_name => $sql) {
            $result = dbDelta($sql);
            $results[$table_name] = $result;
        }
        
        // Update schema version
        update_option(self::VERSION_OPTION, self::SCHEMA_VERSION);
        
        return $results;
    }
    
    /**
     * Check if tables exist
     *
     * @return array Table existence status
     */
    public function check_tables() {
        global $wpdb;
        
        $status = [];
        
        foreach (array_keys($this->tables) as $table_name) {
            $full_table_name = $wpdb->prefix . $table_name;
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'") === $full_table_name;
            $status[$table_name] = $exists;
        }
        
        return $status;
    }
    
    /**
     * Get current schema version
     *
     * @return string|false Current version or false if not set
     */
    public function get_current_version() {
        return get_option(self::VERSION_OPTION, false);
    }
    
    /**
     * Check if migration is needed
     *
     * @return bool True if migration needed
     */
    public function needs_migration() {
        $current_version = $this->get_current_version();
        
        if ($current_version === false) {
            return true; // No version set, needs initial setup
        }
        
        return version_compare($current_version, self::SCHEMA_VERSION, '<');
    }
    
    /**
     * Drop all Phase 2 tables (for testing/uninstall)
     *
     * @return bool Success status
     */
    public function drop_tables() {
        global $wpdb;
        
        foreach (array_keys($this->tables) as $table_name) {
            $full_table_name = $wpdb->prefix . $table_name;
            $wpdb->query("DROP TABLE IF EXISTS $full_table_name");
        }
        
        delete_option(self::VERSION_OPTION);
        
        return true;
    }
    
    /**
     * Get table statistics
     *
     * @return array Table row counts and sizes
     */
    public function get_table_stats() {
        global $wpdb;
        
        $stats = [];
        
        foreach (array_keys($this->tables) as $table_name) {
            $full_table_name = $wpdb->prefix . $table_name;
            
            // Check if table exists
            if ($wpdb->get_var("SHOW TABLES LIKE '$full_table_name'") !== $full_table_name) {
                $stats[$table_name] = [
                    'exists' => false,
                    'rows' => 0,
                    'size' => 0
                ];
                continue;
            }
            
            // Get row count
            $row_count = $wpdb->get_var("SELECT COUNT(*) FROM $full_table_name");
            
            // Get table size
            $size_query = $wpdb->prepare(
                "SELECT 
                    ROUND((data_length + index_length) / 1024 / 1024, 2) as size_mb
                FROM information_schema.TABLES 
                WHERE table_schema = %s 
                AND table_name = %s",
                DB_NAME,
                $full_table_name
            );
            
            $size = $wpdb->get_var($size_query);
            
            $stats[$table_name] = [
                'exists' => true,
                'rows' => (int) $row_count,
                'size_mb' => (float) $size
            ];
        }
        
        return $stats;
    }
    
    /**
     * Verify table indexes
     *
     * @return array Index verification results
     */
    public function verify_indexes() {
        global $wpdb;
        
        $expected_indexes = [
            'mas_v2_audit_log' => ['user_id', 'action', 'timestamp', 'ip_address', 'status'],
            'mas_v2_webhooks' => ['active'],
            'mas_v2_webhook_deliveries' => ['webhook_id', 'status', 'next_retry_at', 'event'],
            'mas_v2_metrics' => ['endpoint', 'status_code', 'timestamp', 'user_id', 'method']
        ];
        
        $results = [];
        
        foreach ($expected_indexes as $table_name => $indexes) {
            $full_table_name = $wpdb->prefix . $table_name;
            
            // Get existing indexes
            $existing_indexes = $wpdb->get_results(
                "SHOW INDEX FROM $full_table_name",
                ARRAY_A
            );
            
            $existing_index_names = array_unique(
                array_column($existing_indexes, 'Key_name')
            );
            
            $results[$table_name] = [
                'expected' => $indexes,
                'existing' => $existing_index_names,
                'missing' => array_diff($indexes, $existing_index_names)
            ];
        }
        
        return $results;
    }
    
    /**
     * Optimize all tables
     *
     * @return array Optimization results
     */
    public function optimize_tables() {
        global $wpdb;
        
        $results = [];
        
        foreach (array_keys($this->tables) as $table_name) {
            $full_table_name = $wpdb->prefix . $table_name;
            
            if ($wpdb->get_var("SHOW TABLES LIKE '$full_table_name'") === $full_table_name) {
                $result = $wpdb->query("OPTIMIZE TABLE $full_table_name");
                $results[$table_name] = $result !== false;
            }
        }
        
        return $results;
    }
}
