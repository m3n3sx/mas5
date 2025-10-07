# Database Migration System - Integration Guide

## Overview
This guide explains how to use the database schema management and migration system for Phase 2 features.

## Quick Start

### 1. Run Migrations on Plugin Activation

Add to your main plugin file (`modern-admin-styler-v2.php`):

```php
/**
 * Plugin activation hook
 */
function mas_v2_activate() {
    // Load required classes
    require_once plugin_dir_path(__FILE__) . 'includes/services/class-mas-database-schema.php';
    require_once plugin_dir_path(__FILE__) . 'includes/services/class-mas-migration-runner.php';
    
    // Run migrations
    $migration_runner = new MAS_Migration_Runner();
    $results = $migration_runner->run_migrations();
    
    // Log results
    if (!$results['success']) {
        error_log('MAS Migration Error: ' . print_r($results['errors'], true));
    }
}
register_activation_hook(__FILE__, 'mas_v2_activate');
```

### 2. Check Migration Status on Admin Load

Add to your admin initialization:

```php
/**
 * Check if migrations are needed
 */
function mas_v2_check_migrations() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    require_once plugin_dir_path(__FILE__) . 'includes/services/class-mas-database-schema.php';
    require_once plugin_dir_path(__FILE__) . 'includes/services/class-mas-migration-runner.php';
    
    $migration_runner = new MAS_Migration_Runner();
    $status = $migration_runner->get_status();
    
    if ($status['needs_migration']) {
        add_action('admin_notices', function() use ($status) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <strong>Modern Admin Styler V2:</strong> 
                    Database migrations are pending. 
                    <?php echo count($status['pending_migrations']); ?> migration(s) need to be run.
                </p>
                <p>
                    <a href="<?php echo admin_url('admin.php?page=mas-v2-database'); ?>" class="button button-primary">
                        Run Migrations
                    </a>
                </p>
            </div>
            <?php
        });
    }
}
add_action('admin_init', 'mas_v2_check_migrations');
```

### 3. Create Admin Page for Database Management

```php
/**
 * Add database management page
 */
function mas_v2_add_database_page() {
    add_submenu_page(
        'mas-v2-settings',
        'Database Management',
        'Database',
        'manage_options',
        'mas-v2-database',
        'mas_v2_render_database_page'
    );
}
add_action('admin_menu', 'mas_v2_add_database_page');

/**
 * Render database management page
 */
function mas_v2_render_database_page() {
    require_once plugin_dir_path(__FILE__) . 'includes/services/class-mas-database-schema.php';
    require_once plugin_dir_path(__FILE__) . 'includes/services/class-mas-migration-runner.php';
    
    $schema = new MAS_Database_Schema();
    $migration_runner = new MAS_Migration_Runner();
    
    // Handle actions
    if (isset($_POST['action'])) {
        check_admin_referer('mas_v2_database_action');
        
        switch ($_POST['action']) {
            case 'run_migrations':
                $results = $migration_runner->run_migrations();
                if ($results['success']) {
                    echo '<div class="notice notice-success"><p>Migrations completed successfully!</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>Migration failed: ' . esc_html(print_r($results['errors'], true)) . '</p></div>';
                }
                break;
                
            case 'verify_integrity':
                $integrity = $migration_runner->verify_integrity();
                if ($integrity['has_issues']) {
                    echo '<div class="notice notice-warning"><p>Issues found: ' . esc_html(implode(', ', $integrity['issues'])) . '</p></div>';
                } else {
                    echo '<div class="notice notice-success"><p>Database integrity verified!</p></div>';
                }
                break;
                
            case 'optimize_tables':
                $results = $schema->optimize_tables();
                echo '<div class="notice notice-success"><p>Tables optimized!</p></div>';
                break;
        }
    }
    
    // Get status
    $status = $migration_runner->get_status();
    $table_stats = $schema->get_table_stats();
    
    ?>
    <div class="wrap">
        <h1>Database Management</h1>
        
        <h2>Migration Status</h2>
        <table class="widefat">
            <tr>
                <th>Current Version</th>
                <td><?php echo esc_html($status['current_version']); ?></td>
            </tr>
            <tr>
                <th>Target Version</th>
                <td><?php echo esc_html($status['target_version']); ?></td>
            </tr>
            <tr>
                <th>Completed Migrations</th>
                <td><?php echo esc_html($status['completed_migrations']); ?> / <?php echo esc_html($status['total_migrations']); ?></td>
            </tr>
            <tr>
                <th>Pending Migrations</th>
                <td><?php echo esc_html(count($status['pending_migrations'])); ?></td>
            </tr>
        </table>
        
        <h2>Table Statistics</h2>
        <table class="widefat">
            <thead>
                <tr>
                    <th>Table</th>
                    <th>Status</th>
                    <th>Rows</th>
                    <th>Size (MB)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($table_stats as $table => $stats): ?>
                <tr>
                    <td><?php echo esc_html($table); ?></td>
                    <td><?php echo $stats['exists'] ? '✓ Exists' : '✗ Missing'; ?></td>
                    <td><?php echo number_format($stats['rows']); ?></td>
                    <td><?php echo number_format($stats['size_mb'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <h2>Actions</h2>
        <form method="post">
            <?php wp_nonce_field('mas_v2_database_action'); ?>
            
            <?php if ($status['needs_migration']): ?>
            <button type="submit" name="action" value="run_migrations" class="button button-primary">
                Run Pending Migrations
            </button>
            <?php endif; ?>
            
            <button type="submit" name="action" value="verify_integrity" class="button">
                Verify Integrity
            </button>
            
            <button type="submit" name="action" value="optimize_tables" class="button">
                Optimize Tables
            </button>
        </form>
    </div>
    <?php
}
```

## API Reference

### MAS_Database_Schema

#### Methods

**`create_tables()`**
Creates all Phase 2 database tables.
```php
$schema = new MAS_Database_Schema();
$results = $schema->create_tables();
```

**`check_tables()`**
Returns array of table existence status.
```php
$status = $schema->check_tables();
// ['mas_v2_audit_log' => true, 'mas_v2_webhooks' => true, ...]
```

**`get_current_version()`**
Returns current schema version.
```php
$version = $schema->get_current_version();
// '2.3.0'
```

**`needs_migration()`**
Checks if migration is needed.
```php
if ($schema->needs_migration()) {
    // Run migrations
}
```

**`get_table_stats()`**
Returns table statistics.
```php
$stats = $schema->get_table_stats();
// ['mas_v2_audit_log' => ['exists' => true, 'rows' => 150, 'size_mb' => 0.05], ...]
```

**`verify_indexes()`**
Verifies index presence.
```php
$indexes = $schema->verify_indexes();
// ['mas_v2_audit_log' => ['expected' => [...], 'existing' => [...], 'missing' => []], ...]
```

**`optimize_tables()`**
Optimizes all tables.
```php
$results = $schema->optimize_tables();
// ['mas_v2_audit_log' => true, 'mas_v2_webhooks' => true, ...]
```

**`drop_tables()`**
Drops all Phase 2 tables (for testing/uninstall).
```php
$schema->drop_tables();
```

### MAS_Migration_Runner

#### Methods

**`run_migrations()`**
Runs all pending migrations.
```php
$migration_runner = new MAS_Migration_Runner();
$results = $migration_runner->run_migrations();
// ['success' => true, 'migrations_run' => [...], 'errors' => []]
```

**`get_status()`**
Returns migration status.
```php
$status = $migration_runner->get_status();
// [
//     'current_version' => '2.3.0',
//     'target_version' => '2.3.0',
//     'needs_migration' => false,
//     'total_migrations' => 2,
//     'completed_migrations' => 2,
//     'pending_migrations' => [],
//     'completed_details' => [...]
// ]
```

**`rollback_last()`**
Rolls back the last migration.
```php
$result = $migration_runner->rollback_last();
// ['success' => true, 'migration' => '002_add_indexes', 'message' => '...']
```

**`verify_integrity()`**
Verifies database integrity.
```php
$integrity = $migration_runner->verify_integrity();
// [
//     'tables' => [...],
//     'indexes' => [...],
//     'stats' => [...],
//     'has_issues' => false,
//     'issues' => []
// ]
```

**`reset()`**
Resets all migrations (for testing).
```php
$migration_runner->reset();
```

## Adding New Migrations

### Step 1: Define Migration in MAS_Migration_Runner

Add to `get_available_migrations()`:
```php
private function get_available_migrations() {
    return [
        '001_create_phase2_tables',
        '002_add_indexes',
        '003_your_new_migration', // Add here
    ];
}
```

### Step 2: Implement Migration Method

Add to `MAS_Migration_Runner`:
```php
private function migration_003_your_new_migration() {
    global $wpdb;
    
    // Your migration logic here
    // Example: Add a new column
    $table_name = $wpdb->prefix . 'mas_v2_audit_log';
    $wpdb->query("ALTER TABLE $table_name ADD COLUMN new_field VARCHAR(255)");
}
```

### Step 3: Implement Rollback Method

Add to `MAS_Migration_Runner`:
```php
private function rollback_003_your_new_migration() {
    global $wpdb;
    
    // Your rollback logic here
    $table_name = $wpdb->prefix . 'mas_v2_audit_log';
    $wpdb->query("ALTER TABLE $table_name DROP COLUMN new_field");
}
```

### Step 4: Update Rollback Switch

Add case to `rollback_migration()`:
```php
switch ($migration) {
    case '001_create_phase2_tables':
        $this->schema->drop_tables();
        break;
    case '002_add_indexes':
        $this->rollback_002_drop_indexes();
        break;
    case '003_your_new_migration':
        $this->rollback_003_your_new_migration();
        break;
}
```

## Best Practices

### 1. Always Use Transactions
Migrations automatically use transactions, but ensure your SQL is transaction-safe.

### 2. Test Rollbacks
Always test that your rollback works before deploying.

### 3. Keep Migrations Small
Each migration should do one thing. Don't combine multiple changes.

### 4. Document Changes
Add comments explaining what each migration does and why.

### 5. Version Carefully
Increment schema version when adding migrations.

### 6. Test on Staging
Always test migrations on a staging environment first.

### 7. Backup Before Migration
Recommend users backup their database before running migrations.

## Troubleshooting

### Migration Fails
1. Check error log for details
2. Verify database permissions
3. Check for conflicting plugins
4. Try rollback and re-run

### Tables Missing
1. Run `verify_integrity()` to identify issues
2. Check if migrations completed
3. Manually run `create_tables()` if needed

### Indexes Missing
1. Run migration `002_add_indexes`
2. Check database user has INDEX privilege
3. Manually add indexes if needed

### Performance Issues
1. Run `optimize_tables()` regularly
2. Check table sizes with `get_table_stats()`
3. Consider archiving old data

## Testing

### Run Test Suite
```
http://your-site.com/wp-content/plugins/modern-admin-styler-v2/test-phase2-task10-database-schema.php
```

### Manual Testing
```php
// Test migration
$migration_runner = new MAS_Migration_Runner();
$results = $migration_runner->run_migrations();
var_dump($results);

// Test rollback
$result = $migration_runner->rollback_last();
var_dump($result);

// Test integrity
$integrity = $migration_runner->verify_integrity();
var_dump($integrity);
```

## Support

For issues or questions:
1. Check the completion report: `PHASE2-TASK10-COMPLETION-REPORT.md`
2. Review the test file: `test-phase2-task10-database-schema.php`
3. Check WordPress error logs
4. Verify database permissions

---

*Last Updated: June 10, 2025*
*Schema Version: 2.3.0*
