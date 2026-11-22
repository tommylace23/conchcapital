<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$count = DB::table('profile_view_requests')->where('status', 'pending')->count();
echo "Found {$count} pending request(s)\n";

if ($count > 0) {
    $deleted = DB::table('profile_view_requests')->where('status', 'pending')->delete();
    echo "Deleted {$deleted} pending request(s)\n";
    echo "✅ You can now test the 'View Portfolio' feature again!\n";
} else {
    echo "No pending requests to delete.\n";
}
