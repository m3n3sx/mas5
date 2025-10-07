<?php
/**
 * Request Deduplication Service for Modern Admin Styler V2
 * 
 * Prevents duplicate requests and ensures consistent results between REST API and AJAX.
 *
 * @package ModernAdminStylerV2
 * @since 2.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class MAS_Request_Deduplication_Service {
    
    /**
     * Cache prefix for request results
     */
    const CACHE_PREFIX = 'mas_v2_request_';
    
    /**
     * Default cache timeout (seconds)
     */
    const DEFAULT_CACHE_TIMEOUT = 60;
    
    /**
     * Feature flags service
     */
    private $feature_flags_service;
    
    /**
     * Operation lock service
     */
    private $operation_lock_service;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->feature_flags_service = new MAS_Feature_Flags_Service();
        $this->operation_lock_service = new MAS_Operation_Lock_Service();
    }
    
    /**
     * Execute request with deduplication
     *
     * @param string $request_type
     * @param array $request_data
     * @param callable $callback
     * @param int $cache_timeout
     * @return mixed
     */
    public function execute_deduplicated_request($request_type, $request_data, $callback, $cache_timeout = self::DEFAULT_CACHE_TIMEOUT) {
        // Generate request fingerprint
        $fingerprint = $this->generate_request_fingerprint($request_type, $request_data);
        
        // Check if we have a cached result
        $cached_result = $this->get_cached_result($fingerprint);
        if ($cached_result !== false) {
            $this->log_cache_hit($request_type, $fingerprint);
            return $cached_result;
        }
        
        // Check if operation is already in progress
        if ($this->operation_lock_service->is_locked($request_type, $request_data)) {
            // Wait for operation to complete and return cached result
            return $this->wait_for_operation_result($fingerprint, $request_type);
        }
        
        // Execute request with lock
        try {
            $result = $this->operation_lock_service->execute_with_lock(
                $request_type,
                $callback,
                $request_data
            );
            
            // Cache the result
            $this->cache_result($fingerprint, $result, $cache_timeout);
            
            $this->log_request_executed($request_type, $fingerprint);
            
            return $result;
            
        } catch (Exception $e) {
            $this->log_request_error($request_type, $fingerprint, $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Generate request fingerprint
     *
     * @param string $request_type
     * @param array $request_data
     * @return string
     */
    private function generate_request_fingerprint($request_type, $request_data) {
        // Normalize request data for consistent fingerprinting
        $normalized_data = $this->normalize_request_data($request_data);
        
        $fingerprint_data = [
            'type' => $request_type,
            'data' => $normalized_data,
            'user_id' => get_current_user_id(),
            'site_url' => get_site_url()
        ];
        
        return md5(serialize($fingerprint_data));
    }
    
    /**
     * Normalize request data for consistent fingerprinting
     *
     * @param array $data
     * @return array
     */
    private function normalize_request_data($data) {
        if (!is_array($data)) {
            return $data;
        }
        
        // Remove non-essential fields that might vary between requests
        $exclude_fields = ['_wpnonce', '_wp_http_referer', 'timestamp', 'request_id'];
        
        foreach ($exclude_fields as $field) {
            unset($data[$field]);
        }
        
        // Sort array keys for consistent ordering
        ksort($data);
        
        // Recursively normalize nested arrays
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->normalize_request_data($value);
            }
        }
        
        return $data;
    }
    
    /**
     * Get cached result
     *
     * @param string $fingerprint
     * @return mixed|false
     */
    private function get_cached_result($fingerprint) {
        $cache_key = self::CACHE_PREFIX . $fingerprint;
        return get_transient($cache_key);
    }
    
    /**
     * Cache result
     *
     * @param string $fingerprint
     * @param mixed $result
     * @param int $timeout
     */
    private function cache_result($fingerprint, $result, $timeout) {
        $cache_key = self::CACHE_PREFIX . $fingerprint;
        set_transient($cache_key, $result, $timeout);
    }
    
    /**
     * Wait for operation result
     *
     * @param string $fingerprint
     * @param string $request_type
     * @param int $max_wait_time
     * @return mixed
     * @throws Exception
     */
    private function wait_for_operation_result($fingerprint, $request_type, $max_wait_time = 30) {
        $start_time = time();
        $wait_interval = 0.5; // 500ms
        
        while ((time() - $start_time) < $max_wait_time) {
            // Check if result is now available
            $result = $this->get_cached_result($fingerprint);
            if ($result !== false) {
                $this->log_wait_success($request_type, $fingerprint, time() - $start_time);
                return $result;
            }
            
            // Wait before checking again
            usleep($wait_interval * 1000000);
        }
        
        $this->log_wait_timeout($request_type, $fingerprint);
        throw new Exception(
            sprintf(__('Timeout waiting for operation "%s" to complete', 'modern-admin-styler-v2'), $request_type)
        );
    }
    
    /**
     * Compare results from different sources
     *
     * @param mixed $rest_result
     * @param mixed $ajax_result
     * @param string $request_type
     * @return bool
     */
    public function compare_results($rest_result, $ajax_result, $request_type) {
        // Normalize results for comparison
        $normalized_rest = $this->normalize_result($rest_result);
        $normalized_ajax = $this->normalize_result($ajax_result);
        
        $are_identical = $this->deep_compare($normalized_rest, $normalized_ajax);
        
        if (!$are_identical) {
            $this->log_result_mismatch($request_type, $normalized_rest, $normalized_ajax);
        }
        
        return $are_identical;
    }
    
    /**
     * Normalize result for comparison
     *
     * @param mixed $result
     * @return mixed
     */
    private function normalize_result($result) {
        if (is_array($result)) {
            // Remove timestamps and other variable fields
            $exclude_fields = ['timestamp', 'request_time', '_timestamp', 'generated_at'];
            
            foreach ($exclude_fields as $field) {
                unset($result[$field]);
            }
            
            // Sort arrays for consistent comparison
            ksort($result);
            
            // Recursively normalize nested arrays
            foreach ($result as $key => $value) {
                $result[$key] = $this->normalize_result($value);
            }
        }
        
        return $result;
    }
    
    /**
     * Deep compare two values
     *
     * @param mixed $a
     * @param mixed $b
     * @return bool
     */
    private function deep_compare($a, $b) {
        if (gettype($a) !== gettype($b)) {
            return false;
        }
        
        if (is_array($a)) {
            if (count($a) !== count($b)) {
                return false;
            }
            
            foreach ($a as $key => $value) {
                if (!array_key_exists($key, $b) || !$this->deep_compare($value, $b[$key])) {
                    return false;
                }
            }
            
            return true;
        }
        
        return $a === $b;
    }
    
    /**
     * Clear cached results
     *
     * @param string $request_type Optional - clear only specific type
     * @return int Number of entries cleared
     */
    public function clear_cache($request_type = null) {
        global $wpdb;
        
        if ($request_type) {
            // Clear specific request type (approximate - we can't easily filter by type)
            $pattern = '_transient_' . self::CACHE_PREFIX . '%';
        } else {
            $pattern = '_transient_' . self::CACHE_PREFIX . '%';
        }
        
        $count = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            $pattern,
            '_transient_timeout_' . self::CACHE_PREFIX . '%'
        ));
        
        $this->log_cache_cleared($count, $request_type);
        
        return $count;
    }
    
    /**
     * Get deduplication statistics
     *
     * @return array
     */
    public function get_stats() {
        global $wpdb;
        
        $cache_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s",
            '_transient_' . self::CACHE_PREFIX . '%'
        ));
        
        return [
            'cached_requests' => intval($cache_count),
            'dual_mode_enabled' => $this->feature_flags_service->is_dual_mode_enabled(),
            'cache_prefix' => self::CACHE_PREFIX,
            'default_timeout' => self::DEFAULT_CACHE_TIMEOUT
        ];
    }
    
    /**
     * Log cache hit
     *
     * @param string $request_type
     * @param string $fingerprint
     */
    private function log_cache_hit($request_type, $fingerprint) {
        if (!$this->feature_flags_service->is_debug_mode()) {
            return;
        }
        
        error_log(sprintf(
            '[MAS Request Dedup] CACHE HIT: %s | Fingerprint: %s | User: %d',
            $request_type,
            $fingerprint,
            get_current_user_id()
        ));
    }
    
    /**
     * Log request executed
     *
     * @param string $request_type
     * @param string $fingerprint
     */
    private function log_request_executed($request_type, $fingerprint) {
        if (!$this->feature_flags_service->is_debug_mode()) {
            return;
        }
        
        error_log(sprintf(
            '[MAS Request Dedup] EXECUTED: %s | Fingerprint: %s | User: %d',
            $request_type,
            $fingerprint,
            get_current_user_id()
        ));
    }
    
    /**
     * Log request error
     *
     * @param string $request_type
     * @param string $fingerprint
     * @param string $error_message
     */
    private function log_request_error($request_type, $fingerprint, $error_message) {
        error_log(sprintf(
            '[MAS Request Dedup] ERROR: %s | Fingerprint: %s | Error: %s | User: %d',
            $request_type,
            $fingerprint,
            $error_message,
            get_current_user_id()
        ));
    }
    
    /**
     * Log wait success
     *
     * @param string $request_type
     * @param string $fingerprint
     * @param int $wait_time
     */
    private function log_wait_success($request_type, $fingerprint, $wait_time) {
        if (!$this->feature_flags_service->is_debug_mode()) {
            return;
        }
        
        error_log(sprintf(
            '[MAS Request Dedup] WAIT SUCCESS: %s | Fingerprint: %s | Wait time: %ds | User: %d',
            $request_type,
            $fingerprint,
            $wait_time,
            get_current_user_id()
        ));
    }
    
    /**
     * Log wait timeout
     *
     * @param string $request_type
     * @param string $fingerprint
     */
    private function log_wait_timeout($request_type, $fingerprint) {
        error_log(sprintf(
            '[MAS Request Dedup] WAIT TIMEOUT: %s | Fingerprint: %s | User: %d',
            $request_type,
            $fingerprint,
            get_current_user_id()
        ));
    }
    
    /**
     * Log result mismatch
     *
     * @param string $request_type
     * @param mixed $rest_result
     * @param mixed $ajax_result
     */
    private function log_result_mismatch($request_type, $rest_result, $ajax_result) {
        error_log(sprintf(
            '[MAS Request Dedup] RESULT MISMATCH: %s | REST: %s | AJAX: %s | User: %d',
            $request_type,
            wp_json_encode($rest_result),
            wp_json_encode($ajax_result),
            get_current_user_id()
        ));
    }
    
    /**
     * Log cache cleared
     *
     * @param int $count
     * @param string $request_type
     */
    private function log_cache_cleared($count, $request_type = null) {
        error_log(sprintf(
            '[MAS Request Dedup] CACHE CLEARED: %d entries | Type: %s | User: %d',
            $count,
            $request_type ?: 'all',
            get_current_user_id()
        ));
    }
}