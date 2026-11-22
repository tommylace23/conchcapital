#!/bin/bash

# Upload 20% weekly growth feature with 7-day lock period
# This includes: default 20% weekly, 7-day lock, incremental daily growth, transfer cooldown

cd /Users/david/Downloads/test

echo "📤 Uploading 20% weekly growth feature..."
echo ""

# Upload migration
echo "1. Uploading migration for lock fields..."
scp -o StrictHostKeyChecking=no backend/database/migrations/2026_01_20_190000_add_lock_and_transfer_fields_to_profit_growths_table.php deploy@conchcapital.co:/var/www/conchcapital/backend/database/migrations/

# Upload backend files
echo "2. Uploading ProfitGrowthController.php..."
scp -o StrictHostKeyChecking=no backend/app/Http/Controllers/Api/ProfitGrowthController.php deploy@conchcapital.co:/var/www/conchcapital/backend/app/Http/Controllers/Api/

echo "3. Uploading ProfitGrowth model..."
scp -o StrictHostKeyChecking=no backend/app/Models/ProfitGrowth.php deploy@conchcapital.co:/var/www/conchcapital/backend/app/Models/

echo "4. Uploading CalculateProfitGrowth command..."
scp -o StrictHostKeyChecking=no backend/app/Console/Commands/CalculateProfitGrowth.php deploy@conchcapital.co:/var/www/conchcapital/backend/app/Console/Commands/

# Upload frontend files
echo "5. Uploading dashboard.html..."
scp -o StrictHostKeyChecking=no dashboard.html deploy@conchcapital.co:/var/www/conchcapital/backend/public/

echo "6. Uploading api.js..."
scp -o StrictHostKeyChecking=no js/api.js deploy@conchcapital.co:/var/www/conchcapital/backend/public/js/

echo "7. Uploading admin-user-detail.html..."
scp -o StrictHostKeyChecking=no admin-user-detail.html deploy@conchcapital.co:/var/www/conchcapital/backend/public/

echo ""
echo "🔄 Running migration and clearing caches..."
ssh -o StrictHostKeyChecking=no deploy@conchcapital.co "cd /var/www/conchcapital/backend && php artisan migrate --force && php artisan route:clear && php artisan config:clear && php artisan cache:clear && echo '✅ Migration and caches cleared'"

echo ""
echo "✅ Upload complete!"
echo ""
echo "Verifying uploads..."
ssh -o StrictHostKeyChecking=no deploy@conchcapital.co "cd /var/www/conchcapital/backend && grep -c 'lock_until_date\|20.0.*weekly' app/Http/Controllers/Api/ProfitGrowthController.php && echo '✅ Backend verified' && grep -c '7-Day Lock Period\|20% weekly' public/dashboard.html && echo '✅ Frontend verified'"
