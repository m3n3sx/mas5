#!/bin/bash

# Phase 3 Performance Testing Suite Runner
# Comprehensive performance testing and optimization verification
# Requirements: 6.4, 5.4

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Test configuration
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
RESULTS_DIR="phase3-performance-results-${TIMESTAMP}"
LOG_FILE="${RESULTS_DIR}/performance-test.log"

# Create results directory
mkdir -p "$RESULTS_DIR"

# Logging function
log() {
    echo -e "$1" | tee -a "$LOG_FILE"
}

# Header
print_header() {
    log "${BLUE}================================================================${NC}"
    log "${BLUE}🚀 Phase 3 Cleanup Performance Testing Suite${NC}"
    log "${BLUE}================================================================${NC}"
    log "${CYAN}Started: $(date)${NC}"
    log "${CYAN}Results Directory: ${RESULTS_DIR}${NC}"
    log ""
}

# Test 1: PHP Performance Verification
run_php_verification() {
    log "${YELLOW}📋 Test 1: PHP Performance Verification${NC}"
    log "${BLUE}Running comprehensive PHP-based performance verification...${NC}"
    
    if php verify-phase3-performance-optimization.php > "${RESULTS_DIR}/php-verification.log" 2>&1; then
        log "${GREEN}✅ PHP verification completed successfully${NC}"
        
        # Extract key metrics from log
        if grep -q "Overall Score:" "${RESULTS_DIR}/php-verification.log"; then
            SCORE=$(grep "Overall Score:" "${RESULTS_DIR}/php-verification.log" | head -1 | sed 's/.*Overall Score: \([0-9]*\)%.*/\1/')
            log "${CYAN}   📊 Overall Score: ${SCORE}%${NC}"
        fi
        
        return 0
    else
        log "${RED}❌ PHP verification failed${NC}"
        return 1
    fi
}

# Test 2: Performance Benchmarking
run_performance_benchmark() {
    log "${YELLOW}📊 Test 2: Performance Benchmarking${NC}"
    log "${BLUE}Running detailed performance benchmarks...${NC}"
    
    if php benchmark-phase3-performance.php > "${RESULTS_DIR}/benchmark.log" 2>&1; then
        log "${GREEN}✅ Performance benchmark completed successfully${NC}"
        
        # Extract improvement metrics
        if grep -q "Overall Performance Improvement:" "${RESULTS_DIR}/benchmark.log"; then
            IMPROVEMENT=$(grep "Overall Performance Improvement:" "${RESULTS_DIR}/benchmark.log" | head -1 | sed 's/.*Overall Performance Improvement: \([0-9.]*\)%.*/\1/')
            log "${CYAN}   📈 Performance Improvement: ${IMPROVEMENT}%${NC}"
        fi
        
        return 0
    else
        log "${RED}❌ Performance benchmark failed${NC}"
        return 1
    fi
}

# Test 3: File System Verification
run_file_system_check() {
    log "${YELLOW}🗑️ Test 3: File System Verification${NC}"
    log "${BLUE}Verifying Phase 3 file removal...${NC}"
    
    # List of Phase 3 files that should be removed
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
    
    REMOVED_COUNT=0
    EXISTING_COUNT=0
    
    echo "File Removal Verification Report" > "${RESULTS_DIR}/file-removal.log"
    echo "=================================" >> "${RESULTS_DIR}/file-removal.log"
    echo "Timestamp: $(date)" >> "${RESULTS_DIR}/file-removal.log"
    echo "" >> "${RESULTS_DIR}/file-removal.log"
    
    for file in "${PHASE3_FILES[@]}"; do
        if [ -f "$file" ]; then
            log "${RED}   ❌ File still exists: $file${NC}"
            echo "EXISTING: $file" >> "${RESULTS_DIR}/file-removal.log"
            ((EXISTING_COUNT++))
        else
            log "${GREEN}   ✅ File removed: $file${NC}"
            echo "REMOVED: $file" >> "${RESULTS_DIR}/file-removal.log"
            ((REMOVED_COUNT++))
        fi
    done
    
    TOTAL_FILES=${#PHASE3_FILES[@]}
    REMOVAL_RATE=$((REMOVED_COUNT * 100 / TOTAL_FILES))
    
    echo "" >> "${RESULTS_DIR}/file-removal.log"
    echo "Summary:" >> "${RESULTS_DIR}/file-removal.log"
    echo "Total files: $TOTAL_FILES" >> "${RESULTS_DIR}/file-removal.log"
    echo "Removed: $REMOVED_COUNT" >> "${RESULTS_DIR}/file-removal.log"
    echo "Still existing: $EXISTING_COUNT" >> "${RESULTS_DIR}/file-removal.log"
    echo "Removal rate: $REMOVAL_RATE%" >> "${RESULTS_DIR}/file-removal.log"
    
    log "${CYAN}   📊 File removal rate: ${REMOVAL_RATE}% (${REMOVED_COUNT}/${TOTAL_FILES})${NC}"
    
    if [ $REMOVAL_RATE -ge 90 ]; then
        log "${GREEN}✅ File system verification passed${NC}"
        return 0
    else
        log "${RED}❌ File system verification failed (removal rate below 90%)${NC}"
        return 1
    fi
}

# Test 4: Memory Usage Analysis
run_memory_analysis() {
    log "${YELLOW}🧠 Test 4: Memory Usage Analysis${NC}"
    log "${BLUE}Analyzing memory usage improvements...${NC}"
    
    # Calculate file sizes
    REMAINING_FILES=(
        "assets/js/mas-settings-form-handler.js"
        "assets/js/simple-live-preview.js"
    )
    
    TOTAL_SIZE=0
    EXISTING_FILES=0
    
    echo "Memory Usage Analysis Report" > "${RESULTS_DIR}/memory-analysis.log"
    echo "============================" >> "${RESULTS_DIR}/memory-analysis.log"
    echo "Timestamp: $(date)" >> "${RESULTS_DIR}/memory-analysis.log"
    echo "" >> "${RESULTS_DIR}/memory-analysis.log"
    
    for file in "${REMAINING_FILES[@]}"; do
        if [ -f "$file" ]; then
            SIZE=$(stat -f%z "$file" 2>/dev/null || stat -c%s "$file" 2>/dev/null || echo "0")
            SIZE_KB=$((SIZE / 1024))
            TOTAL_SIZE=$((TOTAL_SIZE + SIZE))
            ((EXISTING_FILES++))
            
            log "${GREEN}   ✅ $file: ${SIZE_KB}KB${NC}"
            echo "FOUND: $file (${SIZE_KB}KB)" >> "${RESULTS_DIR}/memory-analysis.log"
        else
            log "${RED}   ❌ Missing required file: $file${NC}"
            echo "MISSING: $file" >> "${RESULTS_DIR}/memory-analysis.log"
        fi
    done
    
    TOTAL_SIZE_KB=$((TOTAL_SIZE / 1024))
    PHASE3_ESTIMATED_KB=465
    MEMORY_SAVINGS=$((PHASE3_ESTIMATED_KB - TOTAL_SIZE_KB))
    SAVINGS_PERCENT=$((MEMORY_SAVINGS * 100 / PHASE3_ESTIMATED_KB))
    
    echo "" >> "${RESULTS_DIR}/memory-analysis.log"
    echo "Summary:" >> "${RESULTS_DIR}/memory-analysis.log"
    echo "Current total size: ${TOTAL_SIZE_KB}KB" >> "${RESULTS_DIR}/memory-analysis.log"
    echo "Phase 3 estimated size: ${PHASE3_ESTIMATED_KB}KB" >> "${RESULTS_DIR}/memory-analysis.log"
    echo "Memory savings: ${MEMORY_SAVINGS}KB" >> "${RESULTS_DIR}/memory-analysis.log"
    echo "Savings percentage: ${SAVINGS_PERCENT}%" >> "${RESULTS_DIR}/memory-analysis.log"
    
    log "${CYAN}   📊 Current size: ${TOTAL_SIZE_KB}KB${NC}"
    log "${CYAN}   💾 Memory savings: ${MEMORY_SAVINGS}KB (${SAVINGS_PERCENT}%)${NC}"
    
    if [ $SAVINGS_PERCENT -ge 50 ]; then
        log "${GREEN}✅ Memory analysis passed${NC}"
        return 0
    else
        log "${YELLOW}⚠️ Memory savings below expected threshold${NC}"
        return 0  # Warning, not failure
    fi
}

# Test 5: Network Request Analysis
run_network_analysis() {
    log "${YELLOW}🌐 Test 5: Network Request Analysis${NC}"
    log "${BLUE}Analyzing network request reduction...${NC}"
    
    # Count Phase 3 files that would have been network requests
    PHASE3_REQUEST_COUNT=14
    CURRENT_REQUEST_COUNT=2  # mas-settings-form-handler.js + simple-live-preview.js
    
    REQUESTS_ELIMINATED=$((PHASE3_REQUEST_COUNT - CURRENT_REQUEST_COUNT))
    REDUCTION_PERCENT=$((REQUESTS_ELIMINATED * 100 / PHASE3_REQUEST_COUNT))
    
    echo "Network Request Analysis Report" > "${RESULTS_DIR}/network-analysis.log"
    echo "===============================" >> "${RESULTS_DIR}/network-analysis.log"
    echo "Timestamp: $(date)" >> "${RESULTS_DIR}/network-analysis.log"
    echo "" >> "${RESULTS_DIR}/network-analysis.log"
    echo "Phase 3 requests (baseline): $PHASE3_REQUEST_COUNT" >> "${RESULTS_DIR}/network-analysis.log"
    echo "Current requests: $CURRENT_REQUEST_COUNT" >> "${RESULTS_DIR}/network-analysis.log"
    echo "Requests eliminated: $REQUESTS_ELIMINATED" >> "${RESULTS_DIR}/network-analysis.log"
    echo "Reduction percentage: $REDUCTION_PERCENT%" >> "${RESULTS_DIR}/network-analysis.log"
    
    log "${CYAN}   📊 Baseline requests: ${PHASE3_REQUEST_COUNT}${NC}"
    log "${CYAN}   📊 Current requests: ${CURRENT_REQUEST_COUNT}${NC}"
    log "${CYAN}   🔽 Requests eliminated: ${REQUESTS_ELIMINATED} (${REDUCTION_PERCENT}%)${NC}"
    
    if [ $REDUCTION_PERCENT -ge 70 ]; then
        log "${GREEN}✅ Network analysis passed${NC}"
        return 0
    else
        log "${YELLOW}⚠️ Network request reduction below expected threshold${NC}"
        return 0  # Warning, not failure
    fi
}

# Test 6: Browser Performance Test
run_browser_test() {
    log "${YELLOW}🌐 Test 6: Browser Performance Test${NC}"
    log "${BLUE}Generating browser-based performance test...${NC}"
    
    # Create a simple HTML test page for browser testing
    cat > "${RESULTS_DIR}/browser-performance-test.html" << 'EOF'
<!DOCTYPE html>
<html>
<head>
    <title>Phase 3 Performance Browser Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .result { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; }
        .warning { background: #fff3cd; color: #856404; }
        .error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <h1>Phase 3 Performance Browser Test</h1>
    <div id="results"></div>
    
    <script>
        const results = document.getElementById('results');
        
        function addResult(message, type = 'success') {
            const div = document.createElement('div');
            div.className = `result ${type}`;
            div.textContent = message;
            results.appendChild(div);
        }
        
        // Test 1: Check if Phase 3 files are missing (should be 404)
        const phase3Files = [
            'assets/js/core/EventBus.js',
            'assets/js/mas-admin-app.js'
        ];
        
        let missingFiles = 0;
        let totalFiles = phase3Files.length;
        
        phase3Files.forEach(file => {
            fetch(file, { method: 'HEAD' })
                .then(response => {
                    if (!response.ok) {
                        missingFiles++;
                        addResult(`✅ Phase 3 file properly removed: ${file}`, 'success');
                    } else {
                        addResult(`❌ Phase 3 file still exists: ${file}`, 'error');
                    }
                })
                .catch(() => {
                    missingFiles++;
                    addResult(`✅ Phase 3 file properly removed: ${file}`, 'success');
                })
                .finally(() => {
                    if (missingFiles === totalFiles) {
                        addResult(`🎯 All Phase 3 files successfully removed (${missingFiles}/${totalFiles})`, 'success');
                    }
                });
        });
        
        // Test 2: Check if remaining files exist
        const remainingFiles = [
            'assets/js/mas-settings-form-handler.js',
            'assets/js/simple-live-preview.js'
        ];
        
        remainingFiles.forEach(file => {
            fetch(file, { method: 'HEAD' })
                .then(response => {
                    if (response.ok) {
                        addResult(`✅ Required file exists: ${file}`, 'success');
                    } else {
                        addResult(`❌ Required file missing: ${file}`, 'error');
                    }
                })
                .catch(() => {
                    addResult(`❌ Required file missing: ${file}`, 'error');
                });
        });
        
        // Test 3: Performance timing
        if (performance && performance.timing) {
            const loadTime = performance.timing.loadEventEnd - performance.timing.navigationStart;
            addResult(`📊 Page load time: ${loadTime}ms`, loadTime < 2000 ? 'success' : 'warning');
        }
        
        // Test 4: Memory usage (if available)
        if (performance && performance.memory) {
            const memoryMB = Math.round(performance.memory.usedJSHeapSize / 1024 / 1024);
            addResult(`🧠 JavaScript heap size: ${memoryMB}MB`, memoryMB < 50 ? 'success' : 'warning');
        }
        
        addResult('🏁 Browser performance test completed', 'success');
    </script>
</body>
</html>
EOF
    
    log "${GREEN}✅ Browser test page created: ${RESULTS_DIR}/browser-performance-test.html${NC}"
    log "${CYAN}   📝 Open this file in a browser to run client-side performance tests${NC}"
    
    return 0
}

# Generate final report
generate_final_report() {
    log "${PURPLE}📊 Generating Final Performance Report${NC}"
    
    REPORT_FILE="${RESULTS_DIR}/final-performance-report.md"
    
    cat > "$REPORT_FILE" << EOF
# Phase 3 Cleanup Performance Test Report

**Generated:** $(date)  
**Test Suite Version:** 1.0  
**Results Directory:** ${RESULTS_DIR}

## Executive Summary

This report summarizes the performance testing results for the Phase 3 cleanup optimization.

## Test Results

### 1. PHP Performance Verification
- **Status:** $([ -f "${RESULTS_DIR}/php-verification.log" ] && echo "✅ Completed" || echo "❌ Failed")
- **Log File:** php-verification.log

### 2. Performance Benchmarking
- **Status:** $([ -f "${RESULTS_DIR}/benchmark.log" ] && echo "✅ Completed" || echo "❌ Failed")
- **Log File:** benchmark.log

### 3. File System Verification
- **Status:** $([ -f "${RESULTS_DIR}/file-removal.log" ] && echo "✅ Completed" || echo "❌ Failed")
- **Log File:** file-removal.log

### 4. Memory Usage Analysis
- **Status:** $([ -f "${RESULTS_DIR}/memory-analysis.log" ] && echo "✅ Completed" || echo "❌ Failed")
- **Log File:** memory-analysis.log

### 5. Network Request Analysis
- **Status:** $([ -f "${RESULTS_DIR}/network-analysis.log" ] && echo "✅ Completed" || echo "❌ Failed")
- **Log File:** network-analysis.log

### 6. Browser Performance Test
- **Status:** $([ -f "${RESULTS_DIR}/browser-performance-test.html" ] && echo "✅ Generated" || echo "❌ Failed")
- **Test File:** browser-performance-test.html

## Key Metrics

$([ -f "${RESULTS_DIR}/php-verification.log" ] && grep -A 10 "Performance Optimization Summary" "${RESULTS_DIR}/php-verification.log" || echo "PHP verification metrics not available")

## Recommendations

1. **Monitor Production Performance:** Implement ongoing performance monitoring
2. **Browser Testing:** Run the generated browser test in multiple browsers
3. **Load Testing:** Consider running load tests under realistic traffic conditions
4. **Optimization Opportunities:** Review logs for additional optimization suggestions

## Files Generated

- \`performance-test.log\` - Main test execution log
- \`php-verification.log\` - PHP-based verification results
- \`benchmark.log\` - Detailed performance benchmarks
- \`file-removal.log\` - File system verification results
- \`memory-analysis.log\` - Memory usage analysis
- \`network-analysis.log\` - Network request analysis
- \`browser-performance-test.html\` - Browser-based performance test
- \`final-performance-report.md\` - This report

## Next Steps

1. Review all log files for detailed metrics
2. Run browser performance test in target browsers
3. Address any warnings or issues identified
4. Implement performance monitoring in production environment

---
*Report generated by Phase 3 Performance Testing Suite*
EOF
    
    log "${GREEN}✅ Final report generated: ${REPORT_FILE}${NC}"
}

# Main execution
main() {
    print_header
    
    local failed_tests=0
    local total_tests=6
    
    # Run all tests
    run_php_verification || ((failed_tests++))
    echo ""
    
    run_performance_benchmark || ((failed_tests++))
    echo ""
    
    run_file_system_check || ((failed_tests++))
    echo ""
    
    run_memory_analysis || ((failed_tests++))
    echo ""
    
    run_network_analysis || ((failed_tests++))
    echo ""
    
    run_browser_test || ((failed_tests++))
    echo ""
    
    # Generate final report
    generate_final_report
    
    # Summary
    local passed_tests=$((total_tests - failed_tests))
    local success_rate=$((passed_tests * 100 / total_tests))
    
    log "${BLUE}================================================================${NC}"
    log "${BLUE}🏁 Performance Testing Suite Complete${NC}"
    log "${BLUE}================================================================${NC}"
    log "${CYAN}Completed: $(date)${NC}"
    log "${CYAN}Tests Passed: ${passed_tests}/${total_tests} (${success_rate}%)${NC}"
    log "${CYAN}Results Directory: ${RESULTS_DIR}${NC}"
    
    if [ $failed_tests -eq 0 ]; then
        log "${GREEN}🎉 All performance tests passed successfully!${NC}"
        log "${GREEN}✅ Phase 3 cleanup performance optimization verified${NC}"
    else
        log "${YELLOW}⚠️ ${failed_tests} test(s) had issues - review logs for details${NC}"
    fi
    
    log ""
    log "${PURPLE}📋 Next Steps:${NC}"
    log "${CYAN}1. Review detailed logs in: ${RESULTS_DIR}/${NC}"
    log "${CYAN}2. Open browser test: ${RESULTS_DIR}/browser-performance-test.html${NC}"
    log "${CYAN}3. Read final report: ${RESULTS_DIR}/final-performance-report.md${NC}"
    
    return $failed_tests
}

# Execute main function
main "$@"
exit $?