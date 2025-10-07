<?php
/**
 * Unit tests for MAS_Rate_Limiter_Service
 */

class TestMASRateLimiterService extends WP_UnitTestCase {
    
    private $rate_limiter;
    
    public function setUp() {
        parent::setUp();
        $this->rate_limiter = new MAS_Rate_Limiter_Service();
    }
    
    public function test_allow_request_within_limit() {
        $user_id = 1;
        $endpoint = 'settings';
        
        $result = $this->rate_limiter->is_allowed($user_id, $endpoint);
        $this->assertTrue($result);
    }
    
    public function test_block_request_over_limit() {
        $user_id = 1;
        $endpoint = 'settings';
        $limit = 5; // Assume limit is 5 requests per minute
        
        // Make requests up to the limit
        for ($i = 0; $i < $limit; $i++) {
            $this->rate_limiter->record_request($user_id, $endpoint);
        }
        
        // Next request should be blocked
        $result = $this->rate_limiter->is_allowed($user_id, $endpoint);
        $this->assertFalse($result);
    }
    
    public function test_different_users_separate_limits() {
        $user1 = 1;
        $user2 = 2;
        $endpoint = 'settings';
        $limit = 5;
        
        // User 1 reaches limit
        for ($i = 0; $i < $limit; $i++) {
            $this->rate_limiter->record_request($user1, $endpoint);
        }
        
        // User 1 should be blocked
        $this->assertFalse($this->rate_limiter->is_allowed($user1, $endpoint));
        
        // User 2 should still be allowed
        $this->assertTrue($this->rate_limiter->is_allowed($user2, $endpoint));
    }
    
    public function test_different_endpoints_separate_limits() {
        $user_id = 1;
        $endpoint1 = 'settings';
        $endpoint2 = 'themes';
        $limit = 5;
        
        // Reach limit for endpoint1
        for ($i = 0; $i < $limit; $i++) {
            $this->rate_limiter->record_request($user_id, $endpoint1);
        }
        
        // endpoint1 should be blocked
        $this->assertFalse($this->rate_limiter->is_allowed($user_id, $endpoint1));
        
        // endpoint2 should still be allowed
        $this->assertTrue($this->rate_limiter->is_allowed($user_id, $endpoint2));
    }
    
    public function test_get_remaining_requests() {
        $user_id = 1;
        $endpoint = 'settings';
        $limit = 10;
        
        // Make some requests
        for ($i = 0; $i < 3; $i++) {
            $this->rate_limiter->record_request($user_id, $endpoint);
        }
        
        $remaining = $this->rate_limiter->get_remaining_requests($user_id, $endpoint);
        $this->assertEquals($limit - 3, $remaining);
    }
    
    public function test_get_reset_time() {
        $user_id = 1;
        $endpoint = 'settings';
        
        $this->rate_limiter->record_request($user_id, $endpoint);
        $reset_time = $this->rate_limiter->get_reset_time($user_id, $endpoint);
        
        $this->assertIsInt($reset_time);
        $this->assertGreaterThan(time(), $reset_time);
    }
    
    public function test_reset_limits() {
        $user_id = 1;
        $endpoint = 'settings';
        $limit = 5;
        
        // Reach the limit
        for ($i = 0; $i < $limit; $i++) {
            $this->rate_limiter->record_request($user_id, $endpoint);
        }
        
        // Should be blocked
        $this->assertFalse($this->rate_limiter->is_allowed($user_id, $endpoint));
        
        // Reset limits
        $this->rate_limiter->reset_limits($user_id, $endpoint);
        
        // Should be allowed again
        $this->assertTrue($this->rate_limiter->is_allowed($user_id, $endpoint));
    }
    
    public function test_get_rate_limit_headers() {
        $user_id = 1;
        $endpoint = 'settings';
        
        $this->rate_limiter->record_request($user_id, $endpoint);
        $headers = $this->rate_limiter->get_rate_limit_headers($user_id, $endpoint);
        
        $this->assertIsArray($headers);
        $this->assertArrayHasKey('X-RateLimit-Limit', $headers);
        $this->assertArrayHasKey('X-RateLimit-Remaining', $headers);
        $this->assertArrayHasKey('X-RateLimit-Reset', $headers);
    }
}