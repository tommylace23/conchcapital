#!/bin/bash

echo "=========================================="
echo "Uploading Transfer Received Email Files"
echo "=========================================="
echo ""

# Set server details
SERVER="deploy@conchcapital.co"
SERVER_PATH="/var/www/conchcapital/backend"

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}1. Uploading TransferReceivedEmail Mailable...${NC}"
scp -o StrictHostKeyChecking=no backend/app/Mail/TransferReceivedEmail.php ${SERVER}:${SERVER_PATH}/app/Mail/
if [ $? -eq 0 ]; then
    echo -e "${GREEN}   ✓ TransferReceivedEmail.php uploaded${NC}"
else
    echo "   ✗ Failed to upload TransferReceivedEmail.php"
    exit 1
fi

echo ""
echo -e "${BLUE}2. Uploading Transfer Received Email Template...${NC}"
scp -o StrictHostKeyChecking=no backend/resources/views/emails/transfer-received.blade.php ${SERVER}:${SERVER_PATH}/resources/views/emails/
if [ $? -eq 0 ]; then
    echo -e "${GREEN}   ✓ transfer-received.blade.php uploaded${NC}"
else
    echo "   ✗ Failed to upload transfer-received.blade.php"
    exit 1
fi

echo ""
echo -e "${BLUE}3. Uploading Updated SimulatedTransferController (with email sending)...${NC}"
scp -o StrictHostKeyChecking=no backend/app/Http/Controllers/Api/SimulatedTransferController.php ${SERVER}:${SERVER_PATH}/app/Http/Controllers/Api/
if [ $? -eq 0 ]; then
    echo -e "${GREEN}   ✓ SimulatedTransferController.php uploaded${NC}"
else
    echo "   ✗ Failed to upload SimulatedTransferController.php"
    exit 1
fi

echo ""
echo -e "${BLUE}4. Clearing Laravel caches on server...${NC}"
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
echo "  ✓ TransferReceivedEmail.php (Mailable class)"
echo "  ✓ transfer-received.blade.php (Email template)"
echo "  ✓ SimulatedTransferController.php (Updated with email sending)"
echo ""
echo "Now users will receive email notifications when they receive funds from other users!"
echo ""
