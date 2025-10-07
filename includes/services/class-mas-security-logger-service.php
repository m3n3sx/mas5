<?php
/**
 * Security Logger Service
 *
 * Provides comprehensive security audit logging and suspicious activity detection.
 *
 * @package ModernAdminStyler
 * @subpackage Services
 * @since 2.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Security Logger Service Class
 *
 * Logs security events and detects suspicious activity patterns.
 */
class MAS_Security_Logger_Service {
    
    /**
     * Database table name
     *
     * @var string
     */
    private $table_name;
    
    /**
     * Event types
     *
     * @var array
     */
    private $event_types = [
        'settings_updated',
        'settings_reset',
        'theme_applied',
        'backup_created',
        'backup_restored',
        'backup_deleted',
        'import_success',
        'import_failed',
        'export',
        'auth_failed',
        'rate_limit_exceeded',
        'validation_failed',
        'permission_denied',
    ];
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'mas_v2_audit_log';
    }
    
    /**
     * Create audit log table
     *
     * @return bool Success
     */
    public function create_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
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
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        return true;
    }
    
    /**
     * Log a security event
     *
     * @param string $action Action type
     * @param string $description Human-readable description
     * @param array $data Additional data (old_value, new_value, status)
     * @param int|null $user_id User ID (defaults to current user)
     * @return int|false Log entry ID or false on failure
     */
    public function log_event($action, $description, $data = [], $user_id = null) {
        global $wpdb;
        
        // Get user information
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        $user = get_userdata($user_id);
        $username = $user ? $user->user_login : 'unknown';
        
        // Prepare log entry
        $log_entry = [
            'user_id' => $user_id,
            'username' => $username,
            'action' => sanitize_key($action),
            'description' => sanitize_text_field($description),
            'ip_address' => $this->get_client_ip(),
            'user_agent' => $this->get_user_agent(),
            'timestamp' => current_time('mysql'),
        ];
        
        // Add optional fields
        if (isset($data['old_value'])) {
            $log_entry['old_value'] = is_array($data['old_value']) || is_object($data['old_value'])
                ? wp_json_encode($data['old_value'])
                : $data['old_value'];
        }
        
        if (isset($data['new_value'])) {
            $log_entry['new_value'] = is_array($data['new_value']) || is_object($data['new_value'])
                ? wp_json_encode($data['new_value'])
                : $data['new_value'];
        }
        
        if (isset($data['status'])) {
            $log_entry['status'] = sanitize_key($data['status']);
        }
        
        // Insert log entry
        $result = $wpdb->insert(
            $this->table_name,
            $log_entry,
            [
                '%d', // user_id
                '%s', // username
                '%s', // action
                '%s', // description
                '%s', // ip_address
                '%s', // user_agent
                '%s', // timestamp
            ]
        );
        
        if ($result === false) {
            error_log('MAS Security Logger: Failed to insert log entry - ' . $wpdb->last_error);
            return false;
        }
        
        // Cleanup old logs (keep last 10,000 entries)
        $this->cleanup_old_logs();
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get audit log entries
     *
     * @param array $args Query arguments
     * @return array Log entries
     */
    public function get_audit_log($args = []) {
        global $wpdb;
        
        // Default arguments
        $defaults = [
            'user_id' => null,
            'action' => null,
            'status' => null,
            'ip_address' => null,
            'date_from' => null,
            'date_to' => null,
            'limit' => 50,
            'offset' => 0,
            'orderby' => 'timestamp',
            'order' => 'DESC',
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        // Build WHERE clause
        $where = ['1=1'];
        $where_values = [];
        
        if ($args['user_id']) {
            $where[] = 'user_id = %d';
            $where_values[] = $args['user_id'];
        }
        
        if ($args['action']) {
            $where[] = 'action = %s';
            $where_values[] = $args['action'];
        }
        
        if ($args['status']) {
            $where[] = 'status = %s';
            $where_values[] = $args['status'];
        }
        
        if ($args['ip_address']) {
            $where[] = 'ip_address = %s';
            $where_values[] = $args['ip_address'];
        }
        
        if ($args['date_from']) {
            $where[] = 'timestamp >= %s';
            $where_values[] = $args['date_from'];
        }
        
        if ($args['date_to']) {
            $where[] = 'timestamp <= %s';
            $where_values[] = $args['date_to'];
        }
        
        $where_clause = implode(' AND ', $where);
        
        // Build ORDER BY clause
        $allowed_orderby = ['id', 'timestamp', 'action', 'user_id'];
        $orderby = in_array($args['orderby'], $allowed_orderby) ? $args['orderby'] : 'timestamp';
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
        
        // Build query
        $query = "SELECT * FROM {$this->table_name} WHERE {$where_clause} ORDER BY {$orderby} {$order}";
        
        // Add pagination
        if ($args['limit'] > 0) {
            $query .= $wpdb->prepare(' LIMIT %d OFFSET %d', $args['limit'], $args['offset']);
        }
        
        // Prepare and execute query
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }
        
        $results = $wpdb->get_results($query, ARRAY_A);
        
        // Decode JSON values
        foreach ($results as &$result) {
            if (!empty($result['old_value'])) {
                $decoded = json_decode($result['old_value'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $result['old_value'] = $decoded;
                }
            }
            
            if (!empty($result['new_value'])) {
                $decoded = json_decode($result['new_value'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $result['new_value'] = $decoded;
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Get total count of audit log entries
     *
     * @param array $args Query arguments (same as get_audit_log)
     * @return int Total count
     */
    public function get_audit_log_count($args = []) {
        global $wpdb;
        
        // Build WHERE clause (same as get_audit_log)
        $where = ['1=1'];
        $where_values = [];
        
        if (!empty($args['user_id'])) {
            $where[] = 'user_id = %d';
            $where_values[] = $args['user_id'];
        }
        
        if (!empty($args['action'])) {
            $where[] = 'action = %s';
            $where_values[] = $args['action'];
        }
        
        if (!empty($args['status'])) {
            $where[] = 'status = %s';
            $where_values[] = $args['status'];
        }
        
        if (!empty($args['ip_address'])) {
            $where[] = 'ip_address = %s';
            $where_values[] = $args['ip_address'];
        }
        
        if (!empty($args['date_from'])) {
            $where[] = 'timestamp >= %s';
            $where_values[] = $args['date_from'];
        }
        
        if (!empty($args['date_to'])) {
            $where[] = 'timestamp <= %s';
            $where_values[] = $args['date_to'];
        }
        
        $where_clause = implode(' AND ', $where);
        
        $query = "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_clause}";
        
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }
        
        return (int) $wpdb->get_var($query);
    }
    
    /**
     * Check for suspicious activity patterns
     *
     * @param int|null $user_id User ID to check (defaults to current user)
     * @param string|null $ip_address IP address to check (defaults to current IP)
     * @return array Suspicious activity report
     */
    public function check_suspicious_activity($user_id = null, $ip_address = null) {
        global $wpdb;
        
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        if ($ip_address === null) {
            $ip_address = $this->get_client_ip();
        }
        
        $suspicious = [];
        $time_window = date('Y-m-d H:i:s', strtotime('-1 hour'));
        
        // Check for multiple failed authentication attempts
        $failed_auth_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} 
            WHERE action = 'auth_failed' 
            AND (user_id = %d OR ip_address = %s)
            AND timestamp >= %s",
            $user_id,
            $ip_address,
            $time_window
        ));
        
        if ($failed_auth_count >= 5) {
            $suspicious[] = [
                'type' => 'multiple_failed_auth',
                'severity' => 'high',
                'count' => $failed_auth_count,
                'message' => sprintf(
                    __('%d failed authentication attempts in the last hour', 'modern-admin-styler-v2'),
                    $failed_auth_count
                ),
            ];
        }
        
        // Check for rapid requests (rate limit violations)
        $rate_limit_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} 
            WHERE action = 'rate_limit_exceeded' 
            AND (user_id = %d OR ip_address = %s)
            AND timestamp >= %s",
            $user_id,
            $ip_address,
            $time_window
        ));
        
        if ($rate_limit_count >= 10) {
            $suspicious[] = [
                'type' => 'excessive_rate_limits',
                'severity' => 'medium',
                'count' => $rate_limit_count,
                'message' => sprintf(
                    __('%d rate limit violations in the last hour', 'modern-admin-styler-v2'),
                    $rate_limit_count
                ),
            ];
        }
        
        // Check for multiple validation failures
        $validation_failures = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} 
            WHERE action = 'validation_failed' 
            AND (user_id = %d OR ip_address = %s)
            AND timestamp >= %s",
            $user_id,
            $ip_address,
            $time_window
        ));
        
        if ($validation_failures >= 20) {
            $suspicious[] = [
                'type' => 'excessive_validation_failures',
                'severity' => 'medium',
                'count' => $validation_failures,
                'message' => sprintf(
                    __('%d validation failures in the last hour', 'modern-admin-styler-v2'),
                    $validation_failures
                ),
            ];
        }
        
        // Check for unusual activity patterns (many different actions in short time)
        $action_diversity = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT action) FROM {$this->table_name} 
            WHERE (user_id = %d OR ip_address = %s)
            AND timestamp >= %s",
            $user_id,
            $ip_address,
            date('Y-m-d H:i:s', strtotime('-5 minutes'))
        ));
        
        if ($action_diversity >= 8) {
            $suspicious[] = [
                'type' => 'unusual_activity_pattern',
                'severity' => 'low',
                'count' => $action_diversity,
                'message' => sprintf(
                    __('%d different actions performed in 5 minutes', 'modern-admin-styler-v2'),
                    $action_diversity
                ),
            ];
        }
        
        return [
            'is_suspicious' => !empty($suspicious),
            'user_id' => $user_id,
            'ip_address' => $ip_address,
            'patterns' => $suspicious,
            'checked_at' => current_time('mysql'),
        ];
    }
    
    /**
     * Get client IP address
     *
     * @return string IP address
     */
    private function get_client_ip() {
        $ip = '';
        
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                
                break;
            }
        }
        
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
        
        return '0.0.0.0';
    }
    
    /**
     * Get user agent string
     *
     * @return string User agent
     */
    private function get_user_agent() {
        return !empty($_SERVER['HTTP_USER_AGENT']) 
            ? substr(sanitize_text_field($_SERVER['HTTP_USER_AGENT']), 0, 255)
            : 'unknown';
    }
    
    /**
     * Cleanup old log entries
     *
     * Keeps only the most recent 10,000 entries
     */
    private function cleanup_old_logs() {
        global $wpdb;
        
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
        
        if ($count > 10000) {
            $wpdb->query(
                "DELETE FROM {$this->table_name} 
                WHERE id NOT IN (
                    SELECT id FROM (
                        SELECT id FROM {$this->table_name} 
                        ORDER BY timestamp DESC 
                        LIMIT 10000
                    ) AS keep_ids
                )"
            );
        }
    }
    
    /**
     * Get available event types
     *
     * @return array Event types
     */
    public function get_event_types() {
        return $this->event_types;
    }
}
