/**
 * Handler Diagnostics Utility
 * 
 * Detects and reports event handler conflicts, duplicate handlers,
 * and jQuery vs vanilla JS conflicts in the admin interface.
 * 
 * @class HandlerDiagnostics
 * @since 3.0.0
 */
class HandlerDiagnostics {
    constructor() {
        this.conflicts = [];
        this.handlers = new Map();
        this.jQueryHandlers = [];
        this.vanillaHandlers = [];
    }

    /**
     * Run complete diagnostic scan
     * 
     * @returns {Object} Diagnostic report
     */
    runDiagnostics() {
        console.log('%c[MAS Diagnostics] Starting handler conflict detection...', 'color: #4CAF50; font-weight: bold;');
        
        this.detectAllHandlers();
        this.detectDuplicateHandlers();
        this.detectJQueryVsVanillaConflicts();
        this.analyzeFormHandlers();
        
        return this.generateReport();
    }

    /**
     * Detect all event handlers in the document
     */
    detectAllHandlers() {
        console.log('%c[MAS Diagnostics] Detecting all event handlers...', 'color: #2196F3;');
        
        // Detect vanilla JS handlers
        this.detectVanillaHandlers();
        
        // Detect jQuery handlers
        this.detectJQueryHandlers();
        
        console.log(`Found ${this.vanillaHandlers.length} vanilla JS handlers`);
        console.log(`Found ${this.jQueryHandlers.length} jQuery handlers`);
    }

    /**
     * Detect vanilla JavaScript event handlers
     */
    detectVanillaHandlers() {
        const elements = document.querySelectorAll('*');
        
        elements.forEach(element => {
            // Get all event listeners (Chrome/Edge specific)
            if (typeof getEventListeners === 'function') {
                const listeners = getEventListeners(element);
                
                Object.keys(listeners).forEach(eventType => {
                    listeners[eventType].forEach(listener => {
                        this.vanillaHandlers.push({
                            element: element,
                            selector: this.getElementSelector(element),
                            eventType: eventType,
                            listener: listener.listener,
                            useCapture: listener.useCapture,
                            type: 'vanilla'
                        });
                    });
                });
            }
            
            // Check for inline event handlers
            const inlineEvents = ['onclick', 'onsubmit', 'onchange', 'oninput', 'onkeyup', 'onkeydown'];
            inlineEvents.forEach(eventAttr => {
                if (element[eventAttr]) {
                    this.vanillaHandlers.push({
                        element: element,
                        selector: this.getElementSelector(element),
                        eventType: eventAttr.substring(2),
                        listener: element[eventAttr],
                        inline: true,
                        type: 'vanilla'
                    });
                }
            });
        });
    }

    /**
     * Detect jQuery event handlers
     */
    detectJQueryHandlers() {
        if (typeof jQuery === 'undefined') {
            console.warn('[MAS Diagnostics] jQuery not found');
            return;
        }

        // Get all elements with jQuery data
        jQuery('*').each((index, element) => {
            const $element = jQuery(element);
            const events = jQuery._data(element, 'events');
            
            if (events) {
                Object.keys(events).forEach(eventType => {
                    events[eventType].forEach(handler => {
                        this.jQueryHandlers.push({
                            element: element,
                            selector: this.getElementSelector(element),
                            eventType: eventType,
                            handler: handler.handler,
                            namespace: handler.namespace,
                            delegateTarget: handler.selector,
                            type: 'jquery'
                        });
                    });
                });
            }
        });
    }

    /**
     * Detect duplicate handlers on same elements
     */
    detectDuplicateHandlers() {
        console.log('%c[MAS Diagnostics] Checking for duplicate handlers...', 'color: #FF9800;');
        
        const handlerMap = new Map();
        const allHandlers = [...this.vanillaHandlers, ...this.jQueryHandlers];
        
        allHandlers.forEach(handler => {
            const key = `${handler.selector}:${handler.eventType}`;
            
            if (!handlerMap.has(key)) {
                handlerMap.set(key, []);
            }
            
            handlerMap.get(key).push(handler);
        });
        
        // Find duplicates
        handlerMap.forEach((handlers, key) => {
            if (handlers.length > 1) {
                this.conflicts.push({
                    type: 'duplicate',
                    severity: 'high',
                    key: key,
                    count: handlers.length,
                    handlers: handlers,
                    message: `Found ${handlers.length} handlers for ${key}`
                });
                
                console.warn(`%c[CONFLICT] ${handlers.length} handlers on ${key}`, 'color: #F44336; font-weight: bold;');
                handlers.forEach((h, i) => {
                    console.log(`  ${i + 1}. ${h.type} handler:`, h);
                });
            }
        });
    }

    /**
     * Detect jQuery vs Vanilla JS conflicts
     */
    detectJQueryVsVanillaConflicts() {
        console.log('%c[MAS Diagnostics] Checking for jQuery vs Vanilla JS conflicts...', 'color: #9C27B0;');
        
        const vanillaMap = new Map();
        const jqueryMap = new Map();
        
        this.vanillaHandlers.forEach(h => {
            const key = `${h.selector}:${h.eventType}`;
            vanillaMap.set(key, h);
        });
        
        this.jQueryHandlers.forEach(h => {
            const key = `${h.selector}:${h.eventType}`;
            jqueryMap.set(key, h);
        });
        
        // Find conflicts
        vanillaMap.forEach((vanillaHandler, key) => {
            if (jqueryMap.has(key)) {
                const jqueryHandler = jqueryMap.get(key);
                
                this.conflicts.push({
                    type: 'jquery-vanilla-conflict',
                    severity: 'critical',
                    key: key,
                    vanillaHandler: vanillaHandler,
                    jqueryHandler: jqueryHandler,
                    message: `jQuery and Vanilla JS handlers both attached to ${key}`
                });
                
                console.error(`%c[CRITICAL CONFLICT] jQuery + Vanilla JS on ${key}`, 'color: #F44336; font-weight: bold; font-size: 14px;');
                console.log('  Vanilla handler:', vanillaHandler);
                console.log('  jQuery handler:', jqueryHandler);
            }
        });
    }

    /**
     * Analyze form-specific handlers
     */
    analyzeFormHandlers() {
        console.log('%c[MAS Diagnostics] Analyzing form handlers...', 'color: #00BCD4;');
        
        const forms = document.querySelectorAll('form');
        
        forms.forEach(form => {
            const formSelector = this.getElementSelector(form);
            const submitHandlers = [];
            
            // Find all submit handlers for this form
            [...this.vanillaHandlers, ...this.jQueryHandlers].forEach(handler => {
                if (handler.selector === formSelector && handler.eventType === 'submit') {
                    submitHandlers.push(handler);
                }
            });
            
            if (submitHandlers.length > 1) {
                this.conflicts.push({
                    type: 'form-submit-conflict',
                    severity: 'critical',
                    form: formSelector,
                    count: submitHandlers.length,
                    handlers: submitHandlers,
                    message: `Form ${formSelector} has ${submitHandlers.length} submit handlers`
                });
                
                console.error(`%c[FORM CONFLICT] ${submitHandlers.length} submit handlers on ${formSelector}`, 'color: #F44336; font-weight: bold;');
                submitHandlers.forEach((h, i) => {
                    console.log(`  ${i + 1}. ${h.type} handler:`, h);
                });
            }
            
            // Check for field change handlers
            const fields = form.querySelectorAll('input, select, textarea');
            fields.forEach(field => {
                const fieldSelector = this.getElementSelector(field);
                const changeHandlers = [];
                
                [...this.vanillaHandlers, ...this.jQueryHandlers].forEach(handler => {
                    if (handler.selector === fieldSelector && 
                        (handler.eventType === 'change' || handler.eventType === 'input')) {
                        changeHandlers.push(handler);
                    }
                });
                
                if (changeHandlers.length > 1) {
                    console.warn(`[Field Conflict] ${changeHandlers.length} change handlers on ${fieldSelector}`);
                }
            });
        });
    }

    /**
     * Get CSS selector for an element
     * 
     * @param {HTMLElement} element 
     * @returns {string}
     */
    getElementSelector(element) {
        if (element.id) {
            return `#${element.id}`;
        }
        
        if (element.className && typeof element.className === 'string') {
            const classes = element.className.trim().split(/\s+/).join('.');
            if (classes) {
                return `${element.tagName.toLowerCase()}.${classes}`;
            }
        }
        
        if (element.name) {
            return `${element.tagName.toLowerCase()}[name="${element.name}"]`;
        }
        
        return element.tagName.toLowerCase();
    }

    /**
     * Generate diagnostic report
     * 
     * @returns {Object}
     */
    generateReport() {
        const report = {
            timestamp: new Date().toISOString(),
            summary: {
                totalVanillaHandlers: this.vanillaHandlers.length,
                totalJQueryHandlers: this.jQueryHandlers.length,
                totalConflicts: this.conflicts.length,
                criticalConflicts: this.conflicts.filter(c => c.severity === 'critical').length,
                highConflicts: this.conflicts.filter(c => c.severity === 'high').length
            },
            conflicts: this.conflicts,
            vanillaHandlers: this.vanillaHandlers,
            jQueryHandlers: this.jQueryHandlers,
            recommendations: this.generateRecommendations()
        };
        
        this.printReport(report);
        
        return report;
    }

    /**
     * Generate recommendations based on findings
     * 
     * @returns {Array}
     */
    generateRecommendations() {
        const recommendations = [];
        
        if (this.conflicts.length === 0) {
            recommendations.push({
                priority: 'info',
                message: 'No handler conflicts detected. System is clean.'
            });
            return recommendations;
        }
        
        // Check for critical conflicts
        const criticalConflicts = this.conflicts.filter(c => c.severity === 'critical');
        if (criticalConflicts.length > 0) {
            recommendations.push({
                priority: 'critical',
                message: `Found ${criticalConflicts.length} critical conflicts that need immediate attention`,
                action: 'Remove duplicate handlers or consolidate into single handler'
            });
        }
        
        // Check for jQuery + Vanilla conflicts
        const jqVanillaConflicts = this.conflicts.filter(c => c.type === 'jquery-vanilla-conflict');
        if (jqVanillaConflicts.length > 0) {
            recommendations.push({
                priority: 'critical',
                message: 'jQuery and Vanilla JS handlers are conflicting',
                action: 'Choose one approach (preferably Vanilla JS) and remove the other'
            });
        }
        
        // Check for form conflicts
        const formConflicts = this.conflicts.filter(c => c.type === 'form-submit-conflict');
        if (formConflicts.length > 0) {
            recommendations.push({
                priority: 'critical',
                message: 'Multiple form submit handlers detected',
                action: 'Consolidate form handlers into single unified handler'
            });
        }
        
        // Check for duplicate handlers
        const duplicates = this.conflicts.filter(c => c.type === 'duplicate');
        if (duplicates.length > 0) {
            recommendations.push({
                priority: 'high',
                message: `Found ${duplicates.length} duplicate handler sets`,
                action: 'Remove duplicate event listener registrations'
            });
        }
        
        return recommendations;
    }

    /**
     * Print formatted report to console
     * 
     * @param {Object} report 
     */
    printReport(report) {
        console.log('\n');
        console.log('%c╔════════════════════════════════════════════════════════════╗', 'color: #4CAF50;');
        console.log('%c║         MAS HANDLER DIAGNOSTICS REPORT                    ║', 'color: #4CAF50; font-weight: bold;');
        console.log('%c╚════════════════════════════════════════════════════════════╝', 'color: #4CAF50;');
        console.log('\n');
        
        console.log('%c📊 SUMMARY', 'color: #2196F3; font-weight: bold; font-size: 14px;');
        console.log(`   Vanilla JS Handlers: ${report.summary.totalVanillaHandlers}`);
        console.log(`   jQuery Handlers: ${report.summary.totalJQueryHandlers}`);
        console.log(`   Total Conflicts: ${report.summary.totalConflicts}`);
        console.log(`   Critical Conflicts: ${report.summary.criticalConflicts}`);
        console.log(`   High Priority Conflicts: ${report.summary.highConflicts}`);
        console.log('\n');
        
        if (report.conflicts.length > 0) {
            console.log('%c⚠️  CONFLICTS DETECTED', 'color: #F44336; font-weight: bold; font-size: 14px;');
            report.conflicts.forEach((conflict, index) => {
                const severityColor = conflict.severity === 'critical' ? '#F44336' : '#FF9800';
                console.log(`%c   ${index + 1}. [${conflict.severity.toUpperCase()}] ${conflict.message}`, `color: ${severityColor}; font-weight: bold;`);
            });
            console.log('\n');
        }
        
        if (report.recommendations.length > 0) {
            console.log('%c💡 RECOMMENDATIONS', 'color: #4CAF50; font-weight: bold; font-size: 14px;');
            report.recommendations.forEach((rec, index) => {
                const priorityColor = rec.priority === 'critical' ? '#F44336' : rec.priority === 'high' ? '#FF9800' : '#2196F3';
                console.log(`%c   ${index + 1}. [${rec.priority.toUpperCase()}] ${rec.message}`, `color: ${priorityColor}; font-weight: bold;`);
                if (rec.action) {
                    console.log(`      → Action: ${rec.action}`);
                }
            });
            console.log('\n');
        }
        
        console.log('%c✅ Diagnostic scan complete. Check console for detailed handler information.', 'color: #4CAF50;');
        console.log('\n');
    }

    /**
     * Export report as JSON
     * 
     * @returns {string}
     */
    exportReport() {
        const report = this.generateReport();
        return JSON.stringify(report, null, 2);
    }

    /**
     * Download report as JSON file
     */
    downloadReport() {
        const report = this.exportReport();
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
}

// Make available globally for console access
if (typeof window !== 'undefined') {
    window.MASHandlerDiagnostics = HandlerDiagnostics;
    
    // Auto-run diagnostics if in debug mode
    if (window.masV2Data && window.masV2Data.debugMode) {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                console.log('%c[MAS] Running automatic handler diagnostics...', 'color: #4CAF50; font-weight: bold;');
                const diagnostics = new HandlerDiagnostics();
                diagnostics.runDiagnostics();
            }, 2000); // Wait 2 seconds for all handlers to be attached
        });
    }
}

export default HandlerDiagnostics;
