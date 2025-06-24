<?php

namespace ModernAdminStylerV2\Controllers;

use ModernAdminStylerV2\Services\SettingsService;
use ModernAdminStylerV2\Services\AssetService;

/**
 * DEPRECATED: AdminController
 * 
 * ⚠️ UWAGA: Ten kontroler jest przestarzały!
 * 
 * Nowa architektura modułowa używa:
 * - mas-loader.js → modules/*.js → admin-global.js/admin-modern.js
 * 
 * Ten plik jest utrzymywany TYLKO dla kompatybilności wstecznej.
 * Główna logika jest w modern-admin-styler-v2.php
 * 
 * Status: DEPRECATED - nie używać!
 */
class AdminController {
    private $settingsService;
    private $assetService;
    
    public function __construct() {
        // DEPRECATED: AdminController nie jest używany w nowej architekturze
        error_log('⚠️ AdminController is DEPRECATED - use main plugin class instead');
        return;
        
        // Kod poniżej nie będzie wykonywany
        $this->settingsService = new SettingsService();
        $this->assetService = new AssetService();
        $this->init();
    }
    
    /**
     * DEPRECATED: Nie używać!
     */
    private function init() {
        // DEPRECATED - nie inicjalizuj hooks
        return;
        
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('wp_ajax_mas_v2_save_settings', [$this, 'ajaxSaveSettings']);
        add_action('wp_ajax_mas_v2_reset_settings', [$this, 'ajaxResetSettings']);
        add_action('wp_ajax_mas_v2_export_settings', [$this, 'ajaxExportSettings']);
        add_action('wp_ajax_mas_v2_import_settings', [$this, 'ajaxImportSettings']);
    }
    
    /**
     * DEPRECATED: Menu dodawane w main plugin class
     */
    public function addAdminMenu() {
        error_log('⚠️ AdminController::addAdminMenu() is DEPRECATED');
        return;
    }
    
    /**
     * DEPRECATED: Assets ładowane w main plugin class
     */
    public function enqueueAssets($hook) {
        error_log('⚠️ AdminController::enqueueAssets() is DEPRECATED');
        return;
        
        // STARY KOD (nie wykonywany):
        if ($hook !== 'toplevel_page_mas-v2-settings') {
            return;
        }
        
        $this->assetService->enqueueAdminAssets();
    }
    
    /**
     * Renderuje stronę administracyjną
     */
    public function renderAdminPage() {
        $settings = $this->settingsService->getSettings();
        $tabs = $this->getTabs();
        
        include MAS_V2_PLUGIN_DIR . 'src/views/admin-page.php';
    }
    
    /**
     * AJAX: Zapisuje ustawienia
     */
    public function ajaxSaveSettings() {
        // Weryfikacja bezpieczeństwa
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mas_v2_nonce')) {
            wp_send_json_error(['message' => __('Błąd bezpieczeństwa', 'modern-admin-styler-v2')]);
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Brak uprawnień', 'modern-admin-styler-v2')]);
        }
        
        try {
            $settings = $this->settingsService->sanitizeSettings($_POST);
            $this->settingsService->saveSettings($settings);
            
            wp_send_json_success([
                'message' => __('Ustawienia zostały zapisane pomyślnie!', 'modern-admin-styler-v2'),
                'settings' => $settings
            ]);
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * AJAX: Resetuje ustawienia
     */
    public function ajaxResetSettings() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mas_v2_nonce')) {
            wp_send_json_error(['message' => __('Błąd bezpieczeństwa', 'modern-admin-styler-v2')]);
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Brak uprawnień', 'modern-admin-styler-v2')]);
        }
        
        try {
            $this->settingsService->resetSettings();
            $settings = $this->settingsService->getSettings();
            
            wp_send_json_success([
                'message' => __('Ustawienia zostały przywrócone do domyślnych!', 'modern-admin-styler-v2'),
                'settings' => $settings
            ]);
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * AJAX: Eksportuje ustawienia
     */
    public function ajaxExportSettings() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mas_v2_nonce')) {
            wp_send_json_error(['message' => __('Błąd bezpieczeństwa', 'modern-admin-styler-v2')]);
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Brak uprawnień', 'modern-admin-styler-v2')]);
        }
        
        try {
            $settings = $this->settingsService->getSettings();
            $export = [
                'version' => MAS_V2_VERSION,
                'exported_at' => current_time('mysql'),
                'settings' => $settings
            ];
            
            wp_send_json_success([
                'data' => $export,
                'filename' => 'mas-v2-settings-' . date('Y-m-d-H-i-s') . '.json'
            ]);
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * AJAX: Importuje ustawienia
     */
    public function ajaxImportSettings() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mas_v2_nonce')) {
            wp_send_json_error(['message' => __('Błąd bezpieczeństwa', 'modern-admin-styler-v2')]);
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Brak uprawnień', 'modern-admin-styler-v2')]);
        }
        
        try {
            $data = json_decode(stripslashes($_POST['data'] ?? ''), true);
            
            if (!$data || !isset($data['settings'])) {
                throw new Exception(__('Nieprawidłowy format pliku', 'modern-admin-styler-v2'));
            }
            
            $settings = $this->settingsService->sanitizeSettings($data['settings']);
            $this->settingsService->saveSettings($settings);
            
            wp_send_json_success([
                'message' => __('Ustawienia zostały zaimportowane pomyślnie!', 'modern-admin-styler-v2'),
                'settings' => $settings
            ]);
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Zwraca definicje tabów interfejsu
     */
    private function getTabs() {
        return [
            'general' => [
                'title' => __('Ogólne', 'modern-admin-styler-v2'),
                'icon' => 'settings',
                'description' => __('Podstawowe ustawienia wyglądu', 'modern-admin-styler-v2')
            ],
            'admin-bar' => [
                'title' => __('Admin Bar', 'modern-admin-styler-v2'),
                'icon' => 'admin-bar',
                'description' => __('Stylowanie górnego paska administracyjnego', 'modern-admin-styler-v2')
            ],
            'menu' => [
                'title' => __('Menu', 'modern-admin-styler-v2'),
                'icon' => 'menu',
                'description' => __('Konfiguracja menu bocznego', 'modern-admin-styler-v2')
            ],
            'content' => [
                'title' => __('Treść', 'modern-admin-styler-v2'),
                'icon' => 'content',
                'description' => __('Stylowanie obszaru treści', 'modern-admin-styler-v2')
            ],
            'typography' => [
                'title' => __('Typografia', 'modern-admin-styler-v2'),
                'icon' => 'typography',
                'description' => __('Ustawienia czcionek i tekstów', 'modern-admin-styler-v2')
            ],
            'effects' => [
                'title' => __('Efekty', 'modern-admin-styler-v2'),
                'icon' => 'effects',
                'description' => __('Animacje i efekty specjalne', 'modern-admin-styler-v2')
            ],
            'advanced' => [
                'title' => __('Zaawansowane', 'modern-admin-styler-v2'),
                'icon' => 'advanced',
                'description' => __('Niestandardowe CSS i opcje deweloperskie', 'modern-admin-styler-v2')
            ]
        ];
    }
} 