#!/bin/bash

# IELTS Platform Test Runner
# This script runs all PHPUnit tests for the application

echo "=========================================="
echo "IELTS Learning Platform - Test Suite"
echo "=========================================="
echo ""

# Check if we're in the backend directory
if [ ! -f "artisan" ]; then
    echo "Error: Please run this script from the backend directory"
    exit 1
fi

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Setting up test environment...${NC}"

# Clear cache
php artisan config:clear
php artisan cache:clear

echo ""
echo -e "${YELLOW}Running all tests...${NC}"
echo ""

# Run all tests
php artisan test

TEST_RESULT=$?

echo ""
echo "=========================================="

if [ $TEST_RESULT -eq 0 ]; then
    echo -e "${GREEN}✓ All tests passed!${NC}"
else
    echo -e "${RED}✗ Some tests failed${NC}"
fi

echo "=========================================="

exit $TEST_RESULT
