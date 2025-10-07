<?php
/**
 * MAS Handler Diagnostics - WordPress Admin Page
 * 
 * This script provides a diagnostic interface for detecting
 * event handler conflicts in the WordPress admin.
 * 
 * Usage: Place in plugin root and access via WordPress admin
 * 
 * @package ModernAdminStyler
 * @since 3.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add diagnostics page to WordPress admin
 */
function mas_add_diagnostics_page() {
    add_submenu_page(
        'tools.php',
        'MAS Handler Diagnostics',
        'MAS Diagnostics',
        'manage_options',
        'mas-handler-diagnostics',
        'mas_render_diagnostics_page'
    );
}
add_action('admin_menu', 'mas_add_diagnostics_page');

/**
 * Enqueue diagnostics scripts
 */
function mas_enqueue_diagnostics_scripts($hook) {
    if ($hook !== 'tools_page_mas-handler-diagnostics') {
        return;
    }
    
    // Enqueue jQuery
    wp_enqueue_script('jquery');
    
    // Enqueue diagnostics tool
    wp_enqueue_script(
        'mas-handler-diagnostics',
        plugins_url('assets/js/utils/HandlerDiagnostics.js', __FILE__),
        [],
        '3.0.0',
        true
    );
    
    // Enqueue diagnostics styles
    wp_add_inline_style('wp-admin', '
        .mas-diagnostics-container {
            max-width: 1200px;
            margin: 20px 0;
        }
        
        .mas-diagnostics-header {
            background: white;
            padding: 20px;
            border-left: 4px solid #4CAF50;
            margin-bottom: 20px;
        }
        
        .mas-diagnostics-header h1 {
            margin: 0 0 10px 0;
            font-size: 24px;
        }
        
        .mas-diagnostics-actions {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .mas-diagnostics-actions .button {
            margin-right: 10px;
        }
        
        .mas-diagnostics-info {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .mas-diagnostics-warning {
            background: #fff3e0;
            border-left: 4px solid #FF9800;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .mas-diagnostics-results {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .mas-status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 3px;
            font-weight: 600;
            font-size: 12px;
            margin-left: 10px;
        }
        
        .mas-status-badge.success {
            background: #c8e6c9;
            color: #2e7d32;
        }
        
        .mas-status-badge.warning {
            background: #ffe0b2;
            color: #e65100;
        }
        
        .mas-status-badge.error {
            background: #ffcdd2;
            color: #c62828;
        }
        
        .mas-conflict-item {
            background: #f9f9f9;
            border-left: 3px solid #F44336;
            padding: 15px;
            margin-bottom: 10px;
        }
        
        .mas-conflict-item.warning {
            border-left-color: #FF9800;
        }
        
        .mas-recommendation-item {
            background: #f1f8e9;
            border-left: 3px solid #4CAF50;
            padding: 15px;
            margin-bottom: 10px;
        }
    ');
}
add_action('admin_enqueue_scripts', 'mas_enqueue_diagnostics_scripts');

/**
 * Render diagnostics page
 */
function mas_render_diagnostics_page() {
    ?>
    <div class="wrap mas-diagnostics-container">
        <div class="mas-diagnostics-header">
            <h1>🔍 MAS Handler Diagnostics</h1>
            <p>Detect and analyze event handler conflicts in the Modern Admin Styler plugin</p>
        </div>
        
        <div class="mas-diagnostics-info">
            <strong>ℹ️ About this tool:</strong><br>
            This diagnostic tool scans the current page for event handlers and identifies conflicts, 
            duplicates, and jQuery vs Vanilla JS issues. Open your browser's console (F12) to see 
            detailed diagnostic output.
        </div>
        
        <div class="mas-diagnostics-actions">
            <h2>Actions</h2>
            <button type="button" class="button button-primary" onclick="runMasDiagnostics()">
                🔍 Run Diagnostics
            </button>
            <button type="button" class="button button-secondary" onclick="downloadMasReport()">
                📥 Download Report
            </button>
            <button type="button" class="button" onclick="clearMasResults()">
                🗑️ Clear Results
            </button>
        </div>
        
        <div id="mas-diagnostics-results" class="mas-diagnostics-results" style="display: none;">
            <h2>Diagnostic Results</h2>
            <div id="mas-results-content"></div>
        </div>
        
        <div class="mas-diagnostics-warning">
            <strong>⚠️ Note:</strong><br>
            For best results, run diagnostics on the actual MAS settings page where conflicts may occur.
            Navigate to the settings page and use the browser console to run:
            <code>new MASHandlerDiagnostics().runDiagnostics()</code>
        </div>
        
        <div class="mas-diagnostics-info">
            <h3>Current Page Information</h3>
            <ul>
                <li><strong>WordPress Version:</strong> <?php echo get_bloginfo('version'); ?></li>
                <li><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></li>
                <li><strong>jQuery Version:</strong> <span id="jquery-version">Detecting...</span></li>
                <li><strong>MAS Plugin Version:</strong> <?php echo defined('MAS_VERSION') ? MAS_VERSION : 'Unknown'; ?></li>
            </ul>
        </div>
    </div>
    
    <script>
        // Display jQuery version
        jQuery(document).ready(function($) {
            $('#jquery-version').text($.fn.jquery);
        });
        
        // Run diagnostics
        function runMasDiagnostics() {
            console.clear();
            console.log('%c=== MAS Handler Diagnostics ===', 'color: #4CAF50; font-size: 18px; font-weight: bold;');
            
            if (typeof MASHandlerDiagnostics === 'undefined') {
                alert('Error: HandlerDiagnostics class not loaded. Please check console for errors.');
                console.error('MASHandlerDiagnostics class not found');
                return;
            }
            
            const diagnostics = new MASHandlerDiagnostics();
            const report = diagnostics.runDiagnostics();
            
            // Store for download
            window.masLastReport = report;
            
            // Display results
            displayResults(report);
            
            console.log('%c=== Diagnostics Complete ===', 'color: #4CAF50; font-size: 16px;');
        }
        
        // Display results in UI
        function displayResults(report) {
            const resultsDiv = document.getElementById('mas-diagnostics-results');
            const contentDiv = document.getElementById('mas-results-content');
            
            let html = '<h3>Summary</h3>';
            html += '<ul>';
            html += `<li>Vanilla JS Handlers: <strong>${report.summary.totalVanillaHandlers}</strong></li>`;
            html += `<li>jQuery Handlers: <strong>${report.summary.totalJQueryHandlers}</strong></li>`;
            html += `<li>Total Conflicts: <strong>${report.summary.totalConflicts}</strong>`;
            
            if (report.summary.criticalConflicts > 0) {
                html += `<span class="mas-status-badge error">❌ ${report.summary.criticalConflicts} Critical</span>`;
            } else if (report.summary.totalConflicts > 0) {
                html += `<span class="mas-status-badge warning">⚠️ ${report.summary.totalConflicts} Issues</span>`;
            } else {
                html += `<span class="mas-status-badge success">✅ Clean</span>`;
            }
            
            html += '</li>';
            html += '</ul>';
            
            if (report.conflicts.length > 0) {
                html += '<h3>Conflicts Detected</h3>';
                report.conflicts.forEach((conflict, index) => {
                    const cssClass = conflict.severity === 'critical' ? 'mas-conflict-item' : 'mas-conflict-item warning';
                    html += `<div class="${cssClass}">`;
                    html += `<strong>${index + 1}. [${conflict.severity.toUpperCase()}] ${conflict.message}</strong><br>`;
                    html += `<small>Type: ${conflict.type}</small>`;
                    html += '</div>';
                });
            }
            
            if (report.recommendations.length > 0) {
                html += '<h3>Recommendations</h3>';
                report.recommendations.forEach((rec, index) => {
                    html += '<div class="mas-recommendation-item">';
                    html += `<strong>${index + 1}. [${rec.priority.toUpperCase()}] ${rec.message}</strong><br>`;
                    if (rec.action) {
                        html += `<small>→ Action: ${rec.action}</small>`;
                    }
                    html += '</div>';
                });
            }
            
            contentDiv.innerHTML = html;
            resultsDiv.style.display = 'block';
        }
        
        // Download report
        function downloadMasReport() {
            if (!window.masLastReport) {
                alert('Please run diagnostics first');
                return;
            }
            
            const report = JSON.stringify(window.masLastReport, null, 2);
            const blob = new Blob([report], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `mas-handler-diagnostics-${Date.now()}.json`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            
            console.log('%c📥 Report downloaded', 'color: #4CAF50; font-weight: bold;');
        }
        
        // Clear results
        function clearMasResults() {
            document.getElementById('mas-diagnostics-results').style.display = 'none';
            document.getElementById('mas-results-content').innerHTML = '';
            window.masLastReport = null;
            console.clear();
        }
    </script>
    <?php
}
