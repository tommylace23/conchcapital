<?php
/**
 * Quick test script to verify withdrawals endpoint
 * Upload this to /var/www/conchcapital/backend/public/ and access via browser
 * Or run via SSH: php /var/www/conchcapital/backend/public/test_withdrawals_endpoint.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "Testing withdrawals endpoint...\n";
echo "Route exists: " . (Route::has('api.admin.withdrawals.pending') ? 'YES' : 'NO') . "\n";

// Check if method exists
$controller = new \App\Http\Controllers\Api\AdminController();
if (method_exists($controller, 'getPendingWithdrawals')) {
    echo "Method getPendingWithdrawals exists: YES\n";
} else {
    echo "Method getPendingWithdrawals exists: NO\n";
}

echo "\nDone.\n";
