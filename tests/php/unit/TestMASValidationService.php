<?php
/**
 * Unit tests for MAS_Validation_Service
 */

class TestMASValidationService extends WP_UnitTestCase {
    
    private $validation_service;
    
    public function setUp() {
        parent::setUp();
        $this->validation_service = new MAS_Validation_Service();
    }
    
    public function test_validate_color_valid_hex() {
        $result = $this->validation_service->validate_color('#1e1e2e');
        $this->assertTrue($result);
    }
    
    public function test_validate_color_invalid_hex() {
        $result = $this->validation_service->validate_color('invalid-color');
        $this->assertFalse($result);
    }
    
    public function test_validate_color_short_hex() {
        $result = $this->validation_service->validate_color('#fff');
        $this->assertTrue($result);
    }
    
    public function test_validate_css_unit_pixels() {
        $result = $this->validation_service->validate_css_unit('280px');
        $this->assertTrue($result);
    }
    
    public function test_validate_css_unit_em() {
        $result = $this->validation_service->validate_css_unit('1.5em');
        $this->assertTrue($result);
    }
    
    public function test_validate_css_unit_percent() {
        $result = $this->validation_service->validate_css_unit('100%');
        $this->assertTrue($result);
    }
    
    public function test_validate_css_unit_invalid() {
        $result = $this->validation_service->validate_css_unit('invalid-unit');
        $this->assertFalse($result);
    }
    
    public function test_validate_boolean_true() {
        $result = $this->validation_service->validate_boolean(true);
        $this->assertTrue($result);
    }
    
    public function test_validate_boolean_false() {
        $result = $this->validation_service->validate_boolean(false);
        $this->assertTrue($result);
    }
    
    public function test_validate_boolean_string_true() {
        $result = $this->validation_service->validate_boolean('true');
        $this->assertTrue($result);
    }
    
    public function test_validate_boolean_invalid() {
        $result = $this->validation_service->validate_boolean('invalid');
        $this->assertFalse($result);
    }
    
    public function test_validate_array_valid() {
        $result = $this->validation_service->validate_array(['item1', 'item2']);
        $this->assertTrue($result);
    }
    
    public function test_validate_array_invalid() {
        $result = $this->validation_service->validate_array('not-an-array');
        $this->assertFalse($result);
    }
    
    public function test_sanitize_settings_complete() {
        $input = [
            'menu_background' => '#1e1e2e',
            'menu_text_color' => '#ffffff',
            'menu_detached' => 'true',
            'menu_width' => '280px'
        ];
        
        $result = $this->validation_service->sanitize_settings($input);
        
        $this->assertEquals('#1e1e2e', $result['menu_background']);
        $this->assertEquals('#ffffff', $result['menu_text_color']);
        $this->assertTrue($result['menu_detached']);
        $this->assertEquals('280px', $result['menu_width']);
    }
    
    public function test_validate_settings_schema_valid() {
        $settings = [
            'menu_background' => '#1e1e2e',
            'menu_text_color' => '#ffffff',
            'menu_detached' => false
        ];
        
        $result = $this->validation_service->validate_settings_schema($settings);
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }
    
    public function test_validate_settings_schema_invalid() {
        $settings = [
            'menu_background' => 'invalid-color',
            'menu_width' => 'invalid-unit'
        ];
        
        $result = $this->validation_service->validate_settings_schema($settings);
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }
}