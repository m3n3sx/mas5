#!/bin/bash

# Phase 2 End-to-End Test Runner
# Executes comprehensive integration tests for all Phase 2 features

set -e

echo "========================================="
echo "Phase 2 End-to-End Integration Tests"
echo "========================================="
echo ""

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test results tracking
TESTS_PASSED=0
TESTS_FAILED=0
TESTS_SKIPPED=0

# Function to run a test file
run_test() {
    local test_file=$1
    local test_name=$2
    
    echo -e "${YELLOW}Running: ${test_name}${NC}"
    
    if php "$test_file"; then
        echo -e "${GREEN}✓ PASSED: ${test_name}${NC}"
        ((TESTS_PASSED++))
    else
        echo -e "${RED}✗ FAILED: ${test_name}${NC}"
        ((TESTS_FAILED++))
    fi
    echo ""
}

# Function to check if PHPUnit is available
check_phpunit() {
    if ! command -v phpunit &> /dev/null; then
        echo -e "${RED}Error: PHPUnit is not installed${NC}"
        echo "Please install PHPUnit to run tests"
        exit 1
    fi
}

# Main test execution
main() {
    echo "Checking test environment..."
    check_phpunit
    
    echo "Starting Phase 2 end-to-end tests..."
    echo ""
    
    # Run comprehensive end-to-end test
    if [ -f "tests/php/integration/TestPhase2EndToEnd.php" ]; then
        echo -e "${YELLOW}=== Comprehensive End-to-End Tests ===${NC}"
        if phpunit tests/php/integration/TestPhase2EndToEnd.php; then
            echo -e "${GREEN}✓ All end-to-end tests passed${NC}"
            ((TESTS_PASSED++))
        else
            echo -e "${RED}✗ Some end-to-end tests failed${NC}"
            ((TESTS_FAILED++))
        fi
        echo ""
    fi
    
    # Run individual feature tests
    echo -e "${YELLOW}=== Individual Feature Tests ===${NC}"
    
    if [ -f "tests/php/integration/TestPhase2ThemeManagement.php" ]; then
        if phpunit tests/php/integration/TestPhase2ThemeManagement.php; then
            echo -e "${GREEN}✓ Theme Management tests passed${NC}"
            ((TESTS_PASSED++))
        else
            echo -e "${RED}✗ Theme Management tests failed${NC}"
            ((TESTS_FAILED++))
        fi
    fi
    
    if [ -f "tests/php/integration/TestPhase2BackupSystem.php" ]; then
        if phpunit tests/php/integration/TestPhase2BackupSystem.php; then
            echo -e "${GREEN}✓ Backup System tests passed${NC}"
            ((TESTS_PASSED++))
        else
            echo -e "${RED}✗ Backup System tests failed${NC}"
            ((TESTS_FAILED++))
        fi
    fi
    
    if [ -f "tests/php/integration/TestPhase2Diagnostics.php" ]; then
        if phpunit tests/php/integration/TestPhase2Diagnostics.php; then
            echo -e "${GREEN}✓ Diagnostics tests passed${NC}"
            ((TESTS_PASSED++))
        else
            echo -e "${RED}✗ Diagnostics tests failed${NC}"
            ((TESTS_FAILED++))
        fi
    fi
    
    if [ -f "tests/php/integration/TestPhase2SecurityFeatures.php" ]; then
        if phpunit tests/php/integration/TestPhase2SecurityFeatures.php; then
            echo -e "${GREEN}✓ Security Features tests passed${NC}"
            ((TESTS_PASSED++))
        else
            echo -e "${RED}✗ Security Features tests failed${NC}"
            ((TESTS_FAILED++))
        fi
    fi
    
    if [ -f "tests/php/integration/TestPhase2BatchOperations.php" ]; then
        if phpunit tests/php/integration/TestPhase2BatchOperations.php; then
            echo -e "${GREEN}✓ Batch Operations tests passed${NC}"
            ((TESTS_PASSED++))
        else
            echo -e "${RED}✗ Batch Operations tests failed${NC}"
            ((TESTS_FAILED++))
        fi
    fi
    
    if [ -f "tests/php/integration/TestPhase2Webhooks.php" ]; then
        if phpunit tests/php/integration/TestPhase2Webhooks.php; then
            echo -e "${GREEN}✓ Webhooks tests passed${NC}"
            ((TESTS_PASSED++))
        else
            echo -e "${RED}✗ Webhooks tests failed${NC}"
            ((TESTS_FAILED++))
        fi
    fi
    
    if [ -f "tests/php/integration/TestPhase2BackwardCompatibility.php" ]; then
        if phpunit tests/php/integration/TestPhase2BackwardCompatibility.php; then
            echo -e "${GREEN}✓ Backward Compatibility tests passed${NC}"
            ((TESTS_PASSED++))
        else
            echo -e "${RED}✗ Backward Compatibility tests failed${NC}"
            ((TESTS_FAILED++))
        fi
    fi
    
    echo ""
    echo "========================================="
    echo "Test Results Summary"
    echo "========================================="
    echo -e "${GREEN}Passed: ${TESTS_PASSED}${NC}"
    echo -e "${RED}Failed: ${TESTS_FAILED}${NC}"
    echo -e "${YELLOW}Skipped: ${TESTS_SKIPPED}${NC}"
    echo ""
    
    if [ $TESTS_FAILED -eq 0 ]; then
        echo -e "${GREEN}All tests passed! ✓${NC}"
        exit 0
    else
        echo -e "${RED}Some tests failed. Please review the output above.${NC}"
        exit 1
    fi
}

# Run main function
main
