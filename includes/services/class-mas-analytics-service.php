<?php
/**
 * Analytics Service Class
 *
 * Handles API usage analytics, performance metrics tracking, and error analysis.
 *
 * @package    Modern_Admin_Styler_V2
 * @subpackage Services
 * @since      2.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MAS_Analytics_Service
 *
 * Provides comprehensive analytics and monitoring capabilities for the REST API.
 */
class MAS_Analytics_Service {

    /**
     * Database table name for metrics
     *
     * @var string
     */
    private $metrics_table = 'mas_v2_metrics';

    /**
     * Constructor
     */
    public function __construct() {
        $this->maybe_create_table();
    }

    /**
     * Create metrics table if it doesn't exist
     *
     * @return void
     */
    private function maybe_create_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . $this->metrics_table;
        $charset_collate = $wpdb->get_charset_collate();

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
            return;
        }

        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            endpoint varchar(255) NOT NULL,
            method varchar(10) NOT NULL,
            response_time int(11) NOT NULL,
            status_code int(11) NOT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            timestamp datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY endpoint (endpoint),
            KEY status_code (status_code),
            KEY timestamp (timestamp),
            KEY user_id (user_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Track an API call
     *
     * @param string $endpoint      The endpoint path
     * @param string $method        HTTP method (GET, POST, etc.)
     * @param int    $response_time Response time in milliseconds
     * @param int    $status_code   HTTP status code
     * @return bool Success status
     */
    public function track_api_call($endpoint, $method, $response_time, $status_code) {
        global $wpdb;

        $result = $wpdb->insert(
            $wpdb->prefix . $this->metrics_table,
            [
                'endpoint' => sanitize_text_field($endpoint),
                'method' => strtoupper(sanitize_text_field($method)),
                'response_time' => absint($response_time),
                'status_code' => absint($status_code),
                'user_id' => get_current_user_id() ?: null,
                'timestamp' => current_time('mysql')
            ],
            ['%s', '%s', '%d', '%d', '%d', '%s']
        );

        return $result !== false;
    }

    /**
     * Get usage statistics
     *
     * @param string|null $start_date Start date (Y-m-d H:i:s format)
     * @param string|null $end_date   End date (Y-m-d H:i:s format)
     * @return array Usage statistics
     */
    public function get_usage_stats($start_date = null, $end_date = null) {
        global $wpdb;

        $table_name = $wpdb->prefix . $this->metrics_table;

        // Default to last 7 days if no dates provided
        if (!$start_date) {
            $start_date = date('Y-m-d H:i:s', strtotime('-7 days'));
        }
        if (!$end_date) {
            $end_date = current_time('mysql');
        }

        // Total requests
        $total_requests = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE timestamp BETWEEN %s AND %s",
            $start_date,
            $end_date
        ));

        // Requests by endpoint
        $by_endpoint = $wpdb->get_results($wpdb->prepare(
            "SELECT endpoint, COUNT(*) as count 
             FROM $table_name 
             WHERE timestamp BETWEEN %s AND %s 
             GROUP BY endpoint 
             ORDER BY count DESC 
             LIMIT 10",
            $start_date,
            $end_date
        ), ARRAY_A);

        // Requests by method
        $by_method = $wpdb->get_results($wpdb->prepare(
            "SELECT method, COUNT(*) as count 
             FROM $table_name 
             WHERE timestamp BETWEEN %s AND %s 
             GROUP BY method",
            $start_date,
            $end_date
        ), ARRAY_A);

        // Requests by status code
        $by_status = $wpdb->get_results($wpdb->prepare(
            "SELECT status_code, COUNT(*) as count 
             FROM $table_name 
             WHERE timestamp BETWEEN %s AND %s 
             GROUP BY status_code 
             ORDER BY count DESC",
            $start_date,
            $end_date
        ), ARRAY_A);

        // Requests over time (daily)
        $over_time = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(timestamp) as date, COUNT(*) as count 
             FROM $table_name 
             WHERE timestamp BETWEEN %s AND %s 
             GROUP BY DATE(timestamp) 
             ORDER BY date ASC",
            $start_date,
            $end_date
        ), ARRAY_A);

        return [
            'total_requests' => (int) $total_requests,
            'date_range' => [
                'start' => $start_date,
                'end' => $end_date
            ],
            'by_endpoint' => $by_endpoint,
            'by_method' => $by_method,
            'by_status' => $by_status,
            'over_time' => $over_time
        ];
    }

    /**
     * Get performance percentiles
     *
     * @param string|null $start_date Start date (Y-m-d H:i:s format)
     * @param string|null $end_date   End date (Y-m-d H:i:s format)
     * @return array Performance percentiles (p50, p75, p90, p95, p99)
     */
    public function get_performance_percentiles($start_date = null, $end_date = null) {
        global $wpdb;

        $table_name = $wpdb->prefix . $this->metrics_table;

        // Default to last 7 days if no dates provided
        if (!$start_date) {
            $start_date = date('Y-m-d H:i:s', strtotime('-7 days'));
        }
        if (!$end_date) {
            $end_date = current_time('mysql');
        }

        // Get all response times
        $response_times = $wpdb->get_col($wpdb->prepare(
            "SELECT response_time FROM $table_name 
             WHERE timestamp BETWEEN %s AND %s 
             ORDER BY response_time ASC",
            $start_date,
            $end_date
        ));

        if (empty($response_times)) {
            return [
                'p50' => 0,
                'p75' => 0,
                'p90' => 0,
                'p95' => 0,
                'p99' => 0,
                'min' => 0,
                'max' => 0,
                'avg' => 0,
                'count' => 0
            ];
        }

        $count = count($response_times);

        return [
            'p50' => $this->calculate_percentile($response_times, 50),
            'p75' => $this->calculate_percentile($response_times, 75),
            'p90' => $this->calculate_percentile($response_times, 90),
            'p95' => $this->calculate_percentile($response_times, 95),
            'p99' => $this->calculate_percentile($response_times, 99),
            'min' => min($response_times),
            'max' => max($response_times),
            'avg' => round(array_sum($response_times) / $count, 2),
            'count' => $count
        ];
    }

    /**
     * Calculate percentile from sorted array
     *
     * @param array $sorted_values Sorted array of values
     * @param int   $percentile    Percentile to calculate (0-100)
     * @return float Percentile value
     */
    private function calculate_percentile($sorted_values, $percentile) {
        $count = count($sorted_values);
        $index = ($percentile / 100) * ($count - 1);
        
        $lower = floor($index);
        $upper = ceil($index);
        
        if ($lower === $upper) {
            return $sorted_values[$lower];
        }
        
        $weight = $index - $lower;
        return $sorted_values[$lower] * (1 - $weight) + $sorted_values[$upper] * $weight;
    }

    /**
     * Get error statistics
     *
     * @param string|null $start_date Start date (Y-m-d H:i:s format)
     * @param string|null $end_date   End date (Y-m-d H:i:s format)
     * @return array Error statistics
     */
    public function get_error_stats($start_date = null, $end_date = null) {
        global $wpdb;

        $table_name = $wpdb->prefix . $this->metrics_table;

        // Default to last 7 days if no dates provided
        if (!$start_date) {
            $start_date = date('Y-m-d H:i:s', strtotime('-7 days'));
        }
        if (!$end_date) {
            $end_date = current_time('mysql');
        }

        // Total requests
        $total_requests = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE timestamp BETWEEN %s AND %s",
            $start_date,
            $end_date
        ));

        // Error requests (4xx and 5xx)
        $error_requests = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name 
             WHERE timestamp BETWEEN %s AND %s 
             AND status_code >= 400",
            $start_date,
            $end_date
        ));

        // Client errors (4xx)
        $client_errors = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name 
             WHERE timestamp BETWEEN %s AND %s 
             AND status_code >= 400 AND status_code < 500",
            $start_date,
            $end_date
        ));

        // Server errors (5xx)
        $server_errors = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name 
             WHERE timestamp BETWEEN %s AND %s 
             AND status_code >= 500",
            $start_date,
            $end_date
        ));

        // Errors by endpoint
        $by_endpoint = $wpdb->get_results($wpdb->prepare(
            "SELECT endpoint, status_code, COUNT(*) as count 
             FROM $table_name 
             WHERE timestamp BETWEEN %s AND %s 
             AND status_code >= 400 
             GROUP BY endpoint, status_code 
             ORDER BY count DESC 
             LIMIT 10",
            $start_date,
            $end_date
        ), ARRAY_A);

        // Errors over time (daily)
        $over_time = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(timestamp) as date, 
                    COUNT(*) as total,
                    SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) as errors
             FROM $table_name 
             WHERE timestamp BETWEEN %s AND %s 
             GROUP BY DATE(timestamp) 
             ORDER BY date ASC",
            $start_date,
            $end_date
        ), ARRAY_A);

        $error_rate = $total_requests > 0 
            ? round(($error_requests / $total_requests) * 100, 2) 
            : 0;

        return [
            'total_requests' => (int) $total_requests,
            'error_requests' => (int) $error_requests,
            'error_rate' => $error_rate,
            'client_errors' => (int) $client_errors,
            'server_errors' => (int) $server_errors,
            'by_endpoint' => $by_endpoint,
            'over_time' => $over_time,
            'date_range' => [
                'start' => $start_date,
                'end' => $end_date
            ]
        ];
    }

    /**
     * Export analytics data as CSV
     *
     * @param string|null $start_date Start date (Y-m-d H:i:s format)
     * @param string|null $end_date   End date (Y-m-d H:i:s format)
     * @return string CSV content
     */
    public function export_to_csv($start_date = null, $end_date = null) {
        global $wpdb;

        $table_name = $wpdb->prefix . $this->metrics_table;

        // Default to last 7 days if no dates provided
        if (!$start_date) {
            $start_date = date('Y-m-d H:i:s', strtotime('-7 days'));
        }
        if (!$end_date) {
            $end_date = current_time('mysql');
        }

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT endpoint, method, response_time, status_code, user_id, timestamp 
             FROM $table_name 
             WHERE timestamp BETWEEN %s AND %s 
             ORDER BY timestamp DESC",
            $start_date,
            $end_date
        ), ARRAY_A);

        // Create CSV content
        $csv = "Endpoint,Method,Response Time (ms),Status Code,User ID,Timestamp\n";
        
        foreach ($results as $row) {
            $csv .= sprintf(
                '"%s","%s",%d,%d,%s,"%s"' . "\n",
                $row['endpoint'],
                $row['method'],
                $row['response_time'],
                $row['status_code'],
                $row['user_id'] ?: 'N/A',
                $row['timestamp']
            );
        }

        return $csv;
    }

    /**
     * Clean up old metrics data
     *
     * @param int $days Number of days to keep (default: 30)
     * @return int Number of rows deleted
     */
    public function cleanup_old_metrics($days = 30) {
        global $wpdb;

        $table_name = $wpdb->prefix . $this->metrics_table;
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name WHERE timestamp < %s",
            $cutoff_date
        ));

        return $deleted !== false ? $deleted : 0;
    }

    /**
     * Check performance thresholds and trigger alerts
     *
     * @param array $thresholds Performance thresholds to check
     * @return array Alert information
     */
    public function check_performance_thresholds($thresholds = []) {
        // Default thresholds
        $defaults = [
            'avg_response_time' => 500,  // 500ms average
            'p95_response_time' => 1000, // 1000ms p95
            'error_rate' => 5.0,         // 5% error rate
            'time_window' => 3600        // Check last hour
        ];

        $thresholds = array_merge($defaults, $thresholds);

        $alerts = [];
        $start_date = date('Y-m-d H:i:s', time() - $thresholds['time_window']);
        $end_date = current_time('mysql');

        // Check average response time
        $percentiles = $this->get_performance_percentiles($start_date, $end_date);
        if ($percentiles['avg'] > $thresholds['avg_response_time']) {
            $alerts[] = [
                'type' => 'performance',
                'severity' => 'warning',
                'metric' => 'avg_response_time',
                'current_value' => $percentiles['avg'],
                'threshold' => $thresholds['avg_response_time'],
                'message' => sprintf(
                    'Average response time (%.2fms) exceeds threshold (%.2fms)',
                    $percentiles['avg'],
                    $thresholds['avg_response_time']
                ),
                'timestamp' => current_time('mysql')
            ];
        }

        // Check p95 response time
        if ($percentiles['p95'] > $thresholds['p95_response_time']) {
            $alerts[] = [
                'type' => 'performance',
                'severity' => 'warning',
                'metric' => 'p95_response_time',
                'current_value' => $percentiles['p95'],
                'threshold' => $thresholds['p95_response_time'],
                'message' => sprintf(
                    'P95 response time (%.2fms) exceeds threshold (%.2fms)',
                    $percentiles['p95'],
                    $thresholds['p95_response_time']
                ),
                'timestamp' => current_time('mysql')
            ];
        }

        // Check error rate
        $error_stats = $this->get_error_stats($start_date, $end_date);
        if ($error_stats['error_rate'] > $thresholds['error_rate']) {
            $alerts[] = [
                'type' => 'errors',
                'severity' => 'critical',
                'metric' => 'error_rate',
                'current_value' => $error_stats['error_rate'],
                'threshold' => $thresholds['error_rate'],
                'message' => sprintf(
                    'Error rate (%.2f%%) exceeds threshold (%.2f%%)',
                    $error_stats['error_rate'],
                    $thresholds['error_rate']
                ),
                'timestamp' => current_time('mysql')
            ];
        }

        // Log alerts if any
        if (!empty($alerts)) {
            $this->log_performance_alerts($alerts);
        }

        return $alerts;
    }

    /**
     * Log performance alerts
     *
     * @param array $alerts Array of alert information
     * @return void
     */
    private function log_performance_alerts($alerts) {
        foreach ($alerts as $alert) {
            // Log to WordPress error log if debug mode is enabled
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf(
                    'MAS Performance Alert [%s]: %s',
                    strtoupper($alert['severity']),
                    $alert['message']
                ));
            }

            // Store alert in transient for admin notice
            $alert_key = 'mas_performance_alert_' . $alert['metric'];
            set_transient($alert_key, $alert, 3600); // Store for 1 hour
        }

        // Trigger action hook for custom alert handling
        do_action('mas_performance_alerts', $alerts);
    }

    /**
     * Generate optimization recommendations based on metrics
     *
     * @param string|null $start_date Start date (Y-m-d H:i:s format)
     * @param string|null $end_date   End date (Y-m-d H:i:s format)
     * @return array Optimization recommendations
     */
    public function generate_optimization_recommendations($start_date = null, $end_date = null) {
        $recommendations = [];

        // Default to last 24 hours if no dates provided
        if (!$start_date) {
            $start_date = date('Y-m-d H:i:s', strtotime('-24 hours'));
        }
        if (!$end_date) {
            $end_date = current_time('mysql');
        }

        // Get performance metrics
        $percentiles = $this->get_performance_percentiles($start_date, $end_date);
        $error_stats = $this->get_error_stats($start_date, $end_date);
        $usage_stats = $this->get_usage_stats($start_date, $end_date);

        // Check slow endpoints
        if ($percentiles['p95'] > 1000) {
            $recommendations[] = [
                'category' => 'performance',
                'priority' => 'high',
                'title' => 'Slow API Response Times',
                'description' => 'P95 response time exceeds 1 second. Consider implementing caching or optimizing database queries.',
                'actions' => [
                    'Enable object caching',
                    'Review and optimize database queries',
                    'Implement response caching with ETags'
                ]
            ];
        }

        // Check high error rate
        if ($error_stats['error_rate'] > 5.0) {
            $recommendations[] = [
                'category' => 'reliability',
                'priority' => 'critical',
                'title' => 'High Error Rate',
                'description' => sprintf('Error rate is %.2f%%. Review error logs and fix failing endpoints.', $error_stats['error_rate']),
                'actions' => [
                    'Review error logs for common issues',
                    'Add input validation',
                    'Implement better error handling'
                ]
            ];
        }

        // Check for frequently used endpoints
        if (!empty($usage_stats['by_endpoint'])) {
            $top_endpoint = $usage_stats['by_endpoint'][0];
            if ($top_endpoint['count'] > 1000) {
                $recommendations[] = [
                    'category' => 'optimization',
                    'priority' => 'medium',
                    'title' => 'High Traffic Endpoint',
                    'description' => sprintf('Endpoint %s has %d requests. Consider caching or rate limiting.', $top_endpoint['endpoint'], $top_endpoint['count']),
                    'actions' => [
                        'Implement response caching',
                        'Add rate limiting',
                        'Optimize endpoint logic'
                    ]
                ];
            }
        }

        // Check for slow queries
        if ($percentiles['max'] > 5000) {
            $recommendations[] = [
                'category' => 'performance',
                'priority' => 'high',
                'title' => 'Very Slow Requests Detected',
                'description' => sprintf('Maximum response time is %.2fms. Investigate and optimize slow operations.', $percentiles['max']),
                'actions' => [
                    'Profile slow endpoints',
                    'Add database indexes',
                    'Implement query caching'
                ]
            ];
        }

        return $recommendations;
    }

    /**
     * Get active performance alerts
     *
     * @return array Active alerts
     */
    public function get_active_alerts() {
        $alerts = [];
        $metrics = ['avg_response_time', 'p95_response_time', 'error_rate'];

        foreach ($metrics as $metric) {
            $alert_key = 'mas_performance_alert_' . $metric;
            $alert = get_transient($alert_key);
            
            if ($alert !== false) {
                $alerts[] = $alert;
            }
        }

        return $alerts;
    }
}
