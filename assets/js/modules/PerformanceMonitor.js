/**
 * Performance Monitor Module
 * 
 * Displays real-time performance metrics including:
 * - Cache hit rates
 * - API response time percentiles
 * - System health status
 * 
 * @package ModernAdminStylerV2
 * @since 2.3.0
 */

class PerformanceMonitor {
    constructor() {
        this.restClient = window.MASRestClient || null;
        this.refreshInterval = 30000; // 30 seconds
        this.refreshTimer = null;
        this.charts = {};
        
        this.init();
    }
    
    /**
     * Initialize performance monitor
     */
    init() {
        if (!this.restClient) {
            console.error('MAS REST Client not available');
            return;
        }
        
        this.createDashboard();
        this.loadInitialData();
        this.startAutoRefresh();
    }
    
    /**
     * Create dashboard HTML
     */
    createDashboard() {
        const container = document.getElementById('mas-performance-dashboard');
        if (!container) {
            console.warn('Performance dashboard container not found');
            return;
        }
        
        container.innerHTML = `
            <div class="mas-performance-dashboard">
                <div class="dashboard-header">
                    <h2>Performance Monitoring Dashboard</h2>
                    <button id="mas-refresh-metrics" class="button">
                        <span class="dashicons dashicons-update"></span> Refresh
                    </button>
                </div>
                
                <div class="metrics-grid">
                    <!-- Cache Metrics -->
                    <div class="metric-card">
                        <div class="metric-header">
                            <h3>Cache Performance</h3>
                            <span class="metric-status" id="cache-status"></span>
                        </div>
                        <div class="metric-body">
                            <div class="metric-value">
                                <span class="value" id="cache-hit-rate">--</span>
                                <span class="unit">%</span>
                            </div>
                            <div class="metric-label">Hit Rate</div>
                            <div class="metric-details">
                                <div class="detail-row">
                                    <span>Hits:</span>
                                    <span id="cache-hits">--</span>
                                </div>
                                <div class="detail-row">
                                    <span>Misses:</span>
                                    <span id="cache-misses">--</span>
                                </div>
                                <div class="detail-row">
                                    <span>Target:</span>
                                    <span>80%</span>
                                </div>
                            </div>
                            <div class="metric-chart">
                                <canvas id="cache-chart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- API Performance -->
                    <div class="metric-card">
                        <div class="metric-header">
                            <h3>API Response Times</h3>
                            <span class="metric-status" id="api-status"></span>
                        </div>
                        <div class="metric-body">
                            <div class="metric-value">
                                <span class="value" id="api-p95">--</span>
                                <span class="unit">ms</span>
                            </div>
                            <div class="metric-label">P95 Response Time</div>
                            <div class="metric-details">
                                <div class="detail-row">
                                    <span>P50:</span>
                                    <span id="api-p50">--</span>
                                </div>
                                <div class="detail-row">
                                    <span>P75:</span>
                                    <span id="api-p75">--</span>
                                </div>
                                <div class="detail-row">
                                    <span>P99:</span>
                                    <span id="api-p99">--</span>
                                </div>
                            </div>
                            <div class="metric-chart">
                                <canvas id="response-time-chart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- System Health -->
                    <div class="metric-card">
                        <div class="metric-header">
                            <h3>System Health</h3>
                            <span class="metric-status" id="health-status"></span>
                        </div>
                        <div class="metric-body">
                            <div class="health-checks" id="health-checks">
                                <!-- Health checks will be populated here -->
                            </div>
                        </div>
                    </div>
                    
                    <!-- Error Rate -->
                    <div class="metric-card">
                        <div class="metric-header">
                            <h3>Error Rate</h3>
                            <span class="metric-status" id="error-status"></span>
                        </div>
                        <div class="metric-body">
                            <div class="metric-value">
                                <span class="value" id="error-rate">--</span>
                                <span class="unit">%</span>
                            </div>
                            <div class="metric-label">Error Rate (24h)</div>
                            <div class="metric-details">
                                <div class="detail-row">
                                    <span>Total Errors:</span>
                                    <span id="total-errors">--</span>
                                </div>
                                <div class="detail-row">
                                    <span>Total Requests:</span>
                                    <span id="total-requests">--</span>
                                </div>
                            </div>
                            <div class="metric-chart">
                                <canvas id="error-chart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recommendations -->
                <div class="recommendations-section" id="recommendations-section">
                    <h3>Optimization Recommendations</h3>
                    <div id="recommendations-list"></div>
                </div>
                
                <!-- Last Updated -->
                <div class="dashboard-footer">
                    <span>Last updated: <span id="last-updated">--</span></span>
                    <span>Auto-refresh: <span id="auto-refresh-status">Enabled</span></span>
                </div>
            </div>
        `;
        
        // Attach event listeners
        document.getElementById('mas-refresh-metrics')?.addEventListener('click', () => {
            this.loadInitialData();
        });
    }
    
    /**
     * Load initial data
     */
    async loadInitialData() {
        try {
            this.showLoading();
            
            // Load all metrics in parallel
            const [cacheData, performanceData, healthData, errorData] = await Promise.all([
                this.loadCacheMetrics(),
                this.loadPerformanceMetrics(),
                this.loadHealthMetrics(),
                this.loadErrorMetrics()
            ]);
            
            this.updateCacheDisplay(cacheData);
            this.updatePerformanceDisplay(performanceData);
            this.updateHealthDisplay(healthData);
            this.updateErrorDisplay(errorData);
            
            this.loadRecommendations();
            this.updateLastUpdated();
            
        } catch (error) {
            console.error('Error loading performance metrics:', error);
            this.showError('Failed to load performance metrics');
        }
    }
    
    /**
     * Load cache metrics
     */
    async loadCacheMetrics() {
        try {
            const response = await this.restClient.request('/system/cache', { method: 'GET' });
            return response.data;
        } catch (error) {
            console.error('Error loading cache metrics:', error);
            return null;
        }
    }
    
    /**
     * Load performance metrics
     */
    async loadPerformanceMetrics() {
        try {
            const response = await this.restClient.request('/analytics/performance', { method: 'GET' });
            return response.data;
        } catch (error) {
            console.error('Error loading performance metrics:', error);
            return null;
        }
    }
    
    /**
     * Load health metrics
     */
    async loadHealthMetrics() {
        try {
            const response = await this.restClient.request('/system/health', { method: 'GET' });
            return response.data;
        } catch (error) {
            console.error('Error loading health metrics:', error);
            return null;
        }
    }
    
    /**
     * Load error metrics
     */
    async loadErrorMetrics() {
        try {
            const response = await this.restClient.request('/analytics/errors', { method: 'GET' });
            return response.data;
        } catch (error) {
            console.error('Error loading error metrics:', error);
            return null;
        }
    }
    
    /**
     * Update cache display
     */
    updateCacheDisplay(data) {
        if (!data) return;
        
        const hitRate = data.hit_rate || 0;
        const meetsTarget = hitRate >= 80;
        
        document.getElementById('cache-hit-rate').textContent = hitRate.toFixed(2);
        document.getElementById('cache-hits').textContent = data.hits || 0;
        document.getElementById('cache-misses').textContent = data.misses || 0;
        
        const statusEl = document.getElementById('cache-status');
        statusEl.textContent = meetsTarget ? '✅ Good' : '⚠️ Below Target';
        statusEl.className = `metric-status ${meetsTarget ? 'status-good' : 'status-warning'}`;
        
        // Update chart
        this.updateCacheChart(data);
    }
    
    /**
     * Update performance display
     */
    updatePerformanceDisplay(data) {
        if (!data) return;
        
        const percentiles = data.percentiles || {};
        
        document.getElementById('api-p50').textContent = (percentiles.p50 || 0).toFixed(2) + 'ms';
        document.getElementById('api-p75').textContent = (percentiles.p75 || 0).toFixed(2) + 'ms';
        document.getElementById('api-p95').textContent = (percentiles.p95 || 0).toFixed(2) + 'ms';
        document.getElementById('api-p99').textContent = (percentiles.p99 || 0).toFixed(2) + 'ms';
        
        const p95 = percentiles.p95 || 0;
        const isGood = p95 < 500;
        
        const statusEl = document.getElementById('api-status');
        statusEl.textContent = isGood ? '✅ Good' : '⚠️ Slow';
        statusEl.className = `metric-status ${isGood ? 'status-good' : 'status-warning'}`;
        
        // Update chart
        this.updateResponseTimeChart(data);
    }
    
    /**
     * Update health display
     */
    updateHealthDisplay(data) {
        if (!data) return;
        
        const status = data.status || 'unknown';
        const checks = data.checks || {};
        
        const statusEl = document.getElementById('health-status');
        const statusMap = {
            'healthy': { text: '✅ Healthy', class: 'status-good' },
            'warning': { text: '⚠️ Warning', class: 'status-warning' },
            'critical': { text: '❌ Critical', class: 'status-error' }
        };
        
        const statusInfo = statusMap[status] || { text: '❓ Unknown', class: 'status-unknown' };
        statusEl.textContent = statusInfo.text;
        statusEl.className = `metric-status ${statusInfo.class}`;
        
        // Display health checks
        const checksContainer = document.getElementById('health-checks');
        checksContainer.innerHTML = '';
        
        Object.entries(checks).forEach(([name, check]) => {
            const checkEl = document.createElement('div');
            checkEl.className = 'health-check';
            
            const icon = check.status === 'pass' ? '✅' : '❌';
            checkEl.innerHTML = `
                <span class="check-icon">${icon}</span>
                <span class="check-name">${this.formatCheckName(name)}</span>
                <span class="check-message">${check.message}</span>
            `;
            
            checksContainer.appendChild(checkEl);
        });
    }
    
    /**
     * Update error display
     */
    updateErrorDisplay(data) {
        if (!data) return;
        
        const errorRate = data.error_rate || 0;
        const totalErrors = data.total_errors || 0;
        const totalRequests = data.total_requests || 0;
        
        document.getElementById('error-rate').textContent = errorRate.toFixed(2);
        document.getElementById('total-errors').textContent = totalErrors;
        document.getElementById('total-requests').textContent = totalRequests;
        
        const isGood = errorRate < 1;
        
        const statusEl = document.getElementById('error-status');
        statusEl.textContent = isGood ? '✅ Good' : '⚠️ High';
        statusEl.className = `metric-status ${isGood ? 'status-good' : 'status-warning'}`;
        
        // Update chart
        this.updateErrorChart(data);
    }
    
    /**
     * Load recommendations
     */
    async loadRecommendations() {
        try {
            const response = await this.restClient.request('/system/performance', { method: 'GET' });
            const recommendations = response.data?.recommendations || [];
            
            const container = document.getElementById('recommendations-list');
            container.innerHTML = '';
            
            if (recommendations.length === 0) {
                container.innerHTML = '<p class="no-recommendations">✅ No optimization recommendations - system is performing well!</p>';
                return;
            }
            
            recommendations.forEach(rec => {
                const recEl = document.createElement('div');
                recEl.className = `recommendation recommendation-${rec.severity}`;
                
                const icon = rec.severity === 'warning' ? '⚠️' : 'ℹ️';
                recEl.innerHTML = `
                    <div class="rec-header">
                        <span class="rec-icon">${icon}</span>
                        <span class="rec-title">${rec.type}</span>
                    </div>
                    <div class="rec-message">${rec.message}</div>
                    ${rec.action ? `<div class="rec-action">→ ${rec.action}</div>` : ''}
                `;
                
                container.appendChild(recEl);
            });
            
        } catch (error) {
            console.error('Error loading recommendations:', error);
        }
    }
    
    /**
     * Update cache chart
     */
    updateCacheChart(data) {
        // Placeholder for chart implementation
        // In production, use Chart.js or similar library
        console.log('Cache chart data:', data);
    }
    
    /**
     * Update response time chart
     */
    updateResponseTimeChart(data) {
        // Placeholder for chart implementation
        console.log('Response time chart data:', data);
    }
    
    /**
     * Update error chart
     */
    updateErrorChart(data) {
        // Placeholder for chart implementation
        console.log('Error chart data:', data);
    }
    
    /**
     * Format check name
     */
    formatCheckName(name) {
        return name.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    }
    
    /**
     * Update last updated timestamp
     */
    updateLastUpdated() {
        const now = new Date();
        document.getElementById('last-updated').textContent = now.toLocaleTimeString();
    }
    
    /**
     * Start auto-refresh
     */
    startAutoRefresh() {
        this.refreshTimer = setInterval(() => {
            this.loadInitialData();
        }, this.refreshInterval);
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
     * Show loading state
     */
    showLoading() {
        // Add loading indicators
        document.querySelectorAll('.metric-value .value').forEach(el => {
            el.textContent = '...';
        });
    }
    
    /**
     * Show error
     */
    showError(message) {
        const container = document.getElementById('mas-performance-dashboard');
        if (container) {
            const errorEl = document.createElement('div');
            errorEl.className = 'mas-error-notice';
            errorEl.textContent = message;
            container.prepend(errorEl);
            
            setTimeout(() => errorEl.remove(), 5000);
        }
    }
    
    /**
     * Destroy monitor
     */
    destroy() {
        this.stopAutoRefresh();
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.MASPerformanceMonitor = new PerformanceMonitor();
    });
} else {
    window.MASPerformanceMonitor = new PerformanceMonitor();
}
