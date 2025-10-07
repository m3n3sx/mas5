<?php
/**
 * Unit tests for MAS_Cache_Service
 */

class TestMASCacheService extends WP_UnitTestCase {
    
    private $cache_service;
    
    public function setUp() {
        parent::setUp();
        $this->cache_service = new MAS_Cache_Service();
    }
    
    public function test_set_and_get_cache() {
        $key = 'test_key';
        $value = ['test' => 'data'];
        
        $this->cache_service->set($key, $value);
        $result = $this->cache_service->get($key);
        
        $this->assertEquals($value, $result);
    }
    
    public function test_get_nonexistent_cache() {
        $result = $this->cache_service->get('nonexistent_key');
        $this->assertFalse($result);
    }
    
    public function test_delete_cache() {
        $key = 'test_key';
        $value = ['test' => 'data'];
        
        $this->cache_service->set($key, $value);
        $this->cache_service->delete($key);
        $result = $this->cache_service->get($key);
        
        $this->assertFalse($result);
    }
    
    public function test_flush_cache() {
        $this->cache_service->set('key1', 'value1');
        $this->cache_service->set('key2', 'value2');
        
        $this->cache_service->flush();
        
        $this->assertFalse($this->cache_service->get('key1'));
        $this->assertFalse($this->cache_service->get('key2'));
    }
    
    public function test_cache_expiration() {
        $key = 'expiring_key';
        $value = 'expiring_value';
        $expiration = 1; // 1 second
        
        $this->cache_service->set($key, $value, $expiration);
        
        // Should exist immediately
        $this->assertEquals($value, $this->cache_service->get($key));
        
        // Wait for expiration (simulate)
        sleep(2);
        
        // Should be expired (this test might be flaky in real scenarios)
        $result = $this->cache_service->get($key);
        $this->assertFalse($result);
    }
    
    public function test_cache_key_generation() {
        $prefix = 'test_prefix';
        $data = ['param1' => 'value1', 'param2' => 'value2'];
        
        $key = $this->cache_service->generate_key($prefix, $data);
        
        $this->assertIsString($key);
        $this->assertStringStartsWith($prefix, $key);
    }
    
    public function test_cache_statistics() {
        // Set some cache entries
        $this->cache_service->set('key1', 'value1');
        $this->cache_service->set('key2', 'value2');
        
        // Get some entries (hits)
        $this->cache_service->get('key1');
        $this->cache_service->get('key1'); // Another hit
        
        // Try to get non-existent entry (miss)
        $this->cache_service->get('nonexistent');
        
        $stats = $this->cache_service->get_statistics();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('hits', $stats);
        $this->assertArrayHasKey('misses', $stats);
    }
}