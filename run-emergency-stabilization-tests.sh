#!/bin/bash

# Emergency Stabilization Test Suite Runner
# This script runs all emergency stabilization tests

echo "=========================================="
echo "Emergency Stabilization Test Suite"
echo "=========================================="
echo ""

# Color codes
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test counter
TOTAL_TESTS=9
PASSED_TESTS=0
FAILED_TESTS=0

# Function to run a test
run_test() {
    local test_file=$1
    local test_name=$2
    
    echo "Running: $test_name"
    echo "----------------------------------------"
    
    if php "$test_file" 2>&1; then
        echo -e "${GREEN}✓ PASSED${NC}"
        ((PASSED_TESTS++))
    else
        echo -e "${RED}✗ FAILED${NC}"
        ((FAILED_TESTS++))
    fi
    
    echo ""
}

# Task 1: Feature Flags Override
echo "=== Task 1: Feature Flags Override ==="
run_test "test-emergency-mode-override.php" "Feature flags service override"

# Task 2: Enqueue Simplification
echo "=== Task 2: Enqueue Simplification ==="
run_test "test-task2-enqueue-simplification.php" "EnqueueAssets() simplification"

# Task 3: Method Disabling
echo "=== Task 3: Method Disabling ==="
run_test "test-task3-method-disabling.php" "Broken methods disabled"

# Task 4: Feature Flags UI
echo "=== Task 4: Feature Flags UI ==="
run_test "test-task4-feature-flags-ui.php" "Feature flags admin UI"

# Task 5: Emergency Stabilization Testing
echo "=== Task 5: Emergency Stabilization Testing ==="

echo "--- Sub-task 5.1: Plugin Load ---"
run_test "test-emergency-stabilization-5.1.php" "Plugin loads without errors"

echo "--- Sub-task 5.2: Settings Save ---"
run_test "test-emergency-stabilization-5.2.php" "Settings save functionality"

echo "--- Sub-task 5.3: Live Preview ---"
run_test "test-emergency-stabilization-5.3.php" "Live preview functionality"

echo "--- Sub-task 5.4: Import/Export ---"
run_test "test-emergency-stabilization-5.4.php" "Import/export functionality"

echo "--- Sub-task 5.5: Feature Flags Page ---"
run_test "test-emergency-stabilization-5.5.php" "Feature flags admin page"

# Summary
echo "=========================================="
echo "Test Suite Summary"
echo "=========================================="
echo "Total Tests: $TOTAL_TESTS"
echo -e "Passed: ${GREEN}$PASSED_TESTS${NC}"
echo -e "Failed: ${RED}$FAILED_TESTS${NC}"
echo ""

if [ $FAILED_TESTS -eq 0 ]; then
    echo -e "${GREEN}✓ ALL TESTS PASSED${NC}"
    echo ""
    echo "Emergency stabilization is verified and ready!"
    exit 0
else
    echo -e "${RED}✗ SOME TESTS FAILED${NC}"
    echo ""
    echo "Please review the failed tests above."
    exit 1
fi
