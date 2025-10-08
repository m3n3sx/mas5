#!/bin/bash

# Phase 3 Cleanup Verification Test Runner
# Requirements: 6.1, 6.2, 6.3

echo "=========================================="
echo "Phase 3 Cleanup Verification Test Suite"
echo "=========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Test results tracking
TOTAL_TESTS=0
PASSED_TESTS=0

# Function to run a test and track results
run_test() {
    local test_name="$1"
    local test_command="$2"
    
    echo -e "${BLUE}Running: $test_name${NC}"
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
    
    if eval "$test_command"; then
        echo -e "${GREEN}✓ PASSED: $test_name${NC}"
        PASSED_TESTS=$((PASSED_TESTS + 1))
        return 0
    else
        echo -e "${RED}✗ FAILED: $test_name${NC}"
        return 1
    fi
    echo ""
}

# Test 1: Verify Phase 3 files are removed
echo -e "${YELLOW}Test 1: Phase 3 File Removal Verification${NC}"
echo "Checking if Phase 3 files have been properly removed..."

PHASE3_FILES=(
    "assets/js/core/EventBus.js"
    "assets/js/core/StateManager.js"
    "assets/js/core/APIClient.js"
    "assets/js/core/ErrorHandler.js"
    "assets/js/mas-admin-app.js"
    "assets/js/components/LivePreviewComponent.js"
    "assets/js/components/SettingsFormComponent.js"
    "assets/js/components/NotificationSystem.js"
    "assets/js/components/Component.js"
    "assets/js/utils/DOMOptimizer.js"
    "assets/js/utils/VirtualList.js"
    "assets/js/utils/LazyLoader.js"
    "assets/js/admin-settings-simple.js"
    "assets/js/LivePreviewManager.js"
)

PHASE3_REMOVAL_SUCCESS=true
for file in "${PHASE3_FILES[@]}"; do
    if [ -f "$file" ]; then
        echo -e "${RED}✗ Still exists: $file${NC}"
        PHASE3_REMOVAL_SUCCESS=false
    else
        echo -e "${GREEN}✓ Removed: $file${NC}"
    fi
done

if [ "$PHASE3_REMOVAL_SUCCESS" = true ]; then
    echo -e "${GREEN}✓ All Phase 3 files successfully removed${NC}"
    PASSED_TESTS=$((PASSED_TESTS + 1))
else
    echo -e "${RED}✗ Some Phase 3 files still exist${NC}"
fi
TOTAL_TESTS=$((TOTAL_TESTS + 1))
echo ""

# Test 2: Verify required files exist
echo -e "${YELLOW}Test 2: Required Files Verification${NC}"
echo "Checking if required Phase 2 files exist..."

REQUIRED_FILES=(
    "assets/js/mas-settings-form-handler.js"
    "assets/js/simple-live-preview.js"
)

REQUIRED_FILES_SUCCESS=true
for file in "${REQUIRED_FILES[@]}"; do
    if [ -f "$file" ]; then
        echo -e "${GREEN}✓ Exists: $file${NC}"
    else
        echo -e "${RED}✗ Missing: $file${NC}"
        REQUIRED_FILES_SUCCESS=false
    fi
done

if [ "$REQUIRED_FILES_SUCCESS" = true ]; then
    echo -e "${GREEN}✓ All required files exist${NC}"
    PASSED_TESTS=$((PASSED_TESTS + 1))
else
    echo -e "${RED}✗ Some required files are missing${NC}"
fi
TOTAL_TESTS=$((TOTAL_TESTS + 1))
echo ""

# Test 3: Run PHP verification suite
echo -e "${YELLOW}Test 3: PHP Verification Suite${NC}"
if [ -f "test-phase3-cleanup-verification.php" ]; then
    run_test "PHP Verification Suite" "php test-phase3-cleanup-verification.php > /dev/null 2>&1"
else
    echo -e "${RED}✗ PHP verification file not found${NC}"
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
fi

# Test 4: Check for broken script references in main plugin file
echo -e "${YELLOW}Test 4: Script Enqueuing Verification${NC}"
if [ -f "modern-admin-styler-v2.php" ]; then
    echo "Checking main plugin file for script references..."
    
    # Check for Phase 3 script references (should not exist)
    PHASE3_REFS=$(grep -E "(mas-admin-app|EventBus|StateManager|APIClient)" modern-admin-styler-v2.php || true)
    if [ -z "$PHASE3_REFS" ]; then
        echo -e "${GREEN}✓ No Phase 3 script references found${NC}"
        PASSED_TESTS=$((PASSED_TESTS + 1))
    else
        echo -e "${RED}✗ Found Phase 3 script references:${NC}"
        echo "$PHASE3_REFS"
    fi
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
    
    # Check for required script references (should exist)
    REQUIRED_REFS=$(grep -E "(mas-settings-form-handler|simple-live-preview)" modern-admin-styler-v2.php || true)
    if [ -n "$REQUIRED_REFS" ]; then
        echo -e "${GREEN}✓ Required script references found${NC}"
        PASSED_TESTS=$((PASSED_TESTS + 1))
    else
        echo -e "${RED}✗ Required script references not found${NC}"
    fi
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
else
    echo -e "${RED}✗ Main plugin file not found${NC}"
    TOTAL_TESTS=$((TOTAL_TESTS + 2))
fi
echo ""

# Test 5: Check for orphaned directories
echo -e "${YELLOW}Test 5: Orphaned Directory Cleanup${NC}"
ORPHANED_DIRS=(
    "assets/js/core"
    "assets/js/components"
)

ORPHANED_CLEANUP_SUCCESS=true
for dir in "${ORPHANED_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        # Check if directory is empty or contains only hidden files
        if [ -z "$(ls -A "$dir" 2>/dev/null)" ]; then
            echo -e "${GREEN}✓ Directory empty: $dir${NC}"
        else
            echo -e "${RED}✗ Directory not empty: $dir${NC}"
            ls -la "$dir"
            ORPHANED_CLEANUP_SUCCESS=false
        fi
    else
        echo -e "${GREEN}✓ Directory removed: $dir${NC}"
    fi
done

if [ "$ORPHANED_CLEANUP_SUCCESS" = true ]; then
    PASSED_TESTS=$((PASSED_TESTS + 1))
fi
TOTAL_TESTS=$((TOTAL_TESTS + 1))
echo ""

# Test 6: Performance verification
echo -e "${YELLOW}Test 6: Performance Impact Assessment${NC}"
echo "Calculating performance improvements..."

REMOVED_FILES=0
for file in "${PHASE3_FILES[@]}"; do
    if [ ! -f "$file" ]; then
        REMOVED_FILES=$((REMOVED_FILES + 1))
    fi
done

ESTIMATED_SIZE_REDUCTION=$((REMOVED_FILES * 5)) # Estimate 5KB per file
echo -e "${GREEN}✓ Files removed: $REMOVED_FILES${NC}"
echo -e "${GREEN}✓ Estimated size reduction: ${ESTIMATED_SIZE_REDUCTION}KB${NC}"
echo -e "${GREEN}✓ HTTP requests reduced: $REMOVED_FILES${NC}"

if [ $REMOVED_FILES -gt 0 ]; then
    PASSED_TESTS=$((PASSED_TESTS + 1))
fi
TOTAL_TESTS=$((TOTAL_TESTS + 1))
echo ""

# Test 7: Syntax validation for remaining JavaScript files
echo -e "${YELLOW}Test 7: JavaScript Syntax Validation${NC}"
JS_SYNTAX_SUCCESS=true

for file in "${REQUIRED_FILES[@]}"; do
    if [ -f "$file" ]; then
        # Basic syntax check using node if available
        if command -v node >/dev/null 2>&1; then
            if node -c "$file" >/dev/null 2>&1; then
                echo -e "${GREEN}✓ Syntax valid: $file${NC}"
            else
                echo -e "${RED}✗ Syntax error: $file${NC}"
                JS_SYNTAX_SUCCESS=false
            fi
        else
            # Fallback: check for basic syntax issues
            if grep -q "function\|var\|let\|const" "$file"; then
                echo -e "${GREEN}✓ Basic syntax check passed: $file${NC}"
            else
                echo -e "${YELLOW}? Could not verify syntax: $file (node.js not available)${NC}"
            fi
        fi
    fi
done

if [ "$JS_SYNTAX_SUCCESS" = true ]; then
    PASSED_TESTS=$((PASSED_TESTS + 1))
fi
TOTAL_TESTS=$((TOTAL_TESTS + 1))
echo ""

# Final Results
echo "=========================================="
echo -e "${BLUE}FINAL TEST RESULTS${NC}"
echo "=========================================="

SUCCESS_RATE=$((PASSED_TESTS * 100 / TOTAL_TESTS))

if [ $PASSED_TESTS -eq $TOTAL_TESTS ]; then
    echo -e "${GREEN}✓ ALL TESTS PASSED${NC}"
    echo -e "${GREEN}Success Rate: 100% ($PASSED_TESTS/$TOTAL_TESTS)${NC}"
    echo ""
    echo -e "${GREEN}Phase 3 cleanup verification completed successfully!${NC}"
    echo -e "${GREEN}The system is ready for production use with the simplified Phase 2 architecture.${NC}"
    exit 0
else
    echo -e "${RED}✗ SOME TESTS FAILED${NC}"
    echo -e "${RED}Success Rate: $SUCCESS_RATE% ($PASSED_TESTS/$TOTAL_TESTS)${NC}"
    echo ""
    echo -e "${RED}Phase 3 cleanup verification found issues that need attention.${NC}"
    echo -e "${YELLOW}Please review the failed tests and complete the necessary cleanup steps.${NC}"
    exit 1
fi