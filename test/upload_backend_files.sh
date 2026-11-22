#!/bin/bash

# Backend files upload script
# Run this from the project root directory

SERVER="deploy@conchcapital.co"
REMOTE_PATH="/var/www/conchcapital/backend"

echo "Uploading backend files..."

# Migration file
scp backend/database/migrations/2026_01_17_223404_add_growth_fields_to_profit_growths_table.php ${SERVER}:${REMOTE_PATH}/database/migrations/

# Models
scp backend/app/Models/ProfitGrowth.php ${SERVER}:${REMOTE_PATH}/app/Models/

# Controllers
scp backend/app/Http/Controllers/Api/ProfitGrowthController.php ${SERVER}:${REMOTE_PATH}/app/Http/Controllers/Api/
scp backend/app/Http/Controllers/Api/AdminController.php ${SERVER}:${REMOTE_PATH}/app/Http/Controllers/Api/

# Routes
scp backend/routes/api.php ${SERVER}:${REMOTE_PATH}/routes/
scp backend/routes/console.php ${SERVER}:${REMOTE_PATH}/routes/

# Console Command
scp backend/app/Console/Commands/CalculateProfitGrowth.php ${SERVER}:${REMOTE_PATH}/app/Console/Commands/

echo "Upload complete!"
echo "Now SSH into the server and run: php artisan migrate"
