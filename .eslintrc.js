module.exports = {
  env: {
    browser: true,
    es2021: true,
    jest: true,
    jquery: true
  },
  extends: [
    'standard'
  ],
  parserOptions: {
    ecmaVersion: 12,
    sourceType: 'module'
  },
  globals: {
    // WordPress globals
    wp: 'readonly',
    wpApiSettings: 'readonly',
    ajaxurl: 'readonly',
    masV2Data: 'readonly',
    
    // Test globals
    testUtils: 'readonly',
    fetchMock: 'readonly',
    
    // Jest globals
    jest: 'readonly',
    expect: 'readonly',
    describe: 'readonly',
    test: 'readonly',
    it: 'readonly',
    beforeEach: 'readonly',
    afterEach: 'readonly',
    beforeAll: 'readonly',
    afterAll: 'readonly'
  },
  rules: {
    // Customize rules for testing
    'no-unused-vars': ['error', { 'argsIgnorePattern': '^_' }],
    'no-console': 'off', // Allow console in tests
    'prefer-const': 'error',
    'no-var': 'error',
    'object-shorthand': 'error',
    'prefer-arrow-callback': 'error',
    
    // Jest specific rules
    'jest/no-disabled-tests': 'warn',
    'jest/no-focused-tests': 'error',
    'jest/no-identical-title': 'error',
    'jest/prefer-to-have-length': 'warn',
    'jest/valid-expect': 'error'
  },
  plugins: [
    'jest'
  ],
  overrides: [
    {
      files: ['tests/**/*.js'],
      env: {
        jest: true
      },
      rules: {
        // More lenient rules for test files
        'max-len': 'off',
        'no-magic-numbers': 'off'
      }
    }
  ]
};