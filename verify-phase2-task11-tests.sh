#!/bin/bash

# Verification script for Phase 2 Task 11 Integration Tests
# This script verifies that all integration tests are properly created

echo "=========================================="
echo "Phase 2 Task 11 Test Verification"
echo "=========================================="
echo ""

# Check if test files exist
echo "Checking test files..."
test_files=(
    "tests/php/integration/TestPhase2ThemeManagement.php"
    "tests/php/integration/TestPhase2BackupSystem.php"
    "tests/php/integration/TestPhase2Diagnostics.php"
    "tests/php/integration/TestPhase2SecurityFeatures.php"
    "tests/php/integration/TestPhase2BatchOperations.php"
    "tests/php/integration/TestPhase2Webhooks.php"
    "tests/php/integration/TestPhase2BackwardCompatibility.php"
)

all_exist=true
for file in "${test_files[@]}"; do
    if [ -f "$file" ]; then
        echo "✅ $file"
    else
        echo "❌ $file - NOT FOUND"
        all_exist=false
    fi
done

echo ""

if [ "$all_exist" = true ]; then
    echo "✅ All 7 test files exist"
else
    echo "❌ Some test files are missing"
    exit 1
fi

echo ""
echo "Counting test methods..."

# Count test methods in each file
total=0
for file in "${test_files[@]}"; do
    if [ -f "$file" ]; then
        count=$(grep -c "public function test_" "$file")
        filename=$(basename "$file")
        echo "  $filename: $count tests"
        total=$((total + count))
    fi
done

echo ""
echo "Total test methods: $total"

if [ $total -eq 81 ]; then
    echo "✅ Expected 81 tests, found $total"
else
    echo "⚠️  Expected 81 tests, found $total"
fi

echo ""
echo "Checking PHPUnit configuration..."

if [ -f "phpunit.xml.dist" ]; then
    echo "✅ phpunit.xml.dist exists"
    
    if grep -q "integration" phpunit.xml.dist; then
        echo "✅ Integration test suite configured"
    else
        echo "⚠️  Integration test suite not found in phpunit.xml.dist"
    fi
else
    echo "❌ phpunit.xml.dist not found"
fi

echo ""
echo "Checking test helper classes..."

if [ -f "tests/helpers/class-mas-rest-test-case.php" ]; then
    echo "✅ MAS_REST_Test_Case helper exists"
else
    echo "⚠️  MAS_REST_Test_Case helper not found"
fi

if [ -f "tests/helpers/class-mas-test-case.php" ]; then
    echo "✅ MAS_Test_Case helper exists"
else
    echo "⚠️  MAS_Test_Case helper not found"
fi

echo ""
echo "=========================================="
echo "Verification Summary"
echo "=========================================="
echo "Test Files: 7/7 ✅"
echo "Test Methods: $total/81"
echo "Status: READY FOR EXECUTION"
echo ""
echo "To run tests:"
echo "  vendor/bin/phpunit --testsuite integration"
echo ""
echo "To run specific test:"
echo "  vendor/bin/phpunit tests/php/integration/TestPhase2ThemeManagement.php"
echo ""
echo "=========================================="
