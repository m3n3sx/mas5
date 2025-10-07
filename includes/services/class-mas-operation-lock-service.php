<?php
/**
 * Operation Lock Service for Modern Admin Styler V2
 * 
 * Prevents duplicate operations during dual-mode operation.
 *
 * @package ModernAdminStylerV2
 * @since 2.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class MAS_Operation_Lock_Service {
    
    /**
     * Lock prefix for transients
     */
    const LOCK_PREFIX = 'mas_v2_operation_lock_';
    
    /**
     * Default lock timeout (seconds)
     */
    const DEFAULT_TIMEOUT = 30;
    
    /**
     * Feature flags service
     */
    private $feature_flags_service;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->feature_flags_service = new MAS_Feature_Flags_Service();
    }
    
    /**
     * Acquire operation lock
     *
     * @param string $operation_type
     * @param array $operation_data
     * @param int $timeout
     * @return bool|string Returns lock key on success, false on failure
     */
    public function acquire_lock($operation_type, $operation_data = [], $timeout = self::DEFAULT_TIMEOUT) {
        if (!$this->feature_flags_service->is_dual_mode_enabled()) {
            return true; // No locking needed in single mode
        }
        
        $lock_key = $this->generate_lock_key($operation_type, $operation_data);
        $lock_value = $this->generate_lock_value();
        
        // Try to acquire lock
        $acquired = add_transient($lock_key, $lock_value, $timeout);
        
        if ($acquired) {
            $this->log_lock_acquired($operation_type, $lock_key, $operation_data);
            return $lock_key;
        }
        
        // Check if we already own this lock
        $existing_lock = get_transient($lock_key);
        if ($existing_lock === $lock_value) {
            return $lock_key; // We already own this lock
        }
        
        $this->log_lock_failed($operation_type, $lock_key, $operation_data);
        return false;
    }
    
    /**
     * Release operation lock
     *
     * @param string $lock_key
     * @return bool
     */
    public function release_lock($lock_key) {
        if ($lock_key === true) {
            return true; // No actual lock was acquired
        }
        
        $result = delete_transient($lock_key);
        
        if ($result) {
            $this->log_lock_released($lock_key);
        }
        
        return $result;
    }
    
    /**
     * Check if operation is locked
     *
     * @param string $operation_type
     * @param array $operation_data
     * @return bool
     */
    public function is_locked($operation_type, $operation_data = []) {
        if (!$this->feature_flags_service->is_dual_mode_enabled()) {
            return false;
        }
        
        $lock_key = $this->generate_lock_key($operation_type, $operation_data);
        return get_transient($lock_key) !== false;
    }
    
    /**
     * Execute operation with lock
     *
     * @param string $operation_type
     * @param callable $callback
     * @param array $operation_data
     * @param int $timeout
     * @return mixed
     * @throws Exception
     */
    public function execute_with_lock($operation_type, $callback, $operation_data = [], $timeout = self::DEFAULT_TIMEOUT) {
        $lock_key = $this->acquire_lock($operation_type, $operation_data, $timeout);
        
        if (!$lock_key) {
            throw new Exception(
                sprintf(__('Operation "%s" is already in progress', 'modern-admin-styler-v2'), $operation_type)
            );
        }
        
        try {
            $result = call_user_func($callback);
            return $result;
        } finally {
            $this->release_lock($lock_key);
        }
    }
    
    /**
     * Generate lock key for operation
     *
     * @param string $operation_type
     * @param array $operation_data
     * @return string
     */
    private function generate_lock_key($operation_type, $operation_data) {
        $user_id = get_current_user_id();
        $data_hash = md5(serialize($operation_data));
        
        return self::LOCK_PREFIX . $operation_type . '_' . $user_id . '_' . $data_hash;
    }
    
    /**
     * Generate unique lock value
     *
     * @return string
     */
    private function generate_lock_value() {
        return wp_generate_uuid4() . '_' . time();
    }
    
    /**
     * Get operation fingerprint for deduplication
     *
     * @param string $operation_type
     * @param array $operation_data
     * @return string
     */
    public function get_operation_fingerprint($operation_type, $operation_data) {
        $fingerprint_data = [
            'operation' => $operation_type,
            'user_id' => get_current_user_id(),
            'data' => $operation_data,
            'timestamp' => floor(time() / 10) // 10-second window
        ];
        
        return md5(serialize($fingerprint_data));
    }
    
    /**
     * Check if operation was recently executed
     *
     * @param string $operation_type
     * @param array $operation_data
     * @param int $window_seconds
     * @return bool
     */
    public function was_recently_executed($operation_type, $operation_data, $window_seconds = 10) {
        $fingerprint = $this->get_operation_fingerprint($operation_type, $operation_data);
        $recent_key = 'mas_v2_recent_op_' . $fingerprint;
        
        return get_transient($recent_key) !== false;
    }
    
    /**
     * Mark operation as recently executed
     *
     * @param string $operation_type
     * @param array $operation_data
     * @param int $window_seconds
     */
    public function mark_as_executed($operation_type, $operation_data, $window_seconds = 10) {
        $fingerprint = $this->get_operation_fingerprint($operation_type, $operation_data);
        $recent_key = 'mas_v2_recent_op_' . $fingerprint;
        
        set_transient($recent_key, time(), $window_seconds);
    }
    
    /**
     * Get all active locks (for debugging)
     *
     * @return array
     */
    public function get_active_locks() {
        global $wpdb;
        
        $locks = [];
        $prefix = '_transient_' . self::LOCK_PREFIX;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
            $prefix . '%'
        ));
        
        foreach ($results as $result) {
            $lock_key = str_replace('_transient_', '', $result->option_name);
            $locks[$lock_key] = [
                'value' => $result->option_value,
                'expires' => $this->get_lock_expiry($lock_key)
            ];
        }
        
        return $locks;
    }
    
    /**
     * Clear all locks (emergency cleanup)
     *
     * @return int Number of locks cleared
     */
    public function clear_all_locks() {
        global $wpdb;
        
        $prefix = '_transient_' . self::LOCK_PREFIX;
        
        $count = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            $prefix . '%',
            '_transient_timeout_' . self::LOCK_PREFIX . '%'
        ));
        
        $this->log_locks_cleared($count);
        
        return $count;
    }
    
    /**
     * Get lock expiry time
     *
     * @param string $lock_key
     * @return int|null
     */
    private function get_lock_expiry($lock_key) {
        return get_transient('timeout_' . $lock_key);
    }
    
    /**
     * Log lock acquired
     *
     * @param string $operation_type
     * @param string $lock_key
     * @param array $operation_data
     */
    private function log_lock_acquired($operation_type, $lock_key, $operation_data) {
        if (!$this->feature_flags_service->is_debug_mode()) {
            return;
        }
        
        error_log(sprintf(
            '[MAS Operation Lock] ACQUIRED: %s | Key: %s | User: %d | Data: %s',
            $operation_type,
            $lock_key,
            get_current_user_id(),
            wp_json_encode($operation_data)
        ));
    }
    
    /**
     * Log lock acquisition failed
     *
     * @param string $operation_type
     * @param string $lock_key
     * @param array $operation_data
     */
    private function log_lock_failed($operation_type, $lock_key, $operation_data) {
        if (!$this->feature_flags_service->is_debug_mode()) {
            return;
        }
        
        error_log(sprintf(
            '[MAS Operation Lock] FAILED: %s | Key: %s | User: %d | Data: %s',
            $operation_type,
            $lock_key,
            get_current_user_id(),
            wp_json_encode($operation_data)
        ));
    }
    
    /**
     * Log lock released
     *
     * @param string $lock_key
     */
    private function log_lock_released($lock_key) {
        if (!$this->feature_flags_service->is_debug_mode()) {
            return;
        }
        
        error_log(sprintf(
            '[MAS Operation Lock] RELEASED: %s | User: %d',
            $lock_key,
            get_current_user_id()
        ));
    }
    
    /**
     * Log locks cleared
     *
     * @param int $count
     */
    private function log_locks_cleared($count) {
        error_log(sprintf(
            '[MAS Operation Lock] CLEARED ALL: %d locks removed | User: %d',
            $count,
            get_current_user_id()
        ));
    }
    
    /**
     * Get lock statistics
     *
     * @return array
     */
    public function get_lock_stats() {
        $active_locks = $this->get_active_locks();
        
        return [
            'active_locks_count' => count($active_locks),
            'active_locks' => $active_locks,
            'dual_mode_enabled' => $this->feature_flags_service->is_dual_mode_enabled(),
            'debug_mode' => $this->feature_flags_service->is_debug_mode()
        ];
    }
}