<?php
/**
 * Deprecation Service for Modern Admin Styler V2
 * 
 * Manages endpoint deprecation tracking, warnings, and migration guidance.
 *
 * @package ModernAdminStylerV2
 * @subpackage Services
 * @since 2.3.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Deprecation Service Class
 * 
 * Handles deprecation management for API endpoints and features.
 */
class MAS_Deprecation_Service {
    
    /**
     * Deprecated endpoints registry
     * 
     * @var array
     */
    private $deprecated_endpoints = [];
    
    /**
     * Migration guides base URL
     * 
     * @var string
     */
    private $migration_guide_url = 'https://github.com/yourusername/modern-admin-styler-v2/wiki/API-Migration-Guide';
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_deprecated_endpoints();
    }
    
    /**
     * Initialize deprecated endpoints registry
     * 
     * @return void
     */
    private function init_deprecated_endpoints() {
        // Example: Mark specific endpoints as deprecated
        // This would be populated as endpoints are deprecated
        
        // Example deprecated endpoint (for demonstration)
        // $this->mark_deprecated(
        //     '/settings/legacy',
        //     'v1',
        //     '2025-12-31',
        //     '/settings',
        //     'Use the new /settings endpoint with updated schema'
        // );
    }
    
    /**
     * Mark an endpoint as deprecated
     * 
     * @param string $endpoint Endpoint path (e.g., '/settings/legacy')
     * @param string $version API version
     * @param string $removal_date Date when endpoint will be removed (Y-m-d)
     * @param string $replacement Replacement endpoint path
     * @param string $reason Deprecation reason and migration guidance
     * @return bool True on success
     */
    public function mark_deprecated($endpoint, $version, $removal_date, $replacement = '', $reason = '') {
        $key = $this->get_endpoint_key($endpoint, $version);
        
        $this->deprecated_endpoints[$key] = [
            'endpoint' => $endpoint,
            'version' => $version,
            'deprecated_date' => current_time('mysql'),
            'removal_date' => $removal_date,
            'replacement' => $replacement,
            'reason' => $reason,
            'migration_guide' => $this->get_migration_guide_url($endpoint, $version)
        ];
        
        // Store in database for persistence
        $this->save_to_database();
        
        return true;
    }
    
    /**
     * Check if endpoint is deprecated
     * 
     * @param string $endpoint Endpoint path
     * @param string $version API version
     * @return bool True if deprecated
     */
    public function is_deprecated($endpoint, $version) {
        $key = $this->get_endpoint_key($endpoint, $version);
        return isset($this->deprecated_endpoints[$key]);
    }
    
    /**
     * Get deprecation information for endpoint
     * 
     * @param string $endpoint Endpoint path
     * @param string $version API version
     * @return array|null Deprecation info or null if not deprecated
     */
    public function get_deprecation_info($endpoint, $version) {
        $key = $this->get_endpoint_key($endpoint, $version);
        
        if (!isset($this->deprecated_endpoints[$key])) {
            return null;
        }
        
        return $this->deprecated_endpoints[$key];
    }
    
    /**
     * Get all deprecated endpoints
     * 
     * @param string $version Optional version filter
     * @return array Deprecated endpoints
     */
    public function get_all_deprecated($version = null) {
        if ($version === null) {
            return $this->deprecated_endpoints;
        }
        
        return array_filter($this->deprecated_endpoints, function($info) use ($version) {
            return $info['version'] === $version;
        });
    }
    
    /**
     * Generate deprecation warning message
     * 
     * @param string $endpoint Endpoint path
     * @param string $version API version
     * @return string Warning message
     */
    public function get_warning_message($endpoint, $version) {
        $info = $this->get_deprecation_info($endpoint, $version);
        
        if (!$info) {
            return '';
        }
        
        $message = sprintf(
            'This endpoint is deprecated and will be removed on %s.',
            date('F j, Y', strtotime($info['removal_date']))
        );
        
        if (!empty($info['replacement'])) {
            $message .= sprintf(' Please use %s instead.', $info['replacement']);
        }
        
        if (!empty($info['migration_guide'])) {
            $message .= sprintf(' See migration guide: %s', $info['migration_guide']);
        }
        
        return $message;
    }
    
    /**
     * Generate Warning header value
     * 
     * Follows RFC 7234 Warning header format:
     * Warning: <warn-code> <warn-agent> "<warn-text>" ["<warn-date>"]
     * 
     * @param string $endpoint Endpoint path
     * @param string $version API version
     * @return string Warning header value
     */
    public function get_warning_header($endpoint, $version) {
        $message = $this->get_warning_message($endpoint, $version);
        
        if (empty($message)) {
            return '';
        }
        
        // Use warn-code 299 for miscellaneous persistent warning
        return sprintf(
            '299 %s "%s"',
            parse_url(get_site_url(), PHP_URL_HOST),
            addslashes($message)
        );
    }
    
    /**
     * Add deprecation headers to response
     * 
     * @param WP_REST_Response $response Response object
     * @param string $endpoint Endpoint path
     * @param string $version API version
     * @return WP_REST_Response Modified response
     */
    public function add_deprecation_headers($response, $endpoint, $version) {
        if (!$this->is_deprecated($endpoint, $version)) {
            return $response;
        }
        
        $info = $this->get_deprecation_info($endpoint, $version);
        
        // Add Warning header
        $warning = $this->get_warning_header($endpoint, $version);
        if ($warning) {
            $response->header('Warning', $warning);
        }
        
        // Add custom deprecation headers for easier parsing
        $response->header('X-API-Deprecated', 'true');
        $response->header('X-API-Deprecation-Date', $info['deprecated_date']);
        $response->header('X-API-Removal-Date', $info['removal_date']);
        
        if (!empty($info['replacement'])) {
            $response->header('X-API-Replacement', $info['replacement']);
        }
        
        if (!empty($info['migration_guide'])) {
            $response->header('X-API-Migration-Guide', $info['migration_guide']);
        }
        
        return $response;
    }
    
    /**
     * Log deprecation usage
     * 
     * Tracks when deprecated endpoints are used for analytics.
     * 
     * @param string $endpoint Endpoint path
     * @param string $version API version
     * @param int $user_id User ID
     * @return void
     */
    public function log_usage($endpoint, $version, $user_id = 0) {
        if (!$this->is_deprecated($endpoint, $version)) {
            return;
        }
        
        // Log to database for tracking
        global $wpdb;
        $table = $wpdb->prefix . 'mas_v2_deprecation_log';
        
        // Create table if it doesn't exist
        $this->maybe_create_log_table();
        
        $wpdb->insert(
            $table,
            [
                'endpoint' => $endpoint,
                'version' => $version,
                'user_id' => $user_id,
                'ip_address' => $this->get_client_ip(),
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
                'timestamp' => current_time('mysql')
            ],
            ['%s', '%s', '%d', '%s', '%s', '%s']
        );
    }
    
    /**
     * Get deprecation usage statistics
     * 
     * @param string $endpoint Optional endpoint filter
     * @param string $version Optional version filter
     * @param int $days Number of days to look back
     * @return array Usage statistics
     */
    public function get_usage_stats($endpoint = null, $version = null, $days = 30) {
        global $wpdb;
        $table = $wpdb->prefix . 'mas_v2_deprecation_log';
        
        $where = ['1=1'];
        $params = [];
        
        if ($endpoint) {
            $where[] = 'endpoint = %s';
            $params[] = $endpoint;
        }
        
        if ($version) {
            $where[] = 'version = %s';
            $params[] = $version;
        }
        
        $where[] = 'timestamp >= DATE_SUB(NOW(), INTERVAL %d DAY)';
        $params[] = $days;
        
        $where_clause = implode(' AND ', $where);
        
        $query = "SELECT 
            endpoint,
            version,
            COUNT(*) as usage_count,
            COUNT(DISTINCT user_id) as unique_users,
            MAX(timestamp) as last_used
            FROM $table
            WHERE $where_clause
            GROUP BY endpoint, version
            ORDER BY usage_count DESC";
        
        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }
        
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * Get endpoint key for registry
     * 
     * @param string $endpoint Endpoint path
     * @param string $version API version
     * @return string Unique key
     */
    private function get_endpoint_key($endpoint, $version) {
        return $version . ':' . $endpoint;
    }
    
    /**
     * Get migration guide URL for endpoint
     * 
     * @param string $endpoint Endpoint path
     * @param string $version API version
     * @return string Migration guide URL
     */
    private function get_migration_guide_url($endpoint, $version) {
        $slug = sanitize_title($endpoint);
        return $this->migration_guide_url . '#' . $version . '-' . $slug;
    }
    
    /**
     * Save deprecated endpoints to database
     * 
     * @return void
     */
    private function save_to_database() {
        update_option('mas_v2_deprecated_endpoints', $this->deprecated_endpoints, false);
    }
    
    /**
     * Load deprecated endpoints from database
     * 
     * @return void
     */
    private function load_from_database() {
        $saved = get_option('mas_v2_deprecated_endpoints', []);
        if (is_array($saved)) {
            $this->deprecated_endpoints = array_merge($this->deprecated_endpoints, $saved);
        }
    }
    
    /**
     * Create deprecation log table
     * 
     * @return void
     */
    private function maybe_create_log_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'mas_v2_deprecation_log';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            endpoint varchar(255) NOT NULL,
            version varchar(10) NOT NULL,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            ip_address varchar(45) NOT NULL,
            user_agent text,
            timestamp datetime NOT NULL,
            PRIMARY KEY (id),
            KEY endpoint_version (endpoint, version),
            KEY timestamp (timestamp),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
    
    /**
     * Get client IP address
     * 
     * @return string IP address
     */
    private function get_client_ip() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return sanitize_text_field($ip);
    }
    
    /**
     * Remove endpoint from deprecated list
     * 
     * @param string $endpoint Endpoint path
     * @param string $version API version
     * @return bool True on success
     */
    public function remove_deprecated($endpoint, $version) {
        $key = $this->get_endpoint_key($endpoint, $version);
        
        if (!isset($this->deprecated_endpoints[$key])) {
            return false;
        }
        
        unset($this->deprecated_endpoints[$key]);
        $this->save_to_database();
        
        return true;
    }
}
