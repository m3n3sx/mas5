/**
 * MAS5 Plugin Functionality Test Suite
 * Comprehensive tests for emergency stabilization verification
 */
class MAS5FunctionalityTest {
    constructor() {
        this.results = [];
        this.testContainer = document.getElementById('testResults');
        this.summaryContainer = document.getElementById('summary');
        this.totalTests = 0;
        this.passedTests = 0;
        this.failedTests = 0;
        this.warningTests = 0;
        this.bindEvents();
    }
    
    bindEvents() {
        document.getElementById('runAllTests').addEventListener('click', () => this.runAllTests());
        document.getElementById('runSystemTests').addEventListener('click', () => this.testSystemLoading());
        document.getElementById('runFormTests').addEventListener('click', () => this.testFormHandler());
        document.getElementById('runPreviewTests').addEventListener('click', () => this.testLivePreview());
        document.getElementById('runAjaxTests').addEventListener('click', () => this.testAjaxEndpoints());
        document.getElementById('clearResults').addEventListener('click', () => this.clearResults());
    }
    
    async runAllTests() {
        this.clearResults();
        this.log('info', 'Starting comprehensive test suite...');
        await this.testSystemLoading();
        await this.testFormHandler();
        await this.testLivePreview();
        await this.testAjaxEndpoints();
        this.displaySummary();
        this.log('info', `Test suite complete: ${this.passedTests}/${this.totalTests} passed`);
    }
    
    async testSystemLoading() {
        this.addSection('System Loading Tests');
        
        this.test(
            'masV2Global is defined',
            typeof window.masV2Global !== 'undefined',
            'masV2Global object is available',
            'masV2Global is not defined - plugin may not be loaded'
        );
        
        if (typeof window.masV2Global !== 'undefined') {
            this.test(
                'Emergency mode is active',
                window.masV2Global.emergencyMode === true,
                'Emergency mode flag is set correctly',
                'Emergency mode flag is not set'
            );
            
            this.test(
                'Frontend mode is Phase 2',
                window.masV2Global.frontendMode === 'phase2-stable',
                'Using Phase 2 stable system',
                `Unexpected frontend mode: ${window.masV2Global.frontendMode}`
            );
        }
        
        this.test(
            'Phase 3 frontend is disabled',
            window.MASUseNewFrontend === false,
            'Phase 3 frontend correctly disabled',
            'Phase 3 frontend may still be active'
        );
        
        this.test(
            'Modular system is disabled',
            window.MASDisableModules === true,
            'Modular system correctly disabled',
            'Modular system may still be active'
        );
        
        const phase3Scripts = [
            'mas-admin-app.js', 'EventBus.js', 'StateManager.js', 'APIClient.js',
            'ErrorHandler.js', 'Component.js', 'SettingsFormComponent.js', 'LivePreviewComponent.js'
        ];
        
        const loadedScripts = Array.from(document.scripts).map(s => s.src);
        const phase3Loaded = phase3Scripts.filter(script => 
            loadedScripts.some(src => src.includes(script))
        );
        
        this.test(
            'No Phase 3 scripts loaded',
            phase3Loaded.length === 0,
            'Phase 3 scripts correctly excluded',
            `Phase 3 scripts found: ${phase3Loaded.join(', ')}`
        );
        
        const phase2Scripts = ['mas-rest-client.js', 'mas-settings-form-handler.js', 'simple-live-preview.js'];
        const phase2Loaded = phase2Scripts.filter(script =>
            loadedScripts.some(src => src.includes(script))
        );
        
        this.test(
            'Phase 2 scripts loaded',
            phase2Loaded.length === phase2Scripts.length,
            `All Phase 2 scripts loaded: ${phase2Loaded.join(', ')}`,
            `Missing Phase 2 scripts: ${phase2Scripts.filter(s => !phase2Loaded.includes(s)).join(', ')}`
        );
        
        this.test(
            'jQuery is available',
            typeof jQuery !== 'undefined',
            'jQuery loaded successfully',
            'jQuery is not available'
        );
        
        this.test(
            'wp.colorPicker is available',
            typeof jQuery !== 'undefined' && typeof jQuery.fn.wpColorPicker !== 'undefined',
            'WordPress color picker loaded',
            'WordPress color picker not available'
        );
    }
    
    async testFormHandler() {
        this.addSection('Form Handler Tests');
        
        this.test(
            'Form handler initialized',
            typeof window.masFormHandler !== 'undefined' || 
            (typeof jQuery !== 'undefined' && jQuery('#mas-v2-settings-form').length > 0),
            'Form handler is available',
            'Form handler not found'
        );
        
        if (typeof window.masV2Global !== 'undefined') {
            this.test(
                'AJAX URL configured',
                typeof window.masV2Global.ajaxUrl === 'string' && window.masV2Global.ajaxUrl.length > 0,
                `AJAX URL: ${window.masV2Global.ajaxUrl}`,
                'AJAX URL not configured'
            );
            
            this.test(
                'REST URL configured',
                typeof window.masV2Global.restUrl === 'string' && window.masV2Global.restUrl.length > 0,
                `REST URL: ${window.masV2Global.restUrl}`,
                'REST URL not configured'
            );
            
            this.test(
                'Nonce configured',
                typeof window.masV2Global.nonce === 'string' && window.masV2Global.nonce.length > 0,
                'Security nonce is set',
                'Security nonce not configured'
            );
        }
        
        if (typeof jQuery !== 'undefined') {
            const formExists = jQuery('#mas-v2-settings-form').length > 0;
            this.test(
                'Settings form exists',
                formExists,
                'Settings form found in DOM',
                'Settings form not found - may not be on settings page',
                'warning'
            );
        }
    }
    
    async testLivePreview() {
        this.addSection('Live Preview Tests');
        
        const scripts = Array.from(document.scripts).map(s => s.src);
        const livePreviewLoaded = scripts.some(src => src.includes('simple-live-preview.js'));
        
        this.test(
            'Live preview script loaded',
            livePreviewLoaded,
            'simple-live-preview.js is loaded',
            'Live preview script not found'
        );
        
        const brokenPreviewScripts = ['LivePreviewManager.js', 'LivePreviewComponent.js'];
        const brokenLoaded = brokenPreviewScripts.filter(script =>
            scripts.some(src => src.includes(script))
        );
        
        this.test(
            'Broken preview systems not loaded',
            brokenLoaded.length === 0,
            'No competing live preview systems',
            `Competing systems found: ${brokenLoaded.join(', ')}`
        );
        
        if (typeof jQuery !== 'undefined') {
            const previewExists = jQuery('#mas-live-preview').length > 0 || 
                                jQuery('.mas-preview-frame').length > 0;
            this.test(
                'Preview container exists',
                previewExists,
                'Live preview container found',
                'Preview container not found - may not be on settings page',
                'warning'
            );
        }
    }
    
    async testAjaxEndpoints() {
        this.addSection('AJAX Endpoint Tests');
        
        if (typeof window.masV2Global === 'undefined') {
            this.log('warning', 'Cannot test AJAX endpoints - masV2Global not defined');
            return;
        }
        
        this.log('info', 'Testing mas_v2_save_settings endpoint...');
        
        try {
            const testData = {
                action: 'mas_v2_save_settings',
                nonce: window.masV2Global.nonce,
                settings: { test_field: 'test_value' }
            };
            
            const response = await fetch(window.masV2Global.ajaxUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams(testData)
            });
            
            const result = await response.json();
            
            this.test(
                'Save settings endpoint responds',
                response.ok,
                `Endpoint returned status ${response.status}`,
                `Endpoint failed with status ${response.status}`
            );
            
            this.log('info', `Response: ${JSON.stringify(result, null, 2)}`);
            
        } catch (error) {
            this.test('Save settings endpoint responds', false, '', `Error: ${error.message}`);
        }
        
        this.log('info', 'Testing mas_v2_get_preview_css endpoint...');
        
        try {
            const testData = {
                action: 'mas_v2_get_preview_css',
                nonce: window.masV2Global.nonce,
                settings: { admin_bar_bg: '#2271b1' }
            };
            
            const response = await fetch(window.masV2Global.ajaxUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams(testData)
            });
            
            const result = await response.json();
            
            this.test(
                'Preview CSS endpoint responds',
                response.ok,
                `Endpoint returned status ${response.status}`,
                `Endpoint failed with status ${response.status}`
            );
            
            if (result.success && result.data && result.data.css) {
                this.test(
                    'Preview CSS generated',
                    result.data.css.length > 0,
                    `Generated ${result.data.css.length} characters of CSS`,
                    'No CSS generated'
                );
            }
            
        } catch (error) {
            this.test('Preview CSS endpoint responds', false, '', `Error: ${error.message}`);
        }
    }
    
    test(name, condition, successMsg, failMsg, type = null) {
        this.totalTests++;
        if (condition) {
            this.passedTests++;
            this.log('pass', `✓ ${name}: ${successMsg}`);
        } else {
            if (type === 'warning') {
                this.warningTests++;
                this.log('warning', `⚠ ${name}: ${failMsg}`);
            } else {
                this.failedTests++;
                this.log('fail', `✗ ${name}: ${failMsg}`);
            }
        }
    }
    
    log(type, message) {
        const resultDiv = document.createElement('div');
        resultDiv.className = `test-result ${type}`;
        resultDiv.textContent = message;
        this.testContainer.appendChild(resultDiv);
        console.log(`[MAS5 Test] ${type.toUpperCase()}: ${message}`);
    }
    
    addSection(title) {
        const section = document.createElement('div');
        section.className = 'test-section';
        section.innerHTML = `<h2>${title}</h2>`;
        this.testContainer.appendChild(section);
    }
    
    displaySummary() {
        this.summaryContainer.style.display = 'flex';
        document.getElementById('totalCount').textContent = this.totalTests;
        document.getElementById('passCount').textContent = this.passedTests;
        document.getElementById('failCount').textContent = this.failedTests;
        document.getElementById('warningCount').textContent = this.warningTests;
    }
    
    clearResults() {
        this.testContainer.innerHTML = '';
        this.summaryContainer.style.display = 'none';
        this.totalTests = 0;
        this.passedTests = 0;
        this.failedTests = 0;
        this.warningTests = 0;
    }
}

// Initialize test suite
const testSuite = new MAS5FunctionalityTest();

if (typeof window.masV2Global !== 'undefined') {
    console.log('MAS5 Test Suite: WordPress environment detected');
    console.log('Run testSuite.runAllTests() to start testing');
} else {
    console.log('MAS5 Test Suite: Not on WordPress admin page');
    console.log('Open this file from WordPress admin to run tests');
}
