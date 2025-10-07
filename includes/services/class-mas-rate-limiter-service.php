<?php
/**
 * Rate Limiter Service
 *
 * Provides rate limiting functionality for API endpoints with per-user and per-IP tracking.
 *
 * @package ModernAdminStyler
 * @subpackage Services
 * @since 2.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Custom exception for rate limit violations
 */
class MAS_Rate_Limit_Exception extends Exception {
    private $retry_after;
    
    public function __construct($message, $retry_after = 60) {
        parent::__construct($message, 429);
        $this->retry_after = $retry_after;
    }
    
    public function get_retry_after() {
        return $this->retry_after;
    }
}

/**
 * Rate Limiter Service Class
 *
 * Implements rate limiting with configurable limits per endpoint type.
 */
class MAS_Rate_Limiter_Service {
    
    /**
     * Rate limit configurations
     *
     * @var array
     */
    private $limits = [
        'default' => [
            'requests' => 60,
            'window' => 60, // seconds
        ],
        'settings_save' => [
            'requests' => 10,
            'window' => 60,
        ],
        'backup_create' => [
            'requests' => 5,
            'window' => 300, // 5 minutes
        ],
        'theme_apply' => [
            'requests' => 10,
            'window' => 60,
        ],
        'import' => [
            'requests' => 3,
            'window' => 300,
        ],
    ];
    
    /**
     * Cache group for rate limit data
     *
     * @var string
     */
    private $cache_group = 'mas_v2_rate_limits';
    
    /**
     * Constructor
     */
    public function __construct() {
        // Allow filtering of rate limits
        $this->limits = apply_filters('mas_v2_rate_limits', $this->limits);
    }
    
    /**
     * Check if request is within rate limit
     *
     * @param string $action The action being rate limited (e.g., 'settings_save', 'backup_create')
     * @param int|null $user_id Optional user ID (defaults to current user)
     * @param string|null $ip_address Optional IP address (defaults to current IP)
     * @return bool True if within limit
     * @throws MAS_Rate_Limit_Exception If rate limit exceeded
     */
    public function check_rate_limit($action = 'default', $user_id = null, $ip_address = null) {
        // Get user ID and IP
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        if ($ip_address === null) {
            $ip_address = $this->get_client_ip();
        }
        
        // Get limit configuration
        $limit_config = $this->get_limit_config($action);
        
        // Check per-user limit
        $user_limited = $this->check_limit('user', $user_id, $action, $limit_config);
        
        // Check per-IP limit
        $ip_limited = $this->check_limit('ip', $ip_address, $action, $limit_config);
        
        // If either limit is exceeded, throw exception
        if ($user_limited !== true) {
            throw new MAS_Rate_Limit_Exception(
                sprintf(
                    __('Rate limit exceeded for user. Please try again in %d seconds.', 'modern-admin-styler-v2'),
                    $user_limited
                ),
                $user_limited
            );
        }
        
        if ($ip_limited !== true) {
            throw new MAS_Rate_Limit_Exception(
                sprintf(
                    __('Rate limit exceeded for IP address. Please try again in %d seconds.', 'modern-admin-styler-v2'),
                    $ip_limited
                ),
                $ip_limited
            );
        }
        
        // Increment counters
        $this->increment_counter('user', $user_id, $action, $limit_config['window']);
        $this->increment_counter('ip', $ip_address, $action, $limit_config['window']);
        
        return true;
    }
    
    /**
     * Get current rate limit status
     *
     * @param string $action The action to check
     * @param int|null $user_id Optional user ID
     * @param string|null $ip_address Optional IP address
     * @return array Status information
     */
    public function get_status($action = 'default', $user_id = null, $ip_address = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        if ($ip_address === null) {
            $ip_address = $this->get_client_ip();
        }
        
        $limit_config = $this->get_limit_config($action);
        
        // Get current counts
        $user_key = $this->get_cache_key('user', $user_id, $action);
        $ip_key = $this->get_cache_key('ip', $ip_address, $action);
        
        $user_count = (int) get_transient($user_key);
        $ip_count = (int) get_transient($ip_key);
        
        // Calculate remaining and reset time
        $user_remaining = max(0, $limit_config['requests'] - $user_count);
        $ip_remaining = max(0, $limit_config['requests'] - $ip_count);
        
        $user_ttl = $this->get_ttl($user_key);
        $ip_ttl = $this->get_ttl($ip_key);
        
        return [
            'action' => $action,
            'limit' => $limit_config['requests'],
            'window' => $limit_config['window'],
            'user' => [
                'used' => $user_count,
                'remaining' => $user_remaining,
                'reset_in' => $user_ttl,
            ],
            'ip' => [
                'used' => $ip_count,
                'remaining' => $ip_remaining,
                'reset_in' => $ip_ttl,
            ],
        ];
    }
    
    /**
     * Reset rate limit for a specific identifier
     *
     * @param string $type Type of limit ('user' or 'ip')
     * @param mixed $identifier User ID or IP address
     * @param string $action Action to reset
     * @return bool Success
     */
    public function reset_limit($type, $identifier, $action = 'default') {
        $key = $this->get_cache_key($type, $identifier, $action);
        return delete_transient($key);
    }
    
    /**
     * Check if a specific limit is exceeded
     *
     * @param string $type Type of limit ('user' or 'ip')
     * @param mixed $identifier User ID or IP address
     * @param string $action Action being limited
     * @param array $limit_config Limit configuration
     * @return bool|int True if within limit, seconds to wait if exceeded
     */
    private function check_limit($type, $identifier, $action, $limit_config) {
        $key = $this->get_cache_key($type, $identifier, $action);
        $count = (int) get_transient($key);
        
        if ($count >= $limit_config['requests']) {
            // Get time until reset
            $ttl = $this->get_ttl($key);
            return max(1, $ttl);
        }
        
        return true;
    }
    
    /**
     * Increment request counter
     *
     * @param string $type Type of limit ('user' or 'ip')
     * @param mixed $identifier User ID or IP address
     * @param string $action Action being counted
     * @param int $window Time window in seconds
     */
    private function increment_counter($type, $identifier, $action, $window) {
        $key = $this->get_cache_key($type, $identifier, $action);
        $count = (int) get_transient($key);
        
        if ($count === 0) {
            // First request in window
            set_transient($key, 1, $window);
        } else {
            // Increment existing count
            set_transient($key, $count + 1, $window);
        }
    }
    
    /**
     * Get cache key for rate limit tracking
     *
     * @param string $type Type of limit ('user' or 'ip')
     * @param mixed $identifier User ID or IP address
     * @param string $action Action being limited
     * @return string Cache key
     */
    private function get_cache_key($type, $identifier, $action) {
        return sprintf('mas_v2_rate_limit_%s_%s_%s', $type, $action, md5((string) $identifier));
    }
    
    /**
     * Get limit configuration for an action
     *
     * @param string $action Action name
     * @return array Limit configuration
     */
    private function get_limit_config($action) {
        if (isset($this->limits[$action])) {
            return $this->limits[$action];
        }
        
        return $this->limits['default'];
    }
    
    /**
     * Get client IP address
     *
     * @return string IP address
     */
    private function get_client_ip() {
        $ip = '';
        
        // Check for proxy headers
        $headers = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                
                // Handle comma-separated IPs (X-Forwarded-For)
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                
                break;
            }
        }
        
        // Validate IP
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
        
        return '0.0.0.0';
    }
    
    /**
     * Get time-to-live for a transient
     *
     * @param string $key Transient key
     * @return int Seconds until expiration
     */
    private function get_ttl($key) {
        global $wpdb;
        
        $timeout_key = '_transient_timeout_' . $key;
        $timeout = $wpdb->get_var($wpdb->prepare(
            "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
            $timeout_key
        ));
        
        if ($timeout) {
            return max(0, (int) $timeout - time());
        }
        
        return 0;
    }
}
