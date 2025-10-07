<?php
/**
 * Webhooks REST Controller
 *
 * Handles webhook registration, management, and delivery history endpoints.
 *
 * @package    Modern_Admin_Styler_V2
 * @subpackage API
 * @since      2.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MAS_Webhooks_Controller
 *
 * REST API controller for webhook management endpoints.
 */
class MAS_Webhooks_Controller extends MAS_REST_Controller {

    /**
     * Webhook service instance
     *
     * @var MAS_Webhook_Service
     */
    private $webhook_service;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->webhook_service = new MAS_Webhook_Service();
    }

    /**
     * Register routes for webhook endpoints
     */
    public function register_routes() {
        // GET /webhooks - List all webhooks
        register_rest_route($this->namespace, '/webhooks', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'list_webhooks'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'active' => [
                    'description' => __('Filter by active status', 'modern-admin-styler-v2'),
                    'type' => 'boolean',
                    'required' => false,
                ],
                'limit' => [
                    'description' => __('Number of webhooks to return', 'modern-admin-styler-v2'),
                    'type' => 'integer',
                    'default' => 50,
                    'minimum' => 1,
                    'maximum' => 100,
                ],
                'offset' => [
                    'description' => __('Offset for pagination', 'modern-admin-styler-v2'),
                    'type' => 'integer',
                    'default' => 0,
                    'minimum' => 0,
                ],
            ],
        ]);

        // POST /webhooks - Register new webhook
        register_rest_route($this->namespace, '/webhooks', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'create_webhook'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'url' => [
                    'description' => __('Webhook URL', 'modern-admin-styler-v2'),
                    'type' => 'string',
                    'format' => 'uri',
                    'required' => true,
                    'validate_callback' => function($value) {
                        return filter_var($value, FILTER_VALIDATE_URL) !== false;
                    },
                ],
                'events' => [
                    'description' => __('Array of event names to subscribe to', 'modern-admin-styler-v2'),
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                        'enum' => MAS_Webhook_Service::get_supported_events(),
                    ],
                    'required' => true,
                ],
                'secret' => [
                    'description' => __('Secret for HMAC signature (auto-generated if not provided)', 'modern-admin-styler-v2'),
                    'type' => 'string',
                    'required' => false,
                ],
            ],
        ]);

        // GET /webhooks/{id} - Get specific webhook
        register_rest_route($this->namespace, '/webhooks/(?P<id>\d+)', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_webhook'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'id' => [
                    'description' => __('Webhook ID', 'modern-admin-styler-v2'),
                    'type' => 'integer',
                    'required' => true,
                ],
            ],
        ]);

        // PUT /webhooks/{id} - Update webhook
        register_rest_route($this->namespace, '/webhooks/(?P<id>\d+)', [
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => [$this, 'update_webhook'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'id' => [
                    'description' => __('Webhook ID', 'modern-admin-styler-v2'),
                    'type' => 'integer',
                    'required' => true,
                ],
                'url' => [
                    'description' => __('Webhook URL', 'modern-admin-styler-v2'),
                    'type' => 'string',
                    'format' => 'uri',
                    'required' => false,
                    'validate_callback' => function($value) {
                        return filter_var($value, FILTER_VALIDATE_URL) !== false;
                    },
                ],
                'events' => [
                    'description' => __('Array of event names to subscribe to', 'modern-admin-styler-v2'),
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                        'enum' => MAS_Webhook_Service::get_supported_events(),
                    ],
                    'required' => false,
                ],
                'active' => [
                    'description' => __('Whether webhook is active', 'modern-admin-styler-v2'),
                    'type' => 'boolean',
                    'required' => false,
                ],
            ],
        ]);

        // DELETE /webhooks/{id} - Delete webhook
        register_rest_route($this->namespace, '/webhooks/(?P<id>\d+)', [
            'methods' => WP_REST_Server::DELETABLE,
            'callback' => [$this, 'delete_webhook'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'id' => [
                    'description' => __('Webhook ID', 'modern-admin-styler-v2'),
                    'type' => 'integer',
                    'required' => true,
                ],
            ],
        ]);

        // GET /webhooks/{id}/deliveries - Get delivery history
        register_rest_route($this->namespace, '/webhooks/(?P<id>\d+)/deliveries', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_deliveries'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'id' => [
                    'description' => __('Webhook ID', 'modern-admin-styler-v2'),
                    'type' => 'integer',
                    'required' => true,
                ],
                'status' => [
                    'description' => __('Filter by delivery status', 'modern-admin-styler-v2'),
                    'type' => 'string',
                    'enum' => ['pending', 'success', 'failed'],
                    'required' => false,
                ],
                'limit' => [
                    'description' => __('Number of deliveries to return', 'modern-admin-styler-v2'),
                    'type' => 'integer',
                    'default' => 50,
                    'minimum' => 1,
                    'maximum' => 100,
                ],
                'offset' => [
                    'description' => __('Offset for pagination', 'modern-admin-styler-v2'),
                    'type' => 'integer',
                    'default' => 0,
                    'minimum' => 0,
                ],
            ],
        ]);

        // GET /webhooks/events - Get supported events
        register_rest_route($this->namespace, '/webhooks/events', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_supported_events'],
            'permission_callback' => [$this, 'check_permission'],
        ]);
    }

    /**
     * List all webhooks
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function list_webhooks($request) {
        try {
            $args = [
                'active' => $request->get_param('active'),
                'limit' => $request->get_param('limit') ?: 50,
                'offset' => $request->get_param('offset') ?: 0,
            ];

            $webhooks = $this->webhook_service->list_webhooks($args);

            // Remove secrets from response for security
            foreach ($webhooks as &$webhook) {
                $webhook['secret'] = '***HIDDEN***';
            }

            // Log successful retrieval
            $this->security_logger->log_event(
                get_current_user_id(),
                'webhook_list',
                'success',
                ['count' => count($webhooks)]
            );

            return $this->success_response(
                $webhooks,
                sprintf(__('Retrieved %d webhooks', 'modern-admin-styler-v2'), count($webhooks)),
                200,
                $request
            );
        } catch (Exception $e) {
            return $this->error_response(
                $e->getMessage(),
                'webhook_list_failed',
                500
            );
        }
    }

    /**
     * Create new webhook
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function create_webhook($request) {
        try {
            $url = $request->get_param('url');
            $events = $request->get_param('events');
            $secret = $request->get_param('secret') ?: '';

            $result = $this->webhook_service->register_webhook($url, $events, $secret);

            if (is_wp_error($result)) {
                return $result;
            }

            // Log successful creation
            $this->security_logger->log_event(
                get_current_user_id(),
                'webhook_created',
                'success',
                [
                    'webhook_id' => $result['id'],
                    'url' => $url,
                    'events' => $events,
                ]
            );

            return $this->success_response(
                $result,
                __('Webhook registered successfully', 'modern-admin-styler-v2'),
                201,
                $request
            );
        } catch (Exception $e) {
            return $this->error_response(
                $e->getMessage(),
                'webhook_creation_failed',
                500
            );
        }
    }

    /**
     * Get specific webhook
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function get_webhook($request) {
        try {
            $webhook_id = $request->get_param('id');
            $webhook = $this->webhook_service->get_webhook($webhook_id);

            if (!$webhook) {
                return $this->error_response(
                    __('Webhook not found', 'modern-admin-styler-v2'),
                    'webhook_not_found',
                    404
                );
            }

            // Hide secret for security
            $webhook['secret'] = '***HIDDEN***';

            return $this->success_response(
                $webhook,
                __('Webhook retrieved successfully', 'modern-admin-styler-v2'),
                200,
                $request
            );
        } catch (Exception $e) {
            return $this->error_response(
                $e->getMessage(),
                'webhook_retrieval_failed',
                500
            );
        }
    }

    /**
     * Update webhook
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function update_webhook($request) {
        try {
            $webhook_id = $request->get_param('id');
            
            $update_data = [];
            
            if ($request->has_param('url')) {
                $update_data['url'] = $request->get_param('url');
            }
            
            if ($request->has_param('events')) {
                $update_data['events'] = $request->get_param('events');
            }
            
            if ($request->has_param('active')) {
                $update_data['active'] = $request->get_param('active');
            }

            $result = $this->webhook_service->update_webhook($webhook_id, $update_data);

            if (is_wp_error($result)) {
                return $result;
            }

            // Log successful update
            $this->security_logger->log_event(
                get_current_user_id(),
                'webhook_updated',
                'success',
                [
                    'webhook_id' => $webhook_id,
                    'updated_fields' => array_keys($update_data),
                ]
            );

            // Get updated webhook
            $webhook = $this->webhook_service->get_webhook($webhook_id);
            $webhook['secret'] = '***HIDDEN***';

            return $this->success_response(
                $webhook,
                __('Webhook updated successfully', 'modern-admin-styler-v2'),
                200,
                $request
            );
        } catch (Exception $e) {
            return $this->error_response(
                $e->getMessage(),
                'webhook_update_failed',
                500
            );
        }
    }

    /**
     * Delete webhook
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function delete_webhook($request) {
        try {
            $webhook_id = $request->get_param('id');

            // Check if webhook exists
            $webhook = $this->webhook_service->get_webhook($webhook_id);
            if (!$webhook) {
                return $this->error_response(
                    __('Webhook not found', 'modern-admin-styler-v2'),
                    'webhook_not_found',
                    404
                );
            }

            $result = $this->webhook_service->delete_webhook($webhook_id);

            if (!$result) {
                return $this->error_response(
                    __('Failed to delete webhook', 'modern-admin-styler-v2'),
                    'webhook_deletion_failed',
                    500
                );
            }

            // Log successful deletion
            $this->security_logger->log_event(
                get_current_user_id(),
                'webhook_deleted',
                'success',
                ['webhook_id' => $webhook_id]
            );

            return $this->success_response(
                ['deleted' => true, 'id' => $webhook_id],
                __('Webhook deleted successfully', 'modern-admin-styler-v2'),
                200,
                $request
            );
        } catch (Exception $e) {
            return $this->error_response(
                $e->getMessage(),
                'webhook_deletion_failed',
                500
            );
        }
    }

    /**
     * Get delivery history for a webhook
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function get_deliveries($request) {
        try {
            $webhook_id = $request->get_param('id');

            // Check if webhook exists
            $webhook = $this->webhook_service->get_webhook($webhook_id);
            if (!$webhook) {
                return $this->error_response(
                    __('Webhook not found', 'modern-admin-styler-v2'),
                    'webhook_not_found',
                    404
                );
            }

            $args = [
                'status' => $request->get_param('status'),
                'limit' => $request->get_param('limit') ?: 50,
                'offset' => $request->get_param('offset') ?: 0,
            ];

            $deliveries = $this->webhook_service->get_delivery_history($webhook_id, $args);

            return $this->success_response(
                $deliveries,
                sprintf(__('Retrieved %d deliveries', 'modern-admin-styler-v2'), count($deliveries)),
                200,
                $request
            );
        } catch (Exception $e) {
            return $this->error_response(
                $e->getMessage(),
                'delivery_retrieval_failed',
                500
            );
        }
    }

    /**
     * Get supported webhook events
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function get_supported_events($request) {
        $events = MAS_Webhook_Service::get_supported_events();

        $event_descriptions = [
            'settings.updated' => __('Triggered when plugin settings are updated', 'modern-admin-styler-v2'),
            'theme.applied' => __('Triggered when a theme is applied', 'modern-admin-styler-v2'),
            'backup.created' => __('Triggered when a backup is created', 'modern-admin-styler-v2'),
            'backup.restored' => __('Triggered when a backup is restored', 'modern-admin-styler-v2'),
        ];

        $formatted_events = [];
        foreach ($events as $event) {
            $formatted_events[] = [
                'name' => $event,
                'description' => $event_descriptions[$event] ?? '',
            ];
        }

        return $this->success_response(
            $formatted_events,
            __('Supported webhook events', 'modern-admin-styler-v2'),
            200,
            $request
        );
    }
}
