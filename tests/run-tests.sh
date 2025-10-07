#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "=========================================="
echo "Modern Admin Styler V2 - Test Runner"
echo "=========================================="
echo ""

# Check if PHPUnit is installed
if ! command -v phpunit &> /dev/null; then
    echo -e "${RED}Error: PHPUnit is not installed${NC}"
    echo "Please install PHPUnit:"
    echo "  composer require --dev phpunit/phpunit"
    echo "  or download from https://phar.phpunit.de/"
    exit 1
fi

# Check if WordPress test library is installed
WP_TESTS_DIR=${WP_TESTS_DIR-/tmp/wordpress-tests-lib}
if [ ! -d "$WP_TESTS_DIR" ]; then
    echo -e "${YELLOW}Warning: WordPress test library not found at $WP_TESTS_DIR${NC}"
    echo "Please run: bash bin/install-wp-tests.sh wordpress_test root '' localhost latest"
    echo ""
    read -p "Continue anyway? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

# Parse command line arguments
TESTSUITE=""
COVERAGE=""
FILTER=""

while [[ $# -gt 0 ]]; do
    case $1 in
        --suite)
            TESTSUITE="--testsuite $2"
            shift 2
            ;;
        --coverage)
            COVERAGE="--coverage-html tests/coverage/html"
            shift
            ;;
        --filter)
            FILTER="--filter $2"
            shift 2
            ;;
        --help)
            echo "Usage: ./tests/run-tests.sh [OPTIONS]"
            echo ""
            echo "Options:"
            echo "  --suite <name>    Run specific test suite (e.g., rest-api)"
            echo "  --coverage        Generate HTML coverage report"
            echo "  --filter <name>   Run tests matching filter pattern"
            echo "  --help            Show this help message"
            echo ""
            echo "Examples:"
            echo "  ./tests/run-tests.sh"
            echo "  ./tests/run-tests.sh --suite rest-api"
            echo "  ./tests/run-tests.sh --coverage"
            echo "  ./tests/run-tests.sh --filter TestMASRestController"
            exit 0
            ;;
        *)
            echo -e "${RED}Unknown option: $1${NC}"
            echo "Use --help for usage information"
            exit 1
            ;;
    esac
done

# Run PHPUnit
echo -e "${GREEN}Running tests...${NC}"
echo ""

phpunit $TESTSUITE $COVERAGE $FILTER

EXIT_CODE=$?

echo ""
if [ $EXIT_CODE -eq 0 ]; then
    echo -e "${GREEN}✓ All tests passed!${NC}"
else
    echo -e "${RED}✗ Some tests failed${NC}"
fi

if [ ! -z "$COVERAGE" ]; then
    echo ""
    echo -e "${GREEN}Coverage report generated at: tests/coverage/html/index.html${NC}"
fi

exit $EXIT_CODE
