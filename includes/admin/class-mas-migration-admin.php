<?php
/**
 * Migration Admin Interface for Modern Admin Styler V2
 * 
 * Provides admin interface for managing the REST API migration.
 *
 * @package ModernAdminStylerV2
 * @since 2.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class MAS_Migration_Admin {
    
    /**
     * Migration utility
     */
    private $migration_utility;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->migration_utility = new MAS_Migration_Utility();
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('wp_ajax_mas_migration_check', [$this, 'handle_compatibility_check']);
        add_action('wp_ajax_mas_migration_start', [$this, 'handle_start_migration']);
        add_action('wp_ajax_mas_migration_complete', [$this, 'handle_complete_migration']);
        add_action('wp_ajax_mas_migration_rollback', [$this, 'handle_rollback_migration']);
        add_action('wp_ajax_mas_migration_progress', [$this, 'handle_get_progress']);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'modern-admin-styler-v2',
            __('REST API Migration', 'modern-admin-styler-v2'),
            __('Migration', 'modern-admin-styler-v2'),
            'manage_options',
            'mas-migration',
            [$this, 'render_admin_page']
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        $progress = $this->migration_utility->get_migration_progress();
        $status = $this->migration_utility->get_migration_status();
        
        ?>
        <div class="wrap">
            <h1><?php _e('REST API Migration', 'modern-admin-styler-v2'); ?></h1>
            
            <div class="mas-migration-container">
                <!-- Progress Section -->
                <div class="mas-migration-progress">
                    <h2><?php _e('Migration Progress', 'modern-admin-styler-v2'); ?></h2>
                    
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo esc_attr($progress['progress_percentage']); ?>%"></div>
                        <span class="progress-text"><?php echo esc_html($progress['progress_percentage']); ?>%</span>
                    </div>
                    
                    <div class="progress-info">
                        <p><strong><?php _e('Current Phase:', 'modern-admin-styler-v2'); ?></strong> 
                           <?php echo esc_html($this->get_phase_label($progress['phase'])); ?></p>
                        
                        <?php if ($progress['started_at']): ?>
                            <p><strong><?php _e('Started:', 'modern-admin-styler-v2'); ?></strong> 
                               <?php echo esc_html($progress['started_at']); ?></p>
                        <?php endif; ?>
                        
                        <?php if ($progress['completed_at']): ?>
                            <p><strong><?php _e('Completed:', 'modern-admin-styler-v2'); ?></strong> 
                               <?php echo esc_html($progress['completed_at']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Status Section -->
                <div class="mas-migration-status">
                    <h2><?php _e('Current Configuration', 'modern-admin-styler-v2'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th><?php _e('REST API:', 'modern-admin-styler-v2'); ?></th>
                            <td><?php echo $progress['rest_api_enabled'] ? 
                                '<span class="status-enabled">' . __('Enabled', 'modern-admin-styler-v2') . '</span>' : 
                                '<span class="status-disabled">' . __('Disabled', 'modern-admin-styler-v2') . '</span>'; ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('AJAX Fallback:', 'modern-admin-styler-v2'); ?></th>
                            <td><?php echo $progress['ajax_fallback_enabled'] ? 
                                '<span class="status-enabled">' . __('Enabled', 'modern-admin-styler-v2') . '</span>' : 
                                '<span class="status-disabled">' . __('Disabled', 'modern-admin-styler-v2') . '</span>'; ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('Dual Mode:', 'modern-admin-styler-v2'); ?></th>
                            <td><?php echo $progress['dual_mode_enabled'] ? 
                                '<span class="status-enabled">' . __('Enabled', 'modern-admin-styler-v2') . '</span>' : 
                                '<span class="status-disabled">' . __('Disabled', 'modern-admin-styler-v2') . '</span>'; ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('Rollout Percentage:', 'modern-admin-styler-v2'); ?></th>
                            <td><?php echo esc_html($progress['rollout_percentage']); ?>%</td>
                        </tr>
                    </table>
                </div>
                
                <!-- Actions Section -->
                <div class="mas-migration-actions">
                    <h2><?php _e('Migration Actions', 'modern-admin-styler-v2'); ?></h2>
                    
                    <div class="action-buttons">
                        <button type="button" class="button button-secondary" id="mas-check-compatibility">
                            <?php _e('Check Compatibility', 'modern-admin-styler-v2'); ?>
                        </button>
                        
                        <?php if ($progress['phase'] === 'not_started'): ?>
                            <button type="button" class="button button-primary" id="mas-start-migration">
                                <?php _e('Start Migration', 'modern-admin-styler-v2'); ?>
                            </button>
                        <?php endif; ?>
                        
                        <?php if (in_array($progress['phase'], ['in_progress', 'gradual_rollout_25', 'gradual_rollout_50', 'gradual_rollout_75', 'gradual_rollout_100'])): ?>
                            <button type="button" class="button button-primary" id="mas-complete-migration">
                                <?php _e('Complete Migration', 'modern-admin-styler-v2'); ?>
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($progress['phase'] !== 'not_started' && $progress['phase'] !== 'rolled_back'): ?>
                            <button type="button" class="button button-secondary mas-danger" id="mas-rollback-migration">
                                <?php _e('Rollback Migration', 'modern-admin-styler-v2'); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Results Section -->
                <div class="mas-migration-results" id="mas-migration-results" style="display: none;">
                    <h2><?php _e('Results', 'modern-admin-styler-v2'); ?></h2>
                    <div class="results-content"></div>
                </div>
            </div>
        </div>
        
        <style>
        .mas-migration-container {
            max-width: 800px;
        }
        
        .mas-migration-progress,
        .mas-migration-status,
        .mas-migration-actions,
        .mas-migration-results {
            background: #fff;
            border: 1px solid #ddd;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        
        .progress-bar {
            width: 100%;
            height: 30px;
            background: #f1f1f1;
            border-radius: 15px;
            position: relative;
            overflow: hidden;
            margin: 15px 0;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #4CAF50, #45a049);
            border-radius: 15px;
            transition: width 0.3s ease;
        }
        
        .progress-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-weight: bold;
            color: #333;
        }
        
        .progress-info {
            margin-top: 15px;
        }
        
        .status-enabled {
            color: #46b450;
            font-weight: bold;
        }
        
        .status-disabled {
            color: #dc3232;
            font-weight: bold;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .mas-danger {
            background: #dc3232 !important;
            border-color: #dc3232 !important;
            color: #fff !important;
        }
        
        .mas-danger:hover {
            background: #c62d2d !important;
            border-color: #c62d2d !important;
        }
        
        .compatibility-check {
            margin: 15px 0;
        }
        
        .check-item {
            display: flex;
            align-items: center;
            margin: 10px 0;
            padding: 10px;
            border-radius: 4px;
        }
        
        .check-item.passed {
            background: #d4edda;
            border-left: 4px solid #28a745;
        }
        
        .check-item.failed {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
        }
        
        .check-item.warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
        }
        
        .check-icon {
            margin-right: 10px;
            font-size: 18px;
        }
        
        .recommendations {
            margin-top: 20px;
        }
        
        .recommendation {
            margin: 10px 0;
            padding: 15px;
            border-radius: 4px;
            border-left: 4px solid #007cba;
            background: #f0f6fc;
        }
        
        .recommendation.critical {
            border-left-color: #dc3545;
            background: #f8d7da;
        }
        
        .recommendation.warning {
            border-left-color: #ffc107;
            background: #fff3cd;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            const $results = $('#mas-migration-results');
            const $resultsContent = $('.results-content');
            
            // Check compatibility
            $('#mas-check-compatibility').on('click', function() {
                const $button = $(this);
                $button.prop('disabled', true).text('<?php _e('Checking...', 'modern-admin-styler-v2'); ?>');
                
                $.post(ajaxurl, {
                    action: 'mas_migration_check',
                    nonce: '<?php echo wp_create_nonce('mas_migration'); ?>'
                }, function(response) {
                    if (response.success) {
                        displayCompatibilityResults(response.data);
                    } else {
                        displayError(response.data || '<?php _e('Compatibility check failed', 'modern-admin-styler-v2'); ?>');
                    }
                }).always(function() {
                    $button.prop('disabled', false).text('<?php _e('Check Compatibility', 'modern-admin-styler-v2'); ?>');
                });
            });
            
            // Start migration
            $('#mas-start-migration').on('click', function() {
                if (!confirm('<?php _e('Are you sure you want to start the migration? This will create a backup and begin the gradual rollout.', 'modern-admin-styler-v2'); ?>')) {
                    return;
                }
                
                const $button = $(this);
                $button.prop('disabled', true).text('<?php _e('Starting...', 'modern-admin-styler-v2'); ?>');
                
                $.post(ajaxurl, {
                    action: 'mas_migration_start',
                    nonce: '<?php echo wp_create_nonce('mas_migration'); ?>'
                }, function(response) {
                    if (response.success) {
                        displaySuccess(response.data.message);
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        displayError(response.data || '<?php _e('Migration start failed', 'modern-admin-styler-v2'); ?>');
                    }
                }).always(function() {
                    $button.prop('disabled', false).text('<?php _e('Start Migration', 'modern-admin-styler-v2'); ?>');
                });
            });
            
            // Complete migration
            $('#mas-complete-migration').on('click', function() {
                if (!confirm('<?php _e('Are you sure you want to complete the migration? This will enable REST API for all users and disable AJAX fallback.', 'modern-admin-styler-v2'); ?>')) {
                    return;
                }
                
                const $button = $(this);
                $button.prop('disabled', true).text('<?php _e('Completing...', 'modern-admin-styler-v2'); ?>');
                
                $.post(ajaxurl, {
                    action: 'mas_migration_complete',
                    nonce: '<?php echo wp_create_nonce('mas_migration'); ?>'
                }, function(response) {
                    if (response.success) {
                        displaySuccess(response.data.message);
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        displayError(response.data || '<?php _e('Migration completion failed', 'modern-admin-styler-v2'); ?>');
                    }
                }).always(function() {
                    $button.prop('disabled', false).text('<?php _e('Complete Migration', 'modern-admin-styler-v2'); ?>');
                });
            });
            
            // Rollback migration
            $('#mas-rollback-migration').on('click', function() {
                if (!confirm('<?php _e('Are you sure you want to rollback the migration? This will restore the previous configuration and disable REST API.', 'modern-admin-styler-v2'); ?>')) {
                    return;
                }
                
                const $button = $(this);
                $button.prop('disabled', true).text('<?php _e('Rolling back...', 'modern-admin-styler-v2'); ?>');
                
                $.post(ajaxurl, {
                    action: 'mas_migration_rollback',
                    nonce: '<?php echo wp_create_nonce('mas_migration'); ?>'
                }, function(response) {
                    if (response.success) {
                        displaySuccess(response.data.message);
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        displayError(response.data || '<?php _e('Migration rollback failed', 'modern-admin-styler-v2'); ?>');
                    }
                }).always(function() {
                    $button.prop('disabled', false).text('<?php _e('Rollback Migration', 'modern-admin-styler-v2'); ?>');
                });
            });
            
            function displayCompatibilityResults(data) {
                let html = '<div class="compatibility-check">';
                
                // Overall status
                html += '<h3>' + (data.compatible ? 
                    '✅ <?php _e('System is compatible', 'modern-admin-styler-v2'); ?>' : 
                    '❌ <?php _e('Compatibility issues found', 'modern-admin-styler-v2'); ?>') + '</h3>';
                
                // Individual checks
                Object.keys(data.checks).forEach(function(checkName) {
                    const check = data.checks[checkName];
                    const status = check.passed ? 'passed' : 'failed';
                    const icon = check.passed ? '✅' : '❌';
                    
                    html += '<div class="check-item ' + status + '">';
                    html += '<span class="check-icon">' + icon + '</span>';
                    html += '<span>' + check.message + '</span>';
                    html += '</div>';
                });
                
                // Recommendations
                if (data.recommendations && data.recommendations.length > 0) {
                    html += '<div class="recommendations">';
                    html += '<h4><?php _e('Recommendations', 'modern-admin-styler-v2'); ?></h4>';
                    
                    data.recommendations.forEach(function(rec) {
                        html += '<div class="recommendation ' + rec.type + '">';
                        html += '<h5>' + rec.title + '</h5>';
                        html += '<p>' + rec.description + '</p>';
                        html += '</div>';
                    });
                    
                    html += '</div>';
                }
                
                html += '</div>';
                
                $resultsContent.html(html);
                $results.show();
            }
            
            function displaySuccess(message) {
                $resultsContent.html('<div class="notice notice-success"><p>' + message + '</p></div>');
                $results.show();
            }
            
            function displayError(message) {
                $resultsContent.html('<div class="notice notice-error"><p>' + message + '</p></div>');
                $results.show();
            }
        });
        </script>
        <?php
    }
    
    /**
     * Get phase label
     *
     * @param string $phase
     * @return string
     */
    private function get_phase_label($phase) {
        $labels = [
            'not_started' => __('Not Started', 'modern-admin-styler-v2'),
            'in_progress' => __('In Progress', 'modern-admin-styler-v2'),
            'gradual_rollout_25' => __('Gradual Rollout (25%)', 'modern-admin-styler-v2'),
            'gradual_rollout_50' => __('Gradual Rollout (50%)', 'modern-admin-styler-v2'),
            'gradual_rollout_75' => __('Gradual Rollout (75%)', 'modern-admin-styler-v2'),
            'gradual_rollout_100' => __('Gradual Rollout (100%)', 'modern-admin-styler-v2'),
            'completed' => __('Completed', 'modern-admin-styler-v2'),
            'rolled_back' => __('Rolled Back', 'modern-admin-styler-v2')
        ];
        
        return $labels[$phase] ?? $phase;
    }
    
    /**
     * Handle compatibility check AJAX
     */
    public function handle_compatibility_check() {
        check_ajax_referer('mas_migration', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'modern-admin-styler-v2'));
        }
        
        $results = $this->migration_utility->run_compatibility_check();
        wp_send_json_success($results);
    }
    
    /**
     * Handle start migration AJAX
     */
    public function handle_start_migration() {
        check_ajax_referer('mas_migration', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'modern-admin-styler-v2'));
        }
        
        $result = $this->migration_utility->start_migration();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * Handle complete migration AJAX
     */
    public function handle_complete_migration() {
        check_ajax_referer('mas_migration', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'modern-admin-styler-v2'));
        }
        
        $result = $this->migration_utility->complete_migration();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * Handle rollback migration AJAX
     */
    public function handle_rollback_migration() {
        check_ajax_referer('mas_migration', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'modern-admin-styler-v2'));
        }
        
        $result = $this->migration_utility->rollback_migration();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * Handle get progress AJAX
     */
    public function handle_get_progress() {
        check_ajax_referer('mas_migration', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'modern-admin-styler-v2'));
        }
        
        $progress = $this->migration_utility->get_migration_progress();
        wp_send_json_success($progress);
    }
}

// Initialize if in admin
if (is_admin()) {
    new MAS_Migration_Admin();
}