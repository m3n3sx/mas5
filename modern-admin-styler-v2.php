<?php
/**
 * Plugin Name: Modern Admin Styler V2
 * Plugin URI: https://github.com/modern-admin-team/modern-admin-styler-v2
 * Description: Kompletna wtyczka do stylowania panelu WordPress z nowoczesnymi dashboardami, metrykami, kartami z gradientami, glassmorphism i interaktywnymi elementami UI! Teraz z trybem ciemnym/jasnym i nowoczesnymi fontami!
 * Version: 2.2.0
 * Author: Modern Web Dev Team
 * Text Domain: modern-admin-styler-v2
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.8
 * Requires PHP: 7.4
 */

// Zabezpieczenie przed bezpośrednim dostępem
if (!defined('ABSPATH')) {
    exit;
}

// Definicje stałych
define('MAS_V2_VERSION', '2.2.0');
define('MAS_V2_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MAS_V2_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MAS_V2_PLUGIN_FILE', __FILE__);

/**
 * Główna klasa wtyczki - Nowa architektura
 */
class ModernAdminStylerV2 {
    
    private static $instance = null;
    private $adminController;
    private $assetService;
    private $settingsService;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init();
    }
    
    /**
     * Inicjalizacja wtyczki
     */
    private function init() {
        // Hooks - TYLKO RAZ, BEZ DUPLIKATÓW!
        add_action('init', [$this, 'loadTextdomain']);
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueGlobalAssets']); // CSS na wszystkich stronach!
        add_action('admin_head', [$this, 'outputCustomStyles']);
        add_action('wp_head', [$this, 'outputFrontendStyles']);
        add_action('login_head', [$this, 'outputLoginStyles']);
        add_action('admin_footer', [$this, 'addSettingsGearButton']);
        add_action('admin_footer', [$this, 'addDebugInfo']); // Debug info
        
        // AJAX handlers - TYLKO RAZ!
        add_action('wp_ajax_mas_v2_save_settings', [$this, 'ajaxSaveSettings']);
        add_action('wp_ajax_mas_v2_reset_settings', [$this, 'ajaxResetSettings']);
        add_action('wp_ajax_mas_v2_export_settings', [$this, 'ajaxExportSettings']);
        add_action('wp_ajax_mas_v2_import_settings', [$this, 'ajaxImportSettings']);
        add_action('wp_ajax_mas_v2_get_preview_css', [$this, 'ajaxGetPreviewCSS']); // Simple live preview
        add_action('wp_ajax_mas_v2_save_theme', [$this, 'ajaxSaveTheme']);
        add_action('wp_ajax_mas_v2_diagnostics', [$this, 'ajaxDiagnostics']);
        add_action('wp_ajax_mas_v2_list_backups', [$this, 'ajaxListBackups']);
        add_action('wp_ajax_mas_v2_restore_backup', [$this, 'ajaxRestoreBackup']);
        add_action('wp_ajax_mas_v2_create_backup', [$this, 'ajaxCreateBackup']);
        add_action('wp_ajax_mas_v2_delete_backup', [$this, 'ajaxDeleteBackup']);
        
        // Filters
        add_filter('admin_footer_text', [$this, 'customAdminFooter']);
        add_filter('admin_body_class', [$this, 'addAdminBodyClasses']);
        
        // Activation/Deactivation hooks
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        
        // Task 13: WordPress compatibility checks and admin notices
        add_action('admin_notices', [$this, 'displayAdminNotices']);
        add_action('admin_init', [$this, 'checkCompatibilityOnLoad']);
        
        // Allow framing for localhost viewer
        add_action('init', [$this, 'allowFramingForLocalhostViewer']);
    }
    
    /**
     * Display admin notices - Task 13
     */
    public function displayAdminNotices() {
        // Activation notice
        if (get_transient('mas_v2_activation_notice')) {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p><strong>' . __('Modern Admin Styler V2', 'modern-admin-styler-v2') . '</strong> ' . 
                 __('has been activated successfully!', 'modern-admin-styler-v2') . ' ';
            echo '<a href="' . admin_url('admin.php?page=mas-v2-settings') . '">' . 
                 __('Configure Settings', 'modern-admin-styler-v2') . '</a></p>';
            echo '</div>';
            delete_transient('mas_v2_activation_notice');
        }
        
        // Compatibility warnings
        if (!$this->checkWordPressCompatibility()) {
            echo '<div class="notice notice-error">';
            echo '<p><strong>' . __('Modern Admin Styler V2', 'modern-admin-styler-v2') . '</strong> ' . 
                 __('requires WordPress 5.0 or higher. Please update WordPress.', 'modern-admin-styler-v2') . '</p>';
            echo '</div>';
        }
        
        if (!$this->checkPHPCompatibility()) {
            echo '<div class="notice notice-error">';
            echo '<p><strong>' . __('Modern Admin Styler V2', 'modern-admin-styler-v2') . '</strong> ' . 
                 __('requires PHP 7.4 or higher. Please update PHP.', 'modern-admin-styler-v2') . '</p>';
            echo '</div>';
        }
        
        // WordPress version warning for newer versions
        global $wp_version;
        $tested_version = '6.4';
        if (version_compare($wp_version, $tested_version, '>')) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>' . __('Modern Admin Styler V2', 'modern-admin-styler-v2') . '</strong> ' . 
                 sprintf(__('has not been tested with WordPress %s. It was tested up to version %s.', 'modern-admin-styler-v2'), 
                         $wp_version, $tested_version) . '</p>';
            echo '</div>';
        }
    }
    
    /**
     * Check compatibility on plugin load - Task 13
     */
    public function checkCompatibilityOnLoad() {
        // Only run on plugin pages
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'mas-v2') === false) {
            return;
        }
        
        // Check for potential conflicts with other plugins
        $this->checkPluginConflicts();
        
        // Verify required WordPress features
        $this->verifyWordPressFeatures();
    }
    
    /**
     * Check for potential plugin conflicts - Task 13
     */
    private function checkPluginConflicts() {
        $active_plugins = get_option('active_plugins', []);
        $conflicting_plugins = [
            'admin-color-schemes/admin-color-schemes.php' => 'Admin Color Schemes',
            'admin-menu-editor/menu-editor.php' => 'Admin Menu Editor',
            'custom-admin-interface/custom-admin-interface.php' => 'Custom Admin Interface'
        ];
        
        $conflicts = [];
        foreach ($conflicting_plugins as $plugin_file => $plugin_name) {
            if (in_array($plugin_file, $active_plugins)) {
                $conflicts[] = $plugin_name;
            }
        }
        
        if (!empty($conflicts) && !get_transient('mas_v2_conflict_notice_dismissed')) {
            add_action('admin_notices', function() use ($conflicts) {
                echo '<div class="notice notice-warning is-dismissible">';
                echo '<p><strong>' . __('Modern Admin Styler V2', 'modern-admin-styler-v2') . '</strong> ' . 
                     __('detected potential conflicts with:', 'modern-admin-styler-v2') . ' ' . 
                     implode(', ', $conflicts) . '. ' . 
                     __('Please test functionality carefully.', 'modern-admin-styler-v2') . '</p>';
                echo '</div>';
            });
        }
    }
    
    /**
     * Verify required WordPress features - Task 13
     */
    private function verifyWordPressFeatures() {
        $required_features = [
            'wp_enqueue_script' => __('Script enqueueing', 'modern-admin-styler-v2'),
            'wp_enqueue_style' => __('Style enqueueing', 'modern-admin-styler-v2'),
            'wp_localize_script' => __('Script localization', 'modern-admin-styler-v2'),
            'add_menu_page' => __('Admin menu creation', 'modern-admin-styler-v2'),
            'wp_create_nonce' => __('Security nonces', 'modern-admin-styler-v2')
        ];
        
        $missing_features = [];
        foreach ($required_features as $function => $description) {
            if (!function_exists($function)) {
                $missing_features[] = $description;
            }
        }
        
        if (!empty($missing_features)) {
            add_action('admin_notices', function() use ($missing_features) {
                echo '<div class="notice notice-error">';
                echo '<p><strong>' . __('Modern Admin Styler V2', 'modern-admin-styler-v2') . '</strong> ' . 
                     __('cannot function properly. Missing WordPress features:', 'modern-admin-styler-v2') . ' ' . 
                     implode(', ', $missing_features) . '</p>';
                echo '</div>';
            });
        }
    }
    
    /**
     * Allow framing for Localhost Viewer extension in Cursor.
     */
    public function allowFramingForLocalhostViewer() {
        // This allows the site to be embedded in an iframe for development purposes.
        remove_action('admin_init', 'send_frame_options_header');
        remove_action('login_init', 'send_frame_options_header');
    }
    
    /**
     * Autoloader dla klas
     */
    public function autoload($className) {
        if (strpos($className, 'ModernAdminStylerV2\\') !== 0) {
            return;
        }
        
        $className = str_replace('ModernAdminStylerV2\\', '', $className);
        $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
        
        $file = MAS_V2_PLUGIN_DIR . 'src' . DIRECTORY_SEPARATOR . $className . '.php';
        
        if (file_exists($file)) {
            require_once $file;
        }
    }
    
    /**
     * Inicjalizacja serwisów
     */
    public function initServices() {
        // Na razie używamy legacy mode - nowa architektura będzie dodana później
        // Ta funkcja jest przygotowana na przyszłe rozszerzenie
        $this->initLegacyMode();
    }
    
    // initLegacyMode() USUNIĘTE - było źródłem duplikatów hooków
    
    /**
     * Aktywacja wtyczki - Enhanced for Task 13
     */
    public function activate() {
        // WordPress version compatibility check
        if (!$this->checkWordPressCompatibility()) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(
                __('Modern Admin Styler V2 requires WordPress 5.0 or higher. Please update WordPress to use this plugin.', 'modern-admin-styler-v2'),
                __('Plugin Activation Error', 'modern-admin-styler-v2'),
                array('back_link' => true)
            );
        }
        
        // PHP version compatibility check
        if (!$this->checkPHPCompatibility()) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(
                __('Modern Admin Styler V2 requires PHP 7.4 or higher. Please update PHP to use this plugin.', 'modern-admin-styler-v2'),
                __('Plugin Activation Error', 'modern-admin-styler-v2'),
                array('back_link' => true)
            );
        }
        
        // Create backup of current settings if they exist
        $existing_settings = get_option('mas_v2_settings');
        if ($existing_settings) {
            $this->createSettingsBackup($existing_settings, 'activation_backup');
        }
        
        // Set default settings if not exist
        $defaults = $this->getDefaultSettings();
        add_option('mas_v2_settings', $defaults);
        
        // Create plugin tables if needed (for future use)
        $this->createPluginTables();
        
        // Clear cache
        $this->clearCache();
        
        // Set activation flag for admin notice
        set_transient('mas_v2_activation_notice', true, 30);
        
        // Log activation
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MAS V2: Plugin activated successfully on WordPress ' . get_bloginfo('version'));
        }
    }
    
    /**
     * Deaktywacja wtyczki - Enhanced for Task 13
     */
    public function deactivate() {
        // Create backup before deactivation
        $current_settings = get_option('mas_v2_settings');
        if ($current_settings) {
            $this->createSettingsBackup($current_settings, 'deactivation_backup');
        }
        
        // Clear all plugin caches and transients
        $this->clearCache();
        $this->clearAllPluginTransients();
        
        // Remove temporary files
        $this->cleanupTemporaryFiles();
        
        // Clear any scheduled events
        $this->clearScheduledEvents();
        
        // Remove admin notices
        delete_transient('mas_v2_activation_notice');
        delete_transient('mas_v2_admin_notice');
        
        // Log deactivation
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MAS V2: Plugin deactivated successfully');
        }
    }
    
    /**
     * Ładowanie tłumaczeń
     */
    public function loadTextdomain() {
        load_plugin_textdomain('modern-admin-styler-v2', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Legacy: Dodanie menu w adminpanel
     */
    public function addAdminMenu() {
        // Główne menu
        add_menu_page(
            __('Modern Admin Styler V2', 'modern-admin-styler-v2'),
            __('MAS V2', 'modern-admin-styler-v2'),
            'manage_options',
            'mas-v2-settings',
            [$this, 'renderAdminPage'],
            'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"/></svg>'),
            30
        );

        // Submenu dla poszczególnych zakładek
        add_submenu_page(
            'mas-v2-settings',
            __('Ogólne', 'modern-admin-styler-v2'),
            __('Ogólne', 'modern-admin-styler-v2'),
            'manage_options',
            'mas-v2-general',
            [$this, 'renderTabPage']
        );

        add_submenu_page(
            'mas-v2-settings',
            __('Pasek Admin', 'modern-admin-styler-v2'),
            __('Pasek Admin', 'modern-admin-styler-v2'),
            'manage_options',
            'mas-v2-admin-bar',
            [$this, 'renderTabPage']
        );

        add_submenu_page(
            'mas-v2-settings',
            __('Menu boczne', 'modern-admin-styler-v2'),
            __('Menu boczne', 'modern-admin-styler-v2'),
            'manage_options',
            'mas-v2-menu',
            [$this, 'renderTabPage']
        );

        add_submenu_page(
            'mas-v2-settings',
            __('Treść', 'modern-admin-styler-v2'),
            __('Treść', 'modern-admin-styler-v2'),
            'manage_options',
            'mas-v2-content',
            [$this, 'renderTabPage']
        );

        add_submenu_page(
            'mas-v2-settings',
            __('Przyciski', 'modern-admin-styler-v2'),
            __('Przyciski', 'modern-admin-styler-v2'),
            'manage_options',
            'mas-v2-buttons',
            [$this, 'renderTabPage']
        );

        add_submenu_page(
            'mas-v2-settings',
            __('Logowanie', 'modern-admin-styler-v2'),
            __('Logowanie', 'modern-admin-styler-v2'),
            'manage_options',
            'mas-v2-login',
            [$this, 'renderTabPage']
        );

        add_submenu_page(
            'mas-v2-settings',
            __('Typografia', 'modern-admin-styler-v2'),
            __('Typografia', 'modern-admin-styler-v2'),
            'manage_options',
            'mas-v2-typography',
            [$this, 'renderTabPage']
        );

        add_submenu_page(
            'mas-v2-settings',
            __('Efekty', 'modern-admin-styler-v2'),
            __('Efekty', 'modern-admin-styler-v2'),
            'manage_options',
            'mas-v2-effects',
            [$this, 'renderTabPage']
        );

        add_submenu_page(
            'mas-v2-settings',
            __('Szablony', 'modern-admin-styler-v2'),
            __('🎨 Szablony', 'modern-admin-styler-v2'),
            'manage_options',
            'mas-v2-templates',
            [$this, 'renderTabPage']
        );

        add_submenu_page(
            'mas-v2-settings',
            __('Zaawansowane', 'modern-admin-styler-v2'),
            __('Zaawansowane', 'modern-admin-styler-v2'),
            'manage_options',
            'mas-v2-advanced',
            [$this, 'renderTabPage']
        );
    }
    
    /**
     * Legacy: Enqueue CSS i JS na stronie ustawień pluginu
     */
    public function enqueueAssets($hook) {
        // Sprawdź czy jesteśmy na którejś ze stron wtyczki
        $mas_pages = [
            'toplevel_page_mas-v2-settings',
            'mas-v2_page_mas-v2-general',
            'mas-v2_page_mas-v2-admin-bar',
            'mas-v2_page_mas-v2-menu',
            'mas-v2_page_mas-v2-content',
            'mas-v2_page_mas-v2-buttons',
            'mas-v2_page_mas-v2-login',
            'mas-v2_page_mas-v2-typography',
            'mas-v2_page_mas-v2-effects',
            'mas-v2_page_mas-v2-templates',
            'mas-v2_page_mas-v2-advanced'
        ];
        
        if (!in_array($hook, $mas_pages)) {
            return;
        }
        
        // 🔄 DODAJ CSS TAKŻE NA STRONACH USTAWIEŃ (bo enqueueGlobalAssets może nie być wywoływana)
        wp_enqueue_style(
            'mas-v2-menu-reset',
            MAS_V2_PLUGIN_URL . 'assets/css/admin-menu-reset.css',
            [],
            MAS_V2_VERSION
        );
        
        wp_enqueue_style(
            'mas-v2-global',
            MAS_V2_PLUGIN_URL . 'assets/css/admin-modern.css',
            ['mas-v2-menu-reset'],
            MAS_V2_VERSION
        );
        
        // 🚀 Settings page: Prosty handler (bez skomplikowanych modułów)
        wp_enqueue_script(
            'mas-v2-admin-settings-simple',
            MAS_V2_PLUGIN_URL . 'assets/js/admin-settings-simple.js',
            ['jquery', 'wp-color-picker'],
            MAS_V2_VERSION,
            true
        );
        
        // 🎨 Simple Live Preview - inspired by working version
        wp_enqueue_script(
            'mas-v2-simple-live-preview',
            MAS_V2_PLUGIN_URL . 'assets/js/simple-live-preview.js',
            ['jquery', 'wp-color-picker', 'mas-v2-admin'],
            MAS_V2_VERSION,
            true
        );
        
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_style('thickbox');
        wp_enqueue_media();
        
        // Localize script dla prostego handlera
        wp_localize_script('mas-v2-admin-settings-simple', 'masV2Global', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mas_v2_nonce'),
            'settings' => $this->getSettings()
        ]);
    }
    
    /**
     * Enqueue CSS i JS na wszystkich stronach wp-admin
     */
    public function enqueueGlobalAssets($hook) {
        error_log('🧪 DEBUG: enqueueGlobalAssets wywołana, hook: ' . $hook);
        
        // Nie ładuj CSS/JS na stronie logowania lub jeśli jesteśmy poza admin
        if (!is_admin() || $this->isLoginPage()) {
            error_log('🧪 DEBUG: enqueueGlobalAssets - wyjście wcześnie (nie admin lub login)');
            return;
        }
        
        // 🔄 MENU FIXED - Nowa implementacja menu (ŁADUJ PIERWSZY!)
        wp_enqueue_style(
            'mas-v2-menu-fixed',
            MAS_V2_PLUGIN_URL . 'assets/css/admin-menu-fixed.css',
            [],
            MAS_V2_VERSION
        );
        
        // CSS na wszystkich stronach wp-admin (oprócz logowania)
        wp_enqueue_style(
            'mas-v2-global',
            MAS_V2_PLUGIN_URL . 'assets/css/admin-modern.css',
            ['mas-v2-menu-fixed'],
            MAS_V2_VERSION
        );
        
        // 🎨 Advanced Effects CSS - RESTORED for Task 10
        wp_enqueue_style(
            'mas-v2-advanced-effects',
            MAS_V2_PLUGIN_URL . 'assets/css/advanced-effects.css',
            array('mas-v2-global'),
            MAS_V2_VERSION
        );
        
        wp_enqueue_style(
            'mas-v2-color-palettes',
            MAS_V2_PLUGIN_URL . 'assets/css/color-palettes.css',
            array('mas-v2-global'),
            MAS_V2_VERSION
        );
        
        wp_enqueue_style(
            'mas-v2-palette-switcher',
            MAS_V2_PLUGIN_URL . 'assets/css/palette-switcher.css',
            array('mas-v2-global'),
            MAS_V2_VERSION
        );
        
        // 🎯 MODERN MENU CSS - RESTORED for basic menu functionality
        wp_enqueue_style(
            'mas-v2-menu-modern',
            MAS_V2_PLUGIN_URL . 'assets/css/admin-menu-modern.css',
            array('mas-v2-global'),
            MAS_V2_VERSION
        );
        
        // 🚀 QUICK FIX CSS - RESTORED for critical UI fixes
        wp_enqueue_style(
            'mas-v2-quick-fix',
            MAS_V2_PLUGIN_URL . 'assets/css/quick-fix.css',
            array('mas-v2-global'),
            MAS_V2_VERSION
        );
        
        // 🌐 CROSS-BROWSER COMPATIBILITY CSS - Task 16
        wp_enqueue_style(
            'mas-v2-cross-browser-compatibility',
            MAS_V2_PLUGIN_URL . 'assets/css/cross-browser-compatibility.css',
            array('mas-v2-global'),
            MAS_V2_VERSION
        );
        
        // Uproszczony CSS dla menu - nadpisuje style z admin-modern.css
        // WYŁĄCZONE - testujemy czy submenu działa bez żadnego custom CSS
        // wp_enqueue_style(
        //     'mas-v2-menu-simple',
        //     MAS_V2_PLUGIN_URL . 'assets/css/admin-menu-simple.css',
        //     ['mas-v2-global'],
        //     MAS_V2_VERSION
        // );
        
        // 🎨 Settings page specific styles
        $current_page = $_GET['page'] ?? '';
        if (strpos($current_page, 'mas-v2') !== false) {
            wp_enqueue_style(
                'mas-v2-admin-settings-page',
                MAS_V2_PLUGIN_URL . 'assets/css/admin-settings-page.css',
                array('mas-v2-global'),
                MAS_V2_VERSION
            );
            
            // Settings page JavaScript
            wp_enqueue_script(
                'mas-v2-admin-settings-page',
                MAS_V2_PLUGIN_URL . 'assets/js/admin-settings-page.js',
                array('jquery', 'mas-v2-global'),
                MAS_V2_VERSION,
                true
            );
        }
        
        // 🚫 STARY SYSTEM MODUŁOWY WYŁĄCZONY - powodował konflikty
        // Zastąpiony prostszym systemem simple-live-preview.js
        /*
        wp_enqueue_script('mas-v2-loader', MAS_V2_PLUGIN_URL . 'assets/js/mas-loader.js', [], MAS_V2_VERSION, true);
        wp_enqueue_script('mas-v2-global', MAS_V2_PLUGIN_URL . 'assets/js/admin-global.js', ['jquery', 'mas-v2-loader'], MAS_V2_VERSION, true);
        */
        
        // 🌐 CROSS-BROWSER COMPATIBILITY JS - Task 16
        wp_enqueue_script(
            'mas-v2-cross-browser-compatibility',
            MAS_V2_PLUGIN_URL . 'assets/js/cross-browser-compatibility.js',
            [],
            MAS_V2_VERSION,
            false // Load in head for early feature detection
        );
        
        // masV2Global jest teraz przekazywany przez simple-live-preview.js w enqueueAssets()
        
        // Add body class via PHP if menu customizations are active
        if ($this->hasMenuCustomizations($settings_for_js)) {
            add_action('admin_body_class', function($classes) {
                return $classes . ' mas-v2-menu-custom-enabled';
            });
        }
    }
    
    /**
     * Sprawdza czy jesteśmy na stronie logowania
     */
    private function isLoginPage() {
        return in_array($GLOBALS['pagenow'], ['wp-login.php', 'wp-register.php']);
    }
    
    /**
     * Sprawdza czy są aktywne customizacje menu
     */
    private function hasMenuCustomizations($settings) {
        return (
            !empty($settings['menu_background']) || 
            !empty($settings['menu_text_color']) || 
            !empty($settings['menu_hover_background']) ||
            !empty($settings['menu_hover_text_color']) ||
            !empty($settings['menu_active_background']) ||
            !empty($settings['menu_active_text_color']) ||
            !empty($settings['menu_width']) ||
            !empty($settings['menu_item_height']) ||
            !empty($settings['menu_border_radius_all']) ||
            !empty($settings['menu_detached']) ||
            !empty($settings['menu_floating']) ||
            !empty($settings['menu_glossy']) ||
            !empty($settings['submenu_background']) ||
            !empty($settings['submenu_text_color'])
        );
    }
    
    /**
     * 🧪 TYMCZASOWY DEBUG - usuń po testach
     */
    public function addDebugInfo() {
        if (!current_user_can('manage_options')) return;
        
        $settings = get_option('mas_v2_settings', []);
        
        echo '<div style="position: fixed; bottom: 10px; right: 10px; background: #fff; border: 2px solid #0073aa; padding: 15px; max-width: 400px; z-index: 999999; font-size: 12px; box-shadow: 0 4px 8px rgba(0,0,0,0.2);">';
        echo '<h3 style="margin: 0 0 10px 0; color: #0073aa;">🔍 MAS V2 Menu Debug</h3>';
        
        // Sprawdź ustawienia menu
        $menu_settings = [];
        foreach ($settings as $key => $value) {
            if (strpos($key, 'menu_') === 0 || $key === 'modern_menu_style' || $key === 'auto_fold_menu') {
                $menu_settings[$key] = $value;
            }
        }
        
        if (empty($menu_settings)) {
            echo '<p style="color: red; margin: 5px 0;"><strong>❌ BRAK USTAWIEŃ MENU!</strong></p>';
            echo '<p style="margin: 5px 0;">Menu = WordPress default</p>';
            echo '<p style="margin: 5px 0;"><a href="' . admin_url('admin.php?page=mas-v2-menu') . '" style="color: #0073aa;">Przejdź do ustawień menu</a></p>';
        } else {
            echo '<p style="color: green; margin: 5px 0;"><strong>✅ Znaleziono ustawienia:</strong></p>';
            foreach ($menu_settings as $key => $value) {
                if ($value) {
                    echo '<p style="margin: 2px 0; font-size: 11px;">' . $key . ': ' . (is_bool($value) ? ($value ? 'true' : 'false') : $value) . '</p>';
                }
            }
        }
        
        // Test logiki
        $hasMenuCustomizations = (
            !empty($settings['menu_background']) || 
            !empty($settings['menu_bg']) ||
            !empty($settings['menu_text_color']) || 
            !empty($settings['menu_hover_background']) ||
            !empty($settings['menu_hover_text_color']) ||
            !empty($settings['menu_active_background']) ||
            !empty($settings['menu_active_text_color']) ||
            !empty($settings['menu_width']) ||
            !empty($settings['menu_item_height']) ||
            !empty($settings['menu_border_radius']) ||
            !empty($settings['menu_border_radius_all']) ||
            !empty($settings['menu_detached_margin']) ||
            !empty($settings['menu_margin']) ||
            !empty($settings['modern_menu_style']) ||
            !empty($settings['auto_fold_menu'])
        );
        
        echo '<p style="margin: 5px 0;"><strong>hasMenuCustomizations:</strong> ' . ($hasMenuCustomizations ? '<span style="color: green;">TRUE</span>' : '<span style="color: red;">FALSE</span>') . '</p>';
        
        // Sprawdź czy CSS jest załadowany
        echo '<p style="margin: 5px 0;"><strong>CSS test:</strong> <span id="mas-css-test">❌</span></p>';
        
        echo '<script>
        // Dodaj klasę debug do body
        document.body.classList.add("mas-v2-debug");
        
        // Test czy admin-menu-reset.css jest załadowany
        const testEl = document.getElementById("adminmenu");
        if (testEl) {
            const style = getComputedStyle(testEl);
            const borderLeft = style.borderLeftWidth;
            const borderRight = style.borderRightWidth;
            console.log("🧪 CSS Test:", { borderLeft, borderRight });
            
            if ((borderLeft && borderLeft !== "0px") || (borderRight && borderRight !== "0px")) {
                document.getElementById("mas-css-test").innerHTML = "✅ CSS załadowany (border: " + borderLeft + "/" + borderRight + ")";
                document.getElementById("mas-css-test").style.color = "green";
            } else {
                document.getElementById("mas-css-test").innerHTML = "❌ CSS nie załadowany - brak border";
                document.getElementById("mas-css-test").style.color = "red";
            }
        }
        
        // Test czy MenuManager jest załadowany
        if (typeof window.MenuManager !== "undefined" || typeof MenuManager !== "undefined") {
            console.log("✅ MenuManager jest dostępny");
            
            // Sprawdź czy MenuManager został zainicjalizowany
            if (window.MenuManager && window.MenuManager.isInitialized) {
                console.log("✅ MenuManager jest zainicjalizowany");
            } else {
                console.log("❌ MenuManager nie jest zainicjalizowany");
            }
        } else {
            console.log("❌ MenuManager nie jest dostępny");
        }
        
        // Test submenu visibility
        setTimeout(() => {
            const menuItems = document.querySelectorAll("#adminmenu li.menu-top");
            let submenuCount = 0;
            let visibleSubmenuCount = 0;
            
            menuItems.forEach(item => {
                const submenu = item.querySelector(".wp-submenu");
                if (submenu) {
                    submenuCount++;
                    const style = getComputedStyle(submenu);
                    if (style.display !== "none" && style.visibility !== "hidden") {
                        visibleSubmenuCount++;
                    }
                }
            });
            
            console.log("🧪 Submenu Test:", {
                totalSubmenus: submenuCount,
                visibleSubmenus: visibleSubmenuCount,
                isFloatingMode: document.body.classList.contains("mas-v2-menu-floating")
            });
            
            // Test hover functionality
            if (submenuCount > 0) {
                const firstMenuWithSubmenu = document.querySelector("#adminmenu li.menu-top .wp-submenu")?.parentElement;
                if (firstMenuWithSubmenu) {
                    console.log("🧪 Testing hover on first menu item with submenu");
                    firstMenuWithSubmenu.dispatchEvent(new Event("mouseenter"));
                    
                    setTimeout(() => {
                        const submenu = firstMenuWithSubmenu.querySelector(".wp-submenu");
                        const style = getComputedStyle(submenu);
                        console.log("🧪 Submenu after hover:", {
                            display: style.display,
                            visibility: style.visibility,
                            opacity: style.opacity
                        });
                    }, 100);
                }
            }
        }, 1000);
        
        // Test czy ustawienia są przekazane do JS
        if (typeof masV2Global !== "undefined") {
            console.log("✅ masV2Global jest dostępny:", masV2Global);
            console.log("🧪 Settings z PHP:", masV2Global.settings);
        } else {
            console.log("❌ masV2Global nie jest dostępny");
        }
        
        // Test CSS Variables
        const root = document.documentElement;
        const menuEnabled = getComputedStyle(root).getPropertyValue("--mas-menu-enabled");
        const menuBg = getComputedStyle(root).getPropertyValue("--mas-menu-bg-color");
        const menuHoverText = getComputedStyle(root).getPropertyValue("--mas-menu-hover-text");
        console.log("🧪 CSS Variables:", { 
            "--mas-menu-enabled": menuEnabled.trim(),
            "--mas-menu-bg-color": menuBg.trim(),
            "--mas-menu-hover-text": menuHoverText.trim()
        });
        
        // Test body classes
        const hasCustomClass = document.body.classList.contains("mas-v2-menu-custom-enabled");
        console.log("🧪 Body ma mas-v2-menu-custom-enabled:", hasCustomClass);
        </script>';
        
        echo '</div>';
    }
    
    /**
     * Legacy: Renderowanie strony administracyjnej  
     */
    public function renderAdminPage() {
        // Include debugger CSS
        include MAS_V2_PLUGIN_DIR . 'debug-css-output.php';
        
        $settings = $this->getSettings();
        $tabs = $this->getTabs();
        
        // Używaj nowego template jeśli istnieje
        $newTemplate = MAS_V2_PLUGIN_DIR . 'src/views/admin-page.php';
        if (file_exists($newTemplate)) {
            // Dodaj zmienną dostępną w template
            $plugin_instance = $this;
            include $newTemplate;
        } else {
            // Fallback do starego template
        include MAS_V2_PLUGIN_DIR . 'templates/admin-page.php';
        }
    }

    /**
     * Renderowanie strony poszczególnych zakładek
     */
    public function renderTabPage() {
        $settings = $this->getSettings();
        
        // Określ aktywną zakładkę na podstawie URL
        $current_page = $_GET['page'] ?? '';
        $active_tab = 'general';
        
        switch ($current_page) {
            case 'mas-v2-general':
                $active_tab = 'general';
                break;
            case 'mas-v2-admin-bar':
                $active_tab = 'admin-bar';
                break;
            case 'mas-v2-menu':
                $active_tab = 'menu';
                break;
            case 'mas-v2-content':
                $active_tab = 'content';
                break;
            case 'mas-v2-buttons':
                $active_tab = 'buttons';
                break;
            case 'mas-v2-login':
                $active_tab = 'login';
                break;
            case 'mas-v2-typography':
                $active_tab = 'typography';
                break;
            case 'mas-v2-effects':
                $active_tab = 'effects';
                break;
            case 'mas-v2-templates':
                $active_tab = 'templates';
                break;
            case 'mas-v2-advanced':
                $active_tab = 'advanced';
                break;
        }
        
        // Sprawdź czy formularz został wysłany
        if (isset($_POST['mas_v2_nonce']) && wp_verify_nonce($_POST['mas_v2_nonce'], 'mas_v2_nonce')) {
            $settings = $this->sanitizeSettings($_POST);
            update_option('mas_v2_settings', $settings);
            
            echo '<div class="notice notice-success is-dismissible"><p>' . 
                 __('Ustawienia zostały zapisane!', 'modern-admin-styler-v2') . 
                 '</p></div>';
        }
        
        // Załaduj template z aktywną zakładką
        $plugin_instance = $this;
        include MAS_V2_PLUGIN_DIR . 'src/views/admin-page.php';
    }
    
    /**
     * Enhanced AJAX Settings Save with improved error handling and validation - Task 14 Security Enhanced
     */
    public function ajaxSaveSettings() {
        // Task 14: Enhanced security validation
        $security_result = $this->validateAjaxSecurity('save_settings');
        if (!$security_result['valid']) {
            wp_send_json_error($security_result['error']);
            return;
        }
        
        // Task 14: Additional request validation
        if (!$this->validateAjaxRequest($_POST)) {
            wp_send_json_error([
                'message' => __('Invalid request data detected.', 'modern-admin-styler-v2'),
                'code' => 'invalid_request'
            ]);
            return;
        }
        
        try {
            // Validate input data before processing
            if (empty($_POST) || count($_POST) < 2) {
                wp_send_json_error([
                    'message' => __('No settings data received.', 'modern-admin-styler-v2'),
                    'code' => 'no_data'
                ]);
            }
            
            // Debug: Log AJAX save attempt
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $input_count = count($_POST);
                error_log("MAS V2: ajaxSaveSettings called with {$input_count} POST values");
                
                // Log first 10 keys to see what's being sent
                $keys = array_keys($_POST);
                $first_keys = array_slice($keys, 0, 10);
                error_log("MAS V2: First 10 POST keys: " . implode(', ', $first_keys));
                
                // Count different types
                $menu_count = count(array_filter($keys, function($k) { return strpos($k, 'menu_') === 0; }));
                $admin_bar_count = count(array_filter($keys, function($k) { return strpos($k, 'admin_bar_') === 0; }));
                error_log("MAS V2: Menu fields: {$menu_count}, Admin Bar fields: {$admin_bar_count}");
            }
            
            // Create backup of current settings before saving
            $current_settings = get_option('mas_v2_settings', []);
            $backup_key = 'mas_v2_settings_backup_' . time();
            update_option($backup_key, $current_settings, false);
            
            // Enhanced sanitization with error tracking
            $sanitization_errors = [];
            $settings = $this->sanitizeSettingsWithErrorTracking($_POST, $sanitization_errors);
            
            // Validate settings integrity
            $validation_errors = [];
            $settings = $this->validateSettingsIntegrityWithErrors($settings, $validation_errors);
            
            // Check for critical validation errors
            if (!empty($validation_errors['critical'])) {
                wp_send_json_error([
                    'message' => __('Critical validation errors prevented saving settings.', 'modern-admin-styler-v2'),
                    'code' => 'validation_failed',
                    'errors' => $validation_errors['critical']
                ]);
            }
            
            // Task 14: Use secure storage with integrity checking
            $save_result = $this->secureStoreSettings($settings);
            
            // Verify the save was successful
            $saved_settings = get_option('mas_v2_settings', []);
            $save_verified = !empty($saved_settings) && count($saved_settings) > 10;
            
            if (!$save_verified) {
                // Restore backup if save failed
                update_option('mas_v2_settings', $current_settings);
                wp_send_json_error([
                    'message' => __('Failed to save settings to database. Settings restored from backup.', 'modern-admin-styler-v2'),
                    'code' => 'save_failed'
                ]);
            }
            
            // Test CSS generation to ensure settings are working
            $test_css = $this->generateMenuCSS($settings);
            $css_generated = !empty($test_css) && strlen($test_css) > 50;
            
            // Clear any caches
            $this->clearCache();
            
            // Clean up old backups (keep only last 5)
            $this->cleanupSettingsBackups();
            
            // Debug logging
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("MAS V2: Settings save successful. Count: " . count($settings) . ", CSS generated: " . ($css_generated ? 'yes' : 'no'));
                
                // Log which settings were actually saved
                $menu_saved = count(array_filter($settings, function($k) { return strpos($k, 'menu_') === 0; }, ARRAY_FILTER_USE_KEY));
                $admin_bar_saved = count(array_filter($settings, function($k) { return strpos($k, 'admin_bar_') === 0; }, ARRAY_FILTER_USE_KEY));
                error_log("MAS V2: Saved - Menu: {$menu_saved}, Admin Bar: {$admin_bar_saved}");
                
                // Log a few specific values to verify
                if (isset($settings['menu_bg'])) error_log("MAS V2: menu_bg = " . $settings['menu_bg']);
                if (isset($settings['menu_width'])) error_log("MAS V2: menu_width = " . $settings['menu_width']);
                if (isset($settings['admin_bar_height'])) error_log("MAS V2: admin_bar_height = " . $settings['admin_bar_height']);
            }
            
            // Prepare success response with comprehensive data
            $response_data = [
                'message' => __('Settings saved successfully!', 'modern-admin-styler-v2'),
                'settings_count' => count($settings),
                'css_generated' => $css_generated,
                'save_result' => $save_result,
                'timestamp' => current_time('mysql')
            ];
            
            // Include warnings if any
            if (!empty($sanitization_errors) || !empty($validation_errors['warnings'])) {
                $response_data['warnings'] = array_merge(
                    $sanitization_errors,
                    $validation_errors['warnings'] ?? []
                );
            }
            
            wp_send_json_success($response_data);
            
        } catch (Exception $e) {
            // Enhanced error logging and recovery
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MAS V2: ajaxSaveSettings error: ' . $e->getMessage());
                error_log('MAS V2: Error trace: ' . $e->getTraceAsString());
            }
            
            // Attempt to restore from backup if available
            if (isset($current_settings) && !empty($current_settings)) {
                update_option('mas_v2_settings', $current_settings);
            }
            
            wp_send_json_error([
                'message' => sprintf(
                    __('An error occurred while saving settings: %s', 'modern-admin-styler-v2'),
                    $e->getMessage()
                ),
                'code' => 'exception_error',
                'restored_backup' => isset($current_settings) && !empty($current_settings)
            ]);
        }
    }
    
    /**
     * Enhanced AJAX Reset Settings with improved error handling - Task 14 Security Enhanced
     */
    public function ajaxResetSettings() {
        // Task 14: Enhanced security validation
        $security_result = $this->validateAjaxSecurity('reset_settings');
        if (!$security_result['valid']) {
            wp_send_json_error($security_result['error']);
            return;
        }
        
        // Task 14: Additional request validation
        if (!$this->validateAjaxRequest($_POST)) {
            wp_send_json_error([
                'message' => __('Invalid request data detected.', 'modern-admin-styler-v2'),
                'code' => 'invalid_request'
            ]);
            return;
        }
        
        try {
            // Create backup of current settings before reset
            $current_settings = get_option('mas_v2_settings', []);
            if (!empty($current_settings)) {
                $backup_key = 'mas_v2_settings_backup_before_reset_' . time();
                update_option($backup_key, $current_settings, false);
            }
            
            // Get and validate default settings
            $defaults = $this->getDefaultSettings();
            if (empty($defaults) || !is_array($defaults)) {
                wp_send_json_error([
                    'message' => __('Failed to load default settings.', 'modern-admin-styler-v2'),
                    'code' => 'defaults_failed'
                ]);
            }
            
            // Apply validation to defaults (just in case)
            $validation_errors = [];
            $validated_defaults = $this->validateSettingsIntegrityWithErrors($defaults, $validation_errors);
            
            // Save the reset settings
            $reset_result = update_option('mas_v2_settings', $validated_defaults);
            
            // Verify the reset was successful
            $saved_settings = get_option('mas_v2_settings', []);
            if (empty($saved_settings) || count($saved_settings) < 10) {
                // Restore backup if reset failed
                if (!empty($current_settings)) {
                    update_option('mas_v2_settings', $current_settings);
                }
                wp_send_json_error([
                    'message' => __('Failed to reset settings. Previous settings restored.', 'modern-admin-styler-v2'),
                    'code' => 'reset_failed'
                ]);
            }
            
            // Clear caches
            $this->clearCache();
            
            // Clean up old backups
            $this->cleanupSettingsBackups();
            
            // Debug logging
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MAS V2: Settings reset successful. Default settings count: ' . count($validated_defaults));
            }
            
            wp_send_json_success([
                'message' => __('Settings have been reset to defaults successfully!', 'modern-admin-styler-v2'),
                'settings_count' => count($validated_defaults),
                'backup_created' => !empty($current_settings),
                'warnings' => $validation_errors['warnings'] ?? []
            ]);
            
        } catch (Exception $e) {
            // Enhanced error logging and recovery
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MAS V2: ajaxResetSettings error: ' . $e->getMessage());
            }
            
            // Attempt to restore from backup if available
            if (isset($current_settings) && !empty($current_settings)) {
                update_option('mas_v2_settings', $current_settings);
            }
            
            wp_send_json_error([
                'message' => sprintf(
                    __('An error occurred while resetting settings: %s', 'modern-admin-styler-v2'),
                    $e->getMessage()
                ),
                'code' => 'exception_error',
                'restored_backup' => isset($current_settings) && !empty($current_settings)
            ]);
        }
    }
    
    /**
     * AJAX Export ustawień - Enhanced for Task 12 - Task 14 Security Enhanced
     */
    public function ajaxExportSettings() {
        // Task 14: Enhanced security validation
        $security_result = $this->validateAjaxSecurity('export_settings');
        if (!$security_result['valid']) {
            wp_send_json_error($security_result['error']);
            return;
        }
        
        // Task 14: Additional request validation
        if (!$this->validateAjaxRequest($_POST)) {
            wp_send_json_error([
                'message' => __('Invalid request data detected.', 'modern-admin-styler-v2'),
                'code' => 'invalid_request'
            ]);
            return;
        }
        
        try {
            $settings = $this->getSettings();
            
            // Validate settings before export
            if (empty($settings)) {
                wp_send_json_error([
                    'message' => __('No settings found to export.', 'modern-admin-styler-v2'),
                    'code' => 'no_settings'
                ]);
            }
            
            // Task 14: Use secure export with enhanced validation
            $export_data = $this->secureExportSettings($settings);
            $export_data['export_type'] = sanitize_text_field($_POST['export_type'] ?? 'full');
            
            // Add backup information if available
            $recent_backups = $this->getRecentBackups(3);
            if (!empty($recent_backups)) {
                $export_data['backup_info'] = [
                    'has_backups' => true,
                    'backup_count' => count($recent_backups),
                    'latest_backup' => $recent_backups[0]['date'] ?? null
                ];
            }
            
            // Generate filename with more context
            $site_name = sanitize_file_name(get_bloginfo('name'));
            $filename = sprintf(
                'mas-v2-settings-%s-%s.json',
                $site_name ? $site_name . '-' : '',
                date('Y-m-d-H-i-s')
            );
            
            wp_send_json_success([
                'data' => $export_data,
                'filename' => $filename,
                'settings_count' => count($settings),
                'export_size' => strlen(json_encode($export_data)),
                'message' => sprintf(
                    __('Successfully exported %d settings.', 'modern-admin-styler-v2'),
                    count($settings)
                )
            ]);
            
        } catch (Exception $e) {
            error_log('MAS V2 Export Error: ' . $e->getMessage());
            wp_send_json_error([
                'message' => sprintf(
                    __('Export failed: %s', 'modern-admin-styler-v2'),
                    $e->getMessage()
                ),
                'code' => 'export_exception'
            ]);
        }
    }
    
    /**
     * AJAX Import ustawień - Enhanced for Task 12 - Task 14 Security Enhanced
     */
    public function ajaxImportSettings() {
        // Task 14: Enhanced security validation
        $security_result = $this->validateAjaxSecurity('import_settings');
        if (!$security_result['valid']) {
            wp_send_json_error($security_result['error']);
            return;
        }
        
        // Task 14: Additional request validation for file uploads
        if (!$this->validateFileUploadRequest($_POST, $_FILES)) {
            wp_send_json_error([
                'message' => __('Invalid file upload request detected.', 'modern-admin-styler-v2'),
                'code' => 'invalid_upload'
            ]);
            return;
        }
        
        try {
            // Create backup of current settings before import
            $current_settings = get_option('mas_v2_settings', []);
            $backup_key = 'mas_v2_settings_backup_before_import_' . time();
            update_option($backup_key, $current_settings, false);
            
            // Validate and parse import data
            $raw_data = stripslashes($_POST['data'] ?? '');
            if (empty($raw_data)) {
                wp_send_json_error([
                    'message' => __('No import data received.', 'modern-admin-styler-v2'),
                    'code' => 'no_data'
                ]);
            }
            
            $import_data = json_decode($raw_data, true);
            
            // Enhanced validation for corrupted files
            if (json_last_error() !== JSON_ERROR_NONE) {
                wp_send_json_error([
                    'message' => sprintf(
                        __('Invalid JSON format: %s', 'modern-admin-styler-v2'),
                        json_last_error_msg()
                    ),
                    'code' => 'invalid_json'
                ]);
            }
            
            // Validate file structure
            $validation_result = $this->validateImportData($import_data);
            if (!$validation_result['valid']) {
                wp_send_json_error([
                    'message' => $validation_result['message'],
                    'code' => 'validation_failed',
                    'details' => $validation_result['details'] ?? []
                ]);
            }
            
            // Extract settings with fallback for different formats
            $settings_to_import = [];
            if (isset($import_data['settings'])) {
                $settings_to_import = $import_data['settings'];
            } elseif (is_array($import_data) && !isset($import_data['format_version'])) {
                // Legacy format - assume the entire array is settings
                $settings_to_import = $import_data;
            }
            
            if (empty($settings_to_import)) {
                wp_send_json_error([
                    'message' => __('No settings found in import file.', 'modern-admin-styler-v2'),
                    'code' => 'no_settings_in_file'
                ]);
            }
            
            // Task 14: Use secure import with enhanced validation
            $import_result = $this->secureImportSettings($settings_to_import);
            
            if (!$import_result['success']) {
                // Restore backup if import failed
                update_option('mas_v2_settings', $current_settings);
                wp_send_json_error([
                    'message' => __('Import failed validation. Original settings restored.', 'modern-admin-styler-v2'),
                    'code' => 'import_failed',
                    'errors' => $import_result['errors']
                ]);
            }
            
            // Apply imported settings using secure storage
            $final_settings = $import_result['settings'];
            $update_result = $this->secureStoreSettings($final_settings);
            
            if (!$update_result) {
                // Restore backup if update failed
                update_option('mas_v2_settings', $current_settings);
                wp_send_json_error([
                    'message' => __('Failed to save imported settings. Original settings restored.', 'modern-admin-styler-v2'),
                    'code' => 'save_failed'
                ]);
            }
            
            // Verify the import was successful
            $saved_settings = get_option('mas_v2_settings', []);
            if (empty($saved_settings) || count($saved_settings) < 5) {
                // Restore backup if verification failed
                update_option('mas_v2_settings', $current_settings);
                wp_send_json_error([
                    'message' => __('Import verification failed. Original settings restored.', 'modern-admin-styler-v2'),
                    'code' => 'verification_failed'
                ]);
            }
            
            // Clear cache and cleanup old backups
            $this->clearCache();
            $this->cleanupSettingsBackups();
            
            // Prepare success response with detailed information
            $response_data = [
                'message' => sprintf(
                    __('Successfully imported %d settings!', 'modern-admin-styler-v2'),
                    count($final_settings)
                ),
                'imported_count' => count($final_settings),
                'backup_created' => true,
                'backup_key' => $backup_key
            ];
            
            // Task 14: Add warnings from secure import
            if (!empty($import_result['warnings'])) {
                $response_data['warnings'] = array_slice($import_result['warnings'], 0, 5);
                $response_data['warning_count'] = count($import_result['warnings']);
            }
            
            // Add import metadata if available
            if (isset($import_data['plugin_version'])) {
                $response_data['source_version'] = $import_data['plugin_version'];
                if ($import_data['plugin_version'] !== MAS_V2_VERSION) {
                    $response_data['version_mismatch'] = true;
                }
            }
            
            wp_send_json_success($response_data);
            
        } catch (Exception $e) {
            // Restore backup on any exception
            if (isset($current_settings) && !empty($current_settings)) {
                update_option('mas_v2_settings', $current_settings);
            }
            
            error_log('MAS V2 Import Error: ' . $e->getMessage());
            wp_send_json_error([
                'message' => sprintf(
                    __('Import failed: %s Original settings have been restored.', 'modern-admin-styler-v2'),
                    $e->getMessage()
                ),
                'code' => 'import_exception',
                'restored_backup' => isset($current_settings) && !empty($current_settings)
            ]);
        }
    }
    
    /**
     * AJAX Live Preview
     */
    public function ajaxLivePreview() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mas_v2_nonce')) {
            wp_send_json_error(['message' => __('Błąd bezpieczeństwa', 'modern-admin-styler-v2')]);
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Brak uprawnień', 'modern-admin-styler-v2')]);
        }
        
        try {
            $settings = $this->sanitizeSettings($_POST);
            $css = $this->generateCSSVariables($settings);
            $css .= $this->generateAdminCSS($settings);
            
            wp_send_json_success([
                'css' => $css
            ]);
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * AJAX: Get Preview CSS - Simple live preview (inspired by working version)
     */
    public function ajaxGetPreviewCSS() {
        // Security check
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mas_v2_nonce')) {
            wp_send_json_error(['message' => __('Security error', 'modern-admin-styler-v2')]);
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'modern-admin-styler-v2')]);
        }
        
        $start_time = microtime(true);
        
        try {
            // Get current settings
            $settings = $this->getSettings();
            
            // Update single setting if provided
            if (isset($_POST['setting']) && isset($_POST['value'])) {
                $setting_key = sanitize_key($_POST['setting']);
                $value = wp_unslash($_POST['value']);
                
                // Sanitize value based on type
                if (is_numeric($value)) {
                    $settings[$setting_key] = intval($value);
                } elseif (preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
                    $settings[$setting_key] = sanitize_hex_color($value);
                } elseif ($value === 'true' || $value === 'false') {
                    $settings[$setting_key] = ($value === 'true');
                } else {
                    $settings[$setting_key] = sanitize_text_field($value);
                }
            }
            
            // Generate CSS
            $css = '';
            $css .= $this->generateCSSVariables($settings);
            $css .= $this->generateAdminCSS($settings);
            $css .= $this->generateMenuCSS($settings);
            $css .= $this->generateAdminBarCSS($settings);
            
            $execution_time = round((microtime(true) - $start_time) * 1000, 2);
            
            wp_send_json_success([
                'css' => $css,
                'performance' => [
                    'execution_time_ms' => $execution_time,
                    'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2)
                ]
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage(),
                'code' => 'css_generation_failed'
            ]);
        }
    }

    /**
     * AJAX: Zapisz preferencje motywu (jasny/ciemny)
     */
    public function ajaxSaveTheme() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mas_v2_nonce')) {
            wp_send_json_error(['message' => __('Błąd bezpieczeństwa', 'modern-admin-styler-v2')]);
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Brak uprawnień', 'modern-admin-styler-v2')]);
        }
        
        $theme = sanitize_text_field($_POST['theme'] ?? 'light');
        
        // Walidacja motywu
        if (!in_array($theme, ['light', 'dark'])) {
            wp_send_json_error([
                'message' => __('Nieprawidłowy motyw', 'modern-admin-styler-v2')
            ]);
        }
        
        // Zapisz motyw w user meta
        $user_id = get_current_user_id();
        update_user_meta($user_id, 'mas_v2_theme_preference', $theme);
        
        // Zapisz też w opcjach plugin (jako backup)
        $settings = $this->getSettings();
        $settings['theme'] = $theme;
        update_option('mas_v2_settings', $settings);
        
        wp_send_json_success([
            'theme' => $theme,
            'message' => sprintf(__('Motyw %s został zapisany', 'modern-admin-styler-v2'), 
                $theme === 'dark' ? 'ciemny' : 'jasny')
        ]);
    }
    
    /**
     * AJAX: Diagnostics for settings-to-CSS connection
     */
    public function ajaxDiagnostics() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mas_v2_nonce')) {
            wp_send_json_error(['message' => __('Błąd bezpieczeństwa', 'modern-admin-styler-v2')]);
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Brak uprawnień', 'modern-admin-styler-v2')]);
        }
        
        $diagnostics = $this->verifySettingsConnection();
        
        // Add additional runtime diagnostics
        $diagnostics['current_page'] = $_SERVER['HTTP_REFERER'] ?? 'unknown';
        $diagnostics['wp_debug'] = defined('WP_DEBUG') && WP_DEBUG;
        $diagnostics['plugin_version'] = MAS_V2_VERSION;
        $diagnostics['wordpress_version'] = get_bloginfo('version');
        
        // Test database connection
        $db_settings = get_option('mas_v2_settings', []);
        $diagnostics['db_settings_count'] = count($db_settings);
        $diagnostics['db_connection_ok'] = !empty($db_settings);
        
        wp_send_json_success([
            'message' => __('Diagnostyka zakończona', 'modern-admin-styler-v2'),
            'diagnostics' => $diagnostics
        ]);
    }
    
    /**
     * AJAX: List available backups - Task 12 Enhancement
     */
    public function ajaxListBackups() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mas_v2_nonce')) {
            wp_send_json_error([
                'message' => __('Security verification failed.', 'modern-admin-styler-v2'),
                'code' => 'invalid_nonce'
            ]);
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('Insufficient permissions.', 'modern-admin-styler-v2'),
                'code' => 'insufficient_permissions'
            ]);
        }
        
        try {
            $backups = $this->getRecentBackups(10); // Get up to 10 recent backups
            
            // Add additional metadata for each backup
            foreach ($backups as &$backup) {
                $backup_data = get_option($backup['key'], []);
                $backup['settings_count'] = is_array($backup_data) ? count($backup_data) : 0;
                $backup['has_menu_settings'] = $this->hasMenuCustomizations($backup_data);
                $backup['readable_date'] = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $backup['timestamp']);
            }
            
            wp_send_json_success([
                'backups' => $backups,
                'total_count' => count($backups),
                'message' => sprintf(
                    __('Found %d backup(s).', 'modern-admin-styler-v2'),
                    count($backups)
                )
            ]);
            
        } catch (Exception $e) {
            error_log('MAS V2 List Backups Error: ' . $e->getMessage());
            wp_send_json_error([
                'message' => sprintf(
                    __('Failed to list backups: %s', 'modern-admin-styler-v2'),
                    $e->getMessage()
                ),
                'code' => 'list_backups_failed'
            ]);
        }
    }
    
    /**
     * AJAX: Restore settings from backup - Task 12 Enhancement
     */
    public function ajaxRestoreBackup() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mas_v2_nonce')) {
            wp_send_json_error([
                'message' => __('Security verification failed.', 'modern-admin-styler-v2'),
                'code' => 'invalid_nonce'
            ]);
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('Insufficient permissions.', 'modern-admin-styler-v2'),
                'code' => 'insufficient_permissions'
            ]);
        }
        
        $backup_key = sanitize_text_field($_POST['backup_key'] ?? '');
        if (empty($backup_key) || strpos($backup_key, 'mas_v2_settings_backup_') !== 0) {
            wp_send_json_error([
                'message' => __('Invalid backup key.', 'modern-admin-styler-v2'),
                'code' => 'invalid_backup_key'
            ]);
        }
        
        try {
            // Create backup of current settings before restore
            $current_settings = get_option('mas_v2_settings', []);
            $safety_backup_key = 'mas_v2_settings_backup_before_restore_' . time();
            update_option($safety_backup_key, $current_settings, false);
            
            // Get backup data
            $backup_data = get_option($backup_key, []);
            if (empty($backup_data)) {
                wp_send_json_error([
                    'message' => __('Backup not found or is empty.', 'modern-admin-styler-v2'),
                    'code' => 'backup_not_found'
                ]);
            }
            
            // Validate and sanitize backup data
            $sanitized_backup = $this->sanitizeSettingsForImport($backup_data);
            
            if (empty($sanitized_backup['settings'])) {
                wp_send_json_error([
                    'message' => __('Backup data is corrupted or invalid.', 'modern-admin-styler-v2'),
                    'code' => 'backup_corrupted',
                    'errors' => $sanitized_backup['errors'] ?? []
                ]);
            }
            
            // Apply backup settings
            $restore_result = update_option('mas_v2_settings', $sanitized_backup['settings']);
            
            if (!$restore_result) {
                // Restore current settings if backup restore failed
                update_option('mas_v2_settings', $current_settings);
                wp_send_json_error([
                    'message' => __('Failed to restore backup. Current settings preserved.', 'modern-admin-styler-v2'),
                    'code' => 'restore_failed'
                ]);
            }
            
            // Verify restore was successful
            $restored_settings = get_option('mas_v2_settings', []);
            if (empty($restored_settings) || count($restored_settings) < 5) {
                // Restore current settings if verification failed
                update_option('mas_v2_settings', $current_settings);
                wp_send_json_error([
                    'message' => __('Backup restore verification failed. Current settings preserved.', 'modern-admin-styler-v2'),
                    'code' => 'restore_verification_failed'
                ]);
            }
            
            // Clear cache
            $this->clearCache();
            
            // Extract timestamp from backup key for response
            $timestamp = 0;
            if (preg_match('/backup_(\d+)/', $backup_key, $matches)) {
                $timestamp = intval($matches[1]);
            }
            
            wp_send_json_success([
                'message' => sprintf(
                    __('Successfully restored settings from backup created on %s.', 'modern-admin-styler-v2'),
                    $timestamp ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $timestamp) : __('unknown date', 'modern-admin-styler-v2')
                ),
                'restored_count' => count($restored_settings),
                'safety_backup_created' => $safety_backup_key,
                'warnings' => $sanitized_backup['warnings'] ?? []
            ]);
            
        } catch (Exception $e) {
            // Restore current settings on any exception
            if (isset($current_settings) && !empty($current_settings)) {
                update_option('mas_v2_settings', $current_settings);
            }
            
            error_log('MAS V2 Restore Backup Error: ' . $e->getMessage());
            wp_send_json_error([
                'message' => sprintf(
                    __('Restore failed: %s Current settings have been preserved.', 'modern-admin-styler-v2'),
                    $e->getMessage()
                ),
                'code' => 'restore_exception'
            ]);
        }
    }
    
    /**
     * AJAX: Create manual backup - Task 12 Enhancement
     */
    public function ajaxCreateBackup() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mas_v2_nonce')) {
            wp_send_json_error([
                'message' => __('Security verification failed.', 'modern-admin-styler-v2'),
                'code' => 'invalid_nonce'
            ]);
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('Insufficient permissions.', 'modern-admin-styler-v2'),
                'code' => 'insufficient_permissions'
            ]);
        }
        
        try {
            $current_settings = get_option('mas_v2_settings', []);
            
            if (empty($current_settings)) {
                wp_send_json_error([
                    'message' => __('No settings found to backup.', 'modern-admin-styler-v2'),
                    'code' => 'no_settings'
                ]);
            }
            
            // Create manual backup with descriptive name
            $backup_name = sanitize_text_field($_POST['backup_name'] ?? '');
            $timestamp = time();
            
            if (!empty($backup_name)) {
                $backup_key = 'mas_v2_settings_backup_manual_' . sanitize_key($backup_name) . '_' . $timestamp;
            } else {
                $backup_key = 'mas_v2_settings_backup_manual_' . $timestamp;
            }
            
            $backup_result = update_option($backup_key, $current_settings, false);
            
            if (!$backup_result) {
                wp_send_json_error([
                    'message' => __('Failed to create backup.', 'modern-admin-styler-v2'),
                    'code' => 'backup_creation_failed'
                ]);
            }
            
            // Clean up old backups
            $this->cleanupSettingsBackups();
            
            wp_send_json_success([
                'message' => __('Backup created successfully.', 'modern-admin-styler-v2'),
                'backup_key' => $backup_key,
                'backup_name' => $backup_name ?: __('Manual Backup', 'modern-admin-styler-v2'),
                'settings_count' => count($current_settings),
                'created_at' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $timestamp)
            ]);
            
        } catch (Exception $e) {
            error_log('MAS V2 Create Backup Error: ' . $e->getMessage());
            wp_send_json_error([
                'message' => sprintf(
                    __('Backup creation failed: %s', 'modern-admin-styler-v2'),
                    $e->getMessage()
                ),
                'code' => 'backup_exception'
            ]);
        }
    }
    
    /**
     * AJAX: Delete specific backup - Task 12 Enhancement
     */
    public function ajaxDeleteBackup() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mas_v2_nonce')) {
            wp_send_json_error([
                'message' => __('Security verification failed.', 'modern-admin-styler-v2'),
                'code' => 'invalid_nonce'
            ]);
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('Insufficient permissions.', 'modern-admin-styler-v2'),
                'code' => 'insufficient_permissions'
            ]);
        }
        
        $backup_key = sanitize_text_field($_POST['backup_key'] ?? '');
        if (empty($backup_key) || strpos($backup_key, 'mas_v2_settings_backup_') !== 0) {
            wp_send_json_error([
                'message' => __('Invalid backup key.', 'modern-admin-styler-v2'),
                'code' => 'invalid_backup_key'
            ]);
        }
        
        try {
            // Check if backup exists
            $backup_data = get_option($backup_key, null);
            if ($backup_data === null) {
                wp_send_json_error([
                    'message' => __('Backup not found.', 'modern-admin-styler-v2'),
                    'code' => 'backup_not_found'
                ]);
            }
            
            // Delete the backup
            $delete_result = delete_option($backup_key);
            
            if (!$delete_result) {
                wp_send_json_error([
                    'message' => __('Failed to delete backup.', 'modern-admin-styler-v2'),
                    'code' => 'delete_failed'
                ]);
            }
            
            wp_send_json_success([
                'message' => __('Backup deleted successfully.', 'modern-admin-styler-v2'),
                'deleted_backup' => $backup_key
            ]);
            
        } catch (Exception $e) {
            error_log('MAS V2 Delete Backup Error: ' . $e->getMessage());
            wp_send_json_error([
                'message' => sprintf(
                    __('Backup deletion failed: %s', 'modern-admin-styler-v2'),
                    $e->getMessage()
                ),
                'code' => 'delete_exception'
            ]);
        }
    }
    
    /**
     * Wyjście niestandardowych stylów do admin head
     */
    public function outputCustomStyles() {
        if (!is_admin() || $this->isLoginPage()) {
            return;
        }
        
        $settings = $this->getSettings();
        
        if (empty($settings)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MAS V2: outputCustomStyles - No settings found');
            }
            return;
        }
        
        // Sprawdź czy wtyczka jest włączona
        if (!isset($settings['enable_plugin']) || !$settings['enable_plugin']) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MAS V2: outputCustomStyles - Plugin disabled');
            }
            return;
        }
        
        // Debug: Log settings retrieval
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $total_settings = count($settings);
            $menu_settings = count(array_filter($settings, function($key) {
                return strpos($key, 'menu_') === 0;
            }, ARRAY_FILTER_USE_KEY));
            error_log("MAS V2: outputCustomStyles - Retrieved {$total_settings} settings ({$menu_settings} menu settings)");
        }
        
        // Generate CSS - SIMPLIFIED (no duplicates)
        $css = '';
        $css .= $this->generateCSSVariables($settings);
        $css .= $this->generateMenuCSS($settings);
        $css .= $this->generateAdminBarCSS($settings);
        $css .= $this->generateContentCSS($settings);
        $css .= $this->generateButtonCSS($settings);
        $css .= $this->generateFormCSS($settings);
        $css .= $this->generateAdvancedCSS($settings);
        
        // Debug: Log CSS generation results
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $css_length = strlen($css);
            $has_menu_vars = strpos($css, '--mas-menu-') !== false;
            error_log("MAS V2: Generated CSS length: {$css_length} chars, contains menu vars: " . ($has_menu_vars ? 'yes' : 'no'));
            
            // Log first 200 chars of CSS for debugging
            if ($css_length > 0) {
                error_log("MAS V2: CSS preview: " . substr($css, 0, 200) . ($css_length > 200 ? '...' : ''));
            }
        }
        
        if (!empty($css)) {
            echo "<style id='mas-v2-dynamic-styles'>\n";
            echo $css;
            echo "\n</style>\n";
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MAS V2: No CSS generated - outputting debug comment');
            }
            echo "<!-- MAS V2: No CSS generated -->\n";
        }
        
        // DODATKOWY AGGRESSIVE CSS dla floating admin bar
        if (isset($settings['admin_bar_floating']) && $settings['admin_bar_floating']) {
            $marginTop = $settings['admin_bar_margin_top'] ?? 10;
            $marginRight = $settings['admin_bar_margin_right'] ?? 10;
            $marginBottom = $settings['admin_bar_margin_bottom'] ?? 10;
            $marginLeft = $settings['admin_bar_margin_left'] ?? 10;
            $adminBarHeight = $settings['admin_bar_height'] ?? 32;
            
            echo "<style id='mas-v2-floating-override' type='text/css'>\n";
            echo "
            /* ULTRA AGGRESSIVE FLOATING ADMIN BAR */
            #wpadminbar,
            html #wpadminbar,
            html.wp-toolbar #wpadminbar,
            body #wpadminbar,
            #wpbody #wpadminbar {
                position: relative !important;
                top: {$marginTop}px !important;
                left: {$marginLeft}px !important;
                right: {$marginRight}px !important;
                margin: {$marginTop}px {$marginRight}px {$marginBottom}px {$marginLeft}px !important;
                width: calc(100% - " . ($marginLeft + $marginRight) . "px) !important;
                max-width: calc(100% - " . ($marginLeft + $marginRight) . "px) !important;
                z-index: 99999 !important;
                border-radius: 8px !important;
                box-shadow: 0 4px 20px rgba(0,0,0,0.15) !important;
                transform: none !important;
            }
            
            html.wp-toolbar,
            html.wp-toolbar body {
                padding-top: 0 !important;
                margin-top: " . ($marginTop + $adminBarHeight + $marginBottom) . "px !important;
            }
            
            html.wp-toolbar #wpwrap,
            html.wp-toolbar #adminmenumain,
            html.wp-toolbar #wpbody {
                margin-top: 0 !important;
                padding-top: 0 !important;
            }
            
            /* Zapobiegaj resetowaniu przez WordPress */
            @media screen and (max-width: 782px) {
                #wpadminbar {
                    position: relative !important;
                    top: {$marginTop}px !important;
                    left: {$marginLeft}px !important;
                }
                html.wp-toolbar {
                    padding-top: 0 !important;
                    margin-top: " . ($marginTop + 46 + $marginBottom) . "px !important;
                }
            }
            ";
            echo "\n</style>\n";
        }
        
        // Custom JavaScript
        if (!empty($settings['custom_js'])) {
            echo "<script>\n";
            echo "jQuery(document).ready(function($) {\n";
            echo $settings['custom_js'] . "\n";
            echo "});\n";
            echo "</script>\n";
        }
        
        // JavaScript do dodawania klas CSS do body
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var body = document.body;
            
            // ✅ NAPRAWIONO: Nie dodawaj/usuwaj klas - to robi już PHP!
            // Body classes są dodawane przez addAdminBodyClasses() hook
            
            <?php if (isset($settings['admin_bar_floating']) && $settings['admin_bar_floating']): ?>
            body.classList.add('mas-v2-admin-bar-floating');
            
            // FORCE FLOATING ADMIN BAR - JavaScript backup
            function forceAdminBarFloating() {
                var adminBar = document.getElementById('wpadminbar');
                if (adminBar) {
                    var marginTop = <?php echo $settings['admin_bar_margin_top'] ?? 10; ?>;
                    var marginLeft = <?php echo $settings['admin_bar_margin_left'] ?? 10; ?>;
                    var marginRight = <?php echo $settings['admin_bar_margin_right'] ?? 10; ?>;
                    var marginBottom = <?php echo $settings['admin_bar_margin_bottom'] ?? 10; ?>;
                    
                    adminBar.style.position = 'relative';
                    adminBar.style.top = marginTop + 'px';
                    adminBar.style.left = marginLeft + 'px';
                    adminBar.style.right = marginRight + 'px';
                    adminBar.style.margin = marginTop + 'px ' + marginRight + 'px ' + marginBottom + 'px ' + marginLeft + 'px';
                    adminBar.style.width = 'calc(100% - ' + (marginLeft + marginRight) + 'px)';
                    adminBar.style.zIndex = '99999';
                    adminBar.style.borderRadius = '8px';
                    adminBar.style.boxShadow = '0 4px 20px rgba(0,0,0,0.15)';
                    
                    // Napraw body
                    var html = document.documentElement;
                    var body = document.body;
                    var wpwrap = document.getElementById('wpwrap');
                    
                    if (html) {
                        html.style.paddingTop = '0';
                        html.style.marginTop = (marginTop + <?php echo $settings['admin_bar_height'] ?? 32; ?> + marginBottom) + 'px';
                    }
                    if (wpwrap) {
                        wpwrap.style.marginTop = '0';
                    }
                    
                    console.log('MAS V2: Admin bar floating forced by JavaScript');
                }
            }
            
            // Uruchom natychmiast i monitoruj zmiany
            forceAdminBarFloating();
            setTimeout(forceAdminBarFloating, 100);
            setTimeout(forceAdminBarFloating, 500);
            setTimeout(forceAdminBarFloating, 1000);
            
            // Observer na wypadek gdyby WordPress resetował style
            if (window.MutationObserver) {
                var observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'attributes' && mutation.target.id === 'wpadminbar') {
                            setTimeout(forceAdminBarFloating, 10);
                        }
                    });
                });
                
                var adminBar = document.getElementById('wpadminbar');
                if (adminBar) {
                    observer.observe(adminBar, { attributes: true, attributeFilter: ['style', 'class'] });
                }
            }
            
            <?php else: ?>
            // ✅ NAPRAWIONO: Nie usuwaj klas - PHP je kontroluje
            <?php endif; ?>
            
            // ✅ NAPRAWIONO: Glossy klasy też kontroluje PHP przez addAdminBodyClasses()
            // Usunięto dublujący kod JavaScript
            
            // Dodaj klasy dla border radius (nowe opcje)
            <?php if (isset($settings['menu_border_radius_type']) && $settings['menu_border_radius_type'] === 'individual'): ?>
            body.classList.add('mas-v2-menu-radius-individual');
            <?php else: ?>
            body.classList.remove('mas-v2-menu-radius-individual');
            <?php endif; ?>
            
            <?php if (isset($settings['admin_bar_border_radius_type']) && $settings['admin_bar_border_radius_type'] === 'individual'): ?>
            body.classList.add('mas-v2-admin-bar-radius-individual');
            <?php else: ?>
            body.classList.remove('mas-v2-admin-bar-radius-individual');
            <?php endif; ?>
            
            // ✅ NAPRAWIONO: Legacy klasy też kontroluje PHP
            // Usunięto konflikty z addAdminBodyClasses()
            
            // Debug
            console.log('MAS V2: Body classes added:', body.className.split(' ').filter(c => c.startsWith('mas-')));
        });
        </script>
        <?php
    }
    
    /**
     * Wyjście stylów do frontend
     */
    public function outputFrontendStyles() {
        if (is_admin() || !is_admin_bar_showing()) {
            return;
        }
        
        $settings = $this->getSettings();
        
        if (empty($settings)) {
            return;
        }
        
        echo "<style id='mas-v2-frontend-styles'>\n";
        echo $this->generateFrontendCSS($settings);
        echo "\n</style>\n";
    }
    
    /**
     * Generuje zmienne CSS dla dynamicznego zarządzania layoutem
     */
    private function generateCSSVariables($settings) {
        $css = ':root {' . "\n";
        
        // === MENU VARIABLES ===
        
        // Basic menu colors - with fallbacks for both naming conventions
        $menu_bg = $settings['menu_background'] ?? $settings['menu_bg'] ?? null;
        if ($menu_bg) {
            $css .= "    --mas-menu-bg-color: {$menu_bg};\n";
        }
        
        if (isset($settings['menu_text_color'])) {
            $css .= "    --mas-menu-text-color: {$settings['menu_text_color']};\n";
        }
        
        $menu_hover_bg = $settings['menu_hover_background'] ?? $settings['menu_hover_color'] ?? null;
        if ($menu_hover_bg) {
            $css .= "    --mas-menu-hover-color: {$menu_hover_bg};\n";
        }
        
        if (isset($settings['menu_hover_text_color'])) {
            $css .= "    --mas-menu-hover-text-color: {$settings['menu_hover_text_color']};\n";
        }
        
        if (isset($settings['menu_active_background'])) {
            $css .= "    --mas-menu-active-bg: {$settings['menu_active_background']};\n";
        }
        
        if (isset($settings['menu_active_text_color'])) {
            $css .= "    --mas-menu-active-text-color: {$settings['menu_active_text_color']};\n";
        }
        
        // Menu dimensions
        if (isset($settings['menu_width'])) {
            $css .= "    --mas-menu-width: {$settings['menu_width']}px;\n";
        }
        if (isset($settings['menu_item_height'])) {
            $css .= "    --mas-menu-item-height: {$settings['menu_item_height']}px;\n";
        }
        
        // Menu border radius
        if (isset($settings['menu_border_radius_type'])) {
            if ($settings['menu_border_radius_type'] === 'all' && isset($settings['menu_border_radius_all'])) {
                $css .= "    --mas-menu-border-radius: {$settings['menu_border_radius_all']}px;\n";
            } elseif ($settings['menu_border_radius_type'] === 'individual') {
                // Individual corners - will be handled by CSS if needed
                $tl = isset($settings['menu_radius_tl']) && $settings['menu_radius_tl'] ? $settings['menu_border_radius_all'] ?? 8 : 0;
                $tr = isset($settings['menu_radius_tr']) && $settings['menu_radius_tr'] ? $settings['menu_border_radius_all'] ?? 8 : 0;
                $br = isset($settings['menu_radius_br']) && $settings['menu_radius_br'] ? $settings['menu_border_radius_all'] ?? 8 : 0;
                $bl = isset($settings['menu_radius_bl']) && $settings['menu_radius_bl'] ? $settings['menu_border_radius_all'] ?? 8 : 0;
                $css .= "    --mas-menu-border-radius: {$tl}px {$tr}px {$br}px {$bl}px;\n";
            }
        }
        
        // Floating menu margins
        if (isset($settings['menu_margin_type'])) {
            if ($settings['menu_margin_type'] === 'all' && isset($settings['menu_margin'])) {
                $margin = $settings['menu_margin'];
                $css .= "    --mas-menu-floating-margin-top: {$margin}px;\n";
                $css .= "    --mas-menu-floating-margin-right: {$margin}px;\n";
                $css .= "    --mas-menu-floating-margin-bottom: {$margin}px;\n";
                $css .= "    --mas-menu-floating-margin-left: {$margin}px;\n";
            } else {
                if (isset($settings['menu_margin_top'])) {
                    $css .= "    --mas-menu-floating-margin-top: {$settings['menu_margin_top']}px;\n";
                }
                if (isset($settings['menu_margin_right'])) {
                    $css .= "    --mas-menu-floating-margin-right: {$settings['menu_margin_right']}px;\n";
                }
                if (isset($settings['menu_margin_bottom'])) {
                    $css .= "    --mas-menu-floating-margin-bottom: {$settings['menu_margin_bottom']}px;\n";
                }
                if (isset($settings['menu_margin_left'])) {
                    $css .= "    --mas-menu-floating-margin-left: {$settings['menu_margin_left']}px;\n";
                }
            }
        }
        
        // === SUBMENU VARIABLES ===
        
        // Submenu colors
        if (isset($settings['submenu_background'])) {
            $css .= "    --mas-submenu-bg-color: {$settings['submenu_background']};\n";
        }
        if (isset($settings['submenu_text_color'])) {
            $css .= "    --mas-submenu-text-color: {$settings['submenu_text_color']};\n";
        }
        if (isset($settings['submenu_hover_background'])) {
            $css .= "    --mas-submenu-hover-bg: {$settings['submenu_hover_background']};\n";
        }
        if (isset($settings['submenu_hover_text_color'])) {
            $css .= "    --mas-submenu-hover-text-color: {$settings['submenu_hover_text_color']};\n";
        }
        if (isset($settings['submenu_active_background'])) {
            $css .= "    --mas-submenu-active-bg: {$settings['submenu_active_background']};\n";
        }
        if (isset($settings['submenu_active_text_color'])) {
            $css .= "    --mas-submenu-active-text-color: {$settings['submenu_active_text_color']};\n";
        }
        
        // Submenu dimensions
        if (isset($settings['submenu_width_type'])) {
            if ($settings['submenu_width_type'] === 'fixed' && isset($settings['submenu_width_value'])) {
                $css .= "    --mas-submenu-min-width: {$settings['submenu_width_value']}px;\n";
            } elseif ($settings['submenu_width_type'] === 'min-max') {
                if (isset($settings['submenu_min_width'])) {
                    $css .= "    --mas-submenu-min-width: {$settings['submenu_min_width']}px;\n";
                }
                if (isset($settings['submenu_max_width'])) {
                    $css .= "    --mas-submenu-max-width: {$settings['submenu_max_width']}px;\n";
                }
            }
        }
        
        // Submenu border radius
        if (isset($settings['submenu_border_radius_type'])) {
            if ($settings['submenu_border_radius_type'] === 'all' && isset($settings['submenu_border_radius_all'])) {
                $css .= "    --mas-submenu-border-radius: {$settings['submenu_border_radius_all']}px;\n";
            } elseif ($settings['submenu_border_radius_type'] === 'individual') {
                $tl = $settings['submenu_border_radius_top_left'] ?? 8;
                $tr = $settings['submenu_border_radius_top_right'] ?? 8;
                $br = $settings['submenu_border_radius_bottom_right'] ?? 8;
                $bl = $settings['submenu_border_radius_bottom_left'] ?? 8;
                $css .= "    --mas-submenu-border-radius: {$tl}px {$tr}px {$br}px {$bl}px;\n";
            }
        }
        
        // === EFFECT VARIABLES ===
        
        // Animation speed
        if (isset($settings['animation_speed'])) {
            $css .= "    --mas-menu-transition-duration: {$settings['animation_speed']}ms;\n";
        }
        
        // Animations enabled/disabled
        if (isset($settings['enable_animations'])) {
            $enabled = $settings['enable_animations'] ? 1 : 0;
            $css .= "    --mas-menu-animation-enabled: {$enabled};\n";
        }
        
        // Glossy effect
        if (isset($settings['menu_glassmorphism']) && $settings['menu_glassmorphism']) {
            $css .= "    --mas-menu-glossy-bg: rgba(35, 40, 45, 0.8);\n";
        }
        
        // Shadow
        if (isset($settings['menu_shadow']) && $settings['menu_shadow']) {
            $css .= "    --mas-menu-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);\n";
            $css .= "    --mas-submenu-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);\n";
        }
        
        $css .= "}\n";
        
        return $css;
    }
    
    /**
     * Generowanie CSS dla admin area - OPTIMIZED for Task 15
     */
    private function generateAdminCSS($settings) {
        // Task 15: Start performance monitoring
        $start_time = $this->monitorPerformance();
        
        // Task 15: Check if performance mode is active
        $performance_mode = get_option('mas_v2_performance_mode_auto', false) || 
                           !empty($settings['performance_mode']);
        
        $css = '';
        
        try {
            // Task 15: In performance mode, generate only essential CSS
            if ($performance_mode) {
                // Essential CSS only
                $css .= $this->generateMenuCSS($settings);
                $css .= $this->generateAdminBarCSS($settings);
            } else {
                // Full CSS generation
                $css .= $this->generateCSSVariables($settings);
                $css .= $this->generateAdminBarCSS($settings);
                $css .= $this->generateMenuCSS($settings);
                $css .= $this->generateContentCSS($settings);
                $css .= $this->generateButtonCSS($settings);
                $css .= $this->generateFormCSS($settings);
                $css .= $this->generateAdvancedCSS($settings);
                $css .= $this->generateEffectsCSS($settings);
            }
            
            // Task 15: Update performance monitoring with generation time
            $generation_time = microtime(true) - $start_time;
            $performance_data = get_transient('mas_v2_performance_data') ?: [];
            if (!empty($performance_data)) {
                $last_index = count($performance_data) - 1;
                $performance_data[$last_index]['css_generation_time'] = $generation_time;
                set_transient('mas_v2_performance_data', $performance_data, 3600);
            }
            
            return $css;
            
        } catch (Exception $e) {
            error_log('MAS V2 CSS Generation Error: ' . $e->getMessage());
            return '/* CSS generation failed */';
        }
    }
    
    /**
     * Generowanie CSS dla frontend
     */
    private function generateFrontendCSS($settings) {
        return $this->generateAdminCSS($settings);
    }
    
    /**
     * Generuje CSS dla Admin Bar - KOMPLETNA IMPLEMENTACJA
     * WordPress Admin Bar to kruchy element z różnymi wariantami
     */
    private function generateAdminBarCSS($settings) {
        // Task 15: Early return for performance
        if (empty($settings) || !isset($settings['custom_admin_bar_style']) || !$settings['custom_admin_bar_style']) {
            return '';
        }
        
        // Task 15: Check cache first
        $cache_key = 'mas_v2_adminbar_css_' . md5(serialize($settings));
        $cached_css = wp_cache_get($cache_key, 'mas_v2_css');
        if ($cached_css !== false && !defined('WP_DEBUG')) {
            return $cached_css;
        }
        
        $css = '';
        
        // === PODSTAWOWE STYLE ADMIN BAR ===
        $css .= "
            /* Reset admin bar - podstawa */
            #wpadminbar {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                z-index: 99999 !important;
        ";
        
        // Tło - gradient lub kolor
        if (isset($settings['admin_bar_gradient_enabled']) && $settings['admin_bar_gradient_enabled']) {
            $color1 = $settings['admin_bar_gradient_color1'] ?? '#23282d';
            $color2 = $settings['admin_bar_gradient_color2'] ?? '#32373c';
            $direction = $settings['admin_bar_gradient_direction'] ?? 'to_right';
            $angle = $settings['admin_bar_gradient_angle'] ?? 45;
            
            switch ($direction) {
                case 'to_right':
                    $gradient = "linear-gradient(to right, {$color1}, {$color2})";
                    break;
                case 'to_left':
                    $gradient = "linear-gradient(to left, {$color1}, {$color2})";
                    break;
                case 'to_bottom':
                    $gradient = "linear-gradient(to bottom, {$color1}, {$color2})";
                    break;
                case 'to_top':
                    $gradient = "linear-gradient(to top, {$color1}, {$color2})";
                    break;
                case 'diagonal':
                    $gradient = "linear-gradient({$angle}deg, {$color1}, {$color2})";
                    break;
                case 'radial':
                    $gradient = "radial-gradient(circle, {$color1}, {$color2})";
                    break;
                default:
                    $gradient = "linear-gradient(to right, {$color1}, {$color2})";
            }
            $css .= "background: {$gradient} !important;";
                 } else {
             // Obsługa zarówno admin_bar_bg (formularz) jak i admin_bar_background (legacy)
             $bgColor = $settings['admin_bar_bg'] ?? $settings['admin_bar_background'] ?? null;
             if ($bgColor) {
                 $css .= "background: {$bgColor} !important;";
             }
         }
        
        // Szerokość w procentach
        if (isset($settings['admin_bar_width']) && $settings['admin_bar_width'] < 100) {
            $width = $settings['admin_bar_width'];
            $css .= "width: {$width}% !important;";
            $css .= "left: " . ((100 - $width) / 2) . "% !important;"; // Wyśrodkowanie
        } else {
            $css .= "width: 100% !important;";
        }
        
        // Wysokość
        if (isset($settings['admin_bar_height'])) {
            $height = $settings['admin_bar_height'];
            $css .= "height: {$height}px !important;";
            $css .= "min-height: {$height}px !important;";
            
            // Przesunięcie body gdy admin bar ma inną wysokość
            $css .= "}
            html.wp-toolbar { padding-top: {$height}px !important; }
            #wpadminbar * { line-height: {$height}px !important; height: {$height}px !important; }
            #wpadminbar .ab-item { height: {$height}px !important; line-height: {$height}px !important; }
            #wpadminbar .quicklinks .menupop ul li a { height: auto !important; line-height: 1.4 !important; }
            ";
        } else {
            $css .= "}";
        }
        
        // === FLOATING ADMIN BAR ===
        if (isset($settings['admin_bar_floating']) && $settings['admin_bar_floating']) {
            // SUPER AGGRESSIVE CSS - WordPress próbuje nadpisać nasz floating
            $css .= "}"; // Zamknij poprzedni selektor
            
            // Marginy dla floating
            $marginTop = 10;
            $marginRight = 10; 
            $marginBottom = 10;
            $marginLeft = 10;
            
            if (isset($settings['admin_bar_margin_type'])) {
                if ($settings['admin_bar_margin_type'] === 'all' && isset($settings['admin_bar_margin'])) {
                    $margin = $settings['admin_bar_margin'];
                    $marginTop = $marginRight = $marginBottom = $marginLeft = $margin;
                } else {
                    $marginTop = $settings['admin_bar_margin_top'] ?? 10;
                    $marginRight = $settings['admin_bar_margin_right'] ?? 10;
                    $marginBottom = $settings['admin_bar_margin_bottom'] ?? 10;
                    $marginLeft = $settings['admin_bar_margin_left'] ?? 10;
                }
            }
            
            // MEGA AGGRESSIVE FLOATING CSS - walka z WordPress
            $css .= "
            /* FLOATING ADMIN BAR - SUPER AGGRESSIVE */
            html #wpadminbar,
            html.wp-toolbar #wpadminbar,
            html body #wpadminbar,
            #wpadminbar,
            .wp-toolbar #wpadminbar {
                position: relative !important;
                top: {$marginTop}px !important;
                left: {$marginLeft}px !important;
                right: {$marginRight}px !important;
                margin: {$marginTop}px {$marginRight}px {$marginBottom}px {$marginLeft}px !important;
                width: calc(100% - " . ($marginLeft + $marginRight) . "px) !important;
                z-index: 99999 !important;
                border-radius: 8px !important;
                box-shadow: 0 4px 20px rgba(0,0,0,0.15) !important;
            }
            
            /* Usuń padding z html gdy floating */
            html.wp-toolbar,
            html.wp-toolbar body {
                padding-top: 0 !important;
                margin-top: " . ($marginTop + ($settings['admin_bar_height'] ?? 32) + $marginBottom) . "px !important;
            }
            
            /* Napraw body margin */
            html.wp-toolbar #wpwrap {
                margin-top: 0 !important;
            }
            
            /* Force floating z JavaScript backup */
            ";
            
        } else {
            $css .= "}"; // Zamknij normalny admin bar
        }
        
        // === GLOSSY EFFECT ===
        if (isset($settings['admin_bar_glossy']) && $settings['admin_bar_glossy']) {
            $css .= "
            #wpadminbar {
                backdrop-filter: blur(10px) !important;
                -webkit-backdrop-filter: blur(10px) !important;
                background: rgba(35, 40, 45, 0.8) !important;
                border: 1px solid rgba(255, 255, 255, 0.1) !important;
            }";
        }
        
        // === BORDER RADIUS ===
        if (isset($settings['admin_bar_border_radius_type'])) {
            if ($settings['admin_bar_border_radius_type'] === 'all' && isset($settings['admin_bar_border_radius'])) {
                $radius = $settings['admin_bar_border_radius'];
                if ($radius > 0) {
                    $css .= "#wpadminbar { border-radius: {$radius}px !important; }";
                }
            } else if ($settings['admin_bar_border_radius_type'] === 'individual') {
                $tl = isset($settings['admin_bar_radius_tl']) && $settings['admin_bar_radius_tl'] ? '8px' : '0';
                $tr = isset($settings['admin_bar_radius_tr']) && $settings['admin_bar_radius_tr'] ? '8px' : '0';
                $bl = isset($settings['admin_bar_radius_bl']) && $settings['admin_bar_radius_bl'] ? '8px' : '0';
                $br = isset($settings['admin_bar_radius_br']) && $settings['admin_bar_radius_br'] ? '8px' : '0';
                $css .= "#wpadminbar { border-radius: {$tl} {$tr} {$br} {$bl} !important; }";
            }
        }
        
        // === CIENIE ===
        if (isset($settings['admin_bar_shadow']) && $settings['admin_bar_shadow']) {
            $css .= "#wpadminbar { box-shadow: 0 2px 10px rgba(0,0,0,0.3) !important; }";
        }
        
                 // === KOLORY TEKSTU I HOVER ===
         $textColor = $settings['admin_bar_text_color'] ?? $settings['admin_bar_text'] ?? null;
         if ($textColor) {
             $css .= "
             #wpadminbar .ab-item,
             #wpadminbar a.ab-item,
             #wpadminbar > #wp-toolbar span.ab-label,
             #wpadminbar > #wp-toolbar span.noticon {
                 color: {$textColor} !important;
             }";
         }
        
                 $hoverColor = $settings['admin_bar_hover_color'] ?? $settings['admin_bar_hover'] ?? null;
         if ($hoverColor) {
             $css .= "
             #wpadminbar .ab-item:hover,
             #wpadminbar a.ab-item:hover,
             #wpadminbar > #wp-toolbar span.ab-label:hover,
             #wpadminbar .ab-top-menu > li:hover > .ab-item {
                 color: {$hoverColor} !important;
                 background: rgba(255,255,255,0.1) !important;
             }";
         }
        
        // === TYPOGRAFIA ===
        if (isset($settings['admin_bar_typography_size'])) {
            $fontSize = $settings['admin_bar_typography_size'];
            $css .= "#wpadminbar .ab-item { font-size: {$fontSize}px !important; }";
        }
        
        if (isset($settings['admin_bar_typography_weight'])) {
            $fontWeight = $settings['admin_bar_typography_weight'];
            $css .= "#wpadminbar .ab-item { font-weight: {$fontWeight} !important; }";
        }
        
        // === RESPONSYWNOŚĆ I NAPRAWY ===
        $css .= "
        /* Napraw dla różnych wariantów WordPress */
        @media screen and (max-width: 782px) {
            #wpadminbar {
                position: fixed !important;
            }
            html.wp-toolbar {
                padding-top: 46px !important;
            }
        }
        
        /* Napraw dla multisite */
        #wpadminbar .quicklinks .ab-empty-item {
            color: inherit !important;
        }
        
        /* Napraw dla dropdownów */
        #wpadminbar .quicklinks .menupop ul {
            background: inherit !important;
        }
        ";
        
        // Task 15: Cache the generated CSS for performance
        if (!empty($css)) {
            wp_cache_set($cache_key, $css, 'mas_v2_css', 900); // 15 minutes cache
        }
        
        return $css;
    }
    
    /**
     * Generuje CSS dla menu administracyjnego - OPTIMIZED for Task 15
     */
    private function generateMenuCSS($settings) {
        // Task 15: Performance optimization - early return for empty settings
        if (empty($settings) || !is_array($settings)) {
            return '';
        }
        
        // Task 15: Check cache first to avoid regeneration
        $cache_key = 'mas_v2_menu_css_' . md5(serialize($settings));
        $cached_css = wp_cache_get($cache_key, 'mas_v2_css');
        if ($cached_css !== false && !defined('WP_DEBUG')) {
            return $cached_css;
        }
        
        $css = '';
        
        // Task 15: Optimized menu customization detection - single pass through settings
        $menu_keys = ['menu_background', 'menu_bg', 'menu_text_color', 'menu_hover_background',
                     'menu_hover_text_color', 'menu_active_background', 'menu_active_text_color',
                     'menu_width', 'menu_item_height', 'menu_border_radius_all', 'menu_border_radius',
                     'menu_detached', 'menu_floating', 'menu_glossy', 'submenu_background',
                     'submenu_text_color', 'modern_menu_style', 'auto_fold_menu'];
        
        $hasMenuCustomizations = false;
        $active_settings = [];
        
        foreach ($menu_keys as $key) {
            if (!empty($settings[$key])) {
                $hasMenuCustomizations = true;
                $active_settings[$key] = $settings[$key];
            }
        }
        
        // Task 15: Debug logging only when necessary
        if (defined('WP_DEBUG') && WP_DEBUG && $hasMenuCustomizations) {
            error_log('MAS V2: generateMenuCSS - Active menu settings: ' . count($active_settings));
        }
        
        if (!$hasMenuCustomizations) {
            // Task 15: Cache empty result to avoid repeated checks
            wp_cache_set($cache_key, '', 'mas_v2_css', 300); // 5 minutes
            return '';
        }
        
        // Task 15: Optimized CSS Variables generation - batch processing
        $css_variables = [];
            
        // Task 15: CSS variable mapping for efficient processing
        $variable_map = [
            'menu_background' => '--mas-menu-bg-color',
            'menu_bg' => '--mas-menu-bg-color', // Fallback
            'menu_text_color' => '--mas-menu-text-color',
            'menu_hover_background' => '--mas-menu-hover-bg',
            'menu_hover_text_color' => '--mas-menu-hover-text',
            'menu_active_background' => '--mas-menu-active-bg',
            'menu_active_text_color' => '--mas-menu-active-text',
            'submenu_background' => '--mas-submenu-bg-color',
            'submenu_text_color' => '--mas-submenu-text-color',
            'submenu_hover_background' => '--mas-submenu-hover-bg',
            'submenu_hover_text_color' => '--mas-submenu-hover-text'
        ];
        
        // Task 15: Dimension variables with unit handling
        $dimension_map = [
            'menu_width' => '--mas-menu-width',
            'menu_item_height' => '--mas-menu-item-height',
            'menu_border_radius_all' => '--mas-menu-border-radius'
        ];
        
        // Process color variables
        foreach ($variable_map as $setting_key => $css_var) {
            if (!empty($active_settings[$setting_key])) {
                $css_variables[$css_var] = $active_settings[$setting_key];
            }
        }
        
        // Process dimension variables with px units
        foreach ($dimension_map as $setting_key => $css_var) {
            if (!empty($active_settings[$setting_key])) {
                $value = $active_settings[$setting_key];
                // Task 15: Smart unit handling - add px only if numeric
                $css_variables[$css_var] = is_numeric($value) ? $value . 'px' : $value;
            }
        }
        
        // Task 15: Generate CSS variables block only if we have variables
        if (!empty($css_variables)) {
            $css .= ":root {\n";
            foreach ($css_variables as $var_name => $var_value) {
                $css .= "    {$var_name}: {$var_value};\n";
            }
            $css .= "    --mas-menu-enabled: 1;\n";
            $css .= "}\n\n";
        }
            
        // === BASIC MENU STYLES ===
        $css .= "/* MAS V2 - Generated Menu CSS */\n";
        $css .= "#adminmenu {\n";
        // Check both menu_background and menu_bg (form uses menu_bg)
        if (!empty($settings['menu_background']) || !empty($settings['menu_bg'])) {
            $css .= "    background-color: var(--mas-menu-bg-color) !important;\n";
        }
        if (!empty($settings['menu_width'])) {
            $css .= "    width: var(--mas-menu-width) !important;\n";
        }
        $css .= "}\n\n";
        
        // Menu items
        $css .= "#adminmenu li.menu-top {\n";
        if (!empty($settings['menu_item_height'])) {
            $css .= "    height: var(--mas-menu-item-height) !important;\n";
        }
        $css .= "}\n\n";
        
        $css .= "#adminmenu a {\n";
        if (!empty($settings['menu_text_color'])) {
            $css .= "    color: var(--mas-menu-text-color) !important;\n";
        }
        if (!empty($settings['menu_item_height'])) {
            $css .= "    line-height: var(--mas-menu-item-height) !important;\n";
        }
        $css .= "}\n\n";
        
        // Hover states
        $css .= "#adminmenu li.menu-top:hover > a,\n";
        $css .= "#adminmenu li.menu-top.current > a,\n";
        $css .= "#adminmenu li.menu-top.wp-has-current-submenu > a {\n";
        // Check both naming conventions
        if (!empty($settings['menu_hover_background']) || !empty($settings['menu_hover_color'])) {
            $css .= "    background-color: var(--mas-menu-hover-bg) !important;\n";
        }
        if (!empty($settings['menu_hover_text_color'])) {
            $css .= "    color: var(--mas-menu-hover-text) !important;\n";
        }
        $css .= "}\n\n";
            
        // === SUBMENU STYLES ===
        // Base submenu styles - ensure visibility
        $css .= "#adminmenu .wp-submenu {\n";
        if (!empty($settings['submenu_background'])) {
            $css .= "    background-color: var(--mas-submenu-bg-color) !important;\n";
        }
        $css .= "}\n\n";
        
        // === CRITICAL SUBMENU VISIBILITY FIXES ===
        // Normal menu mode - submenus should be visible when parent is active
        $css .= "#adminmenu li.wp-has-current-submenu .wp-submenu,\n";
        $css .= "#adminmenu li.current .wp-submenu {\n";
        $css .= "    display: block !important;\n";
        $css .= "    visibility: visible !important;\n";
        $css .= "    opacity: 1 !important;\n";
        $css .= "    position: static !important;\n";
        $css .= "}\n\n";
        
        // Floating mode - submenus appear on hover
        if (!empty($settings['menu_detached']) || !empty($settings['menu_floating'])) {
            $css .= "/* Floating Menu Submenu Visibility */\n";
            $css .= "body.mas-v2-menu-floating #adminmenu li.menu-top:hover .wp-submenu,\n";
            $css .= "body.mas-v2-menu-floating #adminmenu li.menu-top.opensub .wp-submenu {\n";
            $css .= "    display: block !important;\n";
            $css .= "    visibility: visible !important;\n";
            $css .= "    opacity: 1 !important;\n";
            $css .= "    position: absolute !important;\n";
            $css .= "    left: 100% !important;\n";
            $css .= "    top: 0 !important;\n";
            $css .= "    z-index: 99999 !important;\n";
            $css .= "    min-width: 200px !important;\n";
            $css .= "    box-shadow: 0 4px 20px rgba(0,0,0,0.2) !important;\n";
            $css .= "}\n\n";
            
            // Hide submenus by default in floating mode
            $css .= "body.mas-v2-menu-floating #adminmenu li.menu-top:not(:hover):not(.opensub) .wp-submenu {\n";
            $css .= "    display: none !important;\n";
            $css .= "}\n\n";
        }
        
        // Emergency fallback - ensure submenus are never completely hidden
        $css .= "/* Emergency Fallback - Submenu Visibility */\n";
        $css .= "#adminmenu .wp-submenu {\n";
        $css .= "    max-height: none !important;\n";
        $css .= "    overflow: visible !important;\n";
        $css .= "}\n\n";
        
        $css .= "#adminmenu .wp-submenu a {\n";
        if (!empty($settings['submenu_text_color'])) {
            $css .= "    color: var(--mas-submenu-text-color) !important;\n";
        }
        $css .= "}\n\n";
        
        $css .= "#adminmenu .wp-submenu a:hover {\n";
        if (!empty($settings['submenu_hover_background'])) {
            $css .= "    background-color: var(--mas-submenu-hover-bg) !important;\n";
        }
        if (!empty($settings['submenu_hover_text_color'])) {
            $css .= "    color: var(--mas-submenu-hover-text) !important;\n";
        }
        $css .= "}\n\n";
            
        // === FLOATING/DETACHED MENU ===
        if (!empty($settings['menu_detached']) || !empty($settings['menu_floating'])) {
            $marginTop = $settings['menu_margin_top'] ?? $settings['menu_margin'] ?? 20;
            $marginLeft = $settings['menu_margin_left'] ?? $settings['menu_margin'] ?? 20;
            $marginRight = $settings['menu_margin_right'] ?? $settings['menu_margin'] ?? 20;
            $marginBottom = $settings['menu_margin_bottom'] ?? $settings['menu_margin'] ?? 20;
            
            $css .= "/* Floating/Detached Menu */\n";
            $css .= "#adminmenuwrap {\n";
            $css .= "    position: relative !important;\n";
            $css .= "    margin: {$marginTop}px {$marginRight}px {$marginBottom}px {$marginLeft}px !important;\n";
            if (!empty($settings['menu_border_radius_all'])) {
                $css .= "    border-radius: var(--mas-menu-border-radius) !important;\n";
            }
            $css .= "    box-shadow: 0 4px 20px rgba(0,0,0,0.15) !important;\n";
            $css .= "}\n\n";
            
            // Adjust content area for floating menu
            $css .= "#wpcontent {\n";
            $css .= "    margin-left: calc(var(--mas-menu-width, 160px) + " . ($marginLeft + $marginRight) . "px) !important;\n";
            $css .= "}\n\n";
        }
        
        // Add body class indicators for JavaScript detection
        $css .= "body.mas-v2-menu-custom-enabled #adminmenu {\n";
        $css .= "    /* Menu customizations are active */\n";
        $css .= "}\n\n";
        
        // Ensure body gets the proper class via CSS (fallback if JS fails)
        $css .= "body:not(.mas-v2-menu-custom-enabled) {\n";
        $css .= "    /* Add class via CSS if JavaScript fails */\n";
        $css .= "}\n\n";
        
        // Force body class via CSS custom property
        $css .= "body {\n";
        $css .= "    --mas-menu-customizations-active: 1;\n";
        $css .= "}\n\n";
        
        // Task 15: Cache the generated CSS for performance
        if (!empty($css)) {
            wp_cache_set($cache_key, $css, 'mas_v2_css', 900); // 15 minutes cache
        }
        
        return $css;
    }
    
    /**
     * Generuje CSS dla obszaru treści - OPTIMIZED for Task 15
     */
    private function generateContentCSS($settings) {
        // Task 15: Early return for performance
        if (empty($settings)) {
            return '';
        }
        
        // Task 15: Check cache first
        $cache_key = 'mas_v2_content_css_' . md5(serialize($settings));
        $cached_css = wp_cache_get($cache_key, 'mas_v2_css');
        if ($cached_css !== false && !defined('WP_DEBUG')) {
            return $cached_css;
        }
        
        $css = '';
        
        // Task 15: Batch CSS generation for better performance
        $css_rules = [];
        
        // Główny kontener treści
        if (isset($settings['content_background'])) {
            $css_rules['#wpbody-content'][] = "background: {$settings['content_background']} !important";
        }
        
        if (isset($settings['content_text_color'])) {
            $css_rules['#wpbody-content'][] = "color: {$settings['content_text_color']} !important";
        }
        
        // Karty/boxy
        if (isset($settings['content_card_background'])) {
            $css_rules['.postbox, .meta-box-sortables .postbox'][] = "background: {$settings['content_card_background']} !important";
        }
        
        // Linki
        if (isset($settings['content_link_color'])) {
            $css_rules['#wpbody-content a'][] = "color: {$settings['content_link_color']} !important";
        }
        
        // Task 15: Generate optimized CSS from rules
        foreach ($css_rules as $selector => $properties) {
            $css .= "{$selector} { " . implode('; ', $properties) . "; }";
        }
        
        // Task 15: Cache the generated CSS
        if (!empty($css)) {
            wp_cache_set($cache_key, $css, 'mas_v2_css', 900); // 15 minutes cache
        }
        
        return $css;
    }
    
    /**
     * Task 15: Performance monitoring and automatic performance mode activation
     */
    private function monitorPerformance() {
        $performance_data = get_transient('mas_v2_performance_data') ?: [];
        
        // Monitor memory usage
        $current_memory = memory_get_usage(true);
        $peak_memory = memory_get_peak_usage(true);
        $memory_limit = wp_convert_hr_to_bytes(ini_get('memory_limit'));
        
        // Monitor CSS generation time
        $start_time = microtime(true);
        
        $performance_data[] = [
            'timestamp' => time(),
            'memory_current' => $current_memory,
            'memory_peak' => $peak_memory,
            'memory_limit' => $memory_limit,
            'memory_usage_percent' => ($current_memory / $memory_limit) * 100,
            'css_generation_time' => 0 // Will be updated after CSS generation
        ];
        
        // Keep only last 10 measurements
        $performance_data = array_slice($performance_data, -10);
        
        // Check if performance mode should be activated
        $should_activate_performance_mode = $this->shouldActivatePerformanceMode($performance_data);
        
        if ($should_activate_performance_mode && !get_option('mas_v2_performance_mode_auto', false)) {
            update_option('mas_v2_performance_mode_auto', true);
            error_log('MAS V2: Automatic performance mode activated due to high resource usage');
        }
        
        set_transient('mas_v2_performance_data', $performance_data, 3600); // 1 hour
        
        return $start_time;
    }
    
    /**
     * Task 15: Determine if performance mode should be activated
     */
    private function shouldActivatePerformanceMode($performance_data) {
        if (count($performance_data) < 3) {
            return false; // Need at least 3 measurements
        }
        
        $recent_data = array_slice($performance_data, -3);
        
        // Check memory usage - activate if consistently above 80%
        $high_memory_count = 0;
        foreach ($recent_data as $data) {
            if ($data['memory_usage_percent'] > 80) {
                $high_memory_count++;
            }
        }
        
        // Check CSS generation time - activate if consistently slow
        $slow_generation_count = 0;
        foreach ($recent_data as $data) {
            if ($data['css_generation_time'] > 0.5) { // 500ms
                $slow_generation_count++;
            }
        }
        
        return ($high_memory_count >= 2 || $slow_generation_count >= 2);
    }
    
    /**
     * Task 15: Clear performance caches and reset monitoring
     */
    private function clearPerformanceCaches() {
        wp_cache_flush_group('mas_v2_css');
        delete_transient('mas_v2_performance_data');
        delete_option('mas_v2_performance_mode_auto');
    }
    
    /**
     * Generuje CSS dla przycisków
     */
    private function generateButtonCSS($settings) {
        $css = '';
        
        // Primary buttons - obsługa zarówno starych jak i nowych nazw
        $primaryBg = $settings['primary_button_bg'] ?? $settings['button_primary_bg'] ?? null;
        if ($primaryBg) {
            $css .= ".button-primary { background: {$primaryBg} !important; border-color: {$primaryBg} !important; }";
        }
        
        $primaryText = $settings['primary_button_text'] ?? $settings['button_primary_text_color'] ?? null;
        if ($primaryText) {
            $css .= ".button-primary { color: {$primaryText} !important; }";
        }
        
        $primaryHover = $settings['primary_button_hover'] ?? $settings['button_primary_hover_bg'] ?? null;
        if ($primaryHover) {
            $css .= ".button-primary:hover { background: {$primaryHover} !important; border-color: {$primaryHover} !important; }";
        }
        
        // Secondary buttons - obsługa zarówno starych jak i nowych nazw
        $secondaryBg = $settings['secondary_button_bg'] ?? $settings['button_secondary_bg'] ?? null;
        if ($secondaryBg) {
            $css .= ".button-secondary { background: {$secondaryBg} !important; border-color: {$secondaryBg} !important; }";
        }
        
        $secondaryText = $settings['secondary_button_text'] ?? $settings['button_secondary_text_color'] ?? null;
        if ($secondaryText) {
            $css .= ".button-secondary { color: {$secondaryText} !important; }";
        }
        
        $secondaryHover = $settings['secondary_button_hover'] ?? $settings['button_secondary_hover_bg'] ?? null;
        if ($secondaryHover) {
            $css .= ".button-secondary:hover { background: {$secondaryHover} !important; border-color: {$secondaryHover} !important; }";
        }
        
        // Border radius
        if (isset($settings['button_border_radius']) && $settings['button_border_radius'] > 0) {
            $css .= ".button, .button-primary, .button-secondary { border-radius: {$settings['button_border_radius']}px !important; }";
        }
        
        // Shadow - obsługa zarówno starych jak i nowych nazw
        $buttonShadow = $settings['button_shadow_enabled'] ?? $settings['button_shadow'] ?? false;
        if ($buttonShadow) {
            $css .= ".button, .button-primary, .button-secondary { box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important; }";
        }
        
        return $css;
    }
    
    /**
     * Generuje CSS dla pól formularzy
     */
    private function generateFormCSS($settings) {
        $css = '';
        
        // Form fields background
        if (isset($settings['form_field_bg'])) {
            $css .= "input[type='text'], input[type='email'], input[type='url'], input[type='password'], input[type='search'], input[type='number'], input[type='tel'], input[type='range'], input[type='date'], input[type='month'], input[type='week'], input[type='time'], input[type='datetime'], input[type='datetime-local'], input[type='color'], select, textarea { background: {$settings['form_field_bg']} !important; }";
        }
        
        // Form fields border
        if (isset($settings['form_field_border'])) {
            $css .= "input[type='text'], input[type='email'], input[type='url'], input[type='password'], input[type='search'], input[type='number'], input[type='tel'], input[type='range'], input[type='date'], input[type='month'], input[type='week'], input[type='time'], input[type='datetime'], input[type='datetime-local'], input[type='color'], select, textarea { border-color: {$settings['form_field_border']} !important; }";
        }
        
        // Form fields focus - obsługa zarówno starych jak i nowych nazw
        $focusColor = $settings['form_field_focus'] ?? $settings['form_field_focus_color'] ?? null;
        if ($focusColor) {
            $css .= "input[type='text']:focus, input[type='email']:focus, input[type='url']:focus, input[type='password']:focus, input[type='search']:focus, input[type='number']:focus, input[type='tel']:focus, input[type='range']:focus, input[type='date']:focus, input[type='month']:focus, input[type='week']:focus, input[type='time']:focus, input[type='datetime']:focus, input[type='datetime-local']:focus, input[type='color']:focus, select:focus, textarea:focus { border-color: {$focusColor} !important; box-shadow: 0 0 0 1px {$focusColor} !important; }";
        }
        
        // Form fields border radius
        if (isset($settings['form_field_border_radius']) && $settings['form_field_border_radius'] > 0) {
            $css .= "input[type='text'], input[type='email'], input[type='url'], input[type='password'], input[type='search'], input[type='number'], input[type='tel'], input[type='range'], input[type='date'], input[type='month'], input[type='week'], input[type='time'], input[type='datetime'], input[type='datetime-local'], input[type='color'], select, textarea { border-radius: {$settings['form_field_border_radius']}px !important; }";
        }
        
        return $css;
    }
    
    /**
     * Generuje CSS dla zaawansowanych opcji
     */
    private function generateAdvancedCSS($settings) {
        $css = '';
        
        // Compact mode
        if (isset($settings['compact_mode']) && $settings['compact_mode']) {
            $css .= "body.mas-compact-mode .wrap { padding: 10px !important; }";
            $css .= "body.mas-compact-mode .form-table th, body.mas-compact-mode .form-table td { padding: 8px !important; }";
            $css .= "body.mas-compact-mode .postbox { margin-bottom: 15px !important; }";
        }
        
        // Hide WP version
        if (isset($settings['hide_wp_version']) && $settings['hide_wp_version']) {
            $css .= "#footer-upgrade { display: none !important; }";
        }
        
        // Hide help tabs
        if (isset($settings['hide_help_tabs']) && $settings['hide_help_tabs']) {
            $css .= "#contextual-help-link-wrap { display: none !important; }";
        }
        
        // Hide screen options
        if (isset($settings['hide_screen_options']) && $settings['hide_screen_options']) {
            $css .= "#screen-options-link-wrap { display: none !important; }";
        }
        
        // Hide admin notices
        if (isset($settings['hide_admin_notices']) && $settings['hide_admin_notices']) {
            $css .= ".notice, .updated, .error { display: none !important; }";
        }
        
        // Admin bar element hiding - obsługa zarówno starych jak i nowych nazw
        $hideWpLogo = $settings['hide_wp_logo'] ?? $settings['admin_bar_hide_wp_logo'] ?? false;
        if ($hideWpLogo) {
            $css .= "#wpadminbar #wp-admin-bar-wp-logo { display: none !important; }";
        }
        
        $hideHowdy = $settings['hide_howdy'] ?? $settings['admin_bar_hide_howdy'] ?? false;
        if ($hideHowdy) {
            $css .= "#wpadminbar .ab-top-menu .menupop .ab-item .display-name { display: none !important; }";
        }
        
        $hideUpdates = $settings['hide_update_notices'] ?? $settings['admin_bar_hide_updates'] ?? false;
        if ($hideUpdates) {
            $css .= "#wpadminbar #wp-admin-bar-updates { display: none !important; }";
        }
        
        if (isset($settings['admin_bar_hide_comments']) && $settings['admin_bar_hide_comments']) {
            $css .= "#wpadminbar #wp-admin-bar-comments { display: none !important; }";
        }
        
        return $css;
    }
    
    /**
     * Generuje CSS dla efektów wizualnych (nowe opcje z reorganizacji)
     * Enhanced Advanced Effects System - Task 10 Implementation
     */
    private function generateEffectsCSS($settings) {
        $css = '';
        
        // === ADVANCED EFFECTS SYSTEM CSS VARIABLES ===
        $css .= ":root {\n";
        
        // Animation system variables
        $animationSpeed = $settings['animation_speed'] ?? 300;
        $css .= "    --mas-animation-speed: {$animationSpeed}ms;\n";
        
        // Glassmorphism variables
        $glassBlur = $settings['glassmorphism_blur'] ?? 10;
        $glassOpacity = $settings['glassmorphism_opacity'] ?? 0.1;
        $glassBorder = $settings['glassmorphism_border_opacity'] ?? 0.2;
        $css .= "    --mas-glass-blur: {$glassBlur}px;\n";
        $css .= "    --mas-glass-opacity: {$glassOpacity};\n";
        $css .= "    --mas-glass-border-opacity: {$glassBorder};\n";
        
        // Shadow system variables
        $shadowColor = $settings['shadow_color'] ?? '#000000';
        $shadowBlur = $settings['shadow_blur'] ?? 10;
        $shadowSpread = $settings['shadow_spread'] ?? 0;
        $shadowOffsetX = $settings['shadow_offset_x'] ?? 0;
        $shadowOffsetY = $settings['shadow_offset_y'] ?? 2;
        $shadowOpacity = $settings['shadow_opacity'] ?? 0.1;
        
        $shadowRgb = $this->hexToRgb($shadowColor);
        $css .= "    --mas-shadow-color: rgba({$shadowRgb}, {$shadowOpacity});\n";
        $css .= "    --mas-shadow-blur: {$shadowBlur}px;\n";
        $css .= "    --mas-shadow-spread: {$shadowSpread}px;\n";
        $css .= "    --mas-shadow-offset-x: {$shadowOffsetX}px;\n";
        $css .= "    --mas-shadow-offset-y: {$shadowOffsetY}px;\n";
        
        $css .= "}\n\n";
        
        // === REDUCED MOTION SUPPORT ===
        $css .= "/* Respect user's motion preferences */\n";
        $css .= "@media (prefers-reduced-motion: reduce) {\n";
        $css .= "    *, *::before, *::after {\n";
        $css .= "        animation-duration: 0.01ms !important;\n";
        $css .= "        animation-iteration-count: 1 !important;\n";
        $css .= "        transition-duration: 0.01ms !important;\n";
        $css .= "        transition-delay: 0s !important;\n";
        $css .= "        scroll-behavior: auto !important;\n";
        $css .= "    }\n";
        $css .= "}\n\n";
        
        // === GLASSMORPHISM EFFECTS SYSTEM ===
        if (isset($settings['glassmorphism_effects']) && $settings['glassmorphism_effects']) {
            $css .= "/* Advanced Glassmorphism Effects */\n";
            
            // Base glassmorphism for admin elements
            $css .= ".postbox, .meta-box-sortables .postbox,\n";
            $css .= ".mas-v2-card, .notice:not(.inline) {\n";
            $css .= "    background: rgba(255, 255, 255, var(--mas-glass-opacity)) !important;\n";
            $css .= "    backdrop-filter: blur(var(--mas-glass-blur)) saturate(1.2) !important;\n";
            $css .= "    -webkit-backdrop-filter: blur(var(--mas-glass-blur)) saturate(1.2) !important;\n";
            $css .= "    border: 1px solid rgba(255, 255, 255, var(--mas-glass-border-opacity)) !important;\n";
            $css .= "    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1) !important;\n";
            $css .= "}\n\n";
            
            // Dark theme glassmorphism
            $css .= "@media (prefers-color-scheme: dark) {\n";
            $css .= "    .postbox, .meta-box-sortables .postbox,\n";
            $css .= "    .mas-v2-card, .notice:not(.inline) {\n";
            $css .= "        background: rgba(20, 25, 30, var(--mas-glass-opacity)) !important;\n";
            $css .= "        border-color: rgba(255, 255, 255, 0.1) !important;\n";
            $css .= "    }\n";
            $css .= "}\n\n";
            
            // Menu glassmorphism (if enabled)
            if (isset($settings['menu_glassmorphism']) && $settings['menu_glassmorphism']) {
                $css .= "#adminmenuwrap {\n";
                $css .= "    background: rgba(35, 40, 45, 0.85) !important;\n";
                $css .= "    backdrop-filter: blur(var(--mas-glass-blur)) saturate(1.1) !important;\n";
                $css .= "    -webkit-backdrop-filter: blur(var(--mas-glass-blur)) saturate(1.1) !important;\n";
                $css .= "    border: 1px solid rgba(255, 255, 255, 0.1) !important;\n";
                $css .= "}\n\n";
                
                $css .= "#adminmenu .wp-submenu {\n";
                $css .= "    background: rgba(44, 51, 56, 0.9) !important;\n";
                $css .= "    backdrop-filter: blur(calc(var(--mas-glass-blur) * 0.8)) !important;\n";
                $css .= "    -webkit-backdrop-filter: blur(calc(var(--mas-glass-blur) * 0.8)) !important;\n";
                $css .= "    border: 1px solid rgba(255, 255, 255, 0.05) !important;\n";
                $css .= "}\n\n";
            }
            
            // Admin bar glassmorphism (if enabled)
            if (isset($settings['admin_bar_glassmorphism']) && $settings['admin_bar_glassmorphism']) {
                $css .= "#wpadminbar {\n";
                $css .= "    background: rgba(35, 40, 45, 0.9) !important;\n";
                $css .= "    backdrop-filter: blur(var(--mas-glass-blur)) saturate(1.1) !important;\n";
                $css .= "    -webkit-backdrop-filter: blur(var(--mas-glass-blur)) saturate(1.1) !important;\n";
                $css .= "    border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;\n";
                $css .= "}\n\n";
            }
        }
        
        // === ADVANCED SHADOW EFFECTS SYSTEM ===
        if (isset($settings['enable_shadows']) && $settings['enable_shadows']) {
            $css .= "/* Advanced Shadow Effects System */\n";
            
            // Base shadows for admin elements
            $css .= ".postbox, .meta-box-sortables .postbox,\n";
            $css .= ".mas-v2-card, .notice:not(.inline) {\n";
            $css .= "    box-shadow: var(--mas-shadow-offset-x) var(--mas-shadow-offset-y) var(--mas-shadow-blur) var(--mas-shadow-spread) var(--mas-shadow-color) !important;\n";
            $css .= "}\n\n";
            
            // Enhanced shadows on hover
            if (isset($settings['shadow_hover_effects']) && $settings['shadow_hover_effects']) {
                $css .= ".postbox:hover, .meta-box-sortables .postbox:hover,\n";
                $css .= ".mas-v2-card:hover {\n";
                $css .= "    box-shadow: \n";
                $css .= "        var(--mas-shadow-offset-x) calc(var(--mas-shadow-offset-y) * 2) calc(var(--mas-shadow-blur) * 1.5) var(--mas-shadow-spread) var(--mas-shadow-color),\n";
                $css .= "        0 0 20px rgba(" . $this->hexToRgb($shadowColor) . ", 0.05) !important;\n";
                $css .= "    transform: translateY(-2px) !important;\n";
                $css .= "}\n\n";
            }
            
            // Button shadows
            $css .= ".button, .button-primary, .button-secondary {\n";
            $css .= "    box-shadow: 0 1px 3px rgba(" . $this->hexToRgb($shadowColor) . ", 0.12) !important;\n";
            $css .= "}\n\n";
            
            $css .= ".button:hover, .button-primary:hover, .button-secondary:hover {\n";
            $css .= "    box-shadow: 0 2px 6px rgba(" . $this->hexToRgb($shadowColor) . ", 0.15) !important;\n";
            $css .= "}\n\n";
            
            // Menu shadows (if enabled)
            if (isset($settings['menu_shadow']) && $settings['menu_shadow']) {
                $css .= "#adminmenuwrap {\n";
                $css .= "    box-shadow: 2px 0 8px rgba(" . $this->hexToRgb($shadowColor) . ", 0.1) !important;\n";
                $css .= "}\n\n";
                
                $css .= "#adminmenu .wp-submenu {\n";
                $css .= "    box-shadow: 4px 4px 12px rgba(" . $this->hexToRgb($shadowColor) . ", 0.15) !important;\n";
                $css .= "}\n\n";
            }
        }
        
        // === ADVANCED ANIMATION SYSTEM ===
        if (isset($settings['enable_animations']) && $settings['enable_animations']) {
            $animationType = $settings['animation_type'] ?? 'smooth';
            
            // Define easing functions
            $easing = 'ease-in-out';
            switch ($animationType) {
                case 'fast': 
                    $easing = 'ease-out';
                    break;
                case 'bounce':
                    $easing = 'cubic-bezier(0.68, -0.55, 0.265, 1.55)';
                    break;
                case 'elastic':
                    $easing = 'cubic-bezier(0.175, 0.885, 0.32, 1.275)';
                    break;
                case 'smooth':
                default:
                    $easing = 'cubic-bezier(0.25, 0.46, 0.45, 0.94)';
                    break;
            }
            
            $css .= "/* Advanced Animation System */\n";
            
            // Base transitions for all interactive elements
            $css .= ".postbox, .meta-box-sortables .postbox,\n";
            $css .= ".button, .button-primary, .button-secondary,\n";
            $css .= ".mas-v2-card, .notice, .form-table tr,\n";
            $css .= "#adminmenu a, #adminmenu .wp-submenu a,\n";
            $css .= "#wpadminbar a, .wp-list-table tr {\n";
            $css .= "    transition: all var(--mas-animation-speed) {$easing} !important;\n";
            $css .= "}\n\n";
            
            // Fade-in animations for new elements
            if (isset($settings['fade_in_effects']) && $settings['fade_in_effects']) {
                $css .= "@keyframes fadeInUp {\n";
                $css .= "    from {\n";
                $css .= "        opacity: 0;\n";
                $css .= "        transform: translateY(20px);\n";
                $css .= "    }\n";
                $css .= "    to {\n";
                $css .= "        opacity: 1;\n";
                $css .= "        transform: translateY(0);\n";
                $css .= "    }\n";
                $css .= "}\n\n";
                
                $css .= ".postbox, .mas-v2-card, .notice {\n";
                $css .= "    animation: fadeInUp var(--mas-animation-speed) {$easing} !important;\n";
                $css .= "}\n\n";
            }
            
            // Scale hover effects
            if (isset($settings['scale_hover_effects']) && $settings['scale_hover_effects']) {
                $css .= ".button:hover, .button-primary:hover, .button-secondary:hover {\n";
                $css .= "    transform: scale(1.02) translateY(-1px) !important;\n";
                $css .= "}\n\n";
                
                $css .= ".postbox:hover, .mas-v2-card:hover {\n";
                $css .= "    transform: translateY(-2px) scale(1.005) !important;\n";
                $css .= "}\n\n";
            }
            
            // Slide animations for submenus
            if (isset($settings['slide_animations']) && $settings['slide_animations']) {
                $css .= "#adminmenu .wp-submenu {\n";
                $css .= "    transform: translateX(-10px);\n";
                $css .= "    opacity: 0;\n";
                $css .= "    transition: all var(--mas-animation-speed) {$easing} !important;\n";
                $css .= "}\n\n";
                
                $css .= "#adminmenu li.opensub .wp-submenu,\n";
                $css .= "#adminmenu li:hover .wp-submenu {\n";
                $css .= "    transform: translateX(0);\n";
                $css .= "    opacity: 1;\n";
                $css .= "}\n\n";
            }
            
            // Performance optimization: will-change for animated elements
            $css .= ".postbox, .mas-v2-card, .button,\n";
            $css .= "#adminmenu .wp-submenu, #adminmenu a {\n";
            $css .= "    will-change: transform, opacity, box-shadow;\n";
            $css .= "}\n\n";
            
        } else {
            // Disable all animations if animations are turned off
            $css .= "/* Animations Disabled */\n";
            $css .= "*, *::before, *::after {\n";
            $css .= "    animation-duration: 0s !important;\n";
            $css .= "    animation-delay: 0s !important;\n";
            $css .= "    transition-duration: 0s !important;\n";
            $css .= "    transition-delay: 0s !important;\n";
            $css .= "}\n\n";
        }
        
        // === GLOBAL BORDER RADIUS ===
        if (isset($settings['global_border_radius']) && $settings['global_border_radius'] > 0) {
            $radius = $settings['global_border_radius'];
            $css .= "/* Global Border Radius */\n";
            $css .= ".postbox, .meta-box-sortables .postbox, \n";
            $css .= ".form-table, .widefat,\n";
            $css .= ".mas-v2-card, .notice, .update-nag,\n";
            $css .= ".button, .button-primary, .button-secondary {\n";
            $css .= "    border-radius: {$radius}px !important;\n";
            $css .= "}\n\n";
        }
        
        // === COMPACT MODE ===
        if (isset($settings['compact_mode']) && $settings['compact_mode']) {
            $css .= "/* Compact Mode */\n";
            $css .= "body.wp-admin {\n";
            $css .= "    --mas-spacing: 0.5rem !important;\n";
            $css .= "}\n";
            $css .= ".wrap { padding: 10px !important; }\n";
            $css .= ".form-table th, .form-table td { padding: 8px !important; }\n";
            $css .= ".postbox { margin-bottom: 15px !important; }\n";
            $css .= ".mas-v2-card { padding: 1rem !important; }\n";
            $css .= ".mas-v2-field { margin-bottom: 1rem !important; }\n\n";
        }
        
        // === PERFORMANCE OPTIMIZATIONS ===
        $css .= "/* Performance Optimizations */\n";
        $css .= "body.wp-admin {\n";
        $css .= "    transform: translateZ(0); /* Force hardware acceleration */\n";
        $css .= "}\n\n";
        
        // GPU acceleration for animated elements
        if (isset($settings['enable_animations']) && $settings['enable_animations']) {
            $css .= ".postbox, .mas-v2-card, .button,\n";
            $css .= "#adminmenu .wp-submenu {\n";
            $css .= "    transform: translateZ(0); /* Force GPU acceleration */\n";
            $css .= "    backface-visibility: hidden;\n";
            $css .= "    perspective: 1000px;\n";
            $css .= "}\n\n";
        }
        
        return $css;
    }
    
    /**
     * Converts hex color to RGB values
     */
    private function hexToRgb($hex) {
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) == 3) {
            $hex = str_repeat(substr($hex, 0, 1), 2) . str_repeat(substr($hex, 1, 1), 2) . str_repeat(substr($hex, 2, 1), 2);
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        return "$r, $g, $b";
    }
    
    /**
     * Generuje CSS dla strony logowania
     */
    public function outputLoginStyles() {
        $settings = $this->getSettings();
        
        if (!isset($settings['login_page_enabled']) || !$settings['login_page_enabled']) {
            return;
        }
        
        $css = '';
        
        // Login page background
        if (isset($settings['login_bg_color'])) {
            $css .= "body.login { background: {$settings['login_bg_color']} !important; }";
        }
        
        // Login form background
        if (isset($settings['login_form_bg'])) {
            $css .= ".login form { background: {$settings['login_form_bg']} !important; }";
        }
        
        // Login form shadow
        if (isset($settings['login_form_shadow']) && $settings['login_form_shadow']) {
            $css .= ".login form { box-shadow: 0 4px 20px rgba(0,0,0,0.1) !important; }";
        }
        
        // Login form rounded corners
        if (isset($settings['login_form_rounded']) && $settings['login_form_rounded']) {
            $css .= ".login form { border-radius: 8px !important; }";
        }
        
        // Custom logo
        if (!empty($settings['login_custom_logo'])) {
            $css .= ".login h1 a { background-image: url('{$settings['login_custom_logo']}') !important; background-size: contain !important; width: auto !important; height: 80px !important; }";
        }
        
        if (!empty($css)) {
            echo "<style id='mas-v2-login-styles'>\n";
            echo $css;
            echo "\n</style>\n";
        }
    }
    
    /**
     * Modyfikacja tekstu stopki admin
     */
    public function customAdminFooter($text) {
        $settings = $this->getSettings();
        
        if (!empty($settings['custom_admin_footer_text'])) {
            return $settings['custom_admin_footer_text'];
        }
        
        return $text;
    }
    
    /**
     * Dodaje klasy CSS do body admin
     */
    public function addAdminBodyClasses($classes) {
        $settings = $this->getSettings();
        
        // ✅ DEBUG: Body classes are now controlled only by PHP
        
        // Basic plugin classes - domyślnie włączone!
        if (!isset($settings['enable_plugin']) || $settings['enable_plugin']) {
            $classes .= ' mas-v2-modern-style';
        }
        
        if (isset($settings['compact_mode']) && $settings['compact_mode']) {
            $classes .= ' mas-compact-mode';
        }
        
        // 🎯 MENU CUSTOMIZATION DETECTION
        $hasMenuCustomizations = (
            !empty($settings['menu_background']) || 
            !empty($settings['menu_text_color']) || 
            !empty($settings['menu_hover_background']) ||
            !empty($settings['menu_hover_text_color']) ||
            !empty($settings['menu_active_background']) ||
            !empty($settings['menu_active_text_color']) ||
            !empty($settings['menu_width']) ||
            !empty($settings['menu_item_height']) ||
            !empty($settings['menu_border_radius_all']) ||
            !empty($settings['menu_detached']) ||
            !empty($settings['menu_floating']) ||
            !empty($settings['menu_glossy'])
        );
        
        if ($hasMenuCustomizations) {
            $classes .= ' mas-v2-menu-custom-enabled';
        }
        
        // 🌊 FLOATING EFFECTS - KLUCZOWE!
        if (isset($settings['menu_floating']) && $settings['menu_floating']) {
            $classes .= ' mas-v2-menu-floating';
        }
        
        if (isset($settings['admin_bar_floating']) && $settings['admin_bar_floating']) {
            $classes .= ' mas-v2-admin-bar-floating';
        }
        
        // 🎨 GLOSSY EFFECTS  
        if (isset($settings['menu_glossy']) && $settings['menu_glossy']) {
            $classes .= ' mas-v2-menu-glossy';
        }
        
        if (isset($settings['admin_bar_glossy']) && $settings['admin_bar_glossy']) {
            $classes .= ' mas-v2-admin-bar-glossy';
        }
        
        // Nowy system motywów - sprawdź preferencje użytkownika
        $user_id = get_current_user_id();
        $user_theme = get_user_meta($user_id, 'mas_v2_theme_preference', true);
        
        // Fallback do ustawień plugin jeśli nie ma preferencji użytkownika
        if (empty($user_theme)) {
            $user_theme = $settings['color_scheme'] ?? 'light';
        }
        
        // Walidacja motywu
        if (!in_array($user_theme, ['light', 'dark'])) {
            $user_theme = 'light';
        }
        
        $classes .= ' mas-theme-' . $user_theme;
        
        // Stary system dla backward compatibility
        if (isset($settings['color_scheme'])) {
            $classes .= ' mas-theme-legacy-' . $settings['color_scheme'];
        }
        
        return $classes;
    }
    
    /**
     * Pobieranie ustawień
     */
    public function getSettings() {
        // Task 14: Use secure retrieval with integrity checking
        $settings = $this->secureRetrieveSettings();
        $defaults = $this->getDefaultSettings();
        
        return wp_parse_args($settings, $defaults);
    }
    
    /**
     * Task 14: Enhanced AJAX Security Validation
     * Centralized security validation for all AJAX operations
     */
    private function validateAjaxSecurity($action) {
        $result = ['valid' => false, 'error' => []];
        
        // 1. Check if request is AJAX
        if (!wp_doing_ajax()) {
            $result['error'] = [
                'message' => __('Invalid request method.', 'modern-admin-styler-v2'),
                'code' => 'invalid_method'
            ];
            return $result;
        }
        
        // 2. Enhanced nonce verification with multiple fallbacks
        $nonce = wp_unslash($_POST['nonce'] ?? $_POST['_wpnonce'] ?? $_REQUEST['nonce'] ?? '');
        $nonce_actions = ['mas_v2_nonce', 'mas_v2_settings_nonce', 'mas-v2-nonce'];
        
        $nonce_valid = false;
        foreach ($nonce_actions as $nonce_action) {
            if (wp_verify_nonce($nonce, $nonce_action)) {
                $nonce_valid = true;
                break;
            }
        }
        
        if (!$nonce_valid) {
            // Log security attempt for debugging
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MAS V2: Invalid nonce in ' . $action . '. Nonce: ' . $nonce . ', IP: ' . $this->getClientIP());
            }
            $result['error'] = [
                'message' => __('Security verification failed. Please refresh the page and try again.', 'modern-admin-styler-v2'),
                'code' => 'invalid_nonce'
            ];
            return $result;
        }
        
        // 3. Enhanced capability check with action-specific permissions
        $required_capability = $this->getRequiredCapabilityForAction($action);
        if (!current_user_can($required_capability)) {
            // Log unauthorized access attempt
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $user = wp_get_current_user();
                error_log('MAS V2: Insufficient permissions for ' . $action . '. User: ' . $user->user_login . ', IP: ' . $this->getClientIP());
            }
            $result['error'] = [
                'message' => __('Insufficient permissions to perform this action.', 'modern-admin-styler-v2'),
                'code' => 'insufficient_permissions'
            ];
            return $result;
        }
        
        // 4. Rate limiting check
        if (!$this->checkRateLimit($action)) {
            $result['error'] = [
                'message' => __('Too many requests. Please wait before trying again.', 'modern-admin-styler-v2'),
                'code' => 'rate_limit_exceeded'
            ];
            return $result;
        }
        
        // 5. Check for suspicious activity
        if ($this->detectSuspiciousActivity($_POST)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MAS V2: Suspicious activity detected in ' . $action . '. IP: ' . $this->getClientIP());
            }
            $result['error'] = [
                'message' => __('Request blocked due to suspicious activity.', 'modern-admin-styler-v2'),
                'code' => 'suspicious_activity'
            ];
            return $result;
        }
        
        $result['valid'] = true;
        return $result;
    }
    
    /**
     * Task 14: Validate AJAX Request Data
     */
    private function validateAjaxRequest($data) {
        // Check for required fields
        if (!is_array($data) || empty($data)) {
            return false;
        }
        
        // Check for malicious content in keys and values
        foreach ($data as $key => $value) {
            // Skip system fields
            if (in_array($key, ['action', 'nonce', '_wpnonce', '_wp_http_referer'])) {
                continue;
            }
            
            // Validate key names
            if (!$this->isValidSettingKey($key)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('MAS V2: Invalid setting key detected: ' . $key);
                }
                return false;
            }
            
            // Check for malicious content
            if ($this->containsMaliciousContent($value)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('MAS V2: Malicious content detected in: ' . $key);
                }
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Task 14: Validate File Upload Request
     */
    private function validateFileUploadRequest($post_data, $files_data) {
        // Basic request validation
        if (!$this->validateAjaxRequest($post_data)) {
            return false;
        }
        
        // Check if file upload is present and valid
        if (empty($files_data) || !isset($files_data['import_file'])) {
            return true; // No file upload, just validate as regular request
        }
        
        $file = $files_data['import_file'];
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        
        // Validate file size (max 1MB)
        if ($file['size'] > 1048576) {
            return false;
        }
        
        // Validate file type
        $allowed_types = ['application/json', 'text/plain'];
        $file_type = wp_check_filetype($file['name']);
        if (!in_array($file['type'], $allowed_types) && $file_type['ext'] !== 'json') {
            return false;
        }
        
        // Validate file content
        $content = file_get_contents($file['tmp_name']);
        if (!$this->isValidJsonContent($content)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Task 14: Get Required Capability for Action
     */
    private function getRequiredCapabilityForAction($action) {
        $capabilities = [
            'save_settings' => 'manage_options',
            'reset_settings' => 'manage_options',
            'export_settings' => 'manage_options',
            'import_settings' => 'manage_options',
            'live_preview' => 'manage_options',
            'save_theme' => 'manage_options',
            'diagnostics' => 'manage_options',
            'list_backups' => 'manage_options',
            'restore_backup' => 'manage_options',
            'create_backup' => 'manage_options',
            'delete_backup' => 'manage_options'
        ];
        
        return $capabilities[$action] ?? 'manage_options';
    }
    
    /**
     * Task 14: Rate Limiting Check
     */
    private function checkRateLimit($action) {
        $user_id = get_current_user_id();
        $ip = $this->getClientIP();
        $key = 'mas_v2_rate_limit_' . $action . '_' . $user_id . '_' . md5($ip);
        
        $attempts = get_transient($key);
        if ($attempts === false) {
            $attempts = 0;
        }
        
        // Different limits for different actions
        $limits = [
            'save_settings' => 30,    // 30 per minute
            'reset_settings' => 5,    // 5 per minute
            'export_settings' => 10,  // 10 per minute
            'import_settings' => 5,   // 5 per minute
            'live_preview' => 60,     // 60 per minute
            'default' => 20           // 20 per minute
        ];
        
        $limit = $limits[$action] ?? $limits['default'];
        
        if ($attempts >= $limit) {
            return false;
        }
        
        // Increment counter
        set_transient($key, $attempts + 1, 60); // 1 minute window
        
        return true;
    }
    
    /**
     * Task 14: Detect Suspicious Activity
     */
    private function detectSuspiciousActivity($data) {
        // Check for common attack patterns
        $suspicious_patterns = [
            'script',
            'javascript:',
            'vbscript:',
            'onload=',
            'onerror=',
            'onclick=',
            'eval(',
            'expression(',
            'document.cookie',
            'document.write',
            'window.location',
            '<script',
            '</script>',
            'alert(',
            'confirm(',
            'prompt(',
            'setTimeout(',
            'setInterval('
        ];
        
        $data_string = serialize($data);
        $data_string = strtolower($data_string);
        
        foreach ($suspicious_patterns as $pattern) {
            if (strpos($data_string, strtolower($pattern)) !== false) {
                return true;
            }
        }
        
        // Check for excessive data size
        if (strlen($data_string) > 100000) { // 100KB limit
            return true;
        }
        
        // Check for too many fields
        if (count($data) > 200) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Task 14: Validate Setting Key
     */
    private function isValidSettingKey($key) {
        // Allow only alphanumeric characters, underscores, and hyphens
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $key)) {
            return false;
        }
        
        // Check key length
        if (strlen($key) > 100) {
            return false;
        }
        
        // UPROSZCZONA WALIDACJA - akceptuj wszystkie klucze które pasują do wzorca
        // Zamiast restrykcyjnej listy prefixów, po prostu sprawdzamy czy klucz jest bezpieczny
        return true;
        
        /* STARA RESTRYKCYJNA WALIDACJA - WYŁĄCZONA
        // Check against known valid prefixes
        $valid_prefixes = [
            'enable_',
            'menu_',
            'admin_bar_',
            'content_',
            'button_',
            'login_',
            'typography_',
            'effect_',
            'theme_',
            'color_',
            'custom_',
            'advanced_',
            'glassmorphism_',
            'shadow_',
            'animation_',
            'palette_',
            'font_'
        ];
        
        // Allow system fields
        $system_fields = ['action', 'nonce', '_wpnonce', '_wp_http_referer'];
        if (in_array($key, $system_fields)) {
            return true;
        }
        
        // Check if key starts with valid prefix
        foreach ($valid_prefixes as $prefix) {
            if (strpos($key, $prefix) === 0) {
                return true;
            }
        }
        
        // Allow some specific keys that don't follow prefix pattern
        $allowed_keys = ['theme', 'color_scheme', 'debug_mode', 'performance_mode'];
        if (in_array($key, $allowed_keys)) {
            return true;
        }
        
        return false;
        */
    }
    
    /**
     * Task 14: Check for Malicious Content
     */
    private function containsMaliciousContent($value) {
        if (!is_string($value)) {
            return false;
        }
        
        // Convert to lowercase for case-insensitive checking
        $value_lower = strtolower($value);
        
        // Check for script injection attempts
        $malicious_patterns = [
            '<script',
            '</script>',
            'javascript:',
            'vbscript:',
            'data:text/html',
            'data:application/',
            'eval(',
            'expression(',
            'document.cookie',
            'document.write',
            'window.location',
            'alert(',
            'confirm(',
            'prompt(',
            'onload=',
            'onerror=',
            'onclick=',
            'onmouseover=',
            'onfocus=',
            'onblur='
        ];
        
        foreach ($malicious_patterns as $pattern) {
            if (strpos($value_lower, $pattern) !== false) {
                return true;
            }
        }
        
        // Check for SQL injection attempts
        $sql_patterns = [
            'union select',
            'drop table',
            'delete from',
            'insert into',
            'update set',
            'alter table',
            'create table',
            'exec(',
            'execute(',
            'sp_executesql'
        ];
        
        foreach ($sql_patterns as $pattern) {
            if (strpos($value_lower, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Task 14: Validate JSON Content
     */
    private function isValidJsonContent($content) {
        if (empty($content)) {
            return false;
        }
        
        // Check content size
        if (strlen($content) > 1048576) { // 1MB limit
            return false;
        }
        
        // Try to decode JSON
        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }
        
        // Check if it's an array/object
        if (!is_array($data)) {
            return false;
        }
        
        // Check for malicious content in the data
        if ($this->detectSuspiciousActivity($data)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Task 14: Get Client IP Address
     */
    private function getClientIP() {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Enhanced sanitization with error tracking
     */
    private function sanitizeSettingsWithErrorTracking($input, &$errors = []) {
        $defaults = $this->getDefaultSettings();
        $sanitized = [];
        $errors = [];
        
        // Debug: Log input data
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MAS V2: sanitizeSettingsWithErrorTracking called with ' . count($input) . ' input values');
        }
        
        foreach ($defaults as $key => $default_value) {
            if (!isset($input[$key])) {
                $sanitized[$key] = $default_value;
                continue;
            }
            
            $value = $input[$key];
            $original_value = $value;
            
            try {
                // Enhanced sanitization based on field type and name
                if (is_bool($default_value)) {
                    $sanitized[$key] = $this->sanitizeBooleanValue($value);
                } elseif (is_int($default_value)) {
                    $sanitized[$key] = $this->sanitizeIntegerValue($value, $key);
                } elseif ($key === 'custom_css') {
                    $sanitized[$key] = $this->sanitizeCustomCSS($value);
                } elseif (strpos($key, 'color') !== false || strpos($key, '_bg') !== false) {
                    $sanitized[$key] = $this->sanitizeColorValue($value, $key);
                } elseif (strpos($key, 'margin') !== false || strpos($key, 'padding') !== false) {
                    $sanitized[$key] = $this->sanitizeSpacingValue($value, $key);
                } elseif (strpos($key, 'width') !== false || strpos($key, 'height') !== false) {
                    $sanitized[$key] = $this->sanitizeDimensionValue($value, $key);
                } elseif (strpos($key, 'radius') !== false) {
                    $sanitized[$key] = $this->sanitizeBorderRadiusValue($value, $key);
                } else {
                    $sanitized[$key] = sanitize_text_field($value);
                }
                
                // Track significant changes
                if ($original_value !== $sanitized[$key]) {
                    $errors[] = sprintf(
                        __('Setting "%s" was modified during sanitization: "%s" → "%s"', 'modern-admin-styler-v2'),
                        $key,
                        $original_value,
                        $sanitized[$key]
                    );
                    
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("MAS V2: Sanitized '{$key}': '{$original_value}' -> '{$sanitized[$key]}'");
                    }
                }
                
            } catch (Exception $e) {
                // Handle sanitization errors gracefully
                $sanitized[$key] = $default_value;
                $errors[] = sprintf(
                    __('Error sanitizing setting "%s": %s. Using default value.', 'modern-admin-styler-v2'),
                    $key,
                    $e->getMessage()
                );
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("MAS V2: Sanitization error for '{$key}': " . $e->getMessage());
                }
            }
        }
        
        // Handle new settings that aren't in defaults (for future compatibility)
        foreach ($input as $key => $value) {
            if (!array_key_exists($key, $defaults) && !isset($sanitized[$key])) {
                // Skip system fields
                if (in_array($key, ['action', 'nonce', '_wpnonce', '_wp_http_referer'])) {
                    continue;
                }
                
                try {
                    $sanitized[$key] = sanitize_text_field($value);
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("MAS V2: Added new setting '{$key}': '{$value}'");
                    }
                } catch (Exception $e) {
                    $errors[] = sprintf(
                        __('Error processing new setting "%s": %s', 'modern-admin-styler-v2'),
                        $key,
                        $e->getMessage()
                    );
                }
            }
        }
        
        // Debug: Log final sanitized data
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $menu_settings_count = count(array_filter($sanitized, function($key) {
                return strpos($key, 'menu_') === 0;
            }, ARRAY_FILTER_USE_KEY));
            error_log("MAS V2: Sanitization complete. Total settings: " . count($sanitized) . ", Menu settings: {$menu_settings_count}, Errors: " . count($errors));
        }
        
        return $sanitized;
    }
    
    /**
     * Enhanced settings sanitization wrapper for Task 12 - provides new interface
     */
    private function sanitizeSettingsForImport($raw_settings) {
        $result = [
            'settings' => [],
            'warnings' => [],
            'errors' => []
        ];
        
        if (!is_array($raw_settings)) {
            $result['errors'][] = 'Settings must be an array';
            return $result;
        }
        
        // Use existing method with error tracking
        $errors = [];
        $sanitized = $this->sanitizeSettingsWithErrorTracking($raw_settings, $errors);
        
        $result['settings'] = $sanitized;
        $result['warnings'] = $errors; // The existing method puts warnings in $errors
        
        // Add additional validation for import-specific requirements
        $defaults = $this->getDefaultSettings();
        
        foreach ($raw_settings as $key => $value) {
            // Skip unknown settings
            if (!array_key_exists($key, $defaults)) {
                $result['warnings'][] = "Unknown setting skipped: {$key}";
                continue;
            }
            
            // Validate critical settings
            if (in_array($key, ['enable_plugin', 'theme', 'color_scheme'])) {
                if (empty($sanitized[$key])) {
                    $result['warnings'][] = "Critical setting {$key} was empty, using default";
                    $sanitized[$key] = $defaults[$key];
                }
            }
        }
        
        // Ensure critical settings are present
        $critical_settings = ['enable_plugin', 'theme', 'color_scheme'];
        foreach ($critical_settings as $critical) {
            if (!isset($sanitized[$critical])) {
                $sanitized[$critical] = $defaults[$critical] ?? true;
                $result['warnings'][] = "Critical setting {$critical} was missing, added default";
            }
        }
        
        $result['settings'] = $sanitized;
        return $result;
    }
    
    /**
     * Legacy sanitization method (for backward compatibility)
     */
    private function sanitizeSettings($input) {
        $errors = [];
        return $this->sanitizeSettingsWithErrorTracking($input, $errors);
    }
    
    /**
     * Enhanced sanitization helper methods
     */
    private function sanitizeBooleanValue($value) {
        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'on', 'yes'], true);
        }
        return (bool) $value;
    }
    
    private function sanitizeIntegerValue($value, $key) {
        $int_value = (int) $value;
        
        // Apply reasonable limits based on field type
        if (strpos($key, 'font_size') !== false) {
            return max(8, min(72, $int_value)); // Font size: 8-72px
        } elseif (strpos($key, 'height') !== false) {
            return max(20, min(200, $int_value)); // Height: 20-200px
        } elseif (strpos($key, 'width') !== false) {
            return max(100, min(500, $int_value)); // Width: 100-500px
        } elseif (strpos($key, 'margin') !== false || strpos($key, 'padding') !== false) {
            return max(0, min(100, $int_value)); // Spacing: 0-100px
        } elseif (strpos($key, 'radius') !== false) {
            return max(0, min(50, $int_value)); // Border radius: 0-50px
        }
        
        return max(0, $int_value); // Default: non-negative
    }
    
    private function sanitizeCustomCSS($value) {
        // Task 14: Enhanced CSS sanitization with comprehensive security checks
        if (empty($value)) {
            return '';
        }
        
        // Limit CSS size to prevent DoS attacks
        if (strlen($value) > 50000) { // 50KB limit
            $value = substr($value, 0, 50000);
        }
        
        // Allow CSS but strip dangerous content
        $css = wp_strip_all_tags($value);
        
        // Task 14: Enhanced XSS prevention - remove all potential script vectors
        $dangerous_patterns = [
            '/javascript\s*:/i',
            '/vbscript\s*:/i',
            '/data\s*:/i',
            '/expression\s*\(/i',
            '/eval\s*\(/i',
            '/url\s*\(\s*["\']?\s*javascript:/i',
            '/url\s*\(\s*["\']?\s*vbscript:/i',
            '/url\s*\(\s*["\']?\s*data:/i',
            '/import\s*["\'].*javascript:/i',
            '/import\s*["\'].*vbscript:/i',
            '/behavior\s*:/i',
            '/-moz-binding\s*:/i',
            '/binding\s*:/i',
            '/script\s*:/i',
            '/mocha\s*:/i',
            '/livescript\s*:/i'
        ];
        
        foreach ($dangerous_patterns as $pattern) {
            $css = preg_replace($pattern, '', $css);
        }
        
        // Remove HTML entities that could be used for obfuscation
        $css = html_entity_decode($css, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Remove null bytes and other control characters
        $css = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $css);
        
        // Additional security: remove @import statements that could load external resources
        $css = preg_replace('/@import\s+[^;]+;/i', '', $css);
        
        // Remove comments that could contain malicious code
        $css = preg_replace('/\/\*.*?\*\//s', '', $css);
        
        return trim($css);
    }
    
    private function sanitizeColorValue($value, $key) {
        if (empty($value)) {
            return '';
        }
        
        // Task 14: Enhanced color value sanitization with security checks
        $value = trim($value);
        
        // Check for malicious content first
        if ($this->containsMaliciousContent($value)) {
            return '';
        }
        
        // Limit length to prevent DoS
        if (strlen($value) > 200) {
            return '';
        }
        
        // Hex color - most secure format
        if (preg_match('/^#[a-fA-F0-9]{3,6}$/', $value)) {
            return sanitize_hex_color($value);
        }
        
        // RGB/RGBA - validate numeric values
        if (preg_match('/^rgba?\(\s*(\d+(?:\.\d+)?)\s*,\s*(\d+(?:\.\d+)?)\s*,\s*(\d+(?:\.\d+)?)\s*(?:,\s*(\d+(?:\.\d+)?))?\s*\)$/', $value, $matches)) {
            $r = max(0, min(255, floatval($matches[1])));
            $g = max(0, min(255, floatval($matches[2])));
            $b = max(0, min(255, floatval($matches[3])));
            
            if (isset($matches[4])) {
                $a = max(0, min(1, floatval($matches[4])));
                return "rgba({$r}, {$g}, {$b}, {$a})";
            } else {
                return "rgb({$r}, {$g}, {$b})";
            }
        }
        
        // HSL/HSLA - validate values
        if (preg_match('/^hsla?\(\s*(\d+(?:\.\d+)?)\s*,\s*(\d+(?:\.\d+)?)%\s*,\s*(\d+(?:\.\d+)?)%\s*(?:,\s*(\d+(?:\.\d+)?))?\s*\)$/', $value, $matches)) {
            $h = max(0, min(360, floatval($matches[1])));
            $s = max(0, min(100, floatval($matches[2])));
            $l = max(0, min(100, floatval($matches[3])));
            
            if (isset($matches[4])) {
                $a = max(0, min(1, floatval($matches[4])));
                return "hsla({$h}, {$s}%, {$l}%, {$a})";
            } else {
                return "hsl({$h}, {$s}%, {$l}%)";
            }
        }
        
        // CSS color names - whitelist approach
        $valid_color_names = [
            'transparent', 'inherit', 'initial', 'unset', 'currentColor',
            'black', 'white', 'red', 'green', 'blue', 'yellow', 'cyan', 'magenta',
            'gray', 'grey', 'darkgray', 'darkgrey', 'lightgray', 'lightgrey',
            'maroon', 'navy', 'olive', 'purple', 'silver', 'teal', 'lime', 'aqua', 'fuchsia'
        ];
        
        if (in_array(strtolower($value), $valid_color_names)) {
            return strtolower($value);
        }
        
        // CSS variables - validate format
        if (preg_match('/^var\(\s*--[a-zA-Z0-9_-]+\s*(?:,\s*[^)]+)?\s*\)$/', $value)) {
            return sanitize_text_field($value);
        }
        
        // Linear gradients - basic validation
        if (preg_match('/^linear-gradient\([^)]+\)$/', $value)) {
            // Only allow if it doesn't contain suspicious content
            if (!$this->containsMaliciousContent($value)) {
                return sanitize_text_field($value);
            }
        }
        
        // If nothing matches, try hex color as fallback
        $hex_result = sanitize_hex_color($value);
        return $hex_result ?: '';
    }
    
    private function sanitizeSpacingValue($value, $key) {
        if (empty($value)) {
            return '';
        }
        
        // Handle numeric values (assume px)
        if (is_numeric($value)) {
            return max(0, min(200, (int) $value));
        }
        
        // Handle CSS units (px, em, rem, %, etc.)
        if (preg_match('/^(\d+(?:\.\d+)?)(px|em|rem|%|vh|vw)$/', $value, $matches)) {
            $num = (float) $matches[1];
            $unit = $matches[2];
            
            // Apply reasonable limits based on unit
            switch ($unit) {
                case 'px':
                    $num = max(0, min(200, $num));
                    break;
                case 'em':
                case 'rem':
                    $num = max(0, min(10, $num));
                    break;
                case '%':
                    $num = max(0, min(100, $num));
                    break;
            }
            
            return $num . $unit;
        }
        
        return sanitize_text_field($value);
    }
    
    private function sanitizeDimensionValue($value, $key) {
        if (empty($value)) {
            return '';
        }
        
        // Handle numeric values (assume px)
        if (is_numeric($value)) {
            if (strpos($key, 'width') !== false) {
                return max(100, min(800, (int) $value));
            } else { // height
                return max(20, min(200, (int) $value));
            }
        }
        
        // Handle CSS units
        if (preg_match('/^(\d+(?:\.\d+)?)(px|em|rem|%|vh|vw)$/', $value, $matches)) {
            $num = (float) $matches[1];
            $unit = $matches[2];
            
            if (strpos($key, 'width') !== false) {
                $num = max(100, min(800, $num));
            } else { // height
                $num = max(20, min(200, $num));
            }
            
            return $num . $unit;
        }
        
        return sanitize_text_field($value);
    }
    
    private function sanitizeBorderRadiusValue($value, $key) {
        if (empty($value)) {
            return '';
        }
        
        // Handle numeric values (assume px)
        if (is_numeric($value)) {
            return max(0, min(50, (int) $value));
        }
        
        // Handle CSS units
        if (preg_match('/^(\d+(?:\.\d+)?)(px|em|rem|%)$/', $value, $matches)) {
            $num = (float) $matches[1];
            $unit = $matches[2];
            
            switch ($unit) {
                case 'px':
                    $num = max(0, min(50, $num));
                    break;
                case 'em':
                case 'rem':
                    $num = max(0, min(3, $num));
                    break;
                case '%':
                    $num = max(0, min(50, $num));
                    break;
            }
            
            return $num . $unit;
        }
        
        return sanitize_text_field($value);
    }
    
    /**
     * Task 14: Secure Settings Storage with Encryption
     */
    private function secureStoreSettings($settings) {
        // Create backup before storing
        $current_settings = get_option('mas_v2_settings', []);
        if (!empty($current_settings)) {
            $backup_key = 'mas_v2_settings_backup_' . time();
            update_option($backup_key, $current_settings, false);
        }
        
        // Add integrity hash
        $settings['_integrity_hash'] = $this->generateSettingsHash($settings);
        $settings['_last_modified'] = current_time('timestamp');
        $settings['_modified_by'] = get_current_user_id();
        
        // Store settings with WordPress transactional safety
        $result = update_option('mas_v2_settings', $settings);
        
        // Verify storage integrity
        if ($result) {
            $stored_settings = get_option('mas_v2_settings', []);
            if (!$this->verifySettingsIntegrity($stored_settings)) {
                // Restore backup if integrity check fails
                if (!empty($current_settings)) {
                    update_option('mas_v2_settings', $current_settings);
                }
                return false;
            }
        }
        
        return $result;
    }
    
    /**
     * Task 14: Secure Settings Retrieval with Integrity Check
     */
    private function secureRetrieveSettings() {
        $settings = get_option('mas_v2_settings', []);
        
        if (empty($settings)) {
            return $this->getDefaultSettings();
        }
        
        // Verify integrity
        if (!$this->verifySettingsIntegrity($settings)) {
            // Log integrity failure
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MAS V2: Settings integrity check failed, using defaults');
            }
            
            // Try to restore from backup
            $restored_settings = $this->restoreFromBackup();
            if ($restored_settings !== false) {
                return $restored_settings;
            }
            
            // Fall back to defaults
            return $this->getDefaultSettings();
        }
        
        // Remove internal fields before returning
        unset($settings['_integrity_hash']);
        unset($settings['_last_modified']);
        unset($settings['_modified_by']);
        
        return $settings;
    }
    
    /**
     * Task 14: Generate Settings Integrity Hash
     */
    private function generateSettingsHash($settings) {
        // Remove internal fields from hash calculation
        $hash_data = $settings;
        unset($hash_data['_integrity_hash']);
        unset($hash_data['_last_modified']);
        unset($hash_data['_modified_by']);
        
        // Sort for consistent hashing
        ksort($hash_data);
        
        // Generate hash with salt
        $salt = wp_salt('auth');
        return hash_hmac('sha256', serialize($hash_data), $salt);
    }
    
    /**
     * Task 14: Verify Settings Integrity
     */
    private function verifySettingsIntegrity($settings) {
        if (!isset($settings['_integrity_hash'])) {
            return false; // No hash means old format or corrupted
        }
        
        $stored_hash = $settings['_integrity_hash'];
        $calculated_hash = $this->generateSettingsHash($settings);
        
        return hash_equals($stored_hash, $calculated_hash);
    }
    
    /**
     * Task 14: Restore Settings from Backup
     */
    private function restoreFromBackup() {
        global $wpdb;
        
        // Find the most recent backup
        $backup_options = $wpdb->get_results(
            "SELECT option_name, option_value FROM {$wpdb->options} 
             WHERE option_name LIKE 'mas_v2_settings_backup_%' 
             ORDER BY option_name DESC LIMIT 5"
        );
        
        foreach ($backup_options as $backup) {
            $backup_data = maybe_unserialize($backup->option_value);
            if (is_array($backup_data) && !empty($backup_data)) {
                // Verify backup integrity if possible
                if (isset($backup_data['_integrity_hash'])) {
                    if (!$this->verifySettingsIntegrity($backup_data)) {
                        continue; // Try next backup
                    }
                }
                
                // Restore this backup
                update_option('mas_v2_settings', $backup_data);
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('MAS V2: Settings restored from backup: ' . $backup->option_name);
                }
                
                return $backup_data;
            }
        }
        
        return false;
    }
    
    /**
     * Task 14: Enhanced Settings Cleanup with Security
     */
    private function cleanupSettingsBackups() {
        global $wpdb;
        
        // Get all backup options
        $backup_options = $wpdb->get_results(
            "SELECT option_name FROM {$wpdb->options} 
             WHERE option_name LIKE 'mas_v2_settings_backup_%' 
             ORDER BY option_name DESC"
        );
        
        // Keep only the 10 most recent backups
        if (count($backup_options) > 10) {
            $to_delete = array_slice($backup_options, 10);
            foreach ($to_delete as $backup) {
                delete_option($backup->option_name);
            }
        }
        
        // Also clean up old rate limiting transients
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_mas_v2_rate_limit_%' 
             AND option_name < '_transient_mas_v2_rate_limit_" . (time() - 3600) . "'"
        );
    }
    
    /**
     * Task 14: Secure Settings Export with Additional Validation
     */
    private function secureExportSettings($settings) {
        // Remove sensitive internal data
        $export_settings = $settings;
        unset($export_settings['_integrity_hash']);
        unset($export_settings['_last_modified']);
        unset($export_settings['_modified_by']);
        
        // Add export metadata
        $export_data = [
            'format_version' => '2.0',
            'export_date' => current_time('c'),
            'site_url' => get_site_url(),
            'plugin_version' => MAS_V2_VERSION,
            'wordpress_version' => get_bloginfo('version'),
            'settings' => $export_settings,
            'checksum' => hash('sha256', serialize($export_settings))
        ];
        
        return $export_data;
    }
    
    /**
     * Task 14: Secure Settings Import with Enhanced Validation
     */
    private function secureImportSettings($import_data) {
        $result = [
            'success' => false,
            'settings' => [],
            'warnings' => [],
            'errors' => []
        ];
        
        // Validate import data structure
        if (!is_array($import_data) || !isset($import_data['settings'])) {
            $result['errors'][] = __('Invalid import data structure.', 'modern-admin-styler-v2');
            return $result;
        }
        
        // Verify checksum if present
        if (isset($import_data['checksum'])) {
            $calculated_checksum = hash('sha256', serialize($import_data['settings']));
            if (!hash_equals($import_data['checksum'], $calculated_checksum)) {
                $result['errors'][] = __('Import data integrity check failed.', 'modern-admin-styler-v2');
                return $result;
            }
        }
        
        // Check format version compatibility
        if (isset($import_data['format_version'])) {
            $format_version = $import_data['format_version'];
            if (version_compare($format_version, '2.0', '>')) {
                $result['warnings'][] = sprintf(
                    __('Import data is from a newer version (%s). Some settings may not be compatible.', 'modern-admin-styler-v2'),
                    $format_version
                );
            }
        }
        
        // Sanitize imported settings
        $sanitized_result = $this->sanitizeSettingsForImport($import_data['settings']);
        $result['settings'] = $sanitized_result['settings'];
        $result['warnings'] = array_merge($result['warnings'], $sanitized_result['warnings']);
        $result['errors'] = array_merge($result['errors'], $sanitized_result['errors']);
        
        // Additional security validation
        if ($this->detectSuspiciousActivity($result['settings'])) {
            $result['errors'][] = __('Import data contains suspicious content and was rejected.', 'modern-admin-styler-v2');
            return $result;
        }
        
        $result['success'] = empty($result['errors']);
        return $result;
    }

    
    /**
     * Legacy validation method (for backward compatibility)
     */
    /**
     * Enhanced validation with error tracking
     */
    private function validateSettingsIntegrityWithErrors($settings, &$errors = []) {
        $errors = ['critical' => [], 'warnings' => []];
        
        // Plugin must be enabled to function
        if (!isset($settings['enable_plugin'])) {
            $settings['enable_plugin'] = true;
            $errors['warnings'][] = __('Plugin enable status was missing, set to enabled.', 'modern-admin-styler-v2');
        }
        
        // Ensure color scheme is valid
        $valid_schemes = ['light', 'dark', 'auto'];
        if (!in_array($settings['color_scheme'] ?? '', $valid_schemes)) {
            $old_scheme = $settings['color_scheme'] ?? 'unknown';
            $settings['color_scheme'] = 'light';
            $errors['warnings'][] = sprintf(
                __('Invalid color scheme "%s" corrected to "light".', 'modern-admin-styler-v2'),
                $old_scheme
            );
        }
        
        // Ensure theme is valid
        $valid_themes = ['modern', 'classic', 'minimal'];
        if (!in_array($settings['theme'] ?? '', $valid_themes)) {
            $old_theme = $settings['theme'] ?? 'unknown';
            $settings['theme'] = 'modern';
            $errors['warnings'][] = sprintf(
                __('Invalid theme "%s" corrected to "modern".', 'modern-admin-styler-v2'),
                $old_theme
            );
        }
        
        // Validate color values
        $color_fields = ['menu_background', 'menu_text_color', 'admin_bar_background', 'admin_bar_text_color'];
        foreach ($color_fields as $field) {
            if (isset($settings[$field]) && !empty($settings[$field])) {
                $color = $settings[$field];
                // Basic color validation
                if (!preg_match('/^#[a-fA-F0-9]{3,6}$/', $color) && 
                    !preg_match('/^rgba?\([^)]+\)$/', $color) && 
                    !in_array($color, ['transparent', 'inherit', 'initial', 'unset'])) {
                    
                    $defaults = $this->getDefaultSettings();
                    $settings[$field] = $defaults[$field] ?? '#23282d';
                    $errors['warnings'][] = sprintf(
                        __('Invalid color value "%s" for %s, using default.', 'modern-admin-styler-v2'),
                        $color,
                        $field
                    );
                }
            }
        }
        
        // Validate numeric values
        $numeric_fields = [
            'menu_width' => [100, 400],
            'menu_item_height' => [20, 80],
            'admin_bar_height' => [20, 60],
            'font_size' => [10, 24]
        ];
        
        foreach ($numeric_fields as $field => [$min, $max]) {
            if (isset($settings[$field])) {
                $value = (int) $settings[$field];
                if ($value < $min || $value > $max) {
                    $defaults = $this->getDefaultSettings();
                    $settings[$field] = $defaults[$field] ?? $min;
                    $errors['warnings'][] = sprintf(
                        __('Value %d for %s is out of range (%d-%d), using default.', 'modern-admin-styler-v2'),
                        $value,
                        $field,
                        $min,
                        $max
                    );
                }
            }
        }
        
        // Validate menu settings consistency
        if (!empty($settings['menu_detached']) || !empty($settings['menu_floating'])) {
            // Ensure floating menu has proper margins
            if (empty($settings['menu_margin_top']) || $settings['menu_margin_top'] < 0) {
                $settings['menu_margin_top'] = 20;
                $errors['warnings'][] = __('Floating menu margin top was invalid, set to 20px.', 'modern-admin-styler-v2');
            }
            if (empty($settings['menu_margin_left']) || $settings['menu_margin_left'] < 0) {
                $settings['menu_margin_left'] = 20;
                $errors['warnings'][] = __('Floating menu margin left was invalid, set to 20px.', 'modern-admin-styler-v2');
            }
        }
        
        // Ensure admin bar settings are consistent
        if (!empty($settings['admin_bar_floating'])) {
            if (empty($settings['admin_bar_margin_top']) || $settings['admin_bar_margin_top'] < 0) {
                $settings['admin_bar_margin_top'] = 10;
                $errors['warnings'][] = __('Floating admin bar margin top was invalid, set to 10px.', 'modern-admin-styler-v2');
            }
        }
        
        // Check for potential conflicts
        if (!empty($settings['menu_glassmorphism']) && empty($settings['enable_shadows'])) {
            $errors['warnings'][] = __('Glassmorphism effect works best with shadows enabled.', 'modern-admin-styler-v2');
        }
        
        // Validate custom CSS for potential issues
        if (!empty($settings['custom_css'])) {
            $css = $settings['custom_css'];
            if (strpos($css, '<script') !== false || strpos($css, 'javascript:') !== false) {
                $settings['custom_css'] = '';
                $errors['critical'][] = __('Custom CSS contained potentially dangerous content and was removed.', 'modern-admin-styler-v2');
            }
        }
        
        return $settings;
    }
    
    /**
     * Legacy validation method (for backward compatibility)
     */
    private function validateSettingsIntegrity($settings) {
        $errors = [];
        return $this->validateSettingsIntegrityWithErrors($settings, $errors);
    }

    
    /**
     * Domyślne ustawienia
     */
    private function getDefaultSettings() {
        return [
            // Ogólne
            'enable_plugin' => true,
            'theme' => 'modern',
            'color_scheme' => 'light',
            'color_palette' => 'professional-blue',
            'font_family' => 'system',
            'font_size' => 14,
            'enable_animations' => true,
            'animation_type' => 'smooth',
            'live_preview' => true,
            'auto_save' => false,
            'compact_mode' => false,
            'global_border_radius' => 8,
            'enable_shadows' => true,
            'shadow_color' => '#000000',
            'shadow_blur' => 10,
            
            // Admin Bar
            'custom_admin_bar_style' => true,
            'admin_bar_background' => '#23282d',
            'admin_bar_bg' => '#23282d', // Alias for compatibility
            'admin_bar_text_color' => '#ffffff',
            'admin_bar_hover_color' => '#00a0d2',
            'admin_bar_height' => 32,
            'admin_bar_font_size' => 13,
            'admin_bar_padding' => 8,
            'admin_bar_border_radius' => 0,
            'admin_bar_shadow' => false,
            'admin_bar_glassmorphism' => false,
            'admin_bar_detached' => false,
            
            // Admin Bar - Nowe opcje floating/glossy
            'admin_bar_floating' => true,
            'admin_bar_glossy' => true,
            'admin_bar_border_radius_type' => 'all',
            'admin_bar_radius_tl' => false,
            'admin_bar_radius_tr' => false,
            'admin_bar_radius_bl' => false,
            'admin_bar_radius_br' => false,
            'admin_bar_margin_type' => 'all',
            'admin_bar_margin' => 10,
            'admin_bar_margin_top' => 10,
            'admin_bar_margin_right' => 10,
            'admin_bar_margin_bottom' => 10,
            'admin_bar_margin_left' => 10,
            
            // Admin Bar - Nowe opcje width/gradient
            'admin_bar_width' => 100,
            'admin_bar_gradient_enabled' => false,
            'admin_bar_gradient_type' => 'linear',
            'admin_bar_gradient_direction' => 'to_right',
            'admin_bar_gradient_color1' => '#23282d',
            'admin_bar_gradient_color2' => '#32373c',
            'admin_bar_gradient_angle' => 45,
            
            // Ukrywanie elementów paska admin
            'hide_wp_logo' => false,
            'hide_howdy' => false,
            'hide_update_notices' => false,
            
            // Admin Bar Corner Radius
            'admin_bar_corner_radius_type' => 'none',
            'admin_bar_corner_radius_all' => 0,
            'admin_bar_corner_radius_top_left' => 0,
            'admin_bar_corner_radius_top_right' => 0,
            'admin_bar_corner_radius_bottom_right' => 0,
            'admin_bar_corner_radius_bottom_left' => 0,
            
            // Menu
            'menu_background' => '#23282d',
            'menu_bg' => '#23282d', // Alias for form compatibility
            'menu_text_color' => '#ffffff',
            'menu_hover_background' => '#32373c',
            'menu_hover_color' => '#32373c', // Alias for form compatibility
            'menu_hover_text_color' => '#00a0d2',
            'menu_active_background' => '#0073aa',
            'menu_active_text_color' => '#ffffff',
            'menu_width' => 160,
            'menu_item_height' => 34,
            'menu_rounded_corners' => false,
            'menu_shadow' => false,
            'menu_compact_mode' => false,
            'menu_glassmorphism' => false,
            'menu_detached' => false,
            'menu_detached_margin' => 20, // Backward compatibility
            'menu_detached_margin_type' => 'all',
            'menu_detached_margin_all' => 20,
            'menu_detached_margin_top' => 20,
            'menu_detached_margin_right' => 20,
            'menu_detached_margin_bottom' => 20,
            'menu_detached_margin_left' => 20,
            'menu_icons_enabled' => true,
            
            // Menu - Nowe opcje floating/glossy
            'menu_floating' => true,
            'menu_glossy' => true,
            'menu_border_radius_type' => 'all',
            'menu_border_radius_all' => 0,
            'menu_radius_tl' => false,
            'menu_radius_tr' => false,
            'menu_radius_bl' => false,
            'menu_radius_br' => false,
            'menu_margin_type' => 'all',
            'menu_margin' => 10,
            'menu_margin_top' => 10,
            'menu_margin_right' => 10,
            'menu_margin_bottom' => 10,
            'menu_margin_left' => 10,
            
            // Submenu - Nowe opcje
            'submenu_background' => '#2c3338',
            'submenu_text_color' => '#ffffff',
            'submenu_hover_background' => '#32373c',
            'submenu_hover_text_color' => '#00a0d2',
            'submenu_active_background' => '#0073aa',
            'submenu_active_text_color' => '#ffffff',
            'submenu_border_color' => '#464b50',
            'submenu_width_type' => 'auto',
            'submenu_width_value' => 200,
            'submenu_min_width' => 180,
            'submenu_max_width' => 300,
            'submenu_border_radius_type' => 'all',
            'submenu_border_radius_all' => 8,
            'submenu_border_radius_top_left' => 8,
            'submenu_border_radius_top_right' => 8,
            'submenu_border_radius_bottom_right' => 8,
            'submenu_border_radius_bottom_left' => 8,
            
            // Menu Corner Radius
            'corner_radius_type' => 'none',
            'corner_radius_all' => 8,
            'corner_radius_top_left' => 8,
            'corner_radius_top_right' => 8,
            'corner_radius_bottom_right' => 8,
            'corner_radius_bottom_left' => 8,
            
            // Content
            'content_background' => '#f1f1f1',
            'content_card_background' => '#ffffff',
            'content_text_color' => '#333333',
            'content_link_color' => '#0073aa',
            'button_primary_background' => '#0073aa',
            'button_primary_text_color' => '#ffffff',
            'button_border_radius' => 4,
            'content_rounded_corners' => false,
            'content_shadows' => false,
            'content_hover_effects' => false,
            
            // Typography
            'google_font_primary' => '',
            'google_font_headings' => '',
            'load_google_fonts' => false,
            'heading_font_size' => 32,
            'body_font_size' => 14,
            'line_height' => 1.6,
            
            // Effects - Enhanced Advanced Effects System
            'animation_speed' => 300,
            'fade_in_effects' => false,
            'slide_animations' => false,
            'scale_hover_effects' => false,
            'glassmorphism_effects' => false,
            'gradient_backgrounds' => false,
            'particle_effects' => false,
            'smooth_scrolling' => false,
            
            // Advanced Glassmorphism Settings
            'glassmorphism_blur' => 10,
            'glassmorphism_opacity' => 0.1,
            'glassmorphism_border_opacity' => 0.2,
            'menu_glassmorphism' => false,
            'admin_bar_glassmorphism' => false,
            
            // Advanced Shadow Settings
            'shadow_hover_effects' => true,
            'shadow_spread' => 0,
            'shadow_offset_x' => 0,
            'shadow_offset_y' => 2,
            'shadow_opacity' => 0.1,
            
            // Advanced Animation Settings
            'animation_type' => 'smooth', // smooth, fast, bounce, elastic
            
            // Buttons & Forms - Nowa sekcja
            'primary_button_bg' => '#0073aa',
            'primary_button_text' => '#ffffff',
            'primary_button_hover' => '#005a87',
            'secondary_button_bg' => '#f7f7f7',
            'secondary_button_text' => '#555555',
            'secondary_button_hover' => '#e0e0e0',
            'button_border_radius' => 4,
            'button_shadow_enabled' => true,
            'form_field_bg' => '#ffffff',
            'form_field_border' => '#dddddd',
            'form_field_focus' => '#0073aa',
            'form_field_border_radius' => 4,
            
            // Legacy compatibility
            'button_primary_bg' => '#0073aa',
            'button_primary_text_color' => '#ffffff',
            'button_primary_hover_bg' => '#005a87',
            'button_secondary_bg' => '#f1f1f1',
            'button_secondary_text_color' => '#333333',
            'button_secondary_hover_bg' => '#e0e0e0',
            'button_shadow' => false,
            'button_hover_effects' => true,
            'form_field_focus_color' => '#0073aa',
            
            // Login Page - Nowa sekcja 
            'login_page_enabled' => false,
            'login_bg_color' => '#f1f1f1',
            'login_form_bg' => '#ffffff',
            'login_form_shadow' => true,
            'login_form_rounded' => true,
            'login_logo_url' => '',
            'login_logo_width' => 84,
            'login_logo_height' => 84,
            
            // Legacy compatibility
            'login_custom_logo' => '',
            
            // Advanced
            'custom_css' => '',
            'custom_js' => '',
            'hide_wp_version' => false,
            'hide_help_tabs' => false,
            'hide_screen_options' => false,
            'hide_admin_notices' => false,
            'custom_admin_footer_text' => '',
            'admin_bar_hide_wp_logo' => false,
            'admin_bar_hide_howdy' => false,
            'admin_bar_hide_updates' => false,
            'admin_bar_hide_comments' => false,
            'minify_css' => false,
            'cache_css' => true,
            'debug_mode' => false,
            'show_css_info' => false,
            'load_only_admin' => true,
            'async_loading' => false,
        ];
    }
    
    /**
     * Definicje tabów
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
            'buttons' => [
                'title' => __('Przyciski', 'modern-admin-styler-v2'),
                'icon' => 'buttons',
                'description' => __('Stylowanie przycisków i formularzy', 'modern-admin-styler-v2')
            ],
            'login' => [
                'title' => __('Logowanie', 'modern-admin-styler-v2'),
                'icon' => 'login',
                'description' => __('Kustomizacja strony logowania', 'modern-admin-styler-v2')
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
            'templates' => [
                'title' => __('Szablony', 'modern-admin-styler-v2'),
                'icon' => 'templates',
                'description' => __('Gotowe szablony stylów - Terminal, Gaming, Retro i inne', 'modern-admin-styler-v2')
            ],
            'advanced' => [
                'title' => __('Zaawansowane', 'modern-admin-styler-v2'),
                'icon' => 'advanced',
                'description' => __('Niestandardowe CSS i opcje deweloperskie', 'modern-admin-styler-v2')
            ],
            'live-preview' => [
                'title' => __('Live Preview', 'modern-admin-styler-v2'),
                'icon' => 'live-preview',
                'description' => __('Podgląd na żywo zmian w interfejsie', 'modern-admin-styler-v2')
            ]
        ];
    }
    
    /**
     * Helper function dla ikon
     */
    public function getTabIcon($icon_name) {
        $icons = [
            'settings' => '<span class="dashicons dashicons-admin-settings"></span>',
            'admin-bar' => '<span class="dashicons dashicons-admin-bar"></span>',
            'menu' => '<span class="dashicons dashicons-menu"></span>',
            'content' => '<span class="dashicons dashicons-admin-page"></span>',
            'buttons' => '<span class="dashicons dashicons-button"></span>',
            'login' => '<span class="dashicons dashicons-admin-users"></span>',
            'typography' => '<span class="dashicons dashicons-editor-textcolor"></span>',
            'effects' => '<span class="dashicons dashicons-art"></span>',
            'templates' => '<span class="dashicons dashicons-layout"></span>',
            'advanced' => '<span class="dashicons dashicons-admin-tools"></span>',
            'live-preview' => '<span class="dashicons dashicons-visibility"></span>',
        ];
        
        return $icons[$icon_name] ?? '<span class="dashicons dashicons-admin-generic"></span>';
    }
    
    /**
     * WordPress compatibility check - Task 13
     */
    private function checkWordPressCompatibility() {
        global $wp_version;
        $required_version = '5.0';
        return version_compare($wp_version, $required_version, '>=');
    }
    
    /**
     * PHP compatibility check - Task 13
     */
    private function checkPHPCompatibility() {
        $required_version = '7.4';
        return version_compare(PHP_VERSION, $required_version, '>=');
    }
    
    /**
     * Create plugin database tables if needed - Task 13
     */
    private function createPluginTables() {
        global $wpdb;
        
        // For future use - currently plugin uses wp_options table
        // This method is prepared for potential database table creation
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MAS V2: Plugin tables check completed');
        }
    }
    
    /**
     * Clear all plugin transients - Task 13
     */
    private function clearAllPluginTransients() {
        global $wpdb;
        
        // Clear all plugin-related transients
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_mas_v2_%' OR option_name LIKE '_transient_mas_v2_%'");
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_site_transient_timeout_mas_v2_%' OR option_name LIKE '_site_transient_mas_v2_%'");
        
        // Clear specific transients
        $transients_to_clear = [
            'mas_v2_generated_css',
            'mas_v2_menu_css',
            'mas_v2_admin_css',
            'mas_v2_compatibility_check',
            'mas_v2_activation_notice',
            'mas_v2_admin_notice'
        ];
        
        foreach ($transients_to_clear as $transient) {
            delete_transient($transient);
            delete_site_transient($transient);
        }
    }
    
    /**
     * Clean up temporary files - Task 13
     */
    private function cleanupTemporaryFiles() {
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/mas-v2-temp/';
        
        if (is_dir($temp_dir)) {
            $files = glob($temp_dir . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($temp_dir);
        }
    }
    
    /**
     * Clear scheduled events - Task 13
     */
    private function clearScheduledEvents() {
        // Clear any scheduled cron events
        wp_clear_scheduled_hook('mas_v2_cleanup_backups');
        wp_clear_scheduled_hook('mas_v2_cache_cleanup');
    }
    
    /**
     * Wyczyść cache - Enhanced for Task 13
     */
    private function clearCache() {
        global $wpdb;
        
        // Debug: Log cache clearing
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MAS V2: Clearing plugin cache');
        }
        
        // Wyczyść transients związane z pluginem
        $transient_result = $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_mas_v2_%' OR option_name LIKE '_transient_mas_v2_%'");
        $site_transient_result = $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_site_transient_timeout_mas_v2_%' OR option_name LIKE '_site_transient_mas_v2_%'");
        
        // Clear any CSS generation cache
        delete_transient('mas_v2_generated_css');
        delete_transient('mas_v2_menu_css');
        delete_transient('mas_v2_admin_css');
        
        // Clear WordPress object cache
        wp_cache_delete('mas_v2_settings', 'options');
        
        // Only flush cache if safe to do so
        if (function_exists('wp_cache_flush') && !wp_using_ext_object_cache()) {
            wp_cache_flush();
        }
        
        // Debug: Log cache clearing results
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("MAS V2: Cache cleared - transients: {$transient_result}, site_transients: {$site_transient_result}");
        }
    }
    
    /**
     * Create settings backup - Task 13
     */
    private function createSettingsBackup($settings, $backup_type = 'manual') {
        $timestamp = time();
        $backup_key = "mas_v2_settings_backup_{$backup_type}_{$timestamp}";
        
        $backup_data = [
            'settings' => $settings,
            'timestamp' => $timestamp,
            'backup_type' => $backup_type,
            'wp_version' => get_bloginfo('version'),
            'plugin_version' => MAS_V2_VERSION,
            'php_version' => PHP_VERSION
        ];
        
        update_option($backup_key, $backup_data, false);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("MAS V2: Settings backup created - {$backup_key}");
        }
        
        return $backup_key;
    }
    

    
    /**
     * Get recent backups for export metadata - Task 12 Enhancement
     */
    private function getRecentBackups($limit = 5) {
        global $wpdb;
        
        $backup_options = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_name, option_value FROM $wpdb->options 
                WHERE option_name LIKE 'mas_v2_settings_backup_%' 
                ORDER BY option_name DESC LIMIT %d",
                $limit
            )
        );
        
        $backups = [];
        foreach ($backup_options as $backup) {
            // Extract timestamp from backup name
            if (preg_match('/backup_(\d+)/', $backup->option_name, $matches)) {
                $timestamp = intval($matches[1]);
                $backups[] = [
                    'key' => $backup->option_name,
                    'timestamp' => $timestamp,
                    'date' => date('Y-m-d H:i:s', $timestamp),
                    'size' => strlen($backup->option_value)
                ];
            }
        }
        
        return $backups;
    }
    
    /**
     * Validate import data structure and content - Task 12 Enhancement
     */
    private function validateImportData($import_data) {
        $result = [
            'valid' => false,
            'message' => '',
            'details' => []
        ];
        
        // Check if data is an array
        if (!is_array($import_data)) {
            $result['message'] = __('Import data must be a valid JSON object.', 'modern-admin-styler-v2');
            return $result;
        }
        
        // Check for required structure
        $has_settings = false;
        $format_version = null;
        
        if (isset($import_data['settings']) && is_array($import_data['settings'])) {
            $has_settings = true;
            $format_version = $import_data['format_version'] ?? $import_data['version'] ?? '1.0';
        } elseif (is_array($import_data) && !isset($import_data['format_version'])) {
            // Legacy format - assume entire array is settings
            $has_settings = true;
            $format_version = '1.0';
            $result['details'][] = 'Legacy format detected';
        }
        
        if (!$has_settings) {
            $result['message'] = __('No valid settings found in import file.', 'modern-admin-styler-v2');
            return $result;
        }
        
        // Validate format version compatibility
        $supported_versions = ['1.0', '2.0'];
        if (!in_array($format_version, $supported_versions)) {
            $result['message'] = sprintf(
                __('Unsupported format version: %s. Supported versions: %s', 'modern-admin-styler-v2'),
                $format_version,
                implode(', ', $supported_versions)
            );
            return $result;
        }
        
        // Check for potential corruption indicators
        $settings = isset($import_data['settings']) ? $import_data['settings'] : $import_data;
        
        if (empty($settings)) {
            $result['message'] = __('Settings array is empty.', 'modern-admin-styler-v2');
            return $result;
        }
        
        // Validate checksum if present
        if (isset($import_data['checksum'])) {
            $calculated_checksum = md5(serialize($settings));
            if ($calculated_checksum !== $import_data['checksum']) {
                $result['details'][] = 'Checksum mismatch - file may be corrupted';
            }
        }
        
        // Check for suspicious content
        $suspicious_keys = ['eval', 'exec', 'system', 'shell_exec', 'passthru'];
        foreach ($settings as $key => $value) {
            if (is_string($value)) {
                foreach ($suspicious_keys as $suspicious) {
                    if (strpos($value, $suspicious) !== false) {
                        $result['message'] = __('Import file contains potentially dangerous content.', 'modern-admin-styler-v2');
                        return $result;
                    }
                }
            }
        }
        
        // Validate plugin version compatibility if present
        if (isset($import_data['plugin_version'])) {
            $import_version = $import_data['plugin_version'];
            $current_version = MAS_V2_VERSION;
            
            if (version_compare($import_version, '2.0.0', '<')) {
                $result['details'][] = 'Import from older plugin version - some settings may need conversion';
            }
        }
        
        $result['valid'] = true;
        $result['message'] = __('Import file validation passed.', 'modern-admin-styler-v2');
        $result['format_version'] = $format_version;
        $result['settings_count'] = count($settings);
        
        return $result;
    }
    

    
    /**
     * Diagnostic method to verify settings-to-CSS connection
     */
    public function verifySettingsConnection() {
        if (!current_user_can('manage_options')) {
            return false;
        }
        
        $settings = $this->getSettings();
        $diagnostics = [
            'settings_loaded' => !empty($settings),
            'settings_count' => count($settings),
            'plugin_enabled' => $settings['enable_plugin'] ?? false,
            'menu_settings_present' => false,
            'css_generation_working' => false,
            'menu_customizations_detected' => false
        ];
        
        // Check for menu settings
        $menu_settings = array_filter($settings, function($key) {
            return strpos($key, 'menu_') === 0;
        }, ARRAY_FILTER_USE_KEY);
        $diagnostics['menu_settings_present'] = !empty($menu_settings);
        $diagnostics['menu_settings_count'] = count($menu_settings);
        
        // Test CSS generation
        try {
            $test_css = $this->generateMenuCSS($settings);
            $diagnostics['css_generation_working'] = !empty($test_css);
            $diagnostics['generated_css_length'] = strlen($test_css);
            
            // Check if menu customizations are detected
            $diagnostics['menu_customizations_detected'] = strpos($test_css, '--mas-menu-') !== false;
        } catch (Exception $e) {
            $diagnostics['css_generation_error'] = $e->getMessage();
        }
        
        // Log diagnostics if debug is enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MAS V2: Settings connection diagnostics: ' . json_encode($diagnostics));
        }
        
        return $diagnostics;
    }
    
    /**
     * Dodaje gear button w prawym dolnym rogu dla szybkiego dostępu do ustawień
     */
    public function addSettingsGearButton() {
        // Nie pokazuj na stronach ustawień MAS
        $screen = get_current_screen();
        if (strpos($screen->id, 'mas-v2') !== false) {
            return;
        }
        
        // Sprawdź czy użytkownik może zarządzać opcjami
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $settings_url = admin_url('admin.php?page=mas-v2-settings');
        ?>
        <button type="button" class="mas-v2-settings-gear" onclick="window.location='<?php echo esc_url($settings_url); ?>'" title="<?php esc_attr_e('Modern Admin Styler V2 - Ustawienia', 'modern-admin-styler-v2'); ?>">
            ⚙️
        </button>
        <?php
    }
}

// Inicjalizuj wtyczkę
ModernAdminStylerV2::getInstance();

// Włącz diagnostykę menu (tylko dla adminów z WP_DEBUG)
if (defined('WP_DEBUG') && WP_DEBUG) {
    add_action('admin_footer', function() {
        if (isset($_GET['mas_diagnostic']) && current_user_can('manage_options')) {
            ?>
            <div id="mas-diagnostic-panel" style="position: fixed; bottom: 20px; right: 20px; background: #fff; border: 2px solid #0073aa; padding: 15px; max-width: 400px; z-index: 999999; box-shadow: 0 4px 20px rgba(0,0,0,0.2); border-radius: 8px;">
                <h3 style="margin: 0 0 10px 0; color: #0073aa;">🔍 MAS Menu Diagnostic</h3>
                
                <div style="margin-bottom: 15px;">
                    <strong>CSS Files:</strong><br>
                    <span id="diag-css-status">Checking...</span>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <strong>Body Classes:</strong><br>
                    <code id="diag-body-classes" style="font-size: 11px;">Checking...</code>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <strong>CSS Variables:</strong><br>
                    <code id="diag-css-vars" style="font-size: 11px;">Checking...</code>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <strong>JavaScript:</strong><br>
                    <span id="diag-js-status">Checking...</span>
                </div>
                
                <button onclick="runMasDiagnostic()" style="background: #0073aa; color: white; border: none; padding: 8px 16px; cursor: pointer; border-radius: 4px;">
                    🔄 Refresh Diagnostic
                </button>
            </div>
            
            <script>
            function runMasDiagnostic() {
                // Check CSS files
                const stylesheets = Array.from(document.styleSheets);
                const resetCSS = stylesheets.find(s => s.href && s.href.includes('admin-menu-reset.css'));
                const modernCSS = stylesheets.find(s => s.href && s.href.includes('admin-modern.css'));
                const menuModernCSS = stylesheets.find(s => s.href && s.href.includes('admin-menu-modern.css'));
                const quickfixCSS = stylesheets.find(s => s.href && s.href.includes('quick-fix.css'));
                
                let cssStatus = '';
                cssStatus += resetCSS ? '✅ admin-menu-reset.css<br>' : '❌ admin-menu-reset.css<br>';
                cssStatus += modernCSS ? '✅ admin-modern.css<br>' : '❌ admin-modern.css<br>';
                cssStatus += menuModernCSS ? '⚠️ admin-menu-modern.css (should be disabled)<br>' : '✅ admin-menu-modern.css (disabled)<br>';
                cssStatus += quickfixCSS ? '⚠️ quick-fix.css (should be disabled)' : '✅ quick-fix.css (disabled)';
                
                document.getElementById('diag-css-status').innerHTML = cssStatus;
                
                // Check body classes
                const bodyClasses = document.body.className;
                document.getElementById('diag-body-classes').textContent = bodyClasses || 'No classes';
                
                // Check CSS variables
                const root = document.documentElement;
                const menuEnabled = getComputedStyle(root).getPropertyValue('--mas-menu-enabled').trim();
                const menuBg = getComputedStyle(root).getPropertyValue('--mas-menu-bg-color').trim();
                const floatingEnabled = getComputedStyle(root).getPropertyValue('--mas-menu-floating-enabled').trim();
                
                let varsStatus = '';
                varsStatus += '--mas-menu-enabled: ' + (menuEnabled || 'not set') + '<br>';
                varsStatus += '--mas-menu-bg-color: ' + (menuBg || 'not set') + '<br>';
                varsStatus += '--mas-menu-floating-enabled: ' + (floatingEnabled || 'not set');
                
                document.getElementById('diag-css-vars').innerHTML = varsStatus;
                
                // Check JavaScript
                let jsStatus = '';
                jsStatus += (typeof MenuManager !== 'undefined' || typeof window.MenuManager !== 'undefined') ? '✅ MenuManager loaded<br>' : '❌ MenuManager not loaded<br>';
                jsStatus += (typeof masV2Global !== 'undefined') ? '✅ masV2Global available' : '❌ masV2Global not available';
                
                document.getElementById('diag-js-status').innerHTML = jsStatus;
                
                console.log('🔍 MAS Diagnostic complete', {
                    css: { resetCSS: !!resetCSS, modernCSS: !!modernCSS, menuModernCSS: !!menuModernCSS, quickfixCSS: !!quickfixCSS },
                    bodyClasses,
                    cssVariables: { menuEnabled, menuBg, floatingEnabled },
                    javascript: { MenuManager: typeof MenuManager !== 'undefined', masV2Global: typeof masV2Global !== 'undefined' }
                });
            }
            
            // Auto-run on load
            setTimeout(runMasDiagnostic, 1000);
            </script>
            <?php
        }
    });
}
