#!/bin/bash
# Cleanup Test Files - Move to tests/archived-tasks

echo "🧹 Cleaning up test files..."

# Create directory if it doesn't exist
mkdir -p tests/archived-tasks

# Move verify-task files
echo "Moving verify-task*.php files..."
mv verify-task*.php tests/archived-tasks/ 2>/dev/null
echo "✓ Moved verify-task files"

# Move test-task files
echo "Moving test-task*.php files..."
mv test-task*.php tests/archived-tasks/ 2>/dev/null
echo "✓ Moved test-task files"

# Move test-*.html files
echo "Moving test-*.html files..."
mv test-*.html tests/archived-tasks/ 2>/dev/null
echo "✓ Moved test-*.html files"

# Move test-*.php files (except test-settings-check.php and test-simple-live-preview.html)
echo "Moving other test-*.php files..."
find . -maxdepth 1 -name "test-*.php" ! -name "test-settings-check.php" ! -name "test-simple-live-preview.html" -exec mv {} tests/archived-tasks/ \; 2>/dev/null
echo "✓ Moved other test files"

# Keep these useful files in root:
# - test-settings-check.php (useful diagnostic)
# - test-simple-live-preview.html (useful diagnostic)
# - test-module-loading.html (useful diagnostic)
# - force-default-settings.php (useful utility)

echo ""
echo "✅ Cleanup complete!"
echo ""
echo "Files kept in root (useful diagnostics):"
ls -1 test-*.php test-*.html force-*.php 2>/dev/null | head -10
echo ""
echo "Archived files location: tests/archived-tasks/"
echo "Count: $(ls -1 tests/archived-tasks/ 2>/dev/null | wc -l) files"
