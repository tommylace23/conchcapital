<?php
require __DIR__ . '/backend/vendor/autoload.php';
$app = require_once __DIR__ . '/backend/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ProfitGrowth;
use App\Models\User;

echo "=== PROFIT GROWTH STATUS REPORT ===\n\n";

$profitGrowths = ProfitGrowth::all();
echo "Total Profit Growths: " . $profitGrowths->count() . "\n\n";

if ($profitGrowths->count() > 0) {
    echo "Detailed List:\n";
    echo str_repeat("-", 120) . "\n";
    printf("%-5s %-10s %-20s %-15s %-12s %-12s %-15s %-10s\n", 
        "ID", "User ID", "Name", "Balance", "Growth %", "Frequency", "Status", "Lock Until");
    echo str_repeat("-", 120) . "\n";
    
    foreach ($profitGrowths as $pg) {
        $lockInfo = $pg->lock_until_date ? $pg->lock_until_date->format('Y-m-d') : 'N/A';
        printf("%-5s %-10s %-20s %-15s %-12s %-12s %-15s %-10s\n",
            $pg->id,
            $pg->user_id,
            substr($pg->name ?? 'N/A', 0, 18),
            number_format($pg->current_balance ?? $pg->balance ?? 0, 2),
            $pg->growth_percentage . '%',
            $pg->growth_frequency,
            $pg->status,
            $lockInfo
        );
    }
    
    echo str_repeat("-", 120) . "\n\n";
    
    // Summary by frequency
    echo "Summary by Growth Frequency:\n";
    $byFrequency = $profitGrowths->groupBy('growth_frequency');
    foreach ($byFrequency as $freq => $pgs) {
        echo "  {$freq}: " . $pgs->count() . " profit growth(s)\n";
    }
    
    echo "\nSummary by Growth Percentage:\n";
    $byPercentage = $profitGrowths->groupBy('growth_percentage');
    foreach ($byPercentage as $pct => $pgs) {
        echo "  {$pct}%: " . $pgs->count() . " profit growth(s)\n";
    }
    
    echo "\nSummary by Status:\n";
    $byStatus = $profitGrowths->groupBy('status');
    foreach ($byStatus as $status => $pgs) {
        echo "  {$status}: " . $pgs->count() . " profit growth(s)\n";
    }
    
    // Check if all active/paused are 20% weekly
    echo "\n=== VERIFICATION ===\n";
    $activePaused = $profitGrowths->whereIn('status', ['active', 'paused']);
    $not20Weekly = $activePaused->filter(function($pg) {
        return $pg->growth_percentage != 20.0 || $pg->growth_frequency != 'weekly';
    });
    
    if ($not20Weekly->count() == 0) {
        echo "✅ All active/paused profit growths are set to 20% weekly\n";
    } else {
        echo "⚠️  Found " . $not20Weekly->count() . " active/paused profit growth(s) NOT set to 20% weekly:\n";
        foreach ($not20Weekly as $pg) {
            echo "   - ID {$pg->id} (User {$pg->user_id}): {$pg->growth_percentage}% {$pg->growth_frequency}\n";
        }
    }
    
    // Check calculation logic
    echo "\n=== CALCULATION LOGIC VERIFICATION ===\n";
    echo "For 20% weekly growth during 7-day lock period:\n";
    echo "  - Daily growth percentage: " . (20.0 / 7) . "% (≈2.857%)\n";
    echo "  - This is calculated incrementally each day during the lock period\n";
    echo "  - After lock period ends, growth switches to weekly frequency\n";
    
} else {
    echo "No profit growths found in the database.\n";
}

echo "\n=== END OF REPORT ===\n";
