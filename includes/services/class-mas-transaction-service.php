<?php
/**
 * Transaction Service Class
 *
 * Provides transaction-like behavior for critical operations with rollback support.
 * Ensures atomic operations and maintains data consistency.
 *
 * @package    Modern_Admin_Styler_V2
 * @subpackage Services
 * @since      2.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MAS_Transaction_Service
 *
 * Manages transaction contexts for batch operations with automatic rollback on failure.
 */
class MAS_Transaction_Service {

    /**
     * Stack of active transactions
     *
     * @var array
     */
    private $transaction_stack = [];

    /**
     * Settings service instance
     *
     * @var MAS_Settings_Service
     */
    private $settings_service;

    /**
     * Security logger service instance
     *
     * @var MAS_Security_Logger_Service
     */
    private $security_logger;

    /**
     * Constructor
     *
     * @param MAS_Settings_Service        $settings_service Settings service instance
     * @param MAS_Security_Logger_Service $security_logger  Security logger instance
     */
    public function __construct($settings_service = null, $security_logger = null) {
        $this->settings_service = $settings_service ?: new MAS_Settings_Service();
        $this->security_logger = $security_logger ?: new MAS_Security_Logger_Service();
    }

    /**
     * Begin a new transaction
     *
     * Creates a transaction context with state backup for potential rollback.
     *
     * @param string|null $transaction_id Optional transaction ID
     * @return string Transaction ID
     */
    public function begin_transaction($transaction_id = null) {
        if (!$transaction_id) {
            $transaction_id = uniqid('txn_', true);
        }

        $this->transaction_stack[] = [
            'id' => $transaction_id,
            'started_at' => microtime(true),
            'operations' => [],
            'backup' => $this->create_state_backup()
        ];

        return $transaction_id;
    }

    /**
     * Add an operation to the current transaction
     *
     * @param string $operation_name Name/type of the operation
     * @param mixed  $operation_data Data associated with the operation
     * @throws Exception If no active transaction
     */
    public function add_operation($operation_name, $operation_data) {
        if (empty($this->transaction_stack)) {
            throw new Exception('No active transaction');
        }

        $current = &$this->transaction_stack[count($this->transaction_stack) - 1];
        $current['operations'][] = [
            'name' => $operation_name,
            'data' => $operation_data,
            'timestamp' => microtime(true)
        ];
    }

    /**
     * Commit the current transaction
     *
     * Finalizes the transaction and logs success.
     *
     * @return string Transaction ID
     * @throws Exception If no active transaction
     */
    public function commit() {
        if (empty($this->transaction_stack)) {
            throw new Exception('No active transaction');
        }

        $transaction = array_pop($this->transaction_stack);

        // Log successful transaction
        $this->security_logger->log_event('transaction_committed', [
            'transaction_id' => $transaction['id'],
            'operations_count' => count($transaction['operations']),
            'duration_ms' => round((microtime(true) - $transaction['started_at']) * 1000, 2)
        ]);

        return $transaction['id'];
    }

    /**
     * Rollback the current transaction
     *
     * Restores the previous state from backup and logs the rollback.
     *
     * @param string $reason Reason for rollback
     * @throws Exception If no active transaction
     */
    public function rollback($reason = '') {
        if (empty($this->transaction_stack)) {
            throw new Exception('No active transaction');
        }

        $transaction = array_pop($this->transaction_stack);

        // Restore state from backup
        $this->restore_state_backup($transaction['backup']);

        // Log rollback
        $this->security_logger->log_event('transaction_rolled_back', [
            'transaction_id' => $transaction['id'],
            'reason' => $reason,
            'operations_count' => count($transaction['operations']),
            'duration_ms' => round((microtime(true) - $transaction['started_at']) * 1000, 2)
        ]);
    }

    /**
     * Create a backup of the current state
     *
     * @return array State backup containing settings and metadata
     */
    public function create_state_backup() {
        return [
            'settings' => $this->settings_service->get_settings(),
            'timestamp' => time(),
            'user_id' => get_current_user_id()
        ];
    }

    /**
     * Restore state from a backup
     *
     * @param array $backup State backup to restore
     * @return bool True on success
     */
    public function restore_state_backup($backup) {
        if (!isset($backup['settings']) || !is_array($backup['settings'])) {
            return false;
        }

        // Save settings without triggering another backup
        return $this->settings_service->save_settings($backup['settings'], false);
    }

    /**
     * Check if there's an active transaction
     *
     * @return bool True if transaction is active
     */
    public function has_active_transaction() {
        return !empty($this->transaction_stack);
    }

    /**
     * Get the current transaction ID
     *
     * @return string|null Transaction ID or null if no active transaction
     */
    public function get_current_transaction_id() {
        if (empty($this->transaction_stack)) {
            return null;
        }

        $current = $this->transaction_stack[count($this->transaction_stack) - 1];
        return $current['id'];
    }

    /**
     * Get transaction details
     *
     * @return array|null Transaction details or null if no active transaction
     */
    public function get_transaction_details() {
        if (empty($this->transaction_stack)) {
            return null;
        }

        $current = $this->transaction_stack[count($this->transaction_stack) - 1];
        return [
            'id' => $current['id'],
            'started_at' => $current['started_at'],
            'operations_count' => count($current['operations']),
            'duration_ms' => round((microtime(true) - $current['started_at']) * 1000, 2)
        ];
    }
}
