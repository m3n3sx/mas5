<?php
/**
 * Test Theme Management Endpoints (Task 3)
 * 
 * This file tests the theme management REST API endpoints implementation.
 * 
 * Usage: Access this file directly in your browser while logged in as admin
 */

// Load WordPress
require_once __DIR__ . '/modern-admin-styler-v2.php';

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('You must be logged in as an administrator to run this test.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Task 3: Theme Management Endpoints Test</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
            background: #f0f0f1;
        }
        .test-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        h1 {
            color: #1d2327;
            border-bottom: 2px solid #2271b1;
            padding-bottom: 10px;
        }
        h2 {
            color: #2271b1;
            margin-top: 30px;
        }
        .test-section {
            margin: 20px 0;
            padding: 15px;
            background: #f6f7f7;
            border-left: 4px solid #2271b1;
        }
        .success {
            color: #00a32a;
            font-weight: bold;
        }
        .error {
            color: #d63638;
            font-weight: bold;
        }
        .info {
            color: #2271b1;
        }
        button {
            background: #2271b1;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
        }
        button:hover {
            background: #135e96;
        }
        pre {
            background: #1d2327;
            color: #f0f0f1;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
        .theme-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin: 10px 0;
        }
        .theme-card h3 {
            margin-top: 0;
            color: #1d2327;
        }
        .theme-colors {
            display: flex;
            gap: 10px;
            margin: 10px 0;
        }
        .color-swatch {
            width: 40px;
            height: 40px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>🎨 Task 3: Theme Management Endpoints Test</h1>
        <p>This page tests the theme management REST API endpoints implementation.</p>
        
        <div class="test-section">
            <h2>Service Tests</h2>
            <div id="service-tests"></div>
        </div>
        
        <div class="test-section">
            <h2>REST API Tests</h2>
            <button onclick="testGetThemes()">Test GET /themes</button>
            <button onclick="testGetTheme()">Test GET /themes/{id}</button>
            <button onclick="testCreateTheme()">Test POST /themes</button>
            <button onclick="testUpdateTheme()">Test PUT /themes/{id}</button>
            <button onclick="testDeleteTheme()">Test DELETE /themes/{id}</button>
            <button onclick="testApplyTheme()">Test POST /themes/{id}/apply</button>
            <div id="rest-results"></div>
        </div>
        
        <div class="test-section">
            <h2>Theme Gallery</h2>
            <div id="theme-gallery"></div>
        </div>
        
        <div class="test-section">
            <h2>JavaScript Client Tests</h2>
            <button onclick="testJSClient()">Test JavaScript Client</button>
            <div id="js-results"></div>
        </div>
    </div>

    <script>
        // Test service layer
        function testServices() {
            const results = document.getElementById('service-tests');
            results.innerHTML = '<p class="info">Running service tests...</p>';
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=test_theme_service&nonce=<?php echo wp_create_nonce('test_theme_service'); ?>'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    results.innerHTML = '<p class="success">✓ Service tests passed</p><pre>' + 
                        JSON.stringify(data.data, null, 2) + '</pre>';
                } else {
                    results.innerHTML = '<p class="error">✗ Service tests failed: ' + data.data + '</p>';
                }
            })
            .catch(error => {
                results.innerHTML = '<p class="error">✗ Error: ' + error.message + '</p>';
            });
        }
        
        // Test GET /themes
        function testGetThemes() {
            const results = document.getElementById('rest-results');
            results.innerHTML = '<p class="info">Testing GET /themes...</p>';
            
            fetch('<?php echo rest_url('mas-v2/v1/themes'); ?>', {
                method: 'GET',
                headers: {
                    'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
                },
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    results.innerHTML = '<p class="success">✓ GET /themes successful</p><pre>' + 
                        JSON.stringify(data, null, 2) + '</pre>';
                    displayThemes(data.data);
                } else {
                    results.innerHTML = '<p class="error">✗ Failed: ' + data.message + '</p>';
                }
            })
            .catch(error => {
                results.innerHTML = '<p class="error">✗ Error: ' + error.message + '</p>';
            });
        }
        
        // Test GET /themes/{id}
        function testGetTheme() {
            const results = document.getElementById('rest-results');
            results.innerHTML = '<p class="info">Testing GET /themes/dark-blue...</p>';
            
            fetch('<?php echo rest_url('mas-v2/v1/themes/dark-blue'); ?>', {
                method: 'GET',
                headers: {
                    'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
                },
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    results.innerHTML = '<p class="success">✓ GET /themes/{id} successful</p><pre>' + 
                        JSON.stringify(data, null, 2) + '</pre>';
                } else {
                    results.innerHTML = '<p class="error">✗ Failed: ' + data.message + '</p>';
                }
            })
            .catch(error => {
                results.innerHTML = '<p class="error">✗ Error: ' + error.message + '</p>';
            });
        }
        
        // Test POST /themes
        function testCreateTheme() {
            const results = document.getElementById('rest-results');
            results.innerHTML = '<p class="info">Testing POST /themes...</p>';
            
            const testTheme = {
                id: 'test-theme-' + Date.now(),
                name: 'Test Theme',
                description: 'A test theme created via REST API',
                settings: {
                    menu_background: '#1a1a2e',
                    menu_text_color: '#ffffff',
                    menu_hover_background: '#16213e',
                    menu_hover_text_color: '#0f3460',
                    admin_bar_background: '#1a1a2e',
                    admin_bar_text_color: '#ffffff'
                }
            };
            
            fetch('<?php echo rest_url('mas-v2/v1/themes'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
                },
                credentials: 'same-origin',
                body: JSON.stringify(testTheme)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    results.innerHTML = '<p class="success">✓ POST /themes successful</p><pre>' + 
                        JSON.stringify(data, null, 2) + '</pre>';
                    window.testThemeId = testTheme.id;
                } else {
                    results.innerHTML = '<p class="error">✗ Failed: ' + data.message + '</p><pre>' +
                        JSON.stringify(data, null, 2) + '</pre>';
                }
            })
            .catch(error => {
                results.innerHTML = '<p class="error">✗ Error: ' + error.message + '</p>';
            });
        }
        
        // Test PUT /themes/{id}
        function testUpdateTheme() {
            if (!window.testThemeId) {
                alert('Please create a test theme first');
                return;
            }
            
            const results = document.getElementById('rest-results');
            results.innerHTML = '<p class="info">Testing PUT /themes/{id}...</p>';
            
            const updatedTheme = {
                name: 'Updated Test Theme',
                description: 'Updated via REST API',
                settings: {
                    menu_background: '#2a2a3e',
                    menu_text_color: '#ffffff'
                }
            };
            
            fetch('<?php echo rest_url('mas-v2/v1/themes/'); ?>' + window.testThemeId, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
                },
                credentials: 'same-origin',
                body: JSON.stringify(updatedTheme)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    results.innerHTML = '<p class="success">✓ PUT /themes/{id} successful</p><pre>' + 
                        JSON.stringify(data, null, 2) + '</pre>';
                } else {
                    results.innerHTML = '<p class="error">✗ Failed: ' + data.message + '</p>';
                }
            })
            .catch(error => {
                results.innerHTML = '<p class="error">✗ Error: ' + error.message + '</p>';
            });
        }
        
        // Test DELETE /themes/{id}
        function testDeleteTheme() {
            if (!window.testThemeId) {
                alert('Please create a test theme first');
                return;
            }
            
            const results = document.getElementById('rest-results');
            results.innerHTML = '<p class="info">Testing DELETE /themes/{id}...</p>';
            
            fetch('<?php echo rest_url('mas-v2/v1/themes/'); ?>' + window.testThemeId, {
                method: 'DELETE',
                headers: {
                    'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
                },
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    results.innerHTML = '<p class="success">✓ DELETE /themes/{id} successful</p><pre>' + 
                        JSON.stringify(data, null, 2) + '</pre>';
                    window.testThemeId = null;
                } else {
                    results.innerHTML = '<p class="error">✗ Failed: ' + data.message + '</p>';
                }
            })
            .catch(error => {
                results.innerHTML = '<p class="error">✗ Error: ' + error.message + '</p>';
            });
        }
        
        // Test POST /themes/{id}/apply
        function testApplyTheme() {
            const results = document.getElementById('rest-results');
            results.innerHTML = '<p class="info">Testing POST /themes/dark-blue/apply...</p>';
            
            fetch('<?php echo rest_url('mas-v2/v1/themes/dark-blue/apply'); ?>', {
                method: 'POST',
                headers: {
                    'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
                },
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    results.innerHTML = '<p class="success">✓ POST /themes/{id}/apply successful</p><pre>' + 
                        JSON.stringify(data, null, 2) + '</pre>';
                } else {
                    results.innerHTML = '<p class="error">✗ Failed: ' + data.message + '</p>';
                }
            })
            .catch(error => {
                results.innerHTML = '<p class="error">✗ Error: ' + error.message + '</p>';
            });
        }
        
        // Display themes in gallery
        function displayThemes(themes) {
            const gallery = document.getElementById('theme-gallery');
            gallery.innerHTML = '';
            
            themes.forEach(theme => {
                const card = document.createElement('div');
                card.className = 'theme-card';
                
                let colorsHTML = '';
                if (theme.settings) {
                    colorsHTML = '<div class="theme-colors">';
                    if (theme.settings.menu_background) {
                        colorsHTML += `<div class="color-swatch" style="background: ${theme.settings.menu_background}" title="Menu Background"></div>`;
                    }
                    if (theme.settings.menu_text_color) {
                        colorsHTML += `<div class="color-swatch" style="background: ${theme.settings.menu_text_color}" title="Menu Text"></div>`;
                    }
                    if (theme.settings.menu_hover_background) {
                        colorsHTML += `<div class="color-swatch" style="background: ${theme.settings.menu_hover_background}" title="Menu Hover"></div>`;
                    }
                    colorsHTML += '</div>';
                }
                
                card.innerHTML = `
                    <h3>${theme.name} ${theme.readonly ? '🔒' : ''}</h3>
                    <p><strong>ID:</strong> ${theme.id}</p>
                    <p><strong>Type:</strong> ${theme.type}</p>
                    <p>${theme.description || ''}</p>
                    ${colorsHTML}
                    <button onclick="applyThemeById('${theme.id}')">Apply Theme</button>
                `;
                
                gallery.appendChild(card);
            });
        }
        
        // Apply theme by ID
        function applyThemeById(themeId) {
            fetch('<?php echo rest_url('mas-v2/v1/themes/'); ?>' + themeId + '/apply', {
                method: 'POST',
                headers: {
                    'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
                },
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Theme applied successfully!');
                } else {
                    alert('Failed to apply theme: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
            });
        }
        
        // Test JavaScript client
        function testJSClient() {
            const results = document.getElementById('js-results');
            results.innerHTML = '<p class="info">Testing JavaScript client...</p>';
            
            // Check if client is available
            if (!window.masRestClient) {
                results.innerHTML = '<p class="error">✗ MAS REST Client not available</p>';
                return;
            }
            
            // Test getThemes
            window.masRestClient.getThemes()
                .then(themes => {
                    results.innerHTML = '<p class="success">✓ JavaScript client working</p>' +
                        '<p>Found ' + themes.length + ' themes</p><pre>' + 
                        JSON.stringify(themes, null, 2) + '</pre>';
                })
                .catch(error => {
                    results.innerHTML = '<p class="error">✗ Error: ' + error.message + '</p>';
                });
        }
        
        // Run service tests on load
        window.addEventListener('load', function() {
            testServices();
            testGetThemes();
        });
    </script>
</body>
</html>
