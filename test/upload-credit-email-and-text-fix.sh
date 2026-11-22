#!/bin/bash

echo "=========================================="
echo "Uploading Credit Email & Text Fix Files"
echo "=========================================="
echo ""

# Set server details
SERVER="deploy@conchcapital.co"
SERVER_PATH="/var/www/conchcapital/backend"

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}1. Uploading Credit Notification Email Mailable...${NC}"
scp -o StrictHostKeyChecking=no backend/app/Mail/CreditNotificationEmail.php ${SERVER}:${SERVER_PATH}/app/Mail/
if [ $? -eq 0 ]; then
    echo -e "${GREEN}   ✓ CreditNotificationEmail.php uploaded${NC}"
else
    echo "   ✗ Failed to upload CreditNotificationEmail.php"
    exit 1
fi

echo ""
echo -e "${BLUE}2. Uploading Credit Notification Email Template...${NC}"
scp -o StrictHostKeyChecking=no backend/resources/views/emails/credit-notification.blade.php ${SERVER}:${SERVER_PATH}/resources/views/emails/
if [ $? -eq 0 ]; then
    echo -e "${GREEN}   ✓ credit-notification.blade.php uploaded${NC}"
else
    echo "   ✗ Failed to upload credit-notification.blade.php"
    exit 1
fi

echo ""
echo -e "${BLUE}3. Uploading Updated AdminController (with email sending)...${NC}"
scp -o StrictHostKeyChecking=no backend/app/Http/Controllers/Api/AdminController.php ${SERVER}:${SERVER_PATH}/app/Http/Controllers/Api/
if [ $? -eq 0 ]; then
    echo -e "${GREEN}   ✓ AdminController.php uploaded${NC}"
else
    echo "   ✗ Failed to upload AdminController.php"
    exit 1
fi

echo ""
echo -e "${BLUE}4. Uploading Updated main.js (text fix)...${NC}"
scp -o StrictHostKeyChecking=no js/main.js ${SERVER}:${SERVER_PATH}/public/js/
if [ $? -eq 0 ]; then
    echo -e "${GREEN}   ✓ main.js uploaded${NC}"
else
    echo "   ✗ Failed to upload main.js"
    exit 1
fi

echo ""
echo -e "${BLUE}5. Uploading Updated dashboard.html (text fix)...${NC}"
scp -o StrictHostKeyChecking=no dashboard.html ${SERVER}:${SERVER_PATH}/public/
if [ $? -eq 0 ]; then
    echo -e "${GREEN}   ✓ dashboard.html uploaded${NC}"
else
    echo "   ✗ Failed to upload dashboard.html"
    exit 1
fi

echo ""
echo -e "${BLUE}6. Clearing Laravel caches on server...${NC}"
ssh -o StrictHostKeyChecking=no ${SERVER} "cd ${SERVER_PATH} && php artisan config:clear && php artisan route:clear && php artisan view:clear && echo 'Caches cleared'"
if [ $? -eq 0 ]; then
    echo -e "${GREEN}   ✓ Laravel caches cleared${NC}"
else
    echo "   ⚠ Warning: Failed to clear caches (you may need to do this manually)"
fi

echo ""
echo "=========================================="
echo -e "${GREEN}✅ All files uploaded successfully!${NC}"
echo "=========================================="
echo ""
echo "Files uploaded:"
echo "  ✓ CreditNotificationEmail.php (Mailable class)"
echo "  ✓ credit-notification.blade.php (Email template)"
echo "  ✓ AdminController.php (Updated with email sending)"
echo "  ✓ main.js (Text fix: 'Funds Received')"
echo "  ✓ dashboard.html (Text fix: 'Funds Received')"
echo ""
echo "Next steps:"
echo "  1. Test by crediting funds to a user account"
echo "  2. Check that the user receives the email notification"
echo "  3. Verify the email doesn't show the amount"
echo "  4. Check that activity logs show 'Funds Received' instead of 'Received Sim Funds'"
echo ""
