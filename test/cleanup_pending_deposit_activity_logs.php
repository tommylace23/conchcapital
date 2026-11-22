<?php

/**
 * Cleanup Pending Deposit Activity Logs
 * 
 * This script removes activity logs for pending deposits that were created
 * before the fix. Run this once to clean up existing pending deposits.
 * 
 * Usage: php cleanup_pending_deposit_activity_logs.php
 */

require __DIR__ . '/backend/vendor/autoload.php';

$app = require_once __DIR__ . '/backend/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ActivityLog;
use App\Models\Transaction;

echo "========================================\n";
echo "Cleaning up pending deposit activity logs\n";
echo "========================================\n\n";

// Find all activity logs for deposits
$depositActivityLogs = ActivityLog::where('action', 'deposit')
    ->where('entity_type', Transaction::class)
    ->get();

$deletedCount = 0;
$keptCount = 0;

foreach ($depositActivityLogs as $activityLog) {
    // Get the associated transaction
    $transaction = Transaction::find($activityLog->entity_id);
    
    // If transaction exists and is still pending, delete the activity log
    if ($transaction && $transaction->status === 'pending') {
        echo "Deleting activity log for pending deposit:\n";
        echo "  - Transaction ID: {$transaction->id}\n";
        echo "  - User ID: {$activityLog->user_id}\n";
        echo "  - Amount: {$activityLog->amount} {$activityLog->currency}\n";
        echo "  - Description: {$activityLog->description}\n\n";
        
        $activityLog->delete();
        $deletedCount++;
    } else {
        // Keep activity logs for completed/cancelled/failed deposits
        $keptCount++;
    }
}

echo "========================================\n";
echo "Cleanup complete!\n";
echo "========================================\n";
echo "Deleted: {$deletedCount} pending deposit activity logs\n";
echo "Kept: {$keptCount} completed deposit activity logs\n";
echo "\n";
