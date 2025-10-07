<?php
/**
 * Feature Flags Admin UI
 * 
 * Provides admin interface for managing feature flags.
 * 
 * @package ModernAdminStylerV2
 * @subpackage Admin
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class MAS_Feature_Flags_Admin {
    
    /**
     * Feature flags service
     */
    private $flags_service;
    
    /**
     * Constructor
     */
    public function __construct() {
        require_once MAS_V2_PLUGIN_DIR . 'includes/services/class-mas-feature-flags-service.php';
        $this->flags_service = MAS_Feature_Flags_Service::get_instance();
        
        // Add hooks
        add_action('admin_menu', [$this, 'add_admin_menu'], 20);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_post_mas_v2_toggle_feature_flag', [$this, 'handle_toggle_flag']);
        add_action('admin_notices', [$this, 'show_frontend_mode_notice']);
        add_action('wp_ajax_mas_v2_dismiss_notice', [$this, 'handle_dismiss_notice']);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'mas-v2-settings',
            __('Feature Flags', 'modern-admin-styler-v2'),
            __('⚙️ Feature Flags', 'modern-admin-styler-v2'),
            'manage_options',
            'mas-v2-feature-flags',
            [$this, 'render_admin_page']
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('mas_v2_feature_flags', 'mas_v2_feature_flags', [
            'sanitize_callback' => [$this, 'sanitize_flags']
        ]);
    }
    
    /**
     * Sanitize flags
     */
    public function sanitize_flags($input) {
        $sanitized = [];
        
        if (!is_array($input)) {
            return $sanitized;
        }
        
        foreach ($input as $key => $value) {
            $sanitized[$key] = (bool) $value;
        }
        
        return $sanitized;
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $flags = $this->flags_service->get_all_flags();
        $frontend_mode = $this->flags_service->get_frontend_mode();
        
        ?>
        <div class="wrap">
            <h1><?php _e('Feature Flags', 'modern-admin-styler-v2'); ?></h1>
            
            <div class="notice notice-info">
                <p>
                    <strong><?php _e('Current Frontend Mode:', 'modern-admin-styler-v2'); ?></strong>
                    <span class="mas-frontend-mode-badge mas-frontend-mode-<?php echo esc_attr($frontend_mode); ?>">
                        <?php echo esc_html(ucfirst($frontend_mode)); ?>
                    </span>
                </p>
                <p>
                    <?php _e('Feature flags allow you to enable or disable specific features. Use these to test new functionality or troubleshoot issues.', 'modern-admin-styler-v2'); ?>
                </p>
            </div>
            
            <?php if ($frontend_mode === 'new'): ?>
            <div class="notice notice-warning">
                <p>
                    <strong><?php _e('New Frontend Active', 'modern-admin-styler-v2'); ?></strong><br>
                    <?php _e('You are using the new Phase 3 frontend architecture. If you experience issues, you can switch back to the legacy frontend below.', 'modern-admin-styler-v2'); ?>
                </p>
            </div>
            <?php endif; ?>
            
            <form method="post" action="options.php">
                <?php settings_fields('mas_v2_feature_flags'); ?>
                
                <table class="form-table mas-feature-flags-table">
                    <tbody>
                        <?php foreach ($flags as $flag_name => $flag_value): ?>
                        <tr>
                            <th scope="row">
                                <label for="flag_<?php echo esc_attr($flag_name); ?>">
                                    <?php echo esc_html(ucwords(str_replace('_', ' ', $flag_name))); ?>
                                </label>
                            </th>
                            <td>
                                <label class="mas-toggle-switch">
                                    <input 
                                        type="checkbox" 
                                        name="mas_v2_feature_flags[<?php echo esc_attr($flag_name); ?>]" 
                                        id="flag_<?php echo esc_attr($flag_name); ?>"
                                        value="1"
                                        <?php checked($flag_value, true); ?>
                                        <?php if ($flag_name === 'use_new_frontend'): ?>
                                        data-warning="<?php esc_attr_e('Changing this will switch between frontend systems. Make sure to test thoroughly.', 'modern-admin-styler-v2'); ?>"
                                        <?php endif; ?>
                                    >
                                    <span class="mas-toggle-slider"></span>
                                </label>
                                
                                <p class="description">
                                    <?php echo esc_html($this->flags_service->get_flag_description($flag_name)); ?>
                                </p>
                                
                                <?php if ($flag_name === 'use_new_frontend'): ?>
                                <p class="description" style="color: #d63638;">
                                    <strong><?php _e('Important:', 'modern-admin-styler-v2'); ?></strong>
                                    <?php _e('This controls which frontend system is loaded. Enable to use the new Phase 3 architecture.', 'modern-admin-styler-v2'); ?>
                                </p>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php submit_button(__('Save Feature Flags', 'modern-admin-styler-v2')); ?>
            </form>
            
            <hr>
            
            <h2><?php _e('Quick Actions', 'modern-admin-styler-v2'); ?></h2>
            
            <div class="mas-quick-actions">
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline-block; margin-right: 10px;">
                    <?php wp_nonce_field('mas_v2_toggle_frontend', 'mas_v2_nonce'); ?>
                    <input type="hidden" name="action" value="mas_v2_toggle_feature_flag">
                    <input type="hidden" name="flag" value="use_new_frontend">
                    <input type="hidden" name="value" value="<?php echo $flags['use_new_frontend'] ? '0' : '1'; ?>">
                    <button type="submit" class="button button-secondary">
                        <?php if ($flags['use_new_frontend']): ?>
                            <?php _e('Switch to Legacy Frontend', 'modern-admin-styler-v2'); ?>
                        <?php else: ?>
                            <?php _e('Switch to New Frontend', 'modern-admin-styler-v2'); ?>
                        <?php endif; ?>
                    </button>
                </form>
                
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline-block;" onsubmit="return confirm('<?php esc_attr_e('Are you sure you want to reset all feature flags to defaults?', 'modern-admin-styler-v2'); ?>');">
                    <?php wp_nonce_field('mas_v2_reset_flags', 'mas_v2_nonce'); ?>
                    <input type="hidden" name="action" value="mas_v2_toggle_feature_flag">
                    <input type="hidden" name="reset" value="1">
                    <button type="submit" class="button button-secondary">
                        <?php _e('Reset to Defaults', 'modern-admin-styler-v2'); ?>
                    </button>
                </form>
            </div>
            
            <style>
                .mas-frontend-mode-badge {
                    display: inline-block;
                    padding: 4px 12px;
                    border-radius: 4px;
                    font-weight: 600;
                    font-size: 14px;
                    margin-left: 10px;
                }
                
                .mas-frontend-mode-new {
                    background: #00a32a;
                    color: white;
                }
                
                .mas-frontend-mode-legacy {
                    background: #dba617;
                    color: white;
                }
                
                .mas-toggle-switch {
                    position: relative;
                    display: inline-block;
                    width: 60px;
                    height: 34px;
                }
                
                .mas-toggle-switch input {
                    opacity: 0;
                    width: 0;
                    height: 0;
                }
                
                .mas-toggle-slider {
                    position: absolute;
                    cursor: pointer;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background-color: #ccc;
                    transition: .4s;
                    border-radius: 34px;
                }
                
                .mas-toggle-slider:before {
                    position: absolute;
                    content: "";
                    height: 26px;
                    width: 26px;
                    left: 4px;
                    bottom: 4px;
                    background-color: white;
                    transition: .4s;
                    border-radius: 50%;
                }
                
                input:checked + .mas-toggle-slider {
                    background-color: #2271b1;
                }
                
                input:checked + .mas-toggle-slider:before {
                    transform: translateX(26px);
                }
                
                .mas-feature-flags-table th {
                    width: 250px;
                }
                
                .mas-quick-actions {
                    margin-top: 20px;
                }
            </style>
            
            <script>
                jQuery(document).ready(function($) {
                    // Warn on critical flag changes
                    $('input[data-warning]').on('change', function() {
                        if ($(this).is(':checked')) {
                            if (!confirm($(this).data('warning'))) {
                                $(this).prop('checked', false);
                            }
                        }
                    });
                });
            </script>
        </div>
        <?php
    }
    
    /**
     * Handle toggle flag action
     */
    public function handle_toggle_flag() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.'));
        }
        
        // Verify nonce
        if (!isset($_POST['mas_v2_nonce'])) {
            wp_die(__('Security check failed.'));
        }
        
        $nonce_action = isset($_POST['reset']) ? 'mas_v2_reset_flags' : 'mas_v2_toggle_frontend';
        if (!wp_verify_nonce($_POST['mas_v2_nonce'], $nonce_action)) {
            wp_die(__('Security check failed.'));
        }
        
        // Handle reset
        if (isset($_POST['reset'])) {
            $this->flags_service->reset_to_defaults();
            
            wp_redirect(add_query_arg([
                'page' => 'mas-v2-feature-flags',
                'message' => 'reset'
            ], admin_url('admin.php')));
            exit;
        }
        
        // Handle toggle
        $flag = sanitize_key($_POST['flag']);
        $value = isset($_POST['value']) ? (bool) $_POST['value'] : false;
        
        $this->flags_service->set_flag($flag, $value);
        
        wp_redirect(add_query_arg([
            'page' => 'mas-v2-feature-flags',
            'message' => 'updated'
        ], admin_url('admin.php')));
        exit;
    }
    
    /**
     * Show frontend mode notice on settings pages
     */
    public function show_frontend_mode_notice() {
        $screen = get_current_screen();
        
        // Only show on MAS pages
        if (!$screen || strpos($screen->id, 'mas-v2') === false) {
            return;
        }
        
        // Don't show on feature flags page
        if ($screen->id === 'mas-v2_page_mas-v2-feature-flags') {
            return;
        }
        
        $frontend_mode = $this->flags_service->get_frontend_mode();
        
        if ($frontend_mode === 'new') {
            ?>
            <div class="notice notice-info">
                <p>
                    <strong><?php _e('New Frontend Active', 'modern-admin-styler-v2'); ?></strong> - 
                    <?php _e('You are using the Phase 3 frontend architecture.', 'modern-admin-styler-v2'); ?>
                    <a href="<?php echo admin_url('admin.php?page=mas-v2-feature-flags'); ?>">
                        <?php _e('Manage Feature Flags', 'modern-admin-styler-v2'); ?>
                    </a>
                </p>
            </div>
            <?php
        } else {
            // Show notice about new frontend availability (dismissible)
            $dismissed = get_user_meta(get_current_user_id(), 'mas_v2_new_frontend_notice_dismissed', true);
            
            if (!$dismissed) {
                ?>
                <div class="notice notice-info is-dismissible" data-notice="mas-v2-new-frontend">
                    <p>
                        <strong><?php _e('New Frontend Available!', 'modern-admin-styler-v2'); ?></strong><br>
                        <?php _e('Modern Admin Styler V2 now includes a new Phase 3 frontend with improved performance and reliability.', 'modern-admin-styler-v2'); ?>
                        <a href="<?php echo admin_url('admin.php?page=mas-v2-feature-flags'); ?>">
                            <?php _e('Try it now', 'modern-admin-styler-v2'); ?>
                        </a>
                        |
                        <a href="<?php echo esc_url(MAS_V2_PLUGIN_URL . 'docs/PHASE3-MIGRATION-GUIDE.md'); ?>" target="_blank">
                            <?php _e('Learn more', 'modern-admin-styler-v2'); ?>
                        </a>
                    </p>
                </div>
                <script>
                jQuery(document).ready(function($) {
                    $(document).on('click', '.notice[data-notice="mas-v2-new-frontend"] .notice-dismiss', function() {
                        $.post(ajaxurl, {
                            action: 'mas_v2_dismiss_notice',
                            notice: 'new_frontend',
                            nonce: '<?php echo wp_create_nonce('mas_v2_dismiss_notice'); ?>'
                        });
                    });
                });
                </script>
                <?php
            }
        }
    }
    
    /**
     * Handle dismiss notice AJAX request
     */
    public function handle_dismiss_notice() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mas_v2_dismiss_notice')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        // Get notice type
        $notice = sanitize_key($_POST['notice']);
        
        // Save dismissal
        update_user_meta(get_current_user_id(), 'mas_v2_' . $notice . '_notice_dismissed', true);
        
        wp_send_json_success();
    }
}

// Initialize
new MAS_Feature_Flags_Admin();
