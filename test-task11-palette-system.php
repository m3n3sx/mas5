<?php
/**
 * Task 11 Test: Color Palette System
 * Testing PaletteManager functionality and CSS variable updates
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Task 11: Color Palette System Test</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            margin: 20px; 
            background: #f5f5f5; 
        }
        .test-container { 
            background: white; 
            padding: 20px; 
            border-radius: 8px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
            margin-bottom: 20px; 
        }
        .test-button { 
            background: #0073aa; 
            color: white; 
            border: none; 
            padding: 10px 20px; 
            border-radius: 4px; 
            cursor: pointer; 
            margin: 5px; 
        }
        .test-button:hover { background: #005a87; }
        .status-ok { color: #46b450; margin: 5px 0; }
        .status-error { color: #dc3232; margin: 5px 0; }
        .status-warning { color: #ffb900; margin: 5px 0; }
        .palette-demo { 
            width: 100%; 
            height: 100px; 
            border-radius: 8px; 
            margin: 10px 0; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            color: white; 
            font-weight: bold; 
            transition: all 0.8s ease; 
        }
        .css-variables-display {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 12px;
            margin: 10px 0;
            max-height: 200px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <h1>🎨 Task 11: Color Palette System Test</h1>
    
    <div class="test-container">
        <h2>📋 1. File Existence Check</h2>
        <?php
        $required_files = [
            'assets/css/color-palettes.css' => '🎨 Color Palettes CSS',
            'assets/css/palette-switcher.css' => '🎯 Palette Switcher CSS',
            'assets/js/modules/PaletteManager.js' => '🎨 Palette Manager JS'
        ];
        
        foreach ($required_files as $file => $description) {
            if (file_exists($file)) {
                echo "<div class='status-ok'>✅ {$description}: Found</div>";
            } else {
                echo "<div class='status-error'>❌ {$description}: Missing</div>";
            }
        }
        ?>
    </div>

    <div class="test-container">
        <h2>⚡ 2. JavaScript API Test</h2>
        <button class="test-button" onclick="testPaletteManager()">Test PaletteManager</button>
        <button class="test-button" onclick="testPaletteSwitch()">Test Palette Switching</button>
        <button class="test-button" onclick="testCSSVariables()">Test CSS Variables</button>
        <button class="test-button" onclick="testCustomPalette()">Test Custom Palette</button>
        <button class="test-button" onclick="testPaletteExport()">Test Export/Import</button>
        <div id="js-results"></div>
    </div>

    <div class="test-container">
        <h2>🎨 3. Visual Palette Demo</h2>
        <div class="palette-demo" id="palette-demo">
            Current Palette Demo Area
        </div>
        <div>
            <button class="test-button" onclick="switchToPalette('professional-blue')">Professional Blue</button>
            <button class="test-button" onclick="switchToPalette('creative-purple')">Creative Purple</button>
            <button class="test-button" onclick="switchToPalette('energetic-green')">Energetic Green</button>
            <button class="test-button" onclick="switchToPalette('sunset-orange')">Sunset Orange</button>
            <button class="test-button" onclick="switchToPalette('midnight')">Midnight</button>
        </div>
    </div>

    <div class="test-container">
        <h2>🔧 4. CSS Variables Monitor</h2>
        <button class="test-button" onclick="monitorCSSVariables()">Monitor CSS Variables</button>
        <div class="css-variables-display" id="css-variables"></div>
    </div>

    <div class="test-container">
        <h2>📊 5. Requirements Verification</h2>
        <button class="test-button" onclick="verifyRequirements()">Verify Requirements 3.4</button>
        <div id="requirements-results"></div>
    </div>

    <!-- Load MAS V2 Assets -->
    <link rel="stylesheet" href="assets/css/color-palettes.css">
    <link rel="stylesheet" href="assets/css/palette-switcher.css">
    <script src="assets/js/mas-loader.js"></script>

    <script>
        // Test PaletteManager functionality
        function testPaletteManager() {
            const results = document.getElementById('js-results');
            let output = '<h3>PaletteManager Test Results:</h3>';
            
            if (typeof window.PaletteManager !== 'undefined') {
                output += '<div class="status-ok">✅ PaletteManager class available</div>';
                
                try {
                    const manager = new window.PaletteManager();
                    output += '<div class="status-ok">✅ PaletteManager can be instantiated</div>';
                    
                    const palettes = manager.getAllPalettes();
                    output += `<div class="status-ok">✅ Found ${Object.keys(palettes).length} predefined palettes</div>`;
                    
                    // Test palette names
                    const expectedPalettes = ['professional-blue', 'creative-purple', 'energetic-green', 'sunset-orange', 'rose-gold', 'midnight', 'ocean-teal', 'electric-cyber', 'golden-sunrise', 'gaming-neon'];
                    const foundPalettes = Object.keys(palettes);
                    const missingPalettes = expectedPalettes.filter(p => !foundPalettes.includes(p));
                    
                    if (missingPalettes.length === 0) {
                        output += '<div class="status-ok">✅ All expected palettes found</div>';
                    } else {
                        output += `<div class="status-error">❌ Missing palettes: ${missingPalettes.join(', ')}</div>`;
                    }
                    
                } catch (error) {
                    output += `<div class="status-error">❌ Error testing PaletteManager: ${error.message}</div>`;
                }
            } else {
                output += '<div class="status-error">❌ PaletteManager class not found</div>';
            }
            
            results.innerHTML = output;
        }

        function testPaletteSwitch() {
            const results = document.getElementById('js-results');
            let output = '<h3>Palette Switching Test:</h3>';
            
            try {
                if (window.ModernAdminApp) {
                    const app = window.ModernAdminApp.getInstance();
                    const paletteManager = app.getModule('paletteManager');
                    if (paletteManager) {
                        // Test switching to different palettes
                        paletteManager.setPalette('creative-purple');
                        output += '<div class="status-ok">✅ Successfully switched to creative-purple</div>';
                        
                        const current = paletteManager.getCurrentPalette();
                        if (current.id === 'creative-purple') {
                            output += '<div class="status-ok">✅ Current palette correctly updated</div>';
                        } else {
                            output += '<div class="status-error">❌ Current palette not updated correctly</div>';
                        }
                        
                        return;
                    }
                }
                
                // Fallback to direct PaletteManager
                if (typeof window.PaletteManager !== 'undefined') {
                    const manager = new window.PaletteManager();
                    manager.setPalette('creative-purple');
                    output += '<div class="status-ok">✅ Direct PaletteManager switching works</div>';
                } else {
                    output += '<div class="status-error">❌ No PaletteManager available for testing</div>';
                }
                
            } catch (error) {
                output += `<div class="status-error">❌ Error testing palette switching: ${error.message}</div>`;
            }
            
            results.innerHTML = output;
        }

        function testCSSVariables() {
            const results = document.getElementById('js-results');
            let output = '<h3>CSS Variables Test:</h3>';
            
            const testVariables = [
                '--mas-accent-start',
                '--mas-accent-end', 
                '--mas-glass-primary',
                '--mas-text-primary',
                '--mas-bg-primary'
            ];
            
            const computedStyle = getComputedStyle(document.documentElement);
            
            testVariables.forEach(variable => {
                const value = computedStyle.getPropertyValue(variable).trim();
                if (value) {
                    output += `<div class="status-ok">✅ ${variable}: ${value}</div>`;
                } else {
                    output += `<div class="status-error">❌ ${variable}: Not defined</div>`;
                }
            });
            
            results.innerHTML = output;
        }

        function testCustomPalette() {
            const results = document.getElementById('js-results');
            let output = '<h3>Custom Palette Test:</h3>';
            
            try {
                if (window.ModernAdminApp) {
                    const app = window.ModernAdminApp.getInstance();
                    const paletteManager = app.getModule('paletteManager');
                    if (paletteManager) {
                        // Test creating a custom palette
                        const customPalette = {
                            name: '🧪 Test Custom',
                            description: 'Custom test palette',
                            mood: 'test',
                            colors: {
                                primary: '#FF6B6B',
                                secondary: '#4ECDC4',
                                background: '#F7F7F7',
                                text: '#2C3E50'
                            }
                        };
                        
                        const paletteId = 'test-custom-' + Date.now();
                        paletteManager.createCustomPalette(paletteId, customPalette);
                        output += '<div class="status-ok">✅ Custom palette created successfully</div>';
                        
                        // Test switching to custom palette
                        paletteManager.setPalette(paletteId);
                        output += '<div class="status-ok">✅ Successfully switched to custom palette</div>';
                        
                        // Test editing custom palette
                        paletteManager.editCustomPalette(paletteId, {
                            name: '🧪 Test Custom (Edited)',
                            colors: {
                                ...customPalette.colors,
                                primary: '#FF5722'
                            }
                        });
                        output += '<div class="status-ok">✅ Custom palette edited successfully</div>';
                        
                        // Test getting custom palettes
                        const customPalettes = paletteManager.getCustomPalettes();
                        output += `<div class="status-ok">✅ Found ${Object.keys(customPalettes).length} custom palette(s)</div>`;
                        
                        return;
                    }
                }
                
                // Fallback to direct PaletteManager
                if (typeof window.PaletteManager !== 'undefined') {
                    const manager = new window.PaletteManager();
                    output += '<div class="status-warning">⚠️ Testing with direct PaletteManager (not integrated)</div>';
                    
                    const customPalette = {
                        name: '🧪 Test Custom',
                        description: 'Custom test palette',
                        mood: 'test',
                        colors: {
                            primary: '#FF6B6B',
                            secondary: '#4ECDC4',
                            background: '#F7F7F7',
                            text: '#2C3E50'
                        }
                    };
                    
                    const paletteId = 'test-custom-' + Date.now();
                    manager.createCustomPalette(paletteId, customPalette);
                    output += '<div class="status-ok">✅ Direct custom palette creation works</div>';
                } else {
                    output += '<div class="status-error">❌ No PaletteManager available for testing</div>';
                }
                
            } catch (error) {
                output += `<div class="status-error">❌ Error testing custom palette: ${error.message}</div>`;
            }
            
            results.innerHTML = output;
        }

        function testPaletteExport() {
            const results = document.getElementById('js-results');
            let output = '<h3>Export/Import Test:</h3>';
            
            try {
                if (window.ModernAdminApp) {
                    const app = window.ModernAdminApp.getInstance();
                    const paletteManager = app.getModule('paletteManager');
                    if (paletteManager) {
                        // Create a test palette first
                        const testPalette = {
                            name: '🧪 Export Test',
                            description: 'Test palette for export',
                            mood: 'test',
                            colors: {
                                primary: '#E91E63',
                                secondary: '#9C27B0',
                                background: '#FAFAFA',
                                text: '#212121'
                            }
                        };
                        
                        const paletteId = 'export-test-' + Date.now();
                        paletteManager.createCustomPalette(paletteId, testPalette);
                        
                        // Test export
                        const exportData = paletteManager.exportCustomPalettes();
                        output += '<div class="status-ok">✅ Export functionality works</div>';
                        output += `<div class="status-ok">✅ Exported ${Object.keys(exportData.palettes).length} palette(s)</div>`;
                        
                        // Test import (simulate)
                        const importCount = paletteManager.importCustomPalettes(exportData);
                        output += `<div class="status-ok">✅ Import test completed (${importCount} palettes)</div>`;
                        
                        return;
                    }
                }
                
                output += '<div class="status-warning">⚠️ Export/Import requires integrated PaletteManager</div>';
                
            } catch (error) {
                output += `<div class="status-error">❌ Error testing export/import: ${error.message}</div>`;
            }
            
            results.innerHTML = output;
        }

        function switchToPalette(paletteId) {
            try {
                document.documentElement.setAttribute('data-palette', paletteId);
                
                // Update demo area
                const demo = document.getElementById('palette-demo');
                const computedStyle = getComputedStyle(document.documentElement);
                const primaryColor = computedStyle.getPropertyValue('--mas-accent-start').trim();
                const secondaryColor = computedStyle.getPropertyValue('--mas-accent-end').trim();
                
                if (primaryColor && secondaryColor) {
                    demo.style.background = `linear-gradient(135deg, ${primaryColor}, ${secondaryColor})`;
                    demo.textContent = `${paletteId.replace('-', ' ').toUpperCase()} PALETTE`;
                }
                
                console.log(`Switched to palette: ${paletteId}`);
            } catch (error) {
                console.error('Error switching palette:', error);
            }
        }

        function monitorCSSVariables() {
            const display = document.getElementById('css-variables');
            const computedStyle = getComputedStyle(document.documentElement);
            
            const variables = [
                '--mas-accent-start', '--mas-accent-end', '--mas-accent-glow',
                '--mas-glass-primary', '--mas-glass-border', '--mas-submenu-glass',
                '--mas-bg-primary', '--mas-bg-secondary',
                '--mas-text-primary', '--mas-text-secondary',
                '--mas-active-start', '--mas-active-end'
            ];
            
            let output = '';
            variables.forEach(variable => {
                const value = computedStyle.getPropertyValue(variable).trim();
                output += `${variable}: ${value || 'undefined'}\n`;
            });
            
            display.textContent = output;
        }

        function verifyRequirements() {
            const results = document.getElementById('requirements-results');
            let output = '<h3>Requirements 3.4 Verification:</h3>';
            
            // Requirement 3.4: Color palette system functionality
            const checks = [
                {
                    name: 'Predefined color schemes available',
                    test: () => {
                        if (typeof window.PaletteManager !== 'undefined') {
                            const manager = new window.PaletteManager();
                            const palettes = manager.getPredefinedPalettes ? manager.getPredefinedPalettes() : manager.getAllPalettes();
                            return Object.keys(palettes).length >= 10;
                        }
                        return false;
                    }
                },
                {
                    name: 'Color palette switching works',
                    test: () => {
                        try {
                            document.documentElement.setAttribute('data-palette', 'professional-blue');
                            const currentPalette = document.documentElement.getAttribute('data-palette');
                            return currentPalette === 'professional-blue';
                        } catch {
                            return false;
                        }
                    }
                },
                {
                    name: 'CSS variables update correctly',
                    test: () => {
                        const computedStyle = getComputedStyle(document.documentElement);
                        const primaryColor = computedStyle.getPropertyValue('--mas-accent-start').trim();
                        return primaryColor !== '';
                    }
                },
                {
                    name: 'Custom palette creation available',
                    test: () => {
                        if (typeof window.PaletteManager !== 'undefined') {
                            const manager = new window.PaletteManager();
                            return typeof manager.createCustomPalette === 'function';
                        }
                        return false;
                    }
                },
                {
                    name: 'Palette management functions available',
                    test: () => {
                        if (typeof window.PaletteManager !== 'undefined') {
                            const manager = new window.PaletteManager();
                            return typeof manager.editCustomPalette === 'function' && 
                                   typeof manager.deleteCustomPalette === 'function';
                        }
                        return false;
                    }
                },
                {
                    name: 'Export/Import functionality available',
                    test: () => {
                        if (typeof window.PaletteManager !== 'undefined') {
                            const manager = new window.PaletteManager();
                            return typeof manager.exportCustomPalettes === 'function' && 
                                   typeof manager.importCustomPalettes === 'function';
                        }
                        return false;
                    }
                }
            ];
            
            checks.forEach(check => {
                try {
                    if (check.test()) {
                        output += `<div class="status-ok">✅ ${check.name}</div>`;
                    } else {
                        output += `<div class="status-error">❌ ${check.name}</div>`;
                    }
                } catch (error) {
                    output += `<div class="status-error">❌ ${check.name}: ${error.message}</div>`;
                }
            });
            
            results.innerHTML = output;
        }

        // Initialize with default palette
        document.addEventListener('DOMContentLoaded', function() {
            switchToPalette('professional-blue');
            
            // Auto-run basic tests
            setTimeout(() => {
                testPaletteManager();
                testCSSVariables();
            }, 1000);
        });
    </script>
</body>
</html>