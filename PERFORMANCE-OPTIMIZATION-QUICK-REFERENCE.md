# Performance Optimization Quick Reference

## Cache Service Usage

### Basic Operations
```php
// Get cache service instance
$cache = new MAS_Cache_Service();

// Set cache
$cache->set('my_key', $data, 300); // Cache for 5 minutes

// Get cache
$cached_data = $cache->get('my_key');

// Delete cache
$cache->delete('my_key');

// Flush all cache
$cache->flush();
```

### Remember Pattern (Cache-or-Generate)
```php
$cache = new MAS_Cache_Service();

$data = $cache->remember('expensive_data', function() {
    // This only runs if cache is empty
    return expensive_database_query();
}, 600); // Cache for 10 minutes
```

### Cache Invalidation
```php
// Invalidate settings cache
do_action('mas_v2_settings_updated', $settings);

// Invalidate theme cache
do_action('mas_v2_theme_applied', $theme_id);

// Manual invalidation
$cache->invalidate_settings_cache();
$cache->invalidate_theme_cache();
```

## Response Optimization

### Using Optimized Response
```php
class My_Controller extends MAS_REST_Controller {
    
    public function get_data($request) {
        $data = $this->service->get_data();
        
        // Optimized response with caching and ETag
        return $this->optimized_response($data, $request, [
            'message' => 'Data retrieved successfully',
            'cache_max_age' => 300,  // 5 minutes
            'use_etag' => true,
            'cache_public' => false
        ]);
    }
}
```

### Manual Cache Headers
```php
// Add cache headers
$response = $this->success_response($data);
$response = $this->add_cache_headers($response, 600); // 10 minutes

// Add ETag
$response = $this->add_etag_header($response, $data, $request);

// Prevent caching
$response = $this->add_no_cache_headers($response);
```

## Pagination

### Backend Implementation
```php
public function list_items($request) {
    $limit = $request->get_param('limit') ?: 10;
    $page = $request->get_param('page') ?: 1;
    $offset = ($page - 1) * $limit;
    
    // Get total count
    $total = $this->service->count_items();
    $total_pages = ceil($total / $limit);
    
    // Get paginated items
    $items = $this->service->get_items($limit, $offset);
    
    // Create response
    $response = $this->success_response($items);
    
    // Add pagination headers
    $response->header('X-WP-Total', $total);
    $response->header('X-WP-TotalPages', $total_pages);
    
    // Add Link header
    $base_url = rest_url($this->namespace . '/' . $this->rest_base);
    $links = [];
    
    if ($page > 1) {
        $links[] = '<' . add_query_arg(['page' => 1, 'limit' => $limit], $base_url) . '>; rel="first"';
        $links[] = '<' . add_query_arg(['page' => $page - 1, 'limit' => $limit], $base_url) . '>; rel="prev"';
    }
    
    if ($page < $total_pages) {
        $links[] = '<' . add_query_arg(['page' => $page + 1, 'limit' => $limit], $base_url) . '>; rel="next"';
        $links[] = '<' . add_query_arg(['page' => $total_pages, 'limit' => $limit], $base_url) . '>; rel="last"';
    }
    
    if (!empty($links)) {
        $response->header('Link', implode(', ', $links));
    }
    
    return $response;
}
```

### Frontend Usage
```javascript
// Fetch paginated data
async function fetchPaginatedData(page = 1, limit = 10) {
    const response = await fetch(
        `/wp-json/mas-v2/v1/backups?page=${page}&limit=${limit}`
    );
    
    const data = await response.json();
    
    // Get pagination info
    const total = response.headers.get('X-WP-Total');
    const totalPages = response.headers.get('X-WP-TotalPages');
    
    return {
        items: data.data,
        total: parseInt(total),
        totalPages: parseInt(totalPages),
        currentPage: page
    };
}

// Usage
const result = await fetchPaginatedData(1, 10);
console.log(`Showing ${result.items.length} of ${result.total} items`);
console.log(`Page ${result.currentPage} of ${result.totalPages}`);
```

## ETag Support

### Backend (Automatic)
```php
// ETag is automatically added with optimized_response
return $this->optimized_response($data, $request, [
    'use_etag' => true
]);
```

### Frontend Usage
```javascript
let lastETag = null;

async function fetchWithETag(url) {
    const headers = {};
    
    if (lastETag) {
        headers['If-None-Match'] = lastETag;
    }
    
    const response = await fetch(url, { headers });
    
    if (response.status === 304) {
        console.log('Data unchanged, using cached version');
        return null; // Use cached data
    }
    
    // Update ETag
    lastETag = response.headers.get('ETag');
    
    return await response.json();
}
```

## Cache Statistics

### Get Cache Stats
```php
$cache = new MAS_Cache_Service();
$stats = $cache->get_stats();

/*
Returns:
[
    'cache_group' => 'mas_v2',
    'tracked_keys' => 15,
    'default_expiration' => 3600,
    'object_cache_enabled' => true
]
*/
```

## Performance Best Practices

### 1. Use Cache Service for Expensive Operations
```php
// ❌ Bad - No caching
public function get_data() {
    return expensive_database_query();
}

// ✅ Good - With caching
public function get_data() {
    return $this->cache->remember('data_key', function() {
        return expensive_database_query();
    }, 600);
}
```

### 2. Invalidate Cache on Data Changes
```php
// ❌ Bad - No cache invalidation
public function save_data($data) {
    update_option('my_data', $data);
}

// ✅ Good - With cache invalidation
public function save_data($data) {
    update_option('my_data', $data);
    $this->cache->delete('data_key');
    do_action('my_data_updated', $data);
}
```

### 3. Use Optimized Responses
```php
// ❌ Bad - No optimization
public function get_settings($request) {
    $settings = $this->service->get_settings();
    return new WP_REST_Response($settings);
}

// ✅ Good - With optimization
public function get_settings($request) {
    $settings = $this->service->get_settings();
    return $this->optimized_response($settings, $request, [
        'cache_max_age' => 300,
        'use_etag' => true
    ]);
}
```

### 4. Implement Pagination for Large Datasets
```php
// ❌ Bad - Return all items
public function list_items($request) {
    return $this->service->get_all_items(); // Could be thousands
}

// ✅ Good - With pagination
public function list_items($request) {
    $limit = $request->get_param('limit') ?: 10;
    $page = $request->get_param('page') ?: 1;
    $offset = ($page - 1) * $limit;
    
    return $this->service->get_items($limit, $offset);
}
```

## Cache Expiration Guidelines

| Data Type | Recommended Expiration | Reason |
|-----------|----------------------|---------|
| Settings | 300s (5 min) | Changes infrequently |
| Themes | 600s (10 min) | Rarely changes |
| Backups | 300s (5 min) | List changes occasionally |
| Diagnostics | 300s (5 min) | System info is relatively stable |
| Generated CSS | 3600s (1 hour) | Expensive to generate |
| User preferences | 1800s (30 min) | User-specific, moderate changes |

## Monitoring Performance

### Log Cache Operations
```php
// Cache service automatically logs operations in debug mode
if (defined('WP_DEBUG') && WP_DEBUG) {
    // Logs appear in debug.log:
    // MAS Cache: HIT for key 'current_settings' in group 'mas_v2'
    // MAS Cache: MISS for key 'backup_123' in group 'mas_v2'
    // MAS Cache: SET key 'current_settings' in group 'mas_v2' (expires in 300s)
}
```

### Measure Response Times
```php
$start = microtime(true);
$data = $this->service->get_data();
$duration = (microtime(true) - $start) * 1000;

error_log("Data retrieval took {$duration}ms");
```

## Troubleshooting

### Cache Not Working
```php
// Check if object cache is enabled
$cache = new MAS_Cache_Service();
$stats = $cache->get_stats();

if (!$stats['object_cache_enabled']) {
    // Install Redis or Memcached for persistent object cache
    error_log('Object cache not enabled - using transient cache');
}
```

### High Memory Usage
```php
// Reduce cache expiration times
$cache->set('key', $data, 60); // 1 minute instead of 1 hour

// Or flush cache periodically
wp_schedule_event(time(), 'hourly', 'mas_flush_cache');
add_action('mas_flush_cache', function() {
    $cache = new MAS_Cache_Service();
    $cache->flush();
});
```

### Stale Data Issues
```php
// Ensure cache is invalidated on updates
add_action('mas_v2_settings_updated', function($settings) {
    $cache = new MAS_Cache_Service();
    $cache->invalidate_settings_cache();
});
```

## API Endpoints with Optimization

| Endpoint | Cache | ETag | Pagination |
|----------|-------|------|------------|
| GET /settings | ✅ 5min | ✅ | ❌ |
| GET /themes | ✅ 10min | ✅ | ✅ |
| GET /backups | ✅ 5min | ✅ | ✅ |
| GET /diagnostics | ✅ 5min | ✅ | ❌ |
| POST /preview | ❌ | ❌ | ❌ |
| POST /settings | ❌ | ❌ | ❌ |

## Summary

- **Cache Service:** Use for expensive operations, automatic invalidation
- **Response Optimization:** ETag + Cache-Control for bandwidth savings
- **Pagination:** Required for lists > 20 items
- **Monitoring:** Check cache stats and response times regularly
