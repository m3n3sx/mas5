<?php
/**
 * Performance Dashboard Admin Page
 * 
 * Displays real-time performance metrics in WordPress admin.
 *
 * @package ModernAdminStylerV2
 * @subpackage Admin
 * @since 2.3.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * MAS Performance Dashboard Admin
 * 
 * Manages the performance monitoring dashboard in WordPress admin.
 */
class MAS_Performance_Dashboard_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'modern-admin-styler-v2',
            __('Performance Monitor', 'modern-admin-styler-v2'),
            __('Performance', 'modern-admin-styler-v2'),
            'manage_options',
            'mas-performance-monitor',
            [$this, 'render_dashboard']
        );
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts($hook) {
        if ($hook !== 'modern-admin-styler-v2_page_mas-performance-monitor') {
            return;
        }
        
        // Enqueue performance dashboard CSS
        wp_enqueue_style(
            'mas-performance-dashboard',
            plugins_url('assets/css/performance-dashboard.css', dirname(dirname(__FILE__))),
            [],
            MAS_VERSION ?? '2.3.0'
        );
        
        // Enqueue Chart.js for charts
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js',
            [],
            '3.9.1',
            true
        );
        
        // Enqueue REST client
        wp_enqueue_script(
            'mas-rest-client',
            plugins_url('assets/js/mas-rest-client.js', dirname(dirname(__FILE__))),
            [],
            MAS_VERSION ?? '2.3.0',
            true
        );
        
        // Enqueue performance monitor
        wp_enqueue_script(
            'mas-performance-monitor',
            plugins_url('assets/js/modules/PerformanceMonitor.js', dirname(dirname(__FILE__))),
            ['mas-rest-client', 'chartjs'],
            MAS_VERSION ?? '2.3.0',
            true
        );
        
        // Localize script with REST API settings
        wp_localize_script('mas-rest-client', 'masApiSettings', [
            'root' => esc_url_raw(rest_url()),
            'nonce' => wp_create_nonce('wp_rest'),
            'namespace' => 'mas-v2/v1'
        ]);
    }
    
    /**
     * Render dashboard
     */
    public function render_dashboard() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div id="mas-performance-dashboard">
                <!-- Dashboard will be rendered by JavaScript -->
                <div class="loading-placeholder">
                    <p><?php _e('Loading performance metrics...', 'modern-admin-styler-v2'); ?></p>
                </div>
            </div>
        </div>
        
        <style>
        .loading-placeholder {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        </style>
        <?php
    }
}

// Initialize
new MAS_Performance_Dashboard_Admin();
