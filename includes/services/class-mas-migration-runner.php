<?php
/**
 * Migration Runner Service
 *
 * Handles database migrations with version tracking and rollback capability.
 *
 * @package ModernAdminStyler
 * @subpackage Services
 * @since 2.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Migration Runner Class
 *
 * Manages database schema migrations with version control.
 */
class MAS_Migration_Runner {
    
    /**
     * Option name for storing migration history
     *
     * @var string
     */
    const MIGRATION_HISTORY_OPTION = 'mas_v2_migration_history';
    
    /**
     * Migration directory path
     *
     * @var string
     */
    private $migrations_dir;
    
    /**
     * Database schema service
     *
     * @var MAS_Database_Schema
     */
    private $schema;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->migrations_dir = dirname(__FILE__) . '/../migrations';
        $this->schema = new MAS_Database_Schema();
    }
    
    /**
     * Run all pending migrations
     *
     * @return array Migration results
     */
    public function run_migrations() {
        $results = [
            'success' => true,
            'migrations_run' => [],
            'errors' => []
        ];
        
        // Get migration history
        $history = $this->get_migration_history();
        
        // Get available migrations
        $migrations = $this->get_available_migrations();
        
        // Filter out already run migrations
        $pending_migrations = array_diff($migrations, $history);
        
        if (empty($pending_migrations)) {
            $results['message'] = 'No pending migrations';
            return $results;
        }
        
        // Run each pending migration
        foreach ($pending_migrations as $migration) {
            try {
                $this->run_migration($migration);
                $results['migrations_run'][] = $migration;
                $this->add_to_history($migration);
            } catch (Exception $e) {
                $results['success'] = false;
                $results['errors'][] = [
                    'migration' => $migration,
                    'error' => $e->getMessage()
                ];
                
                // Stop on first error
                break;
            }
        }
        
        return $results;
    }
    
    /**
     * Run a specific migration
     *
     * @param string $migration Migration name
     * @return bool Success status
     * @throws Exception If migration fails
     */
    private function run_migration($migration) {
        global $wpdb;
        
        // Start transaction if supported
        $wpdb->query('START TRANSACTION');
        
        try {
            // Execute migration based on name
            switch ($migration) {
                case '001_create_phase2_tables':
                    $this->migration_001_create_phase2_tables();
                    break;
                    
                case '002_add_indexes':
                    $this->migration_002_add_indexes();
                    break;
                    
                default:
                    throw new Exception("Unknown migration: $migration");
            }
            
            // Commit transaction
            $wpdb->query('COMMIT');
            
            return true;
            
        } catch (Exception $e) {
            // Rollback transaction
            $wpdb->query('ROLLBACK');
            throw $e;
        }
    }
    
    /**
     * Migration 001: Create Phase 2 tables
     *
     * @return void
     * @throws Exception If table creation fails
     */
    private function migration_001_create_phase2_tables() {
        $results = $this->schema->create_tables();
        
        // Verify all tables were created
        $table_status = $this->schema->check_tables();
        
        foreach ($table_status as $table => $exists) {
            if (!$exists) {
                throw new Exception("Failed to create table: $table");
            }
        }
    }
    
    /**
     * Migration 002: Add performance indexes
     *
     * @return void
     * @throws Exception If index creation fails
     */
    private function migration_002_add_indexes() {
        global $wpdb;
        
        // Additional indexes for optimization
        $indexes = [
            // Audit log composite indexes
            [
                'table' => 'mas_v2_audit_log',
                'name' => 'user_timestamp',
                'columns' => ['user_id', 'timestamp']
            ],
            [
                'table' => 'mas_v2_audit_log',
                'name' => 'action_timestamp',
                'columns' => ['action', 'timestamp']
            ],
            
            // Webhook deliveries composite indexes
            [
                'table' => 'mas_v2_webhook_deliveries',
                'name' => 'webhook_status',
                'columns' => ['webhook_id', 'status']
            ],
            [
                'table' => 'mas_v2_webhook_deliveries',
                'name' => 'status_retry',
                'columns' => ['status', 'next_retry_at']
            ],
            
            // Metrics composite indexes
            [
                'table' => 'mas_v2_metrics',
                'name' => 'endpoint_timestamp',
                'columns' => ['endpoint(191)', 'timestamp']
            ],
            [
                'table' => 'mas_v2_metrics',
                'name' => 'status_timestamp',
                'columns' => ['status_code', 'timestamp']
            ]
        ];
        
        foreach ($indexes as $index) {
            $table_name = $wpdb->prefix . $index['table'];
            $index_name = $index['name'];
            $columns = implode(', ', $index['columns']);
            
            // Check if index already exists
            $existing = $wpdb->get_results(
                "SHOW INDEX FROM $table_name WHERE Key_name = '$index_name'",
                ARRAY_A
            );
            
            if (empty($existing)) {
                $sql = "ALTER TABLE $table_name ADD INDEX $index_name ($columns)";
                $result = $wpdb->query($sql);
                
                if ($result === false) {
                    throw new Exception("Failed to create index $index_name on $table_name: " . $wpdb->last_error);
                }
            }
        }
    }
    
    /**
     * Get available migrations
     *
     * @return array List of migration names
     */
    private function get_available_migrations() {
        // Define migrations in order
        return [
            '001_create_phase2_tables',
            '002_add_indexes'
        ];
    }
    
    /**
     * Get migration history
     *
     * @return array List of completed migrations
     */
    private function get_migration_history() {
        return get_option(self::MIGRATION_HISTORY_OPTION, []);
    }
    
    /**
     * Add migration to history
     *
     * @param string $migration Migration name
     * @return bool Success status
     */
    private function add_to_history($migration) {
        $history = $this->get_migration_history();
        
        if (!in_array($migration, $history)) {
            $history[] = $migration;
            
            // Store with timestamp
            $history_with_meta = get_option(self::MIGRATION_HISTORY_OPTION . '_meta', []);
            $history_with_meta[$migration] = [
                'run_at' => current_time('mysql'),
                'version' => MAS_Database_Schema::SCHEMA_VERSION
            ];
            
            update_option(self::MIGRATION_HISTORY_OPTION, $history);
            update_option(self::MIGRATION_HISTORY_OPTION . '_meta', $history_with_meta);
        }
        
        return true;
    }
    
    /**
     * Rollback last migration
     *
     * @return array Rollback results
     */
    public function rollback_last() {
        $history = $this->get_migration_history();
        
        if (empty($history)) {
            return [
                'success' => false,
                'message' => 'No migrations to rollback'
            ];
        }
        
        $last_migration = array_pop($history);
        
        try {
            $this->rollback_migration($last_migration);
            
            // Update history
            update_option(self::MIGRATION_HISTORY_OPTION, $history);
            
            // Remove from meta
            $history_meta = get_option(self::MIGRATION_HISTORY_OPTION . '_meta', []);
            unset($history_meta[$last_migration]);
            update_option(self::MIGRATION_HISTORY_OPTION . '_meta', $history_meta);
            
            return [
                'success' => true,
                'migration' => $last_migration,
                'message' => "Rolled back migration: $last_migration"
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'migration' => $last_migration,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Rollback a specific migration
     *
     * @param string $migration Migration name
     * @return bool Success status
     * @throws Exception If rollback fails
     */
    private function rollback_migration($migration) {
        global $wpdb;
        
        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            switch ($migration) {
                case '001_create_phase2_tables':
                    // Drop all Phase 2 tables
                    $this->schema->drop_tables();
                    break;
                    
                case '002_add_indexes':
                    // Drop additional indexes
                    $this->rollback_002_drop_indexes();
                    break;
                    
                default:
                    throw new Exception("Unknown migration: $migration");
            }
            
            // Commit transaction
            $wpdb->query('COMMIT');
            
            return true;
            
        } catch (Exception $e) {
            // Rollback transaction
            $wpdb->query('ROLLBACK');
            throw $e;
        }
    }
    
    /**
     * Rollback migration 002: Drop additional indexes
     *
     * @return void
     */
    private function rollback_002_drop_indexes() {
        global $wpdb;
        
        $indexes = [
            ['table' => 'mas_v2_audit_log', 'name' => 'user_timestamp'],
            ['table' => 'mas_v2_audit_log', 'name' => 'action_timestamp'],
            ['table' => 'mas_v2_webhook_deliveries', 'name' => 'webhook_status'],
            ['table' => 'mas_v2_webhook_deliveries', 'name' => 'status_retry'],
            ['table' => 'mas_v2_metrics', 'name' => 'endpoint_timestamp'],
            ['table' => 'mas_v2_metrics', 'name' => 'status_timestamp']
        ];
        
        foreach ($indexes as $index) {
            $table_name = $wpdb->prefix . $index['table'];
            $index_name = $index['name'];
            
            $wpdb->query("ALTER TABLE $table_name DROP INDEX IF EXISTS $index_name");
        }
    }
    
    /**
     * Get migration status
     *
     * @return array Migration status information
     */
    public function get_status() {
        $available = $this->get_available_migrations();
        $history = $this->get_migration_history();
        $history_meta = get_option(self::MIGRATION_HISTORY_OPTION . '_meta', []);
        
        $pending = array_diff($available, $history);
        
        return [
            'current_version' => $this->schema->get_current_version(),
            'target_version' => MAS_Database_Schema::SCHEMA_VERSION,
            'needs_migration' => !empty($pending),
            'total_migrations' => count($available),
            'completed_migrations' => count($history),
            'pending_migrations' => array_values($pending),
            'completed_details' => array_map(function($migration) use ($history_meta) {
                return [
                    'name' => $migration,
                    'run_at' => $history_meta[$migration]['run_at'] ?? 'unknown',
                    'version' => $history_meta[$migration]['version'] ?? 'unknown'
                ];
            }, $history)
        ];
    }
    
    /**
     * Reset all migrations (for testing)
     *
     * @return bool Success status
     */
    public function reset() {
        // Drop all tables
        $this->schema->drop_tables();
        
        // Clear migration history
        delete_option(self::MIGRATION_HISTORY_OPTION);
        delete_option(self::MIGRATION_HISTORY_OPTION . '_meta');
        
        return true;
    }
    
    /**
     * Verify database integrity
     *
     * @return array Integrity check results
     */
    public function verify_integrity() {
        $results = [
            'tables' => $this->schema->check_tables(),
            'indexes' => $this->schema->verify_indexes(),
            'stats' => $this->schema->get_table_stats()
        ];
        
        // Check for issues
        $issues = [];
        
        foreach ($results['tables'] as $table => $exists) {
            if (!$exists) {
                $issues[] = "Table $table does not exist";
            }
        }
        
        foreach ($results['indexes'] as $table => $index_info) {
            if (!empty($index_info['missing'])) {
                $issues[] = "Table $table is missing indexes: " . implode(', ', $index_info['missing']);
            }
        }
        
        $results['has_issues'] = !empty($issues);
        $results['issues'] = $issues;
        
        return $results;
    }
}
