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
 * Tested up to: 6.4
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
        // Autoloader
        spl_autoload_register([$this, 'autoload']);
        
        // Inicjalizacja serwisów
        $this->initServices();
        
        // Legacy mode dla kompatybilności
        $this->initLegacyMode();
        
        // Hooks
        add_action('init', [$this, 'loadTextdomain']);
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueGlobalAssets']);
        
        // AJAX handlers
        add_action('wp_ajax_mas_v2_save_settings', [$this, 'ajaxSaveSettings']);
        add_action('wp_ajax_mas_v2_reset_settings', [$this, 'ajaxResetSettings']);
        add_action('wp_ajax_mas_v2_export_settings', [$this, 'ajaxExportSettings']);
        add_action('wp_ajax_mas_v2_import_settings', [$this, 'ajaxImportSettings']);
        add_action('wp_ajax_mas_v2_live_preview', [$this, 'ajaxLivePreview']);
        
        // Output custom styles
        add_action('admin_head', [$this, 'outputCustomStyles']);
        add_action('wp_head', [$this, 'outputFrontendStyles']);
        add_action('login_head', [$this, 'outputLoginStyles']);
        
        // Footer modifications
        add_filter('admin_footer_text', [$this, 'customAdminFooter']);
        
        // Body class modifications
        add_filter('admin_body_class', [$this, 'addAdminBodyClasses']);
        
        // Activation/Deactivation hooks
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        
        // Allow framing for localhost viewer
        add_action('init', [$this, 'allowFramingForLocalhostViewer']);
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
    
    /**
     * Tryb zgodności ze starą wersją
     */
    private function initLegacyMode() {
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueGlobalAssets']); // CSS na wszystkich stronach
        add_action('admin_head', [$this, 'outputCustomStyles']); // Style inline
        add_action('wp_ajax_mas_v2_save_settings', [$this, 'ajaxSaveSettings']);
        add_action('wp_ajax_mas_v2_reset_settings', [$this, 'ajaxResetSettings']);
        add_action('wp_ajax_mas_v2_export_settings', [$this, 'ajaxExportSettings']);
        add_action('wp_ajax_mas_v2_import_settings', [$this, 'ajaxImportSettings']);
        add_action('wp_ajax_mas_v2_live_preview', [$this, 'ajaxLivePreview']);
        add_action('wp_ajax_mas_v2_save_theme', [$this, 'ajaxSaveTheme']);
        add_action('admin_footer', [$this, 'addSettingsGearButton']);
        
        // 🧪 TYMCZASOWY DEBUG
        add_action('admin_footer', [$this, 'addDebugInfo']);
    }
    
    /**
     * Aktywacja wtyczki
     */
    public function activate() {
        $defaults = $this->getDefaultSettings();
        add_option('mas_v2_settings', $defaults);
        
        // Wyczyść cache
        if (method_exists($this, 'clearCache')) {
            $this->clearCache();
        }
    }
    
    /**
     * Deaktywacja wtyczki
     */
    public function deactivate() {
        // Wyczyść cache i transients
        $this->clearCache();
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
        
        // 🚀 Settings page: Tylko admin-modern (loader będzie z enqueueGlobalAssets)
        wp_enqueue_script(
            'mas-v2-admin',
            MAS_V2_PLUGIN_URL . 'assets/js/admin-modern.js',
            ['jquery', 'wp-color-picker', 'media-upload', 'thickbox', 'mas-v2-loader'],
            MAS_V2_VERSION,
            true
        );
        
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_style('thickbox');
        wp_enqueue_media();
        
        // Localize script dla admin-modern.js
        wp_localize_script('mas-v2-admin', 'masV2', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mas_v2_nonce'),
            'settings' => $this->getSettings(),
            'strings' => [
                'saving' => __('Zapisywanie...', 'modern-admin-styler-v2'),
                'saved' => __('Ustawienia zostały zapisane!', 'modern-admin-styler-v2'),
                'error' => __('Wystąpił błąd podczas zapisywania', 'modern-admin-styler-v2'),
                'confirm_reset' => __('Czy na pewno chcesz przywrócić domyślne ustawienia?', 'modern-admin-styler-v2'),
                'resetting' => __('Resetowanie...', 'modern-admin-styler-v2'),
                'reset_success' => __('Ustawienia zostały przywrócone!', 'modern-admin-styler-v2'),
            ]
        ]);
        
        // DODAJ TAKŻE masV2Global dla MenuManager (jeśli mas-v2-global nie jest załadowany)
        wp_localize_script('mas-v2-admin', 'masV2Global', [
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
        
        // 🔄 MENU RESET - WordPress Default (ŁADUJ PIERWSZY!)
        wp_enqueue_style(
            'mas-v2-menu-reset',
            MAS_V2_PLUGIN_URL . 'assets/css/admin-menu-reset.css',
            [],
            MAS_V2_VERSION
        );
        
        // CSS na wszystkich stronach wp-admin (oprócz logowania)
        wp_enqueue_style(
            'mas-v2-global',
            MAS_V2_PLUGIN_URL . 'assets/css/admin-modern.css',
            ['mas-v2-menu-reset'],
            MAS_V2_VERSION
        );
        
        // 🎨 WOW Effects CSS - WYŁĄCZONE (powodowało problemy z animacjami)
        // wp_enqueue_style(
        //     'mas-v2-advanced-effects',
        //     MAS_V2_PLUGIN_URL . 'assets/css/advanced-effects.css',
        //     array('mas-v2-global'),
        //     MAS_V2_VERSION
        // );
        
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
        
        // 🎯 MODERN MENU CSS - WYŁĄCZONE (używamy nowego systemu reset)
        // wp_enqueue_style(
        //     'mas-v2-menu-modern',
        //     MAS_V2_PLUGIN_URL . 'assets/css/admin-menu-modern.css',
        //     array('mas-v2-global'),
        //     MAS_V2_VERSION
        // );
        
        // 🚀 QUICK FIX CSS - WYŁĄCZONE (powodowało konflikty)
        // wp_enqueue_style(
        //     'mas-v2-quick-fix',
        //     MAS_V2_PLUGIN_URL . 'assets/css/quick-fix.css',
        //     array('mas-v2-global'),
        //     MAS_V2_VERSION . '-' . time() // Force reload
        // );
        
        // Uproszczony CSS dla menu - nadpisuje style z admin-modern.css
        // WYŁĄCZONE - testujemy czy submenu działa bez żadnego custom CSS
        // wp_enqueue_style(
        //     'mas-v2-menu-simple',
        //     MAS_V2_PLUGIN_URL . 'assets/css/admin-menu-simple.css',
        //     ['mas-v2-global'],
        //     MAS_V2_VERSION
        // );
        
        // 🚀 KLUCZOWE: Najpierw ładuj loader modułów!
        wp_enqueue_script(
            'mas-v2-loader',
            MAS_V2_PLUGIN_URL . 'assets/js/mas-loader.js',
            [],
            MAS_V2_VERSION,
            true
        );
        
        // Potem globalny skrypt (który łączy się z modułami)
        wp_enqueue_script(
            'mas-v2-global',
            MAS_V2_PLUGIN_URL . 'assets/js/admin-global.js',
            ['jquery', 'mas-v2-loader'],
            MAS_V2_VERSION,
            true
        );
        
        // Przekaż ustawienia do globalnego JS
        $settings_for_js = $this->getSettings();
        error_log('🧪 DEBUG: wp_localize_script wykonany, settings count: ' . count($settings_for_js));
        
        wp_localize_script('mas-v2-global', 'masV2Global', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mas_v2_nonce'),
            'settings' => $settings_for_js
        ]);
    }
    
    /**
     * Sprawdza czy jesteśmy na stronie logowania
     */
    private function isLoginPage() {
        return in_array($GLOBALS['pagenow'], ['wp-login.php', 'wp-register.php']);
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
     * Legacy: AJAX Zapisywanie ustawień
     */
    public function ajaxSaveSettings() {
        // Weryfikacja nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mas_v2_nonce')) {
            wp_send_json_error(['message' => __('Błąd bezpieczeństwa', 'modern-admin-styler-v2')]);
        }
        
        // Sprawdzenie uprawnień
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Brak uprawnień', 'modern-admin-styler-v2')]);
        }
        
        try {
        // Sanityzacja i zapisanie danych
            $settings = $this->sanitizeSettings($_POST);
            update_option('mas_v2_settings', $settings);
            
            wp_send_json_success([
                'message' => __('Ustawienia zostały zapisane pomyślnie!', 'modern-admin-styler-v2'),
                'settings' => $settings
            ]);
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * AJAX Reset ustawień
     */
    public function ajaxResetSettings() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mas_v2_nonce')) {
            wp_send_json_error(['message' => __('Błąd bezpieczeństwa', 'modern-admin-styler-v2')]);
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Brak uprawnień', 'modern-admin-styler-v2')]);
        }
        
        try {
            $defaults = $this->getDefaultSettings();
            update_option('mas_v2_settings', $defaults);
            $this->clearCache();
        
        wp_send_json_success([
                'message' => __('Ustawienia zostały przywrócone do domyślnych!', 'modern-admin-styler-v2')
            ]);
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * AJAX Export ustawień
     */
    public function ajaxExportSettings() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mas_v2_nonce')) {
            wp_send_json_error(['message' => __('Błąd bezpieczeństwa', 'modern-admin-styler-v2')]);
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Brak uprawnień', 'modern-admin-styler-v2')]);
        }
        
        try {
            $settings = $this->getSettings();
            $export_data = [
                'version' => MAS_V2_VERSION,
                'exported' => date('Y-m-d H:i:s'),
                'settings' => $settings
            ];
            
            wp_send_json_success([
                'data' => $export_data,
                'filename' => 'mas-v2-settings-' . date('Y-m-d-H-i-s') . '.json'
            ]);
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * AJAX Import ustawień
     */
    public function ajaxImportSettings() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mas_v2_nonce')) {
            wp_send_json_error(['message' => __('Błąd bezpieczeństwa', 'modern-admin-styler-v2')]);
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Brak uprawnień', 'modern-admin-styler-v2')]);
        }
        
        try {
            $import_data = json_decode(stripslashes($_POST['data']), true);
            
            if (!$import_data || !isset($import_data['settings'])) {
                wp_send_json_error(['message' => __('Nieprawidłowy format pliku', 'modern-admin-styler-v2')]);
            }
            
            $settings = $this->sanitizeSettings($import_data['settings']);
            update_option('mas_v2_settings', $settings);
            $this->clearCache();
            
            wp_send_json_success([
                'message' => __('Ustawienia zostały zaimportowane pomyślnie!', 'modern-admin-styler-v2')
            ]);
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
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
     * Wyjście niestandardowych stylów do admin head
     */
    public function outputCustomStyles() {
        if (!is_admin() || $this->isLoginPage()) {
            return;
        }
        
        $settings = $this->getSettings();
        
        if (empty($settings)) {
            return;
        }
        
        // Sprawdź czy wtyczka jest włączona
        if (!isset($settings['enable_plugin']) || !$settings['enable_plugin']) {
            return;
        }
        
        $css = $this->generateCSSVariables($settings);
        $css .= $this->generateAdminCSS($settings);
        $css .= $this->generateButtonCSS($settings);
        $css .= $this->generateFormCSS($settings);
        $css .= $this->generateAdvancedCSS($settings);
        
        echo "<style id='mas-v2-dynamic-styles'>\n";
        echo $css;
        echo "\n</style>\n";
        
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
        
        // Basic menu colors
        if (isset($settings['menu_background'])) {
            $css .= "    --mas-menu-bg-color: {$settings['menu_background']};\n";
        }
        if (isset($settings['menu_text_color'])) {
            $css .= "    --mas-menu-text-color: {$settings['menu_text_color']};\n";
        }
        if (isset($settings['menu_hover_background'])) {
            $css .= "    --mas-menu-hover-color: {$settings['menu_hover_background']};\n";
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
     * Generowanie CSS dla admin area
     */
    private function generateAdminCSS($settings) {
        $css = '';
        
        // CSS Variables
        $css .= $this->generateCSSVariables($settings);
        
        // Admin Bar CSS
        $css .= $this->generateAdminBarCSS($settings);
        
        // Menu CSS
        $css .= $this->generateMenuCSS($settings);
        
        // Content CSS
        $css .= $this->generateContentCSS($settings);
        
        // Button CSS
        $css .= $this->generateButtonCSS($settings);
        
        // Form CSS
        $css .= $this->generateFormCSS($settings);
        
        // Advanced CSS (nowe opcje)
        $css .= $this->generateAdvancedCSS($settings);
        
        // Effects CSS (nowe opcje z reorganizacji)
        $css .= $this->generateEffectsCSS($settings);
        
        return $css;
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
        $css = '';
        
        // Sprawdź czy admin bar jest włączony (może być wyłączony przez użytkownika)
        if (!isset($settings['custom_admin_bar_style']) || !$settings['custom_admin_bar_style']) {
            return '';
        }
        
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
        
        return $css;
    }
    
    /**
     * Generuje CSS dla menu administracyjnego
     */
    private function generateMenuCSS($settings) {
        // WYŁĄCZONE - używamy prostego CSS z pliku admin-menu-simple.css
        // Zwracamy pusty string żeby nie nadpisywać domyślnego menu WordPress
        return '';
    }
    
    /**
     * Generuje CSS dla obszaru treści
     */
    private function generateContentCSS($settings) {
        $css = '';
        
        // Główny kontener treści
        if (isset($settings['content_background'])) {
            $css .= "#wpbody-content { background: {$settings['content_background']} !important; }";
        }
        
        if (isset($settings['content_text_color'])) {
            $css .= "#wpbody-content { color: {$settings['content_text_color']} !important; }";
        }
        
        // Karty/boxy
        if (isset($settings['content_card_background'])) {
            $css .= ".postbox, .meta-box-sortables .postbox { background: {$settings['content_card_background']} !important; }";
        }
        
        // Linki
        if (isset($settings['content_link_color'])) {
            $css .= "#wpbody-content a { color: {$settings['content_link_color']} !important; }";
        }
        
        return $css;
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
     */
    private function generateEffectsCSS($settings) {
        $css = '';
        
        // Global border radius
        if (isset($settings['global_border_radius']) && $settings['global_border_radius'] > 0) {
            $radius = $settings['global_border_radius'];
            $css .= "
                .postbox, .meta-box-sortables .postbox, 
                .form-table, .widefat,
                .mas-v2-card, .notice, .update-nag {
                    border-radius: {$radius}px !important;
                }
            ";
        }
        
        // Global box shadows
        if (isset($settings['global_box_shadow']) && $settings['global_box_shadow']) {
            $shadowColor = $settings['shadow_color'] ?? '#000000';
            $shadowBlur = $settings['shadow_blur'] ?? 10;
            $css .= "
                .postbox, .meta-box-sortables .postbox,
                .mas-v2-card, .notice:not(.inline) {
                    box-shadow: 0 2px {$shadowBlur}px rgba(" . $this->hexToRgb($shadowColor) . ", 0.1) !important;
                }
            ";
        }
        
        // Enable/disable animations
        if (isset($settings['enable_animations']) && !$settings['enable_animations']) {
            $css .= "
                *, *::before, *::after {
                    animation-duration: 0s !important;
                    animation-delay: 0s !important;
                    transition-duration: 0s !important;
                    transition-delay: 0s !important;
                }
            ";
        } else if (isset($settings['enable_animations']) && $settings['enable_animations']) {
            $animationType = $settings['animation_type'] ?? 'smooth';
            $animationSpeed = $settings['animation_speed'] ?? 300;
            
            $easing = 'ease';
            switch ($animationType) {
                case 'fast': 
                    $easing = 'ease-out';
                    $animationSpeed = min($animationSpeed, 200);
                    break;
                case 'bounce':
                    $easing = 'cubic-bezier(0.68, -0.55, 0.265, 1.55)';
                    break;
                default:
                    $easing = 'ease-in-out';
            }
            
            $css .= "
                .postbox, .meta-box-sortables .postbox,
                .button, .button-primary, .button-secondary,
                .mas-v2-card, .notice, .form-table tr {
                    transition: all {$animationSpeed}ms {$easing} !important;
                }
            ";
        }
        
        // Hover effects
        if (isset($settings['hover_effects']) && $settings['hover_effects']) {
            $css .= "
                .postbox:hover, .meta-box-sortables .postbox:hover {
                    transform: translateY(-2px) !important;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
                }
                .button:hover, .button-primary:hover, .button-secondary:hover {
                    transform: translateY(-1px) !important;
                }
            ";
        }
        
        // Glassmorphism
        if (isset($settings['glassmorphism']) && $settings['glassmorphism']) {
            $css .= "
                .postbox, .meta-box-sortables .postbox {
                    background: rgba(255, 255, 255, 0.1) !important;
                    backdrop-filter: blur(10px) !important;
                    -webkit-backdrop-filter: blur(10px) !important;
                    border: 1px solid rgba(255, 255, 255, 0.2) !important;
                }
            ";
        }
        
        // Compact mode
        if (isset($settings['compact_mode']) && $settings['compact_mode']) {
            $css .= "
                body.wp-admin {
                    --mas-spacing: 0.5rem !important;
                }
                .wrap { padding: 10px !important; }
                .form-table th, .form-table td { padding: 8px !important; }
                .postbox { margin-bottom: 15px !important; }
                .mas-v2-card { padding: 1rem !important; }
                .mas-v2-field { margin-bottom: 1rem !important; }
            ";
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
        
        // Basic plugin classes
        $classes .= ' mas-v2-modern-style';
        
        if (isset($settings['compact_mode']) && $settings['compact_mode']) {
            $classes .= ' mas-compact-mode';
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
        // Używaj fallback - bezpieczna implementacja
        $settings = get_option('mas_v2_settings', []);
        $defaults = $this->getDefaultSettings();
        
        return wp_parse_args($settings, $defaults);
    }
    
    /**
     * Sanityzacja ustawień
     */
    private function sanitizeSettings($input) {
        // Bezpieczna sanityzacja
        $defaults = $this->getDefaultSettings();
        $sanitized = [];
        
        foreach ($defaults as $key => $default_value) {
            if (!isset($input[$key])) {
                $sanitized[$key] = $default_value;
                continue;
            }
            
            $value = $input[$key];
            
            if (is_bool($default_value)) {
                $sanitized[$key] = (bool) $value;
            } elseif (is_int($default_value)) {
                $sanitized[$key] = (int) $value;
            } elseif ($key === 'custom_css') {
                $sanitized[$key] = wp_strip_all_tags($value);
            } elseif (strpos($key, 'color') !== false) {
                $sanitized[$key] = sanitize_hex_color($value);
            } else {
                $sanitized[$key] = sanitize_text_field($value);
            }
        }
        
        return $sanitized;
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
            'menu_text_color' => '#ffffff',
            'menu_hover_background' => '#32373c',
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
            
            // Effects
            'animation_speed' => 300,
            'fade_in_effects' => false,
            'slide_animations' => false,
            'scale_hover_effects' => false,
            'glassmorphism_effects' => false,
            'gradient_backgrounds' => false,
            'particle_effects' => false,
            'smooth_scrolling' => false,
            
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
            'form_field_border' => '#ddd',
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
     * Wyczyść cache
     */
    private function clearCache() {
        global $wpdb;
        
        // Wyczyść transients związane z pluginem
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_mas_v2_%' OR option_name LIKE '_transient_mas_v2_%'");
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_site_transient_timeout_mas_v2_%' OR option_name LIKE '_site_transient_mas_v2_%'");
        
        // Wyczyść cache obiektów WordPress
        wp_cache_flush();
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
