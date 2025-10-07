/**
 * Analytics Manager Module
 * 
 * Handles analytics dashboard UI and data visualization.
 * 
 * @package ModernAdminStylerV2
 * @since 2.3.0
 */

(function(window) {
    'use strict';
    
    /**
     * Analytics Manager Class
     */
    class AnalyticsManager {
        /**
         * Constructor
         * 
         * @param {MASRestClient} restClient REST API client instance
         * @param {Object} options Configuration options
         */
        constructor(restClient, options = {}) {
            this.restClient = restClient;
            this.options = {
                containerSelector: options.containerSelector || '#mas-analytics-dashboard',
                refreshInterval: options.refreshInterval || 60000, // 1 minute
                dateRange: options.dateRange || 7, // Last 7 days
                ...options
            };
            
            this.container = null;
            this.refreshTimer = null;
            this.isLoading = false;
            
            this.init();
        }
        
        /**
         * Initialize analytics manager
         */
        init() {
            this.container = document.querySelector(this.options.containerSelector);
            
            if (!this.container) {
                console.warn('[Analytics Manager] Container not found:', this.options.containerSelector);
                return;
            }
            
            this.render();
            this.loadData();
            
            // Set up auto-refresh
            if (this.options.refreshInterval > 0) {
                this.startAutoRefresh();
            }
            
            // Set up event listeners
            this.setupEventListeners();
        }
        
        /**
         * Render dashboard structure
         */
        render() {
            this.container.innerHTML = `
                <div class="mas-analytics-dashboard">
                    <div class="mas-analytics-header">
                        <h2>API Analytics</h2>
                        <div class="mas-analytics-controls">
                            <select id="mas-analytics-date-range" class="mas-select">
                                <option value="1">Last 24 Hours</option>
                                <option value="7" selected>Last 7 Days</option>
                                <option value="30">Last 30 Days</option>
                            </select>
                            <button id="mas-analytics-refresh" class="mas-button">
                                <span class="dashicons dashicons-update"></span> Refresh
                            </button>
                            <button id="mas-analytics-export" class="mas-button">
                                <span class="dashicons dashicons-download"></span> Export CSV
                            </button>
                        </div>
                    </div>
                    
                    <div class="mas-analytics-summary">
                        <div class="mas-analytics-card">
                            <h3>Total Requests</h3>
                            <div class="mas-analytics-value" id="mas-total-requests">-</div>
                        </div>
                        <div class="mas-analytics-card">
                            <h3>Avg Response Time</h3>
                            <div class="mas-analytics-value" id="mas-avg-response">-</div>
                        </div>
                        <div class="mas-analytics-card">
                            <h3>Error Rate</h3>
                            <div class="mas-analytics-value" id="mas-error-rate">-</div>
                        </div>
                        <div class="mas-analytics-card">
                            <h3>P95 Response Time</h3>
                            <div class="mas-analytics-value" id="mas-p95-response">-</div>
                        </div>
                    </div>
                    
                    <div class="mas-analytics-charts">
                        <div class="mas-analytics-chart-container">
                            <h3>Top Endpoints</h3>
                            <div id="mas-top-endpoints" class="mas-analytics-list"></div>
                        </div>
                        <div class="mas-analytics-chart-container">
                            <h3>Requests by Method</h3>
                            <div id="mas-requests-by-method" class="mas-analytics-list"></div>
                        </div>
                        <div class="mas-analytics-chart-container">
                            <h3>Error Distribution</h3>
                            <div id="mas-error-distribution" class="mas-analytics-list"></div>
                        </div>
                    </div>
                    
                    <div class="mas-analytics-loading" id="mas-analytics-loading" style="display: none;">
                        <span class="spinner is-active"></span>
                        <p>Loading analytics data...</p>
                    </div>
                </div>
            `;
        }
        
        /**
         * Set up event listeners
         */
        setupEventListeners() {
            // Date range selector
            const dateRangeSelect = document.getElementById('mas-analytics-date-range');
            if (dateRangeSelect) {
                dateRangeSelect.addEventListener('change', (e) => {
                    this.options.dateRange = parseInt(e.target.value);
                    this.loadData();
                });
            }
            
            // Refresh button
            const refreshButton = document.getElementById('mas-analytics-refresh');
            if (refreshButton) {
                refreshButton.addEventListener('click', () => {
                    this.loadData();
                });
            }
            
            // Export button
            const exportButton = document.getElementById('mas-analytics-export');
            if (exportButton) {
                exportButton.addEventListener('click', () => {
                    this.exportData();
                });
            }
        }
        
        /**
         * Load analytics data
         */
        async loadData() {
            if (this.isLoading) {
                return;
            }
            
            this.isLoading = true;
            this.showLoading(true);
            
            try {
                // Calculate date range
                const endDate = new Date();
                const startDate = new Date();
                startDate.setDate(startDate.getDate() - this.options.dateRange);
                
                const params = {
                    start_date: this.formatDate(startDate),
                    end_date: this.formatDate(endDate)
                };
                
                // Load all analytics data in parallel
                const [usageStats, performanceMetrics, errorStats] = await Promise.all([
                    this.restClient.getUsageStats(params),
                    this.restClient.getPerformanceMetrics(params),
                    this.restClient.getErrorStats(params)
                ]);
                
                // Update UI with data
                this.updateSummary(usageStats, performanceMetrics, errorStats);
                this.updateCharts(usageStats, errorStats);
                
            } catch (error) {
                console.error('[Analytics Manager] Failed to load data:', error);
                this.showError('Failed to load analytics data. Please try again.');
            } finally {
                this.isLoading = false;
                this.showLoading(false);
            }
        }
        
        /**
         * Update summary cards
         */
        updateSummary(usageStats, performanceMetrics, errorStats) {
            // Total requests
            const totalRequests = document.getElementById('mas-total-requests');
            if (totalRequests) {
                totalRequests.textContent = this.formatNumber(usageStats.total_requests);
            }
            
            // Average response time
            const avgResponse = document.getElementById('mas-avg-response');
            if (avgResponse) {
                avgResponse.textContent = `${performanceMetrics.avg.toFixed(2)}ms`;
                avgResponse.className = 'mas-analytics-value ' + this.getPerformanceClass(performanceMetrics.avg);
            }
            
            // Error rate
            const errorRate = document.getElementById('mas-error-rate');
            if (errorRate) {
                errorRate.textContent = `${errorStats.error_rate.toFixed(2)}%`;
                errorRate.className = 'mas-analytics-value ' + this.getErrorRateClass(errorStats.error_rate);
            }
            
            // P95 response time
            const p95Response = document.getElementById('mas-p95-response');
            if (p95Response) {
                p95Response.textContent = `${performanceMetrics.p95.toFixed(2)}ms`;
                p95Response.className = 'mas-analytics-value ' + this.getPerformanceClass(performanceMetrics.p95);
            }
        }
        
        /**
         * Update charts
         */
        updateCharts(usageStats, errorStats) {
            // Top endpoints
            this.renderList('mas-top-endpoints', usageStats.by_endpoint, (item) => ({
                label: item.endpoint,
                value: this.formatNumber(item.count)
            }));
            
            // Requests by method
            this.renderList('mas-requests-by-method', usageStats.by_method, (item) => ({
                label: item.method,
                value: this.formatNumber(item.count)
            }));
            
            // Error distribution
            this.renderList('mas-error-distribution', errorStats.by_endpoint, (item) => ({
                label: `${item.endpoint} (${item.status_code})`,
                value: this.formatNumber(item.count)
            }));
        }
        
        /**
         * Render a list
         */
        renderList(containerId, data, formatter) {
            const container = document.getElementById(containerId);
            if (!container) return;
            
            if (!data || data.length === 0) {
                container.innerHTML = '<p class="mas-analytics-empty">No data available</p>';
                return;
            }
            
            const html = data.map(item => {
                const formatted = formatter(item);
                return `
                    <div class="mas-analytics-list-item">
                        <span class="mas-analytics-list-label">${formatted.label}</span>
                        <span class="mas-analytics-list-value">${formatted.value}</span>
                    </div>
                `;
            }).join('');
            
            container.innerHTML = html;
        }
        
        /**
         * Export analytics data
         */
        async exportData() {
            try {
                const endDate = new Date();
                const startDate = new Date();
                startDate.setDate(startDate.getDate() - this.options.dateRange);
                
                const params = {
                    start_date: this.formatDate(startDate),
                    end_date: this.formatDate(endDate)
                };
                
                await this.restClient.exportAnalytics(params);
                
                this.showSuccess('Analytics data exported successfully');
            } catch (error) {
                console.error('[Analytics Manager] Failed to export data:', error);
                this.showError('Failed to export analytics data. Please try again.');
            }
        }
        
        /**
         * Start auto-refresh
         */
        startAutoRefresh() {
            this.stopAutoRefresh();
            
            this.refreshTimer = setInterval(() => {
                this.loadData();
            }, this.options.refreshInterval);
        }
        
        /**
         * Stop auto-refresh
         */
        stopAutoRefresh() {
            if (this.refreshTimer) {
                clearInterval(this.refreshTimer);
                this.refreshTimer = null;
            }
        }
        
        /**
         * Show/hide loading indicator
         */
        showLoading(show) {
            const loading = document.getElementById('mas-analytics-loading');
            if (loading) {
                loading.style.display = show ? 'flex' : 'none';
            }
        }
        
        /**
         * Show error message
         */
        showError(message) {
            // Use WordPress admin notices if available
            if (window.wp && window.wp.data) {
                window.wp.data.dispatch('core/notices').createErrorNotice(message);
            } else {
                console.error(message);
            }
        }
        
        /**
         * Show success message
         */
        showSuccess(message) {
            // Use WordPress admin notices if available
            if (window.wp && window.wp.data) {
                window.wp.data.dispatch('core/notices').createSuccessNotice(message);
            } else {
                console.log(message);
            }
        }
        
        /**
         * Format date for API
         */
        formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            const seconds = String(date.getSeconds()).padStart(2, '0');
            
            return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
        }
        
        /**
         * Format number with commas
         */
        formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }
        
        /**
         * Get performance class based on response time
         */
        getPerformanceClass(responseTime) {
            if (responseTime < 200) return 'mas-analytics-good';
            if (responseTime < 500) return 'mas-analytics-warning';
            return 'mas-analytics-critical';
        }
        
        /**
         * Get error rate class
         */
        getErrorRateClass(errorRate) {
            if (errorRate < 1) return 'mas-analytics-good';
            if (errorRate < 5) return 'mas-analytics-warning';
            return 'mas-analytics-critical';
        }
        
        /**
         * Destroy analytics manager
         */
        destroy() {
            this.stopAutoRefresh();
            
            if (this.container) {
                this.container.innerHTML = '';
            }
        }
    }
    
    // Export to global scope
    window.AnalyticsManager = AnalyticsManager;
    
})(window);
