/**
 * Jest setup verification test
 */

describe('Jest Setup Verification', () => {
  test('Jest is properly configured', () => {
    expect(true).toBe(true);
  });

  test('Global mocks are available', () => {
    expect(global.wpApiSettings).toBeDefined();
    expect(global.masV2Data).toBeDefined();
    expect(global.jQuery).toBeDefined();
    expect(global.fetch).toBeDefined();
  });

  test('Test utilities are available', () => {
    expect(global.testUtils).toBeDefined();
    expect(global.testUtils.createMockSettings).toBeInstanceOf(Function);
    expect(global.testUtils.createMockTheme).toBeInstanceOf(Function);
    expect(global.testUtils.createMockBackup).toBeInstanceOf(Function);
  });

  test('Custom matchers are working', () => {
    expect('#ff5722').toBeValidHexColor();
    expect('280px').toBeValidCSSUnit();
  });

  test('Mock settings creation works', () => {
    const settings = global.testUtils.createMockSettings();
    expect(settings).toHaveProperty('menu_background');
    expect(settings).toHaveProperty('menu_text_color');
    expect(settings.menu_background).toBeValidHexColor();
  });
});