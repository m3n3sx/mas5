<?php
/**
 * Webhook Service Class
 *
 * Manages webhook registration, delivery, and retry mechanism for external integrations.
 *
 * @package    Modern_Admin_Styler_V2
 * @subpackage Services
 * @since      2.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MAS_Webhook_Service
 *
 * Handles webhook registration, delivery with HMAC signatures, and retry mechanism
 * with exponential backoff for failed deliveries.
 */
class MAS_Webhook_Service {

    /**
     * Database table names
     */
    private $webhooks_table;
    private $deliveries_table;

    /**
     * Maximum retry attempts for failed deliveries
     */
    const MAX_RETRY_ATTEMPTS = 5;

    /**
     * Base delay for exponential backoff (in seconds)
     */
    const BASE_RETRY_DELAY = 60;

    /**
     * Supported webhook events
     */
    const SUPPORTED_EVENTS = [
        'settings.updated',
        'theme.applied',
        'backup.created',
        'backup.restored',
    ];

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->webhooks_table = $wpdb->prefix . 'mas_v2_webhooks';
        $this->deliveries_table = $wpdb->prefix . 'mas_v2_webhook_deliveries';
    }

    /**
     * Create database tables for webhooks
     *
     * @return bool True on success, false on failure
     */
    public function create_tables() {
        global $wpdb;
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Webhooks table
        $webhooks_sql = "CREATE TABLE IF NOT EXISTS {$this->webhooks_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            url varchar(500) NOT NULL,
            events text NOT NULL,
            secret varchar(64) NOT NULL,
            active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY active (active)
        ) $charset_collate;";
        
        // Webhook deliveries table
        $deliveries_sql = "CREATE TABLE IF NOT EXISTS {$this->deliveries_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            webhook_id bigint(20) unsigned NOT NULL,
            event varchar(100) NOT NULL,
            payload longtext NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            response_code int(11) DEFAULT NULL,
            response_body text DEFAULT NULL,
            error_message text DEFAULT NULL,
            attempt_count int(11) NOT NULL DEFAULT 0,
            next_retry_at datetime DEFAULT NULL,
            delivered_at datetime DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY webhook_id (webhook_id),
            KEY status (status),
            KEY next_retry_at (next_retry_at)
        ) $charset_collate;";
        
        dbDelta($webhooks_sql);
        dbDelta($deliveries_sql);
        
        return true;
    }

    /**
     * Register a new webhook
     *
     * @param string $url Webhook URL
     * @param array $events Array of event names to subscribe to
     * @param string $secret Optional secret for HMAC signature (auto-generated if not provided)
     * @return array|WP_Error Webhook data on success, WP_Error on failure
     */
    public function register_webhook($url, $events, $secret = '') {
        global $wpdb;
        
        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return new WP_Error(
                'invalid_url',
                __('Invalid webhook URL provided.', 'modern-admin-styler-v2'),
                ['status' => 400]
            );
        }
        
        // Validate events
        if (empty($events) || !is_array($events)) {
            return new WP_Error(
                'invalid_events',
                __('Events must be a non-empty array.', 'modern-admin-styler-v2'),
                ['status' => 400]
            );
        }
        
        // Validate event names
        foreach ($events as $event) {
            if (!in_array($event, self::SUPPORTED_EVENTS)) {
                return new WP_Error(
                    'unsupported_event',
                    sprintf(__('Event "%s" is not supported.', 'modern-admin-styler-v2'), $event),
                    ['status' => 400]
                );
            }
        }
        
        // Generate secret if not provided
        if (empty($secret)) {
            $secret = bin2hex(random_bytes(32));
        }
        
        // Insert webhook
        $result = $wpdb->insert(
            $this->webhooks_table,
            [
                'url' => esc_url_raw($url),
                'events' => wp_json_encode($events),
                'secret' => sanitize_text_field($secret),
                'active' => 1,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ],
            ['%s', '%s', '%s', '%d', '%s', '%s']
        );
        
        if ($result === false) {
            return new WP_Error(
                'database_error',
                __('Failed to register webhook.', 'modern-admin-styler-v2'),
                ['status' => 500]
            );
        }
        
        $webhook_id = $wpdb->insert_id;
        
        return [
            'id' => $webhook_id,
            'url' => $url,
            'events' => $events,
            'secret' => $secret,
            'active' => true,
            'created_at' => current_time('mysql'),
        ];
    }

    /**
     * Trigger webhooks for a specific event
     *
     * @param string $event Event name
     * @param array $payload Event payload data
     * @return int Number of webhooks triggered
     */
    public function trigger_webhook($event, $payload) {
        global $wpdb;
        
        // Validate event
        if (!in_array($event, self::SUPPORTED_EVENTS)) {
            return 0;
        }
        
        // Find active webhooks subscribed to this event
        $webhooks = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->webhooks_table} WHERE active = 1 AND events LIKE %s",
            '%' . $wpdb->esc_like($event) . '%'
        ), ARRAY_A);
        
        if (empty($webhooks)) {
            return 0;
        }
        
        $triggered_count = 0;
        
        foreach ($webhooks as $webhook) {
            $events = json_decode($webhook['events'], true);
            
            // Double-check event is in the list
            if (!in_array($event, $events)) {
                continue;
            }
            
            // Create delivery record
            $delivery_id = $this->create_delivery_record($webhook['id'], $event, $payload);
            
            if ($delivery_id) {
                // Attempt immediate delivery
                $this->deliver_webhook($delivery_id);
                $triggered_count++;
            }
        }
        
        return $triggered_count;
    }

    /**
     * Create a delivery record
     *
     * @param int $webhook_id Webhook ID
     * @param string $event Event name
     * @param array $payload Event payload
     * @return int|false Delivery ID on success, false on failure
     */
    private function create_delivery_record($webhook_id, $event, $payload) {
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->deliveries_table,
            [
                'webhook_id' => $webhook_id,
                'event' => sanitize_text_field($event),
                'payload' => wp_json_encode($payload),
                'status' => 'pending',
                'attempt_count' => 0,
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%s', '%s', '%s', '%d', '%s']
        );
        
        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Deliver a webhook with HMAC signature
     *
     * @param int $delivery_id Delivery record ID
     * @return bool True on success, false on failure
     */
    public function deliver_webhook($delivery_id) {
        global $wpdb;
        
        // Get delivery record
        $delivery = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->deliveries_table} WHERE id = %d",
            $delivery_id
        ), ARRAY_A);
        
        if (!$delivery) {
            return false;
        }
        
        // Get webhook
        $webhook = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->webhooks_table} WHERE id = %d AND active = 1",
            $delivery['webhook_id']
        ), ARRAY_A);
        
        if (!$webhook) {
            // Mark as failed - webhook no longer exists or is inactive
            $this->update_delivery_status($delivery_id, 'failed', null, 'Webhook not found or inactive');
            return false;
        }
        
        // Increment attempt count
        $attempt_count = $delivery['attempt_count'] + 1;
        
        // Prepare payload
        $payload = $delivery['payload'];
        
        // Generate HMAC signature
        $signature = hash_hmac('sha256', $payload, $webhook['secret']);
        
        // Send HTTP request
        $response = wp_remote_post($webhook['url'], [
            'headers' => [
                'Content-Type' => 'application/json',
                'X-MAS-Signature' => $signature,
                'X-MAS-Event' => $delivery['event'],
                'X-MAS-Delivery-ID' => $delivery_id,
            ],
            'body' => $payload,
            'timeout' => 30,
            'sslverify' => true,
        ]);
        
        // Update attempt count
        $wpdb->update(
            $this->deliveries_table,
            ['attempt_count' => $attempt_count],
            ['id' => $delivery_id],
            ['%d'],
            ['%d']
        );
        
        // Handle response
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            $this->handle_delivery_failure($delivery_id, $attempt_count, null, $error_message);
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        // Success: 2xx status codes
        if ($response_code >= 200 && $response_code < 300) {
            $this->update_delivery_status($delivery_id, 'success', $response_code, null, $response_body);
            return true;
        }
        
        // Failure: other status codes
        $this->handle_delivery_failure($delivery_id, $attempt_count, $response_code, $response_body);
        return false;
    }

    /**
     * Handle delivery failure and schedule retry
     *
     * @param int $delivery_id Delivery ID
     * @param int $attempt_count Current attempt count
     * @param int|null $response_code HTTP response code
     * @param string $error_message Error message
     */
    private function handle_delivery_failure($delivery_id, $attempt_count, $response_code, $error_message) {
        global $wpdb;
        
        // Check if we should retry
        if ($attempt_count >= self::MAX_RETRY_ATTEMPTS) {
            // Max retries reached, mark as failed
            $this->update_delivery_status($delivery_id, 'failed', $response_code, $error_message);
            return;
        }
        
        // Calculate next retry time with exponential backoff
        $delay = self::BASE_RETRY_DELAY * pow(2, $attempt_count - 1);
        $next_retry_at = date('Y-m-d H:i:s', time() + $delay);
        
        // Update delivery record
        $wpdb->update(
            $this->deliveries_table,
            [
                'status' => 'pending',
                'response_code' => $response_code,
                'error_message' => sanitize_text_field($error_message),
                'next_retry_at' => $next_retry_at,
            ],
            ['id' => $delivery_id],
            ['%s', '%d', '%s', '%s'],
            ['%d']
        );
    }

    /**
     * Update delivery status
     *
     * @param int $delivery_id Delivery ID
     * @param string $status Status (success, failed, pending)
     * @param int|null $response_code HTTP response code
     * @param string|null $error_message Error message
     * @param string|null $response_body Response body
     */
    private function update_delivery_status($delivery_id, $status, $response_code = null, $error_message = null, $response_body = null) {
        global $wpdb;
        
        $data = [
            'status' => $status,
        ];
        
        if ($response_code !== null) {
            $data['response_code'] = $response_code;
        }
        
        if ($error_message !== null) {
            $data['error_message'] = sanitize_text_field($error_message);
        }
        
        if ($response_body !== null) {
            $data['response_body'] = substr(sanitize_text_field($response_body), 0, 1000); // Limit to 1000 chars
        }
        
        if ($status === 'success') {
            $data['delivered_at'] = current_time('mysql');
            $data['next_retry_at'] = null;
        }
        
        $wpdb->update(
            $this->deliveries_table,
            $data,
            ['id' => $delivery_id],
            array_fill(0, count($data), '%s'),
            ['%d']
        );
    }

    /**
     * Process pending webhook deliveries (for retry mechanism)
     *
     * Should be called by a cron job
     *
     * @return int Number of deliveries processed
     */
    public function process_pending_deliveries() {
        global $wpdb;
        
        // Find pending deliveries that are ready for retry
        $deliveries = $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM {$this->deliveries_table} 
            WHERE status = 'pending' 
            AND next_retry_at IS NOT NULL 
            AND next_retry_at <= %s 
            AND attempt_count < %d
            LIMIT 50",
            current_time('mysql'),
            self::MAX_RETRY_ATTEMPTS
        ), ARRAY_A);
        
        $processed = 0;
        
        foreach ($deliveries as $delivery) {
            $this->deliver_webhook($delivery['id']);
            $processed++;
        }
        
        return $processed;
    }

    /**
     * Get webhook by ID
     *
     * @param int $webhook_id Webhook ID
     * @return array|null Webhook data or null if not found
     */
    public function get_webhook($webhook_id) {
        global $wpdb;
        
        $webhook = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->webhooks_table} WHERE id = %d",
            $webhook_id
        ), ARRAY_A);
        
        if (!$webhook) {
            return null;
        }
        
        $webhook['events'] = json_decode($webhook['events'], true);
        $webhook['active'] = (bool) $webhook['active'];
        
        return $webhook;
    }

    /**
     * List all webhooks
     *
     * @param array $args Query arguments
     * @return array Array of webhooks
     */
    public function list_webhooks($args = []) {
        global $wpdb;
        
        $defaults = [
            'active' => null,
            'limit' => 50,
            'offset' => 0,
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $where = [];
        $where_values = [];
        
        if ($args['active'] !== null) {
            $where[] = 'active = %d';
            $where_values[] = $args['active'] ? 1 : 0;
        }
        
        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $query = "SELECT * FROM {$this->webhooks_table} {$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $where_values[] = $args['limit'];
        $where_values[] = $args['offset'];
        
        $webhooks = $wpdb->get_results($wpdb->prepare($query, $where_values), ARRAY_A);
        
        foreach ($webhooks as &$webhook) {
            $webhook['events'] = json_decode($webhook['events'], true);
            $webhook['active'] = (bool) $webhook['active'];
        }
        
        return $webhooks;
    }

    /**
     * Update webhook
     *
     * @param int $webhook_id Webhook ID
     * @param array $data Update data
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function update_webhook($webhook_id, $data) {
        global $wpdb;
        
        // Check if webhook exists
        $webhook = $this->get_webhook($webhook_id);
        if (!$webhook) {
            return new WP_Error(
                'webhook_not_found',
                __('Webhook not found.', 'modern-admin-styler-v2'),
                ['status' => 404]
            );
        }
        
        $update_data = [];
        
        if (isset($data['url'])) {
            if (!filter_var($data['url'], FILTER_VALIDATE_URL)) {
                return new WP_Error(
                    'invalid_url',
                    __('Invalid webhook URL provided.', 'modern-admin-styler-v2'),
                    ['status' => 400]
                );
            }
            $update_data['url'] = esc_url_raw($data['url']);
        }
        
        if (isset($data['events'])) {
            if (!is_array($data['events']) || empty($data['events'])) {
                return new WP_Error(
                    'invalid_events',
                    __('Events must be a non-empty array.', 'modern-admin-styler-v2'),
                    ['status' => 400]
                );
            }
            
            foreach ($data['events'] as $event) {
                if (!in_array($event, self::SUPPORTED_EVENTS)) {
                    return new WP_Error(
                        'unsupported_event',
                        sprintf(__('Event "%s" is not supported.', 'modern-admin-styler-v2'), $event),
                        ['status' => 400]
                    );
                }
            }
            
            $update_data['events'] = wp_json_encode($data['events']);
        }
        
        if (isset($data['active'])) {
            $update_data['active'] = $data['active'] ? 1 : 0;
        }
        
        if (empty($update_data)) {
            return true; // Nothing to update
        }
        
        $update_data['updated_at'] = current_time('mysql');
        
        $result = $wpdb->update(
            $this->webhooks_table,
            $update_data,
            ['id' => $webhook_id],
            array_fill(0, count($update_data), '%s'),
            ['%d']
        );
        
        return $result !== false;
    }

    /**
     * Delete webhook
     *
     * @param int $webhook_id Webhook ID
     * @return bool True on success, false on failure
     */
    public function delete_webhook($webhook_id) {
        global $wpdb;
        
        // Delete webhook
        $result = $wpdb->delete(
            $this->webhooks_table,
            ['id' => $webhook_id],
            ['%d']
        );
        
        if ($result) {
            // Delete associated deliveries
            $wpdb->delete(
                $this->deliveries_table,
                ['webhook_id' => $webhook_id],
                ['%d']
            );
        }
        
        return $result !== false;
    }

    /**
     * Get delivery history for a webhook
     *
     * @param int $webhook_id Webhook ID
     * @param array $args Query arguments
     * @return array Array of delivery records
     */
    public function get_delivery_history($webhook_id, $args = []) {
        global $wpdb;
        
        $defaults = [
            'status' => null,
            'limit' => 50,
            'offset' => 0,
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $where = ['webhook_id = %d'];
        $where_values = [$webhook_id];
        
        if ($args['status'] !== null) {
            $where[] = 'status = %s';
            $where_values[] = $args['status'];
        }
        
        $where_clause = 'WHERE ' . implode(' AND ', $where);
        
        $query = "SELECT * FROM {$this->deliveries_table} {$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $where_values[] = $args['limit'];
        $where_values[] = $args['offset'];
        
        $deliveries = $wpdb->get_results($wpdb->prepare($query, $where_values), ARRAY_A);
        
        foreach ($deliveries as &$delivery) {
            $delivery['payload'] = json_decode($delivery['payload'], true);
        }
        
        return $deliveries;
    }

    /**
     * Get supported events
     *
     * @return array Array of supported event names
     */
    public static function get_supported_events() {
        return self::SUPPORTED_EVENTS;
    }
}
