<?php
/**
 * API Version Manager for Modern Admin Styler V2
 * 
 * Manages API versioning, routing, and version detection.
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
 * API Version Manager Class
 * 
 * Handles API version routing, detection, and management.
 */
class MAS_Version_Manager {
    
    /**
     * Available API versions
     * 
     * @var array
     */
    private $versions = [
        'v1' => [
            'namespace' => 'mas-v2/v1',
            'status' => 'stable',
            'released' => '2024-01-01',
            'deprecated' => false,
            'removal_date' => null
        ],
        'v2' => [
            'namespace' => 'mas-v2/v2',
            'status' => 'beta',
            'released' => '2025-06-10',
            'deprecated' => false,
            'removal_date' => null
        ]
    ];
    
    /**
     * Default version when not specified
     * 
     * @var string
     */
    private $default_version = 'v1';
    
    /**
     * Latest stable version
     * 
     * @var string
     */
    private $latest_stable = 'v1';
    
    /**
     * Get version from request
     * 
     * Checks multiple sources in order:
     * 1. X-API-Version header
     * 2. Accept header version parameter
     * 3. Query parameter 'version'
     * 4. Route namespace
     * 5. Default version
     * 
     * @param WP_REST_Request $request Request object
     * @return string Version identifier (e.g., 'v1', 'v2')
     */
    public function get_version_from_request($request) {
        // Check X-API-Version header
        $header_version = $request->get_header('X-API-Version');
        if ($header_version && $this->is_valid_version($header_version)) {
            return $this->normalize_version($header_version);
        }
        
        // Check Accept header for version parameter
        $accept_header = $request->get_header('Accept');
        if ($accept_header) {
            $version = $this->extract_version_from_accept($accept_header);
            if ($version && $this->is_valid_version($version)) {
                return $this->normalize_version($version);
            }
        }
        
        // Check query parameter
        $query_version = $request->get_param('version');
        if ($query_version && $this->is_valid_version($query_version)) {
            return $this->normalize_version($query_version);
        }
        
        // Check route namespace
        $route = $request->get_route();
        $version = $this->extract_version_from_route($route);
        if ($version && $this->is_valid_version($version)) {
            return $this->normalize_version($version);
        }
        
        // Return default version
        return $this->default_version;
    }
    
    /**
     * Extract version from Accept header
     * 
     * Looks for version parameter in Accept header like:
     * application/json; version=v2
     * 
     * @param string $accept_header Accept header value
     * @return string|null Version or null if not found
     */
    private function extract_version_from_accept($accept_header) {
        if (preg_match('/version=([v]?\d+)/i', $accept_header, $matches)) {
            return $matches[1];
        }
        return null;
    }
    
    /**
     * Extract version from route
     * 
     * Extracts version from route like /mas-v2/v1/settings
     * 
     * @param string $route Route path
     * @return string|null Version or null if not found
     */
    private function extract_version_from_route($route) {
        if (preg_match('#/mas-v2/(v\d+)/#', $route, $matches)) {
            return $matches[1];
        }
        return null;
    }
    
    /**
     * Normalize version identifier
     * 
     * Ensures version has 'v' prefix (e.g., '1' becomes 'v1')
     * 
     * @param string $version Version identifier
     * @return string Normalized version
     */
    private function normalize_version($version) {
        $version = strtolower(trim($version));
        if (!preg_match('/^v/', $version)) {
            $version = 'v' . $version;
        }
        return $version;
    }
    
    /**
     * Check if version is valid
     * 
     * @param string $version Version identifier
     * @return bool True if valid
     */
    public function is_valid_version($version) {
        $version = $this->normalize_version($version);
        return isset($this->versions[$version]);
    }
    
    /**
     * Get namespace for version
     * 
     * @param string $version Version identifier
     * @return string|null Namespace or null if version not found
     */
    public function get_namespace($version = null) {
        if ($version === null) {
            $version = $this->default_version;
        }
        
        $version = $this->normalize_version($version);
        
        if (!isset($this->versions[$version])) {
            return null;
        }
        
        return $this->versions[$version]['namespace'];
    }
    
    /**
     * Get version information
     * 
     * @param string $version Version identifier
     * @return array|null Version info or null if not found
     */
    public function get_version_info($version) {
        $version = $this->normalize_version($version);
        return isset($this->versions[$version]) ? $this->versions[$version] : null;
    }
    
    /**
     * Get all available versions
     * 
     * @return array All versions with their info
     */
    public function get_all_versions() {
        return $this->versions;
    }
    
    /**
     * Get default version
     * 
     * @return string Default version identifier
     */
    public function get_default_version() {
        return $this->default_version;
    }
    
    /**
     * Get latest stable version
     * 
     * @return string Latest stable version identifier
     */
    public function get_latest_stable_version() {
        return $this->latest_stable;
    }
    
    /**
     * Check if version is deprecated
     * 
     * @param string $version Version identifier
     * @return bool True if deprecated
     */
    public function is_deprecated($version) {
        $version = $this->normalize_version($version);
        
        if (!isset($this->versions[$version])) {
            return false;
        }
        
        return $this->versions[$version]['deprecated'];
    }
    
    /**
     * Get deprecation info for version
     * 
     * @param string $version Version identifier
     * @return array|null Deprecation info or null if not deprecated
     */
    public function get_deprecation_info($version) {
        $version = $this->normalize_version($version);
        
        if (!$this->is_deprecated($version)) {
            return null;
        }
        
        $info = $this->versions[$version];
        
        return [
            'deprecated' => true,
            'removal_date' => $info['removal_date'],
            'replacement_version' => $this->latest_stable,
            'message' => sprintf(
                'API version %s is deprecated and will be removed on %s. Please migrate to version %s.',
                $version,
                $info['removal_date'] ? date('Y-m-d', strtotime($info['removal_date'])) : 'a future date',
                $this->latest_stable
            )
        ];
    }
    
    /**
     * Set version as deprecated
     * 
     * @param string $version Version identifier
     * @param string $removal_date Removal date (Y-m-d format)
     * @return bool True on success
     */
    public function deprecate_version($version, $removal_date = null) {
        $version = $this->normalize_version($version);
        
        if (!isset($this->versions[$version])) {
            return false;
        }
        
        $this->versions[$version]['deprecated'] = true;
        $this->versions[$version]['removal_date'] = $removal_date;
        
        return true;
    }
    
    /**
     * Route request to appropriate version
     * 
     * This method can be used to programmatically route requests
     * to different version handlers.
     * 
     * @param WP_REST_Request $request Request object
     * @return string Namespace to use for routing
     */
    public function route_request($request) {
        $version = $this->get_version_from_request($request);
        $namespace = $this->get_namespace($version);
        
        if (!$namespace) {
            // Fallback to default
            $namespace = $this->get_namespace($this->default_version);
        }
        
        return $namespace;
    }
    
    /**
     * Add version headers to response
     * 
     * @param WP_REST_Response $response Response object
     * @param string $version Version used
     * @return WP_REST_Response Modified response
     */
    public function add_version_headers($response, $version) {
        $version = $this->normalize_version($version);
        
        // Add version header
        $response->header('X-API-Version', $version);
        
        // Add deprecation warning if applicable
        if ($this->is_deprecated($version)) {
            $deprecation_info = $this->get_deprecation_info($version);
            $response->header('Warning', sprintf(
                '299 - "Deprecated API Version: %s"',
                $deprecation_info['message']
            ));
        }
        
        return $response;
    }
}
