<?php
/**
 * MAS Cache Service
 *
 * Provides caching functionality for the Modern Admin Styler V2 plugin.
 * Wraps WordPress object cache with plugin-specific functionality.
 *
 * @package ModernAdminStylerV2
 * @subpackage Services
 * @since 2.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cache Service Class
 *
 * Handles caching operations with automatic invalidation and cache warming.
 */
class MAS_Cache_Service {
    
    /**
     * Cache group for plugin data
     *
     * @var string
     */
    private $cache_group = 'mas_v2';
    
    /**
     * Default cache expiration time (1 hour)
     *
     * @var int
     */
    private $default_expiration = 3600;
    
    /**
     * Cache keys that should be warmed on initialization
     *
     * @var array
     */
    private $warm_cache_keys = [
        'current_settings',
        'predefined_themes',
        'system_diagnostics'
    ];
    
    /**
     * Cache statistics
     *
     * @var array
     */
    private $stats = [
        'hits' => 0,
        'misses' => 0,
        'sets' => 0,
        'deletes' => 0
    ];
    
    /**
     * Constructor
     */
    public function __construct() {
        // Hook into settings changes for cache invalidation
        add_action('mas_v2_settings_updated', [$this, 'invalidate_settings_cache']);
        add_action('mas_v2_theme_applied', [$this, 'invalidate_theme_cache']);
        
        // Load statistics from persistent storage
        $this->load_stats();
    }
    
    /**
     * Get cached data
     *
     * @param string $key Cache key
     * @param string $group Optional. Cache group. Default uses plugin group.
     * @return mixed|false Cached data or false if not found
     */
    public function get($key, $group = null) {
        $group = $group ?? $this->cache_group;
        $cached = wp_cache_get($key, $group);
        
        if ($cached !== false) {
            $this->stats['hits']++;
            error_log("MAS Cache: HIT for key '{$key}' in group '{$group}'");
        } else {
            $this->stats['misses']++;
            error_log("MAS Cache: MISS for key '{$key}' in group '{$group}'");
        }
        
        // Persist stats periodically (every 10 operations)
        if (($this->stats['hits'] + $this->stats['misses']) % 10 === 0) {
            $this->save_stats();
        }
        
        return $cached;
    }
    
    /**
     * Set cached data
     *
     * @param string $key Cache key
     * @param mixed $data Data to cache
     * @param int $expiration Optional. Expiration time in seconds. Default 1 hour.
     * @param string $group Optional. Cache group. Default uses plugin group.
     * @return bool True on success, false on failure
     */
    public function set($key, $data, $expiration = null, $group = null) {
        $group = $group ?? $this->cache_group;
        $expiration = $expiration ?? $this->default_expiration;
        
        $result = wp_cache_set($key, $data, $group, $expiration);
        
        if ($result) {
            $this->stats['sets']++;
            error_log("MAS Cache: SET key '{$key}' in group '{$group}' (expires in {$expiration}s)");
        }
        
        return $result;
    }
    
    /**
     * Delete cached data
     *
     * @param string $key Cache key
     * @param string $group Optional. Cache group. Default uses plugin group.
     * @return bool True on success, false on failure
     */
    public function delete($key, $group = null) {
        $group = $group ?? $this->cache_group;
        $result = wp_cache_delete($key, $group);
        
        if ($result) {
            $this->stats['deletes']++;
            error_log("MAS Cache: DELETE key '{$key}' from group '{$group}'");
        }
        
        return $result;
    }
    
    /**
     * Flush all cached data for the plugin
     *
     * @return bool True on success
     */
    public function flush() {
        error_log("MAS Cache: FLUSH all cache for group '{$this->cache_group}'");
        
        // WordPress doesn't support flushing by group, so we track keys
        $keys = $this->get_tracked_keys();
        
        foreach ($keys as $key) {
            $this->delete($key);
        }
        
        // Clear the tracked keys list
        $this->delete('_tracked_keys', $this->cache_group);
        
        return true;
    }
    
    /**
     * Get or set cached data with callback
     *
     * If data exists in cache, return it. Otherwise, execute callback,
     * cache the result, and return it.
     *
     * @param string $key Cache key
     * @param callable $callback Callback to generate data if not cached
     * @param int $expiration Optional. Expiration time in seconds.
     * @param string $group Optional. Cache group.
     * @return mixed Cached or generated data
     */
    public function remember($key, $callback, $expiration = null, $group = null) {
        $cached = $this->get($key, $group);
        
        if ($cached !== false) {
            return $cached;
        }
        
        // Generate data
        $data = call_user_func($callback);
        
        // Cache it
        $this->set($key, $data, $expiration, $group);
        
        // Track the key for flushing
        $this->track_key($key);
        
        return $data;
    }
    
    /**
     * Invalidate settings-related cache
     *
     * Called when settings are updated.
     */
    public function invalidate_settings_cache() {
        error_log("MAS Cache: Invalidating settings cache");
        
        $this->delete('current_settings');
        $this->delete('generated_css');
        $this->delete('settings_validation');
        
        // Trigger cache warming for frequently accessed data
        $this->warm_settings_cache();
    }
    
    /**
     * Invalidate theme-related cache
     *
     * Called when a theme is applied or modified.
     */
    public function invalidate_theme_cache() {
        error_log("MAS Cache: Invalidating theme cache");
        
        $this->delete('predefined_themes');
        $this->delete('custom_themes');
        $this->delete('current_theme');
        $this->delete('generated_css');
    }
    
    /**
     * Warm settings cache
     */
    private function warm_settings_cache() {
        if (class_exists('MAS_Settings_Service')) {
            $settings_service = new MAS_Settings_Service();
            $settings = $settings_service->get_settings();
            $this->set('current_settings', $settings);
        }
    }
    
    /**
     * Warm predefined themes cache
     */
    private function warm_predefined_themes_cache() {
        if (class_exists('MAS_Theme_Service')) {
            $theme_service = new MAS_Theme_Service();
            $themes = $theme_service->get_predefined_themes();
            $this->set('predefined_themes', $themes);
        }
    }
    
    /**
     * Warm system diagnostics cache
     */
    private function warm_system_diagnostics_cache() {
        if (class_exists('MAS_Diagnostics_Service')) {
            $diagnostics_service = new MAS_Diagnostics_Service();
            $diagnostics = $diagnostics_service->get_system_info();
            $this->set('system_diagnostics', $diagnostics, 300); // 5 minutes
        }
    }
    
    /**
     * Track a cache key for later flushing
     *
     * @param string $key Cache key to track
     */
    private function track_key($key) {
        $keys = $this->get_tracked_keys();
        
        if (!in_array($key, $keys)) {
            $keys[] = $key;
            $this->set('_tracked_keys', $keys, 0); // Never expire
        }
    }
    
    /**
     * Get list of tracked cache keys
     *
     * @return array List of tracked keys
     */
    private function get_tracked_keys() {
        $keys = $this->get('_tracked_keys');
        return is_array($keys) ? $keys : [];
    }
    
    /**
     * Get cache statistics
     *
     * Returns comprehensive cache statistics including hits, misses, and hit rate.
     *
     * @return array Cache statistics
     */
    public function get_stats() {
        $total_requests = $this->stats['hits'] + $this->stats['misses'];
        $hit_rate = $total_requests > 0 ? ($this->stats['hits'] / $total_requests) * 100 : 0;
        
        $stats = [
            'cache_group' => $this->cache_group,
            'tracked_keys' => count($this->get_tracked_keys()),
            'default_expiration' => $this->default_expiration,
            'object_cache_enabled' => wp_using_ext_object_cache(),
            'hits' => $this->stats['hits'],
            'misses' => $this->stats['misses'],
            'sets' => $this->stats['sets'],
            'deletes' => $this->stats['deletes'],
            'total_requests' => $total_requests,
            'hit_rate' => round($hit_rate, 2),
            'hit_rate_percentage' => round($hit_rate, 2) . '%'
        ];
        
        return $stats;
    }
    
    /**
     * Load cache statistics from persistent storage
     *
     * @return void
     */
    private function load_stats() {
        $saved_stats = get_option('mas_v2_cache_stats', []);
        
        if (is_array($saved_stats)) {
            $this->stats = wp_parse_args($saved_stats, $this->stats);
        }
    }
    
    /**
     * Save cache statistics to persistent storage
     *
     * @return void
     */
    private function save_stats() {
        update_option('mas_v2_cache_stats', $this->stats, false);
    }
    
    /**
     * Reset cache statistics
     *
     * @return void
     */
    public function reset_stats() {
        $this->stats = [
            'hits' => 0,
            'misses' => 0,
            'sets' => 0,
            'deletes' => 0
        ];
        
        $this->save_stats();
    }
    
    /**
     * Warm cache for frequently accessed data
     *
     * Pre-loads commonly used data into cache to improve performance.
     * This method can be called manually or scheduled to run periodically.
     *
     * @return array Results of cache warming operations
     */
    public function warm_cache() {
        error_log("MAS Cache: Warming cache for frequently accessed data");
        
        $results = [];
        
        foreach ($this->warm_cache_keys as $key) {
            $method = "warm_{$key}_cache";
            if (method_exists($this, $method)) {
                try {
                    call_user_func([$this, $method]);
                    $results[$key] = 'success';
                } catch (Exception $e) {
                    $results[$key] = 'failed: ' . $e->getMessage();
                    error_log("MAS Cache: Failed to warm cache for '{$key}': " . $e->getMessage());
                }
            }
        }
        
        return $results;
    }
}
