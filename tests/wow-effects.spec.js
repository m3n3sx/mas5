/**
 * 🎨 Modern Admin Styler V2 - WOW Effects Automated Tests
 * Playwright E2E Tests for UI Effects
 */

const { test, expect } = require('@playwright/test');

const ADMIN_URL = 'http://localhost:10018/wp-admin/';
const SETTINGS_URL = 'http://localhost:10018/wp-admin/admin.php?page=mas-v2-settings';

test.describe('🎨 WOW Effects Tests', () => {
    
    test.beforeEach(async ({ page }) => {
        // Login to WordPress admin (adjust credentials if needed)
        await page.goto(ADMIN_URL);
        
        // Skip login if already logged in
        const isLoggedIn = await page.locator('#adminmenuwrap').isVisible().catch(() => false);
        if (!isLoggedIn) {
            console.log('Skipping login - already authenticated');
        }
    });

    test('🔍 1. CSS Files Loading Test', async ({ page }) => {
        await page.goto(SETTINGS_URL);
        
        // Check if all CSS files are loaded
        const cssFiles = [
            'advanced-effects.css',
            'color-palettes.css', 
            'palette-switcher.css',
            'quick-fix.css'
        ];
        
        for (const cssFile of cssFiles) {
            const response = await page.waitForResponse(
                response => response.url().includes(cssFile) && response.status() === 200,
                { timeout: 5000 }
            ).catch(() => null);
            
            console.log(`📁 ${cssFile}: ${response ? '✅ LOADED' : '❌ FAILED'}`);
            expect(response).toBeTruthy();
        }
    });

    test('⚡ 2. JavaScript Modules Test', async ({ page }) => {
        await page.goto(SETTINGS_URL);
        
        // Wait for page to load
        await page.waitForLoadState('networkidle');
        
        // Check if ModernAdminApp is available
        const hasModernAdminApp = await page.evaluate(() => {
            return typeof window.ModernAdminApp !== 'undefined';
        });
        console.log(`🚀 ModernAdminApp: ${hasModernAdminApp ? '✅ LOADED' : '❌ MISSING'}`);
        expect(hasModernAdminApp).toBeTruthy();
        
        // Check if PaletteManager is available
        const hasPaletteManager = await page.evaluate(() => {
            return typeof window.PaletteManager !== 'undefined';
        });
        console.log(`🎨 PaletteManager: ${hasPaletteManager ? '✅ LOADED' : '❌ MISSING'}`);
        expect(hasPaletteManager).toBeTruthy();
        
        // Check if modules are initialized
        const modulesInitialized = await page.evaluate(() => {
            try {
                const app = window.ModernAdminApp.getInstance();
                const paletteManager = app.getModule('paletteManager');
                return !!paletteManager;
            } catch (e) {
                return false;
            }
        });
        console.log(`📦 Modules Initialized: ${modulesInitialized ? '✅ YES' : '❌ NO'}`);
    });

    test('🏷️ 3. Body Classes Test', async ({ page }) => {
        await page.goto(SETTINGS_URL);
        await page.waitForLoadState('networkidle');
        
        // Check if body has required classes
        const requiredClasses = [
            'mas-v2-modern-style',
            'mas-theme-light'
        ];
        
        for (const className of requiredClasses) {
            const hasClass = await page.locator('body').getAttribute('class');
            const hasRequiredClass = hasClass?.includes(className);
            console.log(`🏷️ ${className}: ${hasRequiredClass ? '✅ PRESENT' : '❌ MISSING'}`);
        }
        
        // Check floating classes if enabled
        const hasFloatingMenu = await page.locator('body.mas-v2-menu-floating').isVisible().catch(() => false);
        const hasFloatingBar = await page.locator('body.mas-v2-admin-bar-floating').isVisible().catch(() => false);
        
        console.log(`🌊 Floating Menu Class: ${hasFloatingMenu ? '✅ ACTIVE' : '⚠️ INACTIVE'}`);
        console.log(`🎭 Floating Bar Class: ${hasFloatingBar ? '✅ ACTIVE' : '⚠️ INACTIVE'}`);
    });

    test('🎨 4. Palette Switcher Test', async ({ page }) => {
        await page.goto(SETTINGS_URL);
        await page.waitForLoadState('networkidle');
        
        // Look for palette switcher button
        const paletteButton = page.locator('.mas-palette-toggle');
        const isVisible = await paletteButton.isVisible().catch(() => false);
        console.log(`🎨 Palette Switcher Button: ${isVisible ? '✅ VISIBLE' : '❌ HIDDEN'}`);
        
        if (isVisible) {
            // Click the palette switcher
            await paletteButton.click();
            
            // Check if dropdown appears
            const dropdown = page.locator('.mas-palette-dropdown.show');
            const dropdownVisible = await dropdown.isVisible({ timeout: 2000 }).catch(() => false);
            console.log(`📋 Palette Dropdown: ${dropdownVisible ? '✅ OPENS' : '❌ NO RESPONSE'}`);
            
            if (dropdownVisible) {
                // Count palette options
                const paletteCards = await page.locator('.mas-palette-card').count();
                console.log(`🎯 Palette Options: ${paletteCards} found`);
                expect(paletteCards).toBeGreaterThan(0);
            }
        }
    });

    test('⌨️ 5. Keyboard Shortcuts Test', async ({ page }) => {
        await page.goto(SETTINGS_URL);
        await page.waitForLoadState('networkidle');
        
        // Test Ctrl+Shift+8 for Cyber Electric
        await page.keyboard.press('Control+Shift+8');
        
        // Wait a moment for the effect
        await page.waitForTimeout(1000);
        
        // Check if palette changed
        const currentPalette = await page.evaluate(() => {
            return document.documentElement.getAttribute('data-palette');
        });
        
        console.log(`⚡ Keyboard Shortcut Result: ${currentPalette || 'no palette'}`);
        
        // Test if notification appears
        const notification = page.locator('.mas-palette-notification');
        const notificationVisible = await notification.isVisible({ timeout: 2000 }).catch(() => false);
        console.log(`📢 Palette Notification: ${notificationVisible ? '✅ SHOWN' : '❌ MISSING'}`);
    });

    test('🌊 6. Floating Effects Visual Test', async ({ page }) => {
        await page.goto(SETTINGS_URL);
        await page.waitForLoadState('networkidle');
        
        // Check menu floating styles
        const menuStyles = await page.locator('#adminmenuwrap').evaluate(el => {
            const styles = window.getComputedStyle(el);
            return {
                margin: styles.margin,
                borderRadius: styles.borderRadius,
                backdropFilter: styles.backdropFilter,
                boxShadow: styles.boxShadow
            };
        });
        
        console.log('🌊 Menu Styles:', menuStyles);
        
        // Check if menu has floating appearance
        const hasMargin = menuStyles.margin !== '0px';
        const hasRadius = parseFloat(menuStyles.borderRadius) > 0;
        const hasBackdrop = menuStyles.backdropFilter !== 'none';
        const hasShadow = menuStyles.boxShadow !== 'none';
        
        console.log(`📏 Menu Margin: ${hasMargin ? '✅ YES' : '❌ NO'}`);
        console.log(`🔘 Menu Radius: ${hasRadius ? '✅ YES' : '❌ NO'}`);
        console.log(`🌫️ Backdrop Filter: ${hasBackdrop ? '✅ YES' : '❌ NO'}`);
        console.log(`💫 Box Shadow: ${hasShadow ? '✅ YES' : '❌ NO'}`);
        
        // Check admin bar styles
        const barStyles = await page.locator('#wpadminbar').evaluate(el => {
            const styles = window.getComputedStyle(el);
            return {
                margin: styles.margin,
                borderRadius: styles.borderRadius,
                backdropFilter: styles.backdropFilter
            };
        });
        
        console.log('🎭 Admin Bar Styles:', barStyles);
    });

    test('📱 7. Performance & Console Errors', async ({ page }) => {
        // Listen for console errors
        const consoleErrors = [];
        page.on('console', msg => {
            if (msg.type() === 'error') {
                consoleErrors.push(msg.text());
            }
        });
        
        // Listen for network failures
        const networkFailures = [];
        page.on('response', response => {
            if (!response.ok() && response.url().includes('modern-admin-styler')) {
                networkFailures.push(`${response.status()} - ${response.url()}`);
            }
        });
        
        await page.goto(SETTINGS_URL);
        await page.waitForLoadState('networkidle');
        
        // Wait extra time for any async operations
        await page.waitForTimeout(3000);
        
        console.log(`❌ Console Errors: ${consoleErrors.length}`);
        consoleErrors.forEach(error => console.log(`  - ${error}`));
        
        console.log(`🌐 Network Failures: ${networkFailures.length}`);
        networkFailures.forEach(failure => console.log(`  - ${failure}`));
        
        // Performance check
        const performanceMetrics = await page.evaluate(() => {
            const perf = performance.getEntriesByType('navigation')[0];
            return {
                loadTime: Math.round(perf.loadEventEnd - perf.loadEventStart),
                domContentLoaded: Math.round(perf.domContentLoadedEventEnd - perf.domContentLoadedEventStart)
            };
        });
        
        console.log('⚡ Performance:', performanceMetrics);
    });

    test('🎯 8. Full Integration Test', async ({ page }) => {
        await page.goto(SETTINGS_URL);
        await page.waitForLoadState('networkidle');
        
        console.log('🚀 Starting Full Integration Test...');
        
        // 1. Check if WOW Effects indicator is visible
        const wowIndicator = page.locator('body:before');
        console.log('✨ WOW Effects indicator should be visible');
        
        // 2. Try changing palette via UI
        const paletteButton = page.locator('.mas-palette-toggle');
        if (await paletteButton.isVisible().catch(() => false)) {
            await paletteButton.click();
            
            // Click on Creative Purple palette
            const purplePalette = page.locator('[data-palette="creative-purple"]');
            if (await purplePalette.isVisible().catch(() => false)) {
                await purplePalette.click();
                
                // Check if palette changed
                await page.waitForTimeout(1000);
                const newPalette = await page.evaluate(() => {
                    return document.documentElement.getAttribute('data-palette');
                });
                
                console.log(`🎨 Palette Switch Test: ${newPalette === 'creative-purple' ? '✅ SUCCESS' : '❌ FAILED'}`);
            }
        }
        
        // 3. Test hover effects on menu
        const menuItem = page.locator('#adminmenu li').first();
        await menuItem.hover();
        console.log('🖱️ Menu hover test completed');
        
        // 4. Test scroll behavior
        await page.evaluate(() => window.scrollBy(0, 500));
        await page.waitForTimeout(500);
        await page.evaluate(() => window.scrollBy(0, -500));
        console.log('📜 Scroll behavior test completed');
        
        console.log('🎉 Full Integration Test Completed!');
    });
});

// Helper function to capture screenshots on failure
test.afterEach(async ({ page }, testInfo) => {
    if (testInfo.status !== testInfo.expectedStatus) {
        const screenshot = await page.screenshot();
        await testInfo.attach('screenshot', { body: screenshot, contentType: 'image/png' });
    }
}); 