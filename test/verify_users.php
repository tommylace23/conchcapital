<?php
/**
 * Quick verification script to check if users were created
 */

require __DIR__ . '/backend/vendor/autoload.php';

$app = require_once __DIR__ . '/backend/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

echo "=== User Verification ===\n\n";

$totalUsers = User::count();
echo "Total users in database: {$totalUsers}\n\n";

$familyUsers = User::where('display_name', 'like', '%Family%')->count();
echo "Family users: {$familyUsers}\n\n";

echo "Sample of family users:\n";
echo str_repeat("=", 60) . "\n";

$users = User::where('display_name', 'like', '%Family%')
    ->orderBy('created_at', 'desc')
    ->take(10)
    ->get(['id', 'username', 'display_name', 'email', 'bio', 'created_at']);

foreach ($users as $user) {
    echo sprintf(
        "ID: %-5s | Username: %-20s | Display: %s\n",
        $user->id,
        $user->username,
        $user->display_name
    );
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "✅ Verification complete!\n";
