/**
 * CSS Diagnostics Utility
 * 
 * Analyzes CSS application issues including injection points,
 * specificity conflicts, CSS variable updates, inline style overrides,
 * and cascade/inheritance problems.
 * 
 * @class CSSDiagnostics
 * @since 3.0.0
 */
class CSSDiagnostics {
    constructor() {
        this.injectionPoints = [];
        this.cssVariables = new Map();
        this.inlineStyles = [];
        this.specificityConflicts = [];
        this.issues = [];
    }

    /**
     * Run complete CSS diagnostic scan
     * 
     * @returns {Object} Diagnostic report
     */
    runDiagnostics() {
        console.log('%c[MAS CSS Diagnostics] Starting CSS analysis...', 'color: #9C27B0; font-weight: bold;');
        
        this.auditCSSInjectionPoints();
        this.analyzeCSSVariables();
        this.detectInlineStyleOverrides();
        this.checkSpecificityConflicts();
        this.analyzeCascadeAndInheritance();
        
        return this.generateReport();
    }

    /**
     * Audit all CSS injection points
     */
    auditCSSInjectionPoints() {
        console.log('%c[CSS Diagnostics] Auditing CSS injection points...', 'color: #673AB7;');
        
        // Find all style elements
        const styleElements = document.querySelectorAll('style');
        styleElements.forEach((style, index) => {
            const info = {
                index: index,
                id: style.id || 'unnamed',
                type: 'style-element',
                location: this.getElementLocation(style),
                content: style.textContent,
                length: style.textContent.length,
                hasMASSyntax: this.containsMASStyles(style.textContent)
            };
            
            this.injectionPoints.push(info);
            
            if (info.hasMASSyntax) {
                console.log(`%c  ✓ Found MAS style element: ${info.id}`, 'color: #4CAF50;');
            }
        });
        
        // Find all link elements
        const linkElements = document.querySelectorAll('link[rel="stylesheet"]');
        linkElements.forEach((link, index) => {
            const info = {
                index: index,
                id: link.id || 'unnamed',
                type: 'link-element',
                href: link.href,
                location: this.getElementLocation(link)
            };
            
            this.injectionPoints.push(info);
        });
        
        console.log(`Found ${this.injectionPoints.length} CSS injection points`);
    }

    /**
     * Analyze CSS variables
     */
    analyzeCSSVariables() {
        console.log('%c[CSS Diagnostics] Analyzing CSS variables...', 'color: #673AB7;');
        
        // Get computed styles from root
        const rootStyles = getComputedStyle(document.documentElement);
        
        // Common MAS CSS variables
        const masVariables = [
            '--mas-menu-background',
            '--mas-menu-text-color',
            '--mas-menu-hover-background',
            '--mas-menu-hover-text-color',
            '--mas-menu-active-background',
            '--mas-menu-active-text-color',
            '--mas-admin-bar-background',
            '--mas-admin-bar-text-color',
            '--mas-primary-color',
            '--mas-secondary-color',
            '--mas-accent-color'
        ];
        
        masVariables.forEach(varName => {
            const value = rootStyles.getPropertyValue(varName);
            
            this.cssVariables.set(varName, {
                name: varName,
                value: value.trim(),
                defined: value.trim() !== '',
                computedValue: value.trim()
            });
            
            if (value.trim()) {
                console.log(`  ${varName}: ${value.trim()}`);
            } else {
                console.warn(`  ${varName}: NOT DEFINED`);
                this.issues.push({
                    type: 'missing-css-variable',
                    severity: 'high',
                    variable: varName,
                    message: `CSS variable ${varName} is not defined`
                });
            }
        });
        
        // Check for CSS variable usage in stylesheets
        this.checkCSSVariableUsage();
    }

    /**
     * Check CSS variable usage in stylesheets
     */
    checkCSSVariableUsage() {
        const styleElements = document.querySelectorAll('style');
        
        styleElements.forEach(style => {
            const content = style.textContent;
            const varPattern = /var\((--[a-zA-Z0-9-]+)\)/g;
            let match;
            
            while ((match = varPattern.exec(content)) !== null) {
                const varName = match[1];
                
                if (!this.cssVariables.has(varName)) {
                    this.issues.push({
                        type: 'undefined-css-variable-usage',
                        severity: 'medium',
                        variable: varName,
                        styleElement: style.id || 'unnamed',
                        message: `CSS variable ${varName} is used but not defined`
                    });
                }
            }
        });
    }

    /**
     * Detect inline style overrides
     */
    detectInlineStyleOverrides() {
        console.log('%c[CSS Diagnostics] Detecting inline style overrides...', 'color: #673AB7;');
        
        // Check menu elements
        const menuElements = document.querySelectorAll('#adminmenu, #adminmenu li, #adminmenu a');
        menuElements.forEach(element => {
            if (element.style.length > 0) {
                const inlineStyles = {};
                for (let i = 0; i < element.style.length; i++) {
                    const prop = element.style[i];
                    inlineStyles[prop] = element.style.getPropertyValue(prop);
                }
                
                this.inlineStyles.push({
                    element: this.getElementSelector(element),
                    styles: inlineStyles,
                    priority: element.style.getPropertyPriority('background-color') || 'normal'
                });
                
                console.warn(`  Inline styles on ${this.getElementSelector(element)}:`, inlineStyles);
                
                this.issues.push({
                    type: 'inline-style-override',
                    severity: 'medium',
                    element: this.getElementSelector(element),
                    styles: inlineStyles,
                    message: `Element has inline styles that may override CSS`
                });
            }
        });
        
        // Check admin bar
        const adminBar = document.getElementById('wpadminbar');
        if (adminBar && adminBar.style.length > 0) {
            console.warn('  Admin bar has inline styles');
            this.issues.push({
                type: 'inline-style-override',
                severity: 'low',
                element: '#wpadminbar',
                message: 'Admin bar has inline styles'
            });
        }
    }

    /**
     * Check for specificity conflicts
     */
    checkSpecificityConflicts() {
        console.log('%c[CSS Diagnostics] Checking specificity conflicts...', 'color: #673AB7;');
        
        // Get all stylesheets
        const sheets = Array.from(document.styleSheets);
        const menuRules = [];
        
        sheets.forEach(sheet => {
            try {
                const rules = Array.from(sheet.cssRules || sheet.rules || []);
                
                rules.forEach(rule => {
                    if (rule.selectorText && rule.selectorText.includes('#adminmenu')) {
                        menuRules.push({
                            selector: rule.selectorText,
                            specificity: this.calculateSpecificity(rule.selectorText),
                            properties: this.extractProperties(rule),
                            sheet: sheet.href || 'inline'
                        });
                    }
                });
            } catch (e) {
                // Cross-origin stylesheet, skip
                console.warn('  Cannot access stylesheet:', sheet.href);
            }
        });
        
        // Find conflicts
        const propertyMap = new Map();
        
        menuRules.forEach(rule => {
            Object.keys(rule.properties).forEach(prop => {
                if (!propertyMap.has(prop)) {
                    propertyMap.set(prop, []);
                }
                propertyMap.get(prop).push(rule);
            });
        });
        
        propertyMap.forEach((rules, prop) => {
            if (rules.length > 1) {
                // Sort by specificity
                rules.sort((a, b) => b.specificity - a.specificity);
                
                const conflict = {
                    property: prop,
                    rules: rules,
                    winningRule: rules[0],
                    conflictCount: rules.length
                };
                
                this.specificityConflicts.push(conflict);
                
                console.warn(`  Specificity conflict for ${prop}:`, rules.map(r => ({
                    selector: r.selector,
                    specificity: r.specificity,
                    value: r.properties[prop]
                })));
                
                this.issues.push({
                    type: 'specificity-conflict',
                    severity: 'medium',
                    property: prop,
                    conflictCount: rules.length,
                    message: `${rules.length} rules compete for ${prop} property`
                });
            }
        });
    }

    /**
     * Analyze CSS cascade and inheritance
     */
    analyzeCascadeAndInheritance() {
        console.log('%c[CSS Diagnostics] Analyzing cascade and inheritance...', 'color: #673AB7;');
        
        // Check if menu styles are being inherited correctly
        const menuItems = document.querySelectorAll('#adminmenu li a');
        
        if (menuItems.length > 0) {
            const firstItem = menuItems[0];
            const computed = getComputedStyle(firstItem);
            
            const inheritedProps = {
                'background-color': computed.backgroundColor,
                'color': computed.color,
                'font-size': computed.fontSize,
                'font-family': computed.fontFamily
            };
            
            console.log('  Menu item computed styles:', inheritedProps);
            
            // Check if styles match expected MAS values
            const expectedBg = this.cssVariables.get('--mas-menu-background')?.value;
            const expectedColor = this.cssVariables.get('--mas-menu-text-color')?.value;
            
            if (expectedBg && computed.backgroundColor !== expectedBg) {
                this.issues.push({
                    type: 'cascade-mismatch',
                    severity: 'high',
                    property: 'background-color',
                    expected: expectedBg,
                    actual: computed.backgroundColor,
                    message: 'Menu background color does not match CSS variable'
                });
            }
            
            if (expectedColor && computed.color !== expectedColor) {
                this.issues.push({
                    type: 'cascade-mismatch',
                    severity: 'high',
                    property: 'color',
                    expected: expectedColor,
                    actual: computed.color,
                    message: 'Menu text color does not match CSS variable'
                });
            }
        }
    }

    /**
     * Calculate CSS specificity
     * 
     * @param {string} selector 
     * @returns {number}
     */
    calculateSpecificity(selector) {
        let specificity = 0;
        
        // Count IDs
        const ids = (selector.match(/#/g) || []).length;
        specificity += ids * 100;
        
        // Count classes, attributes, pseudo-classes
        const classes = (selector.match(/\./g) || []).length;
        const attrs = (selector.match(/\[/g) || []).length;
        const pseudoClasses = (selector.match(/:/g) || []).length;
        specificity += (classes + attrs + pseudoClasses) * 10;
        
        // Count elements
        const elements = selector.split(/[\s>+~]/).filter(s => s && !s.match(/[#.\[:]/) ).length;
        specificity += elements;
        
        return specificity;
    }

    /**
     * Extract properties from CSS rule
     * 
     * @param {CSSRule} rule 
     * @returns {Object}
     */
    extractProperties(rule) {
        const properties = {};
        
        if (rule.style) {
            for (let i = 0; i < rule.style.length; i++) {
                const prop = rule.style[i];
                properties[prop] = rule.style.getPropertyValue(prop);
            }
        }
        
        return properties;
    }

    /**
     * Check if content contains MAS-specific styles
     * 
     * @param {string} content 
     * @returns {boolean}
     */
    containsMASStyles(content) {
        const masPatterns = [
            /--mas-/,
            /#adminmenu/,
            /#wpadminbar/,
            /\.mas-/,
            /modern-admin-styler/
        ];
        
        return masPatterns.some(pattern => pattern.test(content));
    }

    /**
     * Get element location in DOM
     * 
     * @param {HTMLElement} element 
     * @returns {string}
     */
    getElementLocation(element) {
        if (element.parentElement === document.head) {
            return 'head';
        } else if (element.parentElement === document.body) {
            return 'body';
        } else {
            return 'other';
        }
    }

    /**
     * Get CSS selector for element
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
                totalInjectionPoints: this.injectionPoints.length,
                masInjectionPoints: this.injectionPoints.filter(p => p.hasMASSyntax).length,
                totalCSSVariables: this.cssVariables.size,
                definedVariables: Array.from(this.cssVariables.values()).filter(v => v.defined).length,
                inlineStyleOverrides: this.inlineStyles.length,
                specificityConflicts: this.specificityConflicts.length,
                totalIssues: this.issues.length,
                criticalIssues: this.issues.filter(i => i.severity === 'critical').length,
                highIssues: this.issues.filter(i => i.severity === 'high').length
            },
            injectionPoints: this.injectionPoints,
            cssVariables: Array.from(this.cssVariables.entries()).map(([name, data]) => ({ name, ...data })),
            inlineStyles: this.inlineStyles,
            specificityConflicts: this.specificityConflicts,
            issues: this.issues,
            recommendations: this.generateRecommendations()
        };
        
        this.printReport(report);
        
        return report;
    }

    /**
     * Generate recommendations
     * 
     * @returns {Array}
     */
    generateRecommendations() {
        const recommendations = [];
        
        if (this.issues.length === 0) {
            recommendations.push({
                priority: 'info',
                message: 'No CSS issues detected. Styles are applying correctly.'
            });
            return recommendations;
        }
        
        // Missing CSS variables
        const missingVars = this.issues.filter(i => i.type === 'missing-css-variable');
        if (missingVars.length > 0) {
            recommendations.push({
                priority: 'high',
                message: `${missingVars.length} CSS variables are not defined`,
                action: 'Ensure CSS variables are set in :root or via JavaScript'
            });
        }
        
        // Inline style overrides
        if (this.inlineStyles.length > 0) {
            recommendations.push({
                priority: 'medium',
                message: `${this.inlineStyles.length} elements have inline styles`,
                action: 'Remove inline styles or use !important in CSS if necessary'
            });
        }
        
        // Specificity conflicts
        if (this.specificityConflicts.length > 0) {
            recommendations.push({
                priority: 'medium',
                message: `${this.specificityConflicts.length} specificity conflicts detected`,
                action: 'Review CSS selectors and increase specificity where needed'
            });
        }
        
        // Cascade mismatches
        const cascadeMismatches = this.issues.filter(i => i.type === 'cascade-mismatch');
        if (cascadeMismatches.length > 0) {
            recommendations.push({
                priority: 'critical',
                message: 'CSS variables are not being applied to elements',
                action: 'Check CSS injection order and ensure variables are defined before use'
            });
        }
        
        return recommendations;
    }

    /**
     * Print formatted report
     * 
     * @param {Object} report 
     */
    printReport(report) {
        console.log('\n');
        console.log('%c╔════════════════════════════════════════════════════════════╗', 'color: #9C27B0;');
        console.log('%c║         MAS CSS DIAGNOSTICS REPORT                        ║', 'color: #9C27B0; font-weight: bold;');
        console.log('%c╚════════════════════════════════════════════════════════════╝', 'color: #9C27B0;');
        console.log('\n');
        
        console.log('%c📊 SUMMARY', 'color: #673AB7; font-weight: bold; font-size: 14px;');
        console.log(`   CSS Injection Points: ${report.summary.totalInjectionPoints} (${report.summary.masInjectionPoints} MAS-related)`);
        console.log(`   CSS Variables: ${report.summary.definedVariables}/${report.summary.totalCSSVariables} defined`);
        console.log(`   Inline Style Overrides: ${report.summary.inlineStyleOverrides}`);
        console.log(`   Specificity Conflicts: ${report.summary.specificityConflicts}`);
        console.log(`   Total Issues: ${report.summary.totalIssues}`);
        console.log('\n');
        
        if (report.issues.length > 0) {
            console.log('%c⚠️  ISSUES DETECTED', 'color: #F44336; font-weight: bold; font-size: 14px;');
            report.issues.forEach((issue, index) => {
                const severityColor = issue.severity === 'critical' ? '#F44336' : issue.severity === 'high' ? '#FF9800' : '#FFC107';
                console.log(`%c   ${index + 1}. [${issue.severity.toUpperCase()}] ${issue.message}`, `color: ${severityColor}; font-weight: bold;`);
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
        
        console.log('%c✅ CSS diagnostic scan complete.', 'color: #4CAF50;');
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
     * Download report
     */
    downloadReport() {
        const report = this.exportReport();
        const blob = new Blob([report], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `mas-css-diagnostics-${Date.now()}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        
        console.log('%c📥 CSS diagnostic report downloaded', 'color: #4CAF50; font-weight: bold;');
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.MASCSSDiagnostics = CSSDiagnostics;
}

export default CSSDiagnostics;
