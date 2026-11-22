<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Transaction;
use Illuminate\Console\Command;

class CleanupPendingDepositActivityLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activity-logs:cleanup-pending-deposits';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove activity logs for pending deposits (created before the fix)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Cleaning up pending deposit activity logs...');

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
                $this->line("Deleting activity log for pending deposit: Transaction ID {$transaction->id} (User ID: {$activityLog->user_id}, Amount: {$activityLog->amount})");
                $activityLog->delete();
                $deletedCount++;
            } else {
                // Keep activity logs for completed/cancelled/failed deposits
                $keptCount++;
            }
        }

        $this->info("Cleanup complete!");
        $this->line("Deleted: {$deletedCount} pending deposit activity logs");
        $this->line("Kept: {$keptCount} completed deposit activity logs");

        return 0;
    }
}
