<?php

/**
 * Check User Profit Growth Calculation
 * 
 * This script checks a user's profit growth account to verify
 * if funds have increased correctly based on the 2.8% daily growth.
 * 
 * Usage: php check_user_profit_growth.php <username>
 */

require __DIR__ . '/backend/vendor/autoload.php';

$app = require_once __DIR__ . '/backend/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\ProfitGrowth;
use Carbon\Carbon;

$username = $argv[1] ?? 'ellakate2813';

echo "========================================\n";
echo "Checking Profit Growth for User: {$username}\n";
echo "========================================\n\n";

// Find user
$user = User::where('username', $username)->first();

if (!$user) {
    echo "❌ User '{$username}' not found!\n";
    exit(1);
}

echo "User Information:\n";
echo "  - ID: {$user->id}\n";
echo "  - Name: {$user->name}\n";
echo "  - Username: {$user->username}\n";
echo "  - Email: {$user->email}\n";
echo "  - Account Created: " . $user->created_at->format('Y-m-d H:i:s') . "\n";
echo "  - Real Balance: $" . number_format($user->real_balance, 2) . "\n";
echo "  - Simulated Balance: $" . number_format($user->simulated_balance, 2) . "\n";
echo "\n";

// Get profit growth accounts
$profitGrowths = ProfitGrowth::where('user_id', $user->id)
    ->whereIn('status', ['active', 'paused'])
    ->get();

if ($profitGrowths->isEmpty()) {
    echo "❌ No active profit growth accounts found for this user.\n";
    exit(0);
}

echo "Profit Growth Accounts:\n";
echo str_repeat("=", 80) . "\n\n";

foreach ($profitGrowths as $pg) {
    echo "Profit Growth ID: {$pg->id}\n";
    echo "  - Name: {$pg->name}\n";
    echo "  - Status: {$pg->status}\n";
    echo "  - Initial Balance: $" . number_format($pg->initial_balance, 2) . "\n";
    echo "  - Current Balance: $" . number_format($pg->current_balance, 2) . "\n";
    echo "  - Growth Percentage: {$pg->growth_percentage}%\n";
    echo "  - Growth Frequency: {$pg->growth_frequency}\n";
    echo "  - Locked Amount: $" . number_format($pg->locked_amount ?? 0, 2) . "\n";
    echo "  - Lock Until Date: " . ($pg->lock_until_date ? $pg->lock_until_date->format('Y-m-d H:i:s') : 'N/A') . "\n";
    echo "  - Last Transfer Date: " . ($pg->last_transfer_date ? $pg->last_transfer_date->format('Y-m-d H:i:s') : 'N/A') . "\n";
    echo "  - Last Growth Calculated: " . ($pg->last_growth_calculated_at ? $pg->last_growth_calculated_at->format('Y-m-d H:i:s') : 'Never') . "\n";
    echo "  - Next Growth Calculation: " . ($pg->next_growth_calculation_at ? $pg->next_growth_calculation_at->format('Y-m-d H:i:s') : 'N/A') . "\n";
    echo "  - Created: " . $pg->created_at->format('Y-m-d H:i:s') . "\n";
    echo "\n";
    
    // Calculate growth
    $initialBalance = floatval($pg->initial_balance);
    $currentBalance = floatval($pg->current_balance);
    $totalGrowth = $currentBalance - $initialBalance;
    $growthPercent = $initialBalance > 0 ? (($totalGrowth / $initialBalance) * 100) : 0;
    
    echo "  Growth Analysis:\n";
    echo "    - Total Growth: $" . number_format($totalGrowth, 2) . "\n";
    echo "    - Growth Percentage: " . number_format($growthPercent, 2) . "%\n";
    echo "\n";
    
    // If it's 20% weekly, calculate expected growth
    if ($pg->growth_percentage == 20.0 && $pg->growth_frequency === 'weekly') {
        $dailyRate = 20.0 / 7; // 2.857% per day
        
        // Calculate days since creation or last transfer
        $startDate = $pg->last_transfer_date ?? $pg->created_at;
        $daysSinceStart = now()->diffInDays($startDate);
        
        // Calculate expected balance using compound interest
        $expectedBalance = $initialBalance * pow(1 + ($dailyRate / 100), $daysSinceStart);
        $expectedGrowth = $expectedBalance - $initialBalance;
        
        echo "  Expected Growth (2.8% daily):\n";
        echo "    - Days since start: {$daysSinceStart} days\n";
        echo "    - Expected Balance: $" . number_format($expectedBalance, 2) . "\n";
        echo "    - Expected Growth: $" . number_format($expectedGrowth, 2) . "\n";
        echo "    - Actual Balance: $" . number_format($currentBalance, 2) . "\n";
        echo "    - Difference: $" . number_format($currentBalance - $expectedBalance, 2) . "\n";
        
        if (abs($currentBalance - $expectedBalance) < 0.01) {
            echo "    ✅ Growth is correct!\n";
        } else {
            echo "    ⚠️  Growth may need recalculation\n";
        }
        echo "\n";
    }
    
    // Check if growth is due
    if ($pg->next_growth_calculation_at) {
        $isDue = now()->gte($pg->next_growth_calculation_at);
        echo "  Growth Calculation Status:\n";
        echo "    - Next calculation due: " . $pg->next_growth_calculation_at->format('Y-m-d H:i:s') . "\n";
        echo "    - Is due now: " . ($isDue ? "✅ YES" : "❌ NO") . "\n";
        if ($isDue) {
            echo "    - ⚠️  Growth calculation is overdue!\n";
        }
        echo "\n";
    }
    
    echo str_repeat("-", 80) . "\n\n";
}

echo "========================================\n";
echo "Analysis Complete\n";
echo "========================================\n";
