#!/bin/bash

# Run All Tests Script
# Executes comprehensive test suite for REST API migration

echo "=== Modern Admin Styler V2 - Comprehensive Test Suite ==="
echo ""

# Create coverage directory
mkdir -p tests/coverage

# Run PHPUnit tests with coverage
echo "Running PHPUnit tests with coverage..."
if command -v vendor/bin/phpunit &> /dev/null; then
    vendor/bin/phpunit --coverage-clover=tests/coverage/clover.xml --coverage-html=tests/coverage/html --coverage-text
    PHPUNIT_EXIT_CODE=$?
else
    echo "PHPUnit not found. Please run 'composer install' first."
    PHPUNIT_EXIT_CODE=1
fi

echo ""

# Run Jest tests with coverage
echo "Running Jest tests with coverage..."
if command -v npm &> /dev/null; then
    npm test -- --coverage --coverageReporters=text --coverageReporters=lcov
    JEST_EXIT_CODE=$?
else
    echo "npm not found. Please install Node.js and npm."
    JEST_EXIT_CODE=1
fi

echo ""

# Run QA automation tests
echo "Running QA automation tests..."
if [ -f "tests/qa-automation.php" ]; then
    php tests/qa-automation.php
    QA_EXIT_CODE=$?
else
    echo "QA automation script not found."
    QA_EXIT_CODE=1
fi

echo ""

# Run performance tests
echo "Running performance tests..."
if [ -f "tests/performance-test.php" ]; then
    php tests/performance-test.php
    PERF_EXIT_CODE=$?
else
    echo "Performance test script not found."
    PERF_EXIT_CODE=1
fi

echo ""

# Run coverage analysis
echo "Running coverage analysis..."
if [ -f "tests/coverage-analysis.php" ]; then
    php tests/coverage-analysis.php
    COVERAGE_EXIT_CODE=$?
else
    echo "Coverage analysis script not found."
    COVERAGE_EXIT_CODE=1
fi

echo ""

# Generate final report
echo "=== Final Test Results ==="
echo "PHPUnit Tests: $([ $PHPUNIT_EXIT_CODE -eq 0 ] && echo "✓ PASS" || echo "✗ FAIL")"
echo "Jest Tests: $([ $JEST_EXIT_CODE -eq 0 ] && echo "✓ PASS" || echo "✗ FAIL")"
echo "QA Tests: $([ $QA_EXIT_CODE -eq 0 ] && echo "✓ PASS" || echo "✗ FAIL")"
echo "Performance Tests: $([ $PERF_EXIT_CODE -eq 0 ] && echo "✓ PASS" || echo "✗ FAIL")"
echo "Coverage Analysis: $([ $COVERAGE_EXIT_CODE -eq 0 ] && echo "✓ PASS" || echo "✗ FAIL")"

# Overall result
OVERALL_EXIT_CODE=$((PHPUNIT_EXIT_CODE + JEST_EXIT_CODE + QA_EXIT_CODE + PERF_EXIT_CODE + COVERAGE_EXIT_CODE))

echo ""
if [ $OVERALL_EXIT_CODE -eq 0 ]; then
    echo "🎉 ALL TESTS PASSED - READY FOR PRODUCTION"
    echo ""
    echo "Coverage reports available at:"
    echo "  - PHP: tests/coverage/html/index.html"
    echo "  - JavaScript: coverage/lcov-report/index.html"
    echo ""
    echo "Detailed reports saved to:"
    echo "  - QA Report: /wp-content/mas-qa-report.json"
    echo "  - Performance Report: /wp-content/mas-performance-report.json"
    echo "  - Coverage Report: /wp-content/mas-coverage-report.json"
else
    echo "❌ SOME TESTS FAILED - REVIEW REQUIRED"
    echo ""
    echo "Please review the test output above and fix any failing tests."
fi

exit $OVERALL_EXIT_CODE