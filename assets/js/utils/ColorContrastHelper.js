/**
 * Color Contrast Helper
 * 
 * Utilities for checking and ensuring WCAG AA color contrast compliance.
 * Provides methods to calculate contrast ratios, validate colors, and
 * suggest accessible alternatives.
 * 
 * @class ColorContrastHelper
 */
class ColorContrastHelper {
    /**
     * WCAG contrast ratio requirements
     */
    static WCAG_AA_NORMAL = 4.5;  // For normal text
    static WCAG_AA_LARGE = 3.0;   // For large text (18pt+ or 14pt+ bold)
    static WCAG_AAA_NORMAL = 7.0; // For AAA compliance
    static WCAG_AAA_LARGE = 4.5;  // For AAA compliance large text

    /**
     * Convert hex color to RGB
     * 
     * @param {string} hex - Hex color (#RRGGBB or #RGB)
     * @returns {Object} RGB object {r, g, b}
     */
    static hexToRgb(hex) {
        // Remove # if present
        hex = hex.replace(/^#/, '');

        // Expand shorthand form (e.g. "03F") to full form (e.g. "0033FF")
        if (hex.length === 3) {
            hex = hex.split('').map(char => char + char).join('');
        }

        const r = parseInt(hex.substring(0, 2), 16);
        const g = parseInt(hex.substring(2, 4), 16);
        const b = parseInt(hex.substring(4, 6), 16);

        return { r, g, b };
    }

    /**
     * Convert RGB to hex color
     * 
     * @param {number} r - Red (0-255)
     * @param {number} g - Green (0-255)
     * @param {number} b - Blue (0-255)
     * @returns {string} Hex color
     */
    static rgbToHex(r, g, b) {
        const toHex = (n) => {
            const hex = Math.round(n).toString(16);
            return hex.length === 1 ? '0' + hex : hex;
        };

        return `#${toHex(r)}${toHex(g)}${toHex(b)}`;
    }

    /**
     * Calculate relative luminance of a color
     * Based on WCAG 2.0 formula
     * 
     * @param {Object} rgb - RGB object {r, g, b}
     * @returns {number} Relative luminance (0-1)
     */
    static getLuminance(rgb) {
        // Convert RGB to sRGB
        const rsRGB = rgb.r / 255;
        const gsRGB = rgb.g / 255;
        const bsRGB = rgb.b / 255;

        // Apply gamma correction
        const r = rsRGB <= 0.03928 ? rsRGB / 12.92 : Math.pow((rsRGB + 0.055) / 1.055, 2.4);
        const g = gsRGB <= 0.03928 ? gsRGB / 12.92 : Math.pow((gsRGB + 0.055) / 1.055, 2.4);
        const b = bsRGB <= 0.03928 ? bsRGB / 12.92 : Math.pow((bsRGB + 0.055) / 1.055, 2.4);

        // Calculate luminance
        return 0.2126 * r + 0.7152 * g + 0.0722 * b;
    }

    /**
     * Calculate contrast ratio between two colors
     * Based on WCAG 2.0 formula
     * 
     * @param {string} color1 - First color (hex)
     * @param {string} color2 - Second color (hex)
     * @returns {number} Contrast ratio (1-21)
     */
    static getContrastRatio(color1, color2) {
        const rgb1 = this.hexToRgb(color1);
        const rgb2 = this.hexToRgb(color2);

        const lum1 = this.getLuminance(rgb1);
        const lum2 = this.getLuminance(rgb2);

        const lighter = Math.max(lum1, lum2);
        const darker = Math.min(lum1, lum2);

        return (lighter + 0.05) / (darker + 0.05);
    }

    /**
     * Check if color combination meets WCAG AA standard
     * 
     * @param {string} foreground - Foreground color (hex)
     * @param {string} background - Background color (hex)
     * @param {boolean} largeText - Whether text is large (18pt+ or 14pt+ bold)
     * @returns {Object} Result {passes, ratio, required}
     */
    static meetsWCAG_AA(foreground, background, largeText = false) {
        const ratio = this.getContrastRatio(foreground, background);
        const required = largeText ? this.WCAG_AA_LARGE : this.WCAG_AA_NORMAL;

        return {
            passes: ratio >= required,
            ratio: ratio,
            required: required,
            level: 'AA'
        };
    }

    /**
     * Check if color combination meets WCAG AAA standard
     * 
     * @param {string} foreground - Foreground color (hex)
     * @param {string} background - Background color (hex)
     * @param {boolean} largeText - Whether text is large
     * @returns {Object} Result {passes, ratio, required}
     */
    static meetsWCAG_AAA(foreground, background, largeText = false) {
        const ratio = this.getContrastRatio(foreground, background);
        const required = largeText ? this.WCAG_AAA_LARGE : this.WCAG_AAA_NORMAL;

        return {
            passes: ratio >= required,
            ratio: ratio,
            required: required,
            level: 'AAA'
        };
    }

    /**
     * Get WCAG compliance level for color combination
     * 
     * @param {string} foreground - Foreground color (hex)
     * @param {string} background - Background color (hex)
     * @param {boolean} largeText - Whether text is large
     * @returns {string} Compliance level ('AAA', 'AA', 'Fail')
     */
    static getComplianceLevel(foreground, background, largeText = false) {
        const aaa = this.meetsWCAG_AAA(foreground, background, largeText);
        if (aaa.passes) {
            return 'AAA';
        }

        const aa = this.meetsWCAG_AA(foreground, background, largeText);
        if (aa.passes) {
            return 'AA';
        }

        return 'Fail';
    }

    /**
     * Suggest accessible foreground color for given background
     * 
     * @param {string} background - Background color (hex)
     * @param {boolean} preferDark - Prefer dark text over light
     * @param {string} level - Target level ('AA' or 'AAA')
     * @returns {string} Suggested foreground color (hex)
     */
    static suggestForeground(background, preferDark = true, level = 'AA') {
        const required = level === 'AAA' ? this.WCAG_AAA_NORMAL : this.WCAG_AA_NORMAL;

        // Try black first if preferDark
        if (preferDark) {
            const blackRatio = this.getContrastRatio('#000000', background);
            if (blackRatio >= required) {
                return '#000000';
            }
        }

        // Try white
        const whiteRatio = this.getContrastRatio('#FFFFFF', background);
        if (whiteRatio >= required) {
            return '#FFFFFF';
        }

        // Try black if not already tried
        if (!preferDark) {
            const blackRatio = this.getContrastRatio('#000000', background);
            if (blackRatio >= required) {
                return '#000000';
            }
        }

        // If neither works, adjust the background luminance
        const bgRgb = this.hexToRgb(background);
        const bgLum = this.getLuminance(bgRgb);

        // If background is light, darken it; if dark, lighten it
        if (bgLum > 0.5) {
            // Background is light, return dark gray
            return '#333333';
        } else {
            // Background is dark, return light gray
            return '#CCCCCC';
        }
    }

    /**
     * Adjust color to meet contrast requirements
     * 
     * @param {string} foreground - Foreground color (hex)
     * @param {string} background - Background color (hex)
     * @param {string} level - Target level ('AA' or 'AAA')
     * @returns {string} Adjusted foreground color (hex)
     */
    static adjustForContrast(foreground, background, level = 'AA') {
        const required = level === 'AAA' ? this.WCAG_AAA_NORMAL : this.WCAG_AA_NORMAL;
        const currentRatio = this.getContrastRatio(foreground, background);

        if (currentRatio >= required) {
            return foreground; // Already meets requirements
        }

        const fgRgb = this.hexToRgb(foreground);
        const bgRgb = this.hexToRgb(background);
        const bgLum = this.getLuminance(bgRgb);

        // Determine if we should lighten or darken
        const shouldLighten = bgLum < 0.5;

        // Binary search for the right adjustment
        let low = 0;
        let high = 255;
        let adjusted = { ...fgRgb };

        for (let i = 0; i < 20; i++) { // Max 20 iterations
            const mid = (low + high) / 2;
            
            if (shouldLighten) {
                adjusted = {
                    r: Math.min(255, fgRgb.r + mid),
                    g: Math.min(255, fgRgb.g + mid),
                    b: Math.min(255, fgRgb.b + mid)
                };
            } else {
                adjusted = {
                    r: Math.max(0, fgRgb.r - mid),
                    g: Math.max(0, fgRgb.g - mid),
                    b: Math.max(0, fgRgb.b - mid)
                };
            }

            const adjustedHex = this.rgbToHex(adjusted.r, adjusted.g, adjusted.b);
            const ratio = this.getContrastRatio(adjustedHex, background);

            if (Math.abs(ratio - required) < 0.1) {
                break; // Close enough
            }

            if (ratio < required) {
                low = mid;
            } else {
                high = mid;
            }
        }

        return this.rgbToHex(adjusted.r, adjusted.g, adjusted.b);
    }

    /**
     * Validate all colors in settings object
     * 
     * @param {Object} settings - Settings object with color pairs
     * @returns {Array} Array of validation results
     */
    static validateSettings(settings) {
        const results = [];

        // Define color pairs to check
        const colorPairs = [
            {
                name: 'Menu',
                foreground: settings.menu_text_color,
                background: settings.menu_background,
                largeText: false
            },
            {
                name: 'Menu Hover',
                foreground: settings.menu_hover_text_color,
                background: settings.menu_hover_background,
                largeText: false
            },
            {
                name: 'Menu Active',
                foreground: settings.menu_active_text_color,
                background: settings.menu_active_background,
                largeText: false
            },
            {
                name: 'Admin Bar',
                foreground: settings.admin_bar_text_color,
                background: settings.admin_bar_background,
                largeText: false
            }
        ];

        for (const pair of colorPairs) {
            if (!pair.foreground || !pair.background) {
                continue;
            }

            const result = this.meetsWCAG_AA(
                pair.foreground,
                pair.background,
                pair.largeText
            );

            results.push({
                name: pair.name,
                foreground: pair.foreground,
                background: pair.background,
                ...result,
                suggestion: result.passes ? null : this.suggestForeground(pair.background)
            });
        }

        return results;
    }

    /**
     * Generate contrast report for settings
     * 
     * @param {Object} settings - Settings object
     * @returns {Object} Report with summary and details
     */
    static generateReport(settings) {
        const results = this.validateSettings(settings);
        const passing = results.filter(r => r.passes).length;
        const failing = results.length - passing;

        return {
            summary: {
                total: results.length,
                passing: passing,
                failing: failing,
                passRate: results.length > 0 ? (passing / results.length * 100).toFixed(1) : 0
            },
            results: results,
            recommendations: results
                .filter(r => !r.passes)
                .map(r => ({
                    name: r.name,
                    issue: `Contrast ratio ${r.ratio.toFixed(2)}:1 is below required ${r.required}:1`,
                    suggestion: `Consider using ${r.suggestion} for text color`
                }))
        };
    }

    /**
     * Add high contrast mode support
     * 
     * @param {boolean} enable - Whether to enable high contrast mode
     * @returns {void}
     */
    static setHighContrastMode(enable) {
        if (enable) {
            document.documentElement.classList.add('high-contrast-mode');
            localStorage.setItem('mas_high_contrast', 'true');
        } else {
            document.documentElement.classList.remove('high-contrast-mode');
            localStorage.removeItem('mas_high_contrast');
        }
    }

    /**
     * Check if high contrast mode is enabled
     * 
     * @returns {boolean} Whether high contrast mode is enabled
     */
    static isHighContrastMode() {
        return localStorage.getItem('mas_high_contrast') === 'true' ||
               document.documentElement.classList.contains('high-contrast-mode');
    }

    /**
     * Initialize high contrast mode from storage
     * 
     * @returns {void}
     */
    static initHighContrastMode() {
        if (this.isHighContrastMode()) {
            document.documentElement.classList.add('high-contrast-mode');
        }
    }

    /**
     * Simulate color blindness for testing
     * 
     * @param {string} type - Type of color blindness ('protanopia', 'deuteranopia', 'tritanopia')
     * @param {string} color - Color to simulate (hex)
     * @returns {string} Simulated color (hex)
     */
    static simulateColorBlindness(type, color) {
        const rgb = this.hexToRgb(color);
        let simulated = { ...rgb };

        switch (type) {
            case 'protanopia': // Red-blind
                simulated.r = 0.567 * rgb.r + 0.433 * rgb.g;
                simulated.g = 0.558 * rgb.r + 0.442 * rgb.g;
                simulated.b = 0.242 * rgb.g + 0.758 * rgb.b;
                break;

            case 'deuteranopia': // Green-blind
                simulated.r = 0.625 * rgb.r + 0.375 * rgb.g;
                simulated.g = 0.7 * rgb.r + 0.3 * rgb.g;
                simulated.b = 0.3 * rgb.g + 0.7 * rgb.b;
                break;

            case 'tritanopia': // Blue-blind
                simulated.r = 0.95 * rgb.r + 0.05 * rgb.g;
                simulated.g = 0.433 * rgb.g + 0.567 * rgb.b;
                simulated.b = 0.475 * rgb.g + 0.525 * rgb.b;
                break;
        }

        return this.rgbToHex(simulated.r, simulated.g, simulated.b);
    }

    /**
     * Check if color is distinguishable for color blind users
     * 
     * @param {string} color1 - First color (hex)
     * @param {string} color2 - Second color (hex)
     * @returns {Object} Results for different types of color blindness
     */
    static checkColorBlindness(color1, color2) {
        const types = ['protanopia', 'deuteranopia', 'tritanopia'];
        const results = {};

        for (const type of types) {
            const sim1 = this.simulateColorBlindness(type, color1);
            const sim2 = this.simulateColorBlindness(type, color2);
            const ratio = this.getContrastRatio(sim1, sim2);

            results[type] = {
                distinguishable: ratio >= this.WCAG_AA_NORMAL,
                ratio: ratio
            };
        }

        return results;
    }
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ColorContrastHelper;
}

// Make available globally
if (typeof window !== 'undefined') {
    window.ColorContrastHelper = ColorContrastHelper;
}
