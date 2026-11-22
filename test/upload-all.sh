#!/bin/bash

# Upload script for InvestEdu profit growth updates
# This script uploads all modified files to the server

SERVER="deploy@conchcapital.co"
BASE_PATH="/var/www/conchcapital/backend"

echo "=========================================="
echo "Uploading InvestEdu Profit Growth Updates"
echo "=========================================="
echo ""

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Counter
SUCCESS=0
FAILED=0

# Function to upload file
upload_file() {
    local source=$1
    local dest=$2
    local description=$3
    
    echo -e "${BLUE}Uploading:${NC} $description"
    echo "  From: $source"
    echo "  To: $dest"
    
    if scp "$source" "$SERVER:$dest" 2>/dev/null; then
        echo -e "${GREEN}✓ Success${NC}"
        ((SUCCESS++))
    else
        echo -e "${RED}✗ Failed${NC}"
        ((FAILED++))
    fi
    echo ""
}

# Frontend files
echo "=== Frontend Files ==="
upload_file "dashboard.html" "$BASE_PATH/public/dashboard.html" "Dashboard (profit growth balance)"
upload_file "admin-user-detail.html" "$BASE_PATH/public/admin-user-detail.html" "Admin User Detail (balance + delete + test frequency)"
upload_file "simu-list-page.html" "$BASE_PATH/public/simu-list-page.html" "Profit Growth List (removed user delete)"
upload_file "js/main.js" "$BASE_PATH/public/js/main.js" "Main JS (balance calculation)"
upload_file "js/api.js" "$BASE_PATH/public/js/api.js" "API JS (admin delete method)"

# Backend files
echo "=== Backend Files ==="
upload_file "backend/app/Http/Controllers/Api/DashboardController.php" "$BASE_PATH/app/Http/Controllers/Api/DashboardController.php" "Dashboard Controller (total balance calculation)"
upload_file "backend/app/Http/Controllers/Api/AdminController.php" "$BASE_PATH/app/Http/Controllers/Api/AdminController.php" "Admin Controller (balance + delete + test frequency)"
upload_file "backend/app/Http/Controllers/Api/ProfitGrowthController.php" "$BASE_PATH/app/Http/Controllers/Api/ProfitGrowthController.php" "Profit Growth Controller (admin-only delete + test frequency)"
upload_file "backend/app/Console/Commands/CalculateProfitGrowth.php" "$BASE_PATH/app/Console/Commands/CalculateProfitGrowth.php" "Calculate Profit Growth Command (test frequency)"
upload_file "backend/routes/api.php" "$BASE_PATH/routes/api.php" "API Routes (admin delete route)"

# Migration file
echo "=== Migration File ==="
upload_file "backend/database/migrations/2026_01_18_181216_add_test_frequency_to_profit_growths_table.php" "$BASE_PATH/database/migrations/2026_01_18_181216_add_test_frequency_to_profit_growths_table.php" "Migration (add test frequency)"

# Summary
echo "=========================================="
echo "Upload Summary"
echo "=========================================="
echo -e "${GREEN}Success: $SUCCESS files${NC}"
if [ $FAILED -gt 0 ]; then
    echo -e "${RED}Failed: $FAILED files${NC}"
fi
echo ""

# Instructions for server commands
if [ $FAILED -eq 0 ]; then
    echo "=========================================="
    echo "Next Steps - Run on Server:"
    echo "=========================================="
    echo ""
    echo "ssh $SERVER"
    echo "cd $BASE_PATH"
    echo ""
    echo "# Run migration"
    echo "php artisan migrate"
    echo ""
    echo "# Clear and cache"
    echo "php artisan route:clear"
    echo "php artisan route:cache"
    echo "php artisan config:clear"
    echo "php artisan config:cache"
    echo ""
else
    echo -e "${RED}Some files failed to upload. Please check the errors above.${NC}"
fi
