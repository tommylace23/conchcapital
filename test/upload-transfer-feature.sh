#!/bin/bash

# Upload transfer feature files to server
# This enables auto-creation of profit growth accounts when transferring funds

cd /Users/david/Downloads/test

echo "📤 Uploading transfer feature files..."
echo ""

# Upload backend files
echo "1. Uploading ProfitGrowthController.php..."
scp -o StrictHostKeyChecking=no backend/app/Http/Controllers/Api/ProfitGrowthController.php deploy@conchcapital.co:/var/www/conchcapital/backend/app/Http/Controllers/Api/

echo "2. Uploading api.php routes..."
scp -o StrictHostKeyChecking=no backend/routes/api.php deploy@conchcapital.co:/var/www/conchcapital/backend/routes/

# Upload frontend files
echo "3. Uploading dashboard.html..."
scp -o StrictHostKeyChecking=no dashboard.html deploy@conchcapital.co:/var/www/conchcapital/backend/public/

echo "4. Uploading api.js..."
scp -o StrictHostKeyChecking=no js/api.js deploy@conchcapital.co:/var/www/conchcapital/backend/public/js/

echo ""
echo "🔄 Clearing Laravel caches..."
ssh -o StrictHostKeyChecking=no deploy@conchcapital.co "cd /var/www/conchcapital/backend && php artisan route:clear && php artisan config:clear && php artisan cache:clear && echo '✅ Caches cleared'"

echo ""
echo "✅ Upload complete!"
echo ""
echo "Verifying uploads..."
ssh -o StrictHostKeyChecking=no deploy@conchcapital.co "cd /var/www/conchcapital/backend && grep -c 'transferFundsWithCreate' app/Http/Controllers/Api/ProfitGrowthController.php routes/api.php && echo '✅ Backend verified' && grep -c 'transferFundsToProfitGrowth.*id.*amount.*options' public/js/api.js && echo '✅ Frontend API verified'"
