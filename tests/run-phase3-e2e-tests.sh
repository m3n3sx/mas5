#!/bin/bash

# Phase 3 End-to-End Test Runner
# Runs all Phase 3 integration tests

set -e

echo "=========================================="
echo "Phase 3 End-to-End Test Suite"
echo "=========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Track results
TESTS_PASSED=0
TESTS_FAILED=0

# Function to run test and track results
run_test() {
    local test_name=$1
    local test_command=$2
    
    echo -e "${YELLOW}Running: $test_name${NC}"
    
    if eval "$test_command"; then
        echo -e "${GREEN}✓ PASSED: $test_name${NC}"
        ((TESTS_PASSED++))
    else
        echo -e "${RED}✗ FAILED: $test_name${NC}"
        ((TESTS_FAILED++))
    fi
    echo ""
}

# 1. PHP Integration Tests
echo "=========================================="
echo "1. PHP Integration Tests"
echo "=========================================="
echo ""

if command -v phpunit &> /dev/null; then
    run_test "Phase 3 End-to-End Tests" \
        "phpunit tests/php/integration/TestPhase3EndToEnd.php"
else
    echo -e "${YELLOW}⚠ PHPUnit not found, skipping PHP tests${NC}"
    echo ""
fi

# 2. JavaScript Integration Tests
echo "=========================================="
echo "2. JavaScript Integration Tests"
echo "=========================================="
echo ""

if command -v npm &> /dev/null; then
    run_test "Phase 3 JavaScript E2E Tests" \
        "npm test -- tests/js/integration/phase3-e2e.test.js --run"
else
    echo -e "${YELLOW}⚠ npm not found, skipping JavaScript tests${NC}"
    echo ""
fi

# 3. Component Tests
echo "=========================================="
echo "3. Component Integration Tests"
echo "=========================================="
echo ""

if command -v npm &> /dev/null; then
    run_test "EventBus Tests" \
        "npm test -- tests/js/core/EventBus.test.js --run"
    
    run_test "StateManager Tests" \
        "npm test -- tests/js/core/StateManager.test.js --run"
    
    run_test "APIClient Tests" \
        "npm test -- tests/js/core/APIClient.test.js --run"
    
    run_test "ErrorHandler Tests" \
        "npm test -- tests/js/core/ErrorHandler.test.js --run"
fi

# 4. Manual Test Verification
echo "=========================================="
echo "4. Manual Test Checklist"
echo "=========================================="
echo ""
echo "Please verify the following manually:"
echo ""
echo "□ Open test-phase3-task3-components.html"
echo "  - Test settings form submission"
echo "  - Verify all fields save correctly"
echo "  - Check validation errors display"
echo ""
echo "□ Open test-phase3-task4-live-preview.html"
echo "  - Enable live preview"
echo "  - Change colors and verify preview updates"
echo "  - Disable preview and verify restoration"
echo ""
echo "□ Open test-phase3-task5-notification-system.html"
echo "  - Test all notification types"
echo "  - Verify auto-dismiss works"
echo "  - Test keyboard dismissal (Escape)"
echo ""
echo "□ Open test-phase3-task6-ui-components.html"
echo "  - Test theme selector"
echo "  - Test backup manager"
echo "  - Test tab navigation"
echo ""
echo "□ Open test-phase3-task7-performance.html"
echo "  - Check load time < 1s"
echo "  - Verify smooth animations"
echo "  - Test with large datasets"
echo ""
echo "□ Open test-phase3-task8-accessibility.html"
echo "  - Test keyboard navigation"
echo "  - Verify screen reader compatibility"
echo "  - Check color contrast"
echo ""
echo "□ Open test-handler-diagnostics.html"
echo "  - Verify no duplicate handlers"
echo "  - Check for conflicts"
echo ""
echo "□ Open test-css-diagnostics.html"
echo "  - Verify CSS injection works"
echo "  - Check for style conflicts"
echo ""

# Summary
echo ""
echo "=========================================="
echo "Test Summary"
echo "=========================================="
echo -e "${GREEN}Passed: $TESTS_PASSED${NC}"
echo -e "${RED}Failed: $TESTS_FAILED${NC}"
echo ""

if [ $TESTS_FAILED -eq 0 ]; then
    echo -e "${GREEN}✓ All automated tests passed!${NC}"
    echo ""
    echo "Next steps:"
    echo "1. Complete manual test checklist above"
    echo "2. Run cross-browser tests (task 13.2)"
    echo "3. Run performance validation (task 13.3)"
    echo "4. Run security review (task 13.4)"
    exit 0
else
    echo -e "${RED}✗ Some tests failed. Please review and fix.${NC}"
    exit 1
fi
