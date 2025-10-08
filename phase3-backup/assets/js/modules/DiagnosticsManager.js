/**
 * Diagnostics Manager Module
 * 
 * Manages system diagnostics dashboard UI including health status,
 * performance metrics, conflict detection, and cache management.
 * 
 * @package ModernAdminStylerV2
 * @since 2.3.0
 */

(function(window) {
    'use strict';
    
    /**
     * Diagnostics Manager Class
     * 
     * @class
     */
    class DiagnosticsManager {
        /**
         * Constructor
         * 
         * @param {MASRestClient} restClient REST API client instance
         * @param {Object} options Configuration options
         */
        constructor(restClient, options = {}) {
            this.restClient = restClient;
            this.options = {
                autoRefresh: options.autoRefresh || false,
                refreshInterval: options.refreshInterval || 60000, // 1 minute
                containerId: options.containerId || 'mas-diagnostics-dashboard',
                ...options
            };
            
            this.refreshTimer = null;
            this.isLoading = false;
            
            // Bind methods
            this.init = this.init.bind(this);
            this.loadHealthStatus = this.loadHealthStatus.bind(this);
            this.loadSystemInfo = this.loadSystemInfo.bind(this);
            this.loadPerformanceMetrics = this.loadPerformanceMetrics.bind(this);
            this.loadConflicts = this.loadConflicts.bind(this);
            this.loadCacheStatus = this.loadCacheStatus.bind(this);
            this.clearCache = this.clearCache.bind(this);
            this.refresh = this.refresh.bind(this);
        }
        
        /**
         * Initialize diagnostics manager
         */
        init() {
            console.log('[Diagnostics Manager] Initializing...');
            
            // Load initial data
            this.loadHealthStatus();
            
            // Setup auto-refresh if enabled
            if (this.options.autoRefresh) {
                this.startAutoRefresh();
            }
            
            // Setup event listeners
            this.setupEventListeners();
        }
        
        /**
         * Setup event listeners
         */
        setupEventListeners() {
            // Refresh button
            const refreshBtn = document.getElementById('mas-diagnostics-refresh');
            if (refreshBtn) {
                refreshBtn.addEventListener('click', this.refresh);
            }
            
            // Clear cache button
            const clearCacheBtn = document.getElementById('mas-diagnostics-clear-cache');
            if (clearCacheBtn) {
                clearCacheBtn.addEventListener('click', this.clearCache);
            }
            
            // Tab navigation
            const tabs = document.querySelectorAll('.mas-diagnostics-tab');
            tabs.forEach(tab => {
                tab.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.switchTab(tab.dataset.tab);
                });
            });
        }
        
        /**
         * Load health status
         */
        async loadHealthStatus() {
            if (this.isLoading) return;
            
            this.isLoading = true;
            this.showLoading('health');
            
            try {
                const health = await this.restClient.getSystemHealth();
                this.renderHealthStatus(health);
            } catch (error) {
                this.showError('health', error.message);
            } finally {
                this.isLoading = false;
                this.hideLoading('health');
            }
        }
        
        /**
         * Load system information
         */
        async loadSystemInfo() {
            if (this.isLoading) return;
            
            this.isLoading = true;
            this.showLoading('info');
            
            try {
                const info = await this.restClient.getSystemInfo();
                this.renderSystemInfo(info);
            } catch (error) {
                this.showError('info', error.message);
            } finally {
                this.isLoading = false;
                this.hideLoading('info');
            }
        }
        
        /**
         * Load performance metrics
         */
        async loadPerformanceMetrics() {
            if (this.isLoading) return;
            
            this.isLoading = true;
            this.showLoading('performance');
            
            try {
                const metrics = await this.restClient.getPerformanceMetrics();
                this.renderPerformanceMetrics(metrics);
            } catch (error) {
                this.showError('performance', error.message);
            } finally {
                this.isLoading = false;
                this.hideLoading('performance');
            }
        }
        
        /**
         * Load conflicts
         */
        async loadConflicts() {
            if (this.isLoading) return;
            
            this.isLoading = true;
            this.showLoading('conflicts');
            
            try {
                const conflicts = await this.restClient.getConflicts();
                this.renderConflicts(conflicts);
            } catch (error) {
                this.showError('conflicts', error.message);
            } finally {
                this.isLoading = false;
                this.hideLoading('conflicts');
            }
        }
        
        /**
         * Load cache status
         */
        async loadCacheStatus() {
            if (this.isLoading) return;
            
            this.isLoading = true;
            this.showLoading('cache');
            
            try {
                const cache = await this.restClient.getCacheStatus();
                this.renderCacheStatus(cache);
            } catch (error) {
                this.showError('cache', error.message);
            } finally {
                this.isLoading = false;
                this.hideLoading('cache');
            }
        }
        
        /**
         * Clear cache
         */
        async clearCache() {
            if (!confirm('Are you sure you want to clear all caches? This may temporarily slow down the site.')) {
                return;
            }
            
            this.showLoading('cache');
            
            try {
                const result = await this.restClient.clearCache();
                this.showNotice('Cache cleared successfully', 'success');
                
                // Reload cache status
                await this.loadCacheStatus();
            } catch (error) {
                this.showNotice('Failed to clear cache: ' + error.message, 'error');
            } finally {
                this.hideLoading('cache');
            }
        }
        
        /**
         * Render health status
         * 
         * @param {Object} health Health status data
         */
        renderHealthStatus(health) {
            const container = document.getElementById('mas-health-status');
            if (!container) return;
            
            const statusClass = health.status === 'healthy' ? 'success' : 
                               health.status === 'warning' ? 'warning' : 'error';
            
            let html = `
                <div class="mas-health-overview">
                    <div class="mas-health-badge mas-health-${statusClass}">
                        <span class="mas-health-icon">${this.getStatusIcon(health.status)}</span>
                        <span class="mas-health-text">${health.status.toUpperCase()}</span>
                    </div>
                    <div class="mas-health-summary">
                        <p>${health.summary.healthy} of ${health.summary.total_checks} checks passed</p>
                        <p class="mas-health-percentage">${health.summary.health_percentage}% healthy</p>
                    </div>
                </div>
            `;
            
            // Render recommendations
            if (health.recommendations && health.recommendations.length > 0) {
                html += '<div class="mas-recommendations">';
                html += '<h3>Recommendations</h3>';
                html += '<ul class="mas-recommendations-list">';
                
                health.recommendations.forEach(rec => {
                    html += `
                        <li class="mas-recommendation mas-recommendation-${rec.severity}">
                            <strong>${rec.title}</strong>
                            <p>${rec.description}</p>
                            <p class="mas-recommendation-action"><em>Action: ${rec.action}</em></p>
                        </li>
                    `;
                });
                
                html += '</ul></div>';
            }
            
            container.innerHTML = html;
        }
        
        /**
         * Render system information
         * 
         * @param {Object} info System information data
         */
        renderSystemInfo(info) {
            const container = document.getElementById('mas-system-info');
            if (!container) return;
            
            let html = '<div class="mas-info-sections">';
            
            // PHP Info
            html += '<div class="mas-info-section">';
            html += '<h3>PHP Information</h3>';
            html += '<table class="mas-info-table">';
            html += `<tr><td>Version</td><td>${info.php.version}</td></tr>`;
            html += `<tr><td>Memory Limit</td><td>${info.php.memory_limit}</td></tr>`;
            html += `<tr><td>Max Execution Time</td><td>${info.php.max_execution_time}s</td></tr>`;
            html += '</table></div>';
            
            // WordPress Info
            html += '<div class="mas-info-section">';
            html += '<h3>WordPress Information</h3>';
            html += '<table class="mas-info-table">';
            html += `<tr><td>Version</td><td>${info.wordpress.version}</td></tr>`;
            html += `<tr><td>Memory Limit</td><td>${info.wordpress.memory_limit}</td></tr>`;
            html += `<tr><td>Multisite</td><td>${info.wordpress.multisite ? 'Yes' : 'No'}</td></tr>`;
            html += '</table></div>';
            
            // Plugin Info
            html += '<div class="mas-info-section">';
            html += '<h3>Plugin Information</h3>';
            html += '<table class="mas-info-table">';
            html += `<tr><td>Version</td><td>${info.plugin.version}</td></tr>`;
            html += `<tr><td>Active</td><td>${info.plugin.active ? 'Yes' : 'No'}</td></tr>`;
            html += '</table></div>';
            
            html += '</div>';
            
            container.innerHTML = html;
        }
        
        /**
         * Render performance metrics
         * 
         * @param {Object} metrics Performance metrics data
         */
        renderPerformanceMetrics(metrics) {
            const container = document.getElementById('mas-performance-metrics');
            if (!container) return;
            
            let html = '<div class="mas-metrics-grid">';
            
            // Memory metrics
            html += '<div class="mas-metric-card">';
            html += '<h3>Memory Usage</h3>';
            html += `<p class="mas-metric-value">${metrics.memory.current}</p>`;
            html += `<p class="mas-metric-detail">Peak: ${metrics.memory.peak}</p>`;
            html += `<p class="mas-metric-detail">Limit: ${metrics.memory.limit}</p>`;
            html += '</div>';
            
            // Cache metrics
            if (metrics.cache) {
                html += '<div class="mas-metric-card">';
                html += '<h3>Cache</h3>';
                html += `<p class="mas-metric-value">${metrics.cache.object_cache_enabled ? 'Enabled' : 'Disabled'}</p>`;
                html += `<p class="mas-metric-detail">Transients: ${metrics.cache.transients_count}</p>`;
                if (metrics.cache.hit_rate !== undefined) {
                    html += `<p class="mas-metric-detail">Hit Rate: ${metrics.cache.hit_rate}%</p>`;
                }
                html += '</div>';
            }
            
            // Database metrics
            if (metrics.database) {
                html += '<div class="mas-metric-card">';
                html += '<h3>Database</h3>';
                html += `<p class="mas-metric-value">${metrics.database.queries} queries</p>`;
                html += `<p class="mas-metric-detail">Time: ${metrics.database.query_time}</p>`;
                html += '</div>';
            }
            
            html += '</div>';
            
            container.innerHTML = html;
        }
        
        /**
         * Render conflicts
         * 
         * @param {Object} conflicts Conflicts data
         */
        renderConflicts(conflicts) {
            const container = document.getElementById('mas-conflicts');
            if (!container) return;
            
            if (conflicts.total_conflicts === 0) {
                container.innerHTML = '<p class="mas-no-conflicts">No conflicts detected.</p>';
                return;
            }
            
            let html = '<div class="mas-conflicts-list">';
            
            // Plugin conflicts
            if (conflicts.plugin_conflicts && conflicts.plugin_conflicts.length > 0) {
                html += '<div class="mas-conflict-section">';
                html += '<h3>Plugin Conflicts</h3>';
                html += '<ul>';
                
                conflicts.plugin_conflicts.forEach(conflict => {
                    html += `
                        <li class="mas-conflict-item mas-conflict-${conflict.severity}">
                            <strong>${conflict.name}</strong>
                            <p>${conflict.reason}</p>
                        </li>
                    `;
                });
                
                html += '</ul></div>';
            }
            
            // Theme conflicts
            if (conflicts.theme_conflicts && conflicts.theme_conflicts.length > 0) {
                html += '<div class="mas-conflict-section">';
                html += '<h3>Theme Conflicts</h3>';
                html += '<ul>';
                
                conflicts.theme_conflicts.forEach(conflict => {
                    html += `
                        <li class="mas-conflict-item mas-conflict-${conflict.severity}">
                            <strong>${conflict.theme}</strong>
                            <p>${conflict.reason}</p>
                        </li>
                    `;
                });
                
                html += '</ul></div>';
            }
            
            html += '</div>';
            
            container.innerHTML = html;
        }
        
        /**
         * Render cache status
         * 
         * @param {Object} cache Cache status data
         */
        renderCacheStatus(cache) {
            const container = document.getElementById('mas-cache-status');
            if (!container) return;
            
            let html = '<div class="mas-cache-info">';
            
            html += '<div class="mas-cache-overview">';
            html += `<p><strong>Cache Type:</strong> ${cache.cache_type}</p>`;
            html += `<p><strong>Object Cache:</strong> ${cache.object_cache_enabled ? 'Enabled' : 'Disabled'}</p>`;
            html += `<p><strong>Transients:</strong> ${cache.transients_count}</p>`;
            html += '</div>';
            
            if (cache.service_stats) {
                html += '<div class="mas-cache-stats">';
                html += '<h3>Cache Statistics</h3>';
                html += `<p><strong>Hit Rate:</strong> ${cache.service_stats.hit_rate}%</p>`;
                html += `<p><strong>Hits:</strong> ${cache.service_stats.hits}</p>`;
                html += `<p><strong>Misses:</strong> ${cache.service_stats.misses}</p>`;
                html += '</div>';
            }
            
            html += '</div>';
            
            container.innerHTML = html;
        }
        
        /**
         * Get status icon
         * 
         * @param {string} status Status string
         * @returns {string} Icon HTML
         */
        getStatusIcon(status) {
            const icons = {
                'healthy': '✓',
                'warning': '⚠',
                'critical': '✗'
            };
            
            return icons[status] || '?';
        }
        
        /**
         * Switch tab
         * 
         * @param {string} tabName Tab name
         */
        switchTab(tabName) {
            // Update active tab
            document.querySelectorAll('.mas-diagnostics-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            const activeTab = document.querySelector(`[data-tab="${tabName}"]`);
            if (activeTab) {
                activeTab.classList.add('active');
            }
            
            // Show corresponding content
            document.querySelectorAll('.mas-diagnostics-content').forEach(content => {
                content.style.display = 'none';
            });
            
            const activeContent = document.getElementById(`mas-${tabName}`);
            if (activeContent) {
                activeContent.style.display = 'block';
            }
            
            // Load data for the tab if not already loaded
            switch (tabName) {
                case 'health-status':
                    this.loadHealthStatus();
                    break;
                case 'system-info':
                    this.loadSystemInfo();
                    break;
                case 'performance-metrics':
                    this.loadPerformanceMetrics();
                    break;
                case 'conflicts':
                    this.loadConflicts();
                    break;
                case 'cache-status':
                    this.loadCacheStatus();
                    break;
            }
        }
        
        /**
         * Refresh all data
         */
        async refresh() {
            console.log('[Diagnostics Manager] Refreshing data...');
            
            await this.loadHealthStatus();
            
            this.showNotice('Diagnostics refreshed', 'success');
        }
        
        /**
         * Start auto-refresh
         */
        startAutoRefresh() {
            if (this.refreshTimer) {
                clearInterval(this.refreshTimer);
            }
            
            this.refreshTimer = setInterval(() => {
                this.refresh();
            }, this.options.refreshInterval);
            
            console.log('[Diagnostics Manager] Auto-refresh started');
        }
        
        /**
         * Stop auto-refresh
         */
        stopAutoRefresh() {
            if (this.refreshTimer) {
                clearInterval(this.refreshTimer);
                this.refreshTimer = null;
            }
            
            console.log('[Diagnostics Manager] Auto-refresh stopped');
        }
        
        /**
         * Show loading indicator
         * 
         * @param {string} section Section name
         */
        showLoading(section) {
            const container = document.getElementById(`mas-${section}`);
            if (container) {
                container.classList.add('mas-loading');
            }
        }
        
        /**
         * Hide loading indicator
         * 
         * @param {string} section Section name
         */
        hideLoading(section) {
            const container = document.getElementById(`mas-${section}`);
            if (container) {
                container.classList.remove('mas-loading');
            }
        }
        
        /**
         * Show error message
         * 
         * @param {string} section Section name
         * @param {string} message Error message
         */
        showError(section, message) {
            const container = document.getElementById(`mas-${section}`);
            if (container) {
                container.innerHTML = `<div class="mas-error">Error: ${message}</div>`;
            }
        }
        
        /**
         * Show notice
         * 
         * @param {string} message Notice message
         * @param {string} type Notice type (success, error, warning, info)
         */
        showNotice(message, type = 'info') {
            // Create notice element
            const notice = document.createElement('div');
            notice.className = `mas-notice mas-notice-${type}`;
            notice.textContent = message;
            
            // Add to page
            const container = document.getElementById(this.options.containerId);
            if (container) {
                container.insertBefore(notice, container.firstChild);
                
                // Auto-remove after 3 seconds
                setTimeout(() => {
                    notice.remove();
                }, 3000);
            }
        }
        
        /**
         * Destroy diagnostics manager
         */
        destroy() {
            this.stopAutoRefresh();
            console.log('[Diagnostics Manager] Destroyed');
        }
    }
    
    // Export to window
    window.DiagnosticsManager = DiagnosticsManager;
    
})(window);
