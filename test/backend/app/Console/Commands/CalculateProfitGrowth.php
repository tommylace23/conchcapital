<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\ProfitGrowth;
use Illuminate\Console\Command;

class CalculateProfitGrowth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'profit-growth:calculate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate growth for all active profit growths that are due';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting profit growth calculation...');

        // Get all profit growths that are due for calculation
        $profitGrowths = ProfitGrowth::where('status', 'active')
            ->where('auto_calculate', true)
            ->where('growth_percentage', '>', 0)
            ->where('next_growth_calculation_at', '<=', now())
            ->get();

        if ($profitGrowths->isEmpty()) {
            $this->info('No profit growths due for calculation.');
            return 0;
        }

        $calculatedCount = 0;
        $totalGrowth = 0;

        foreach ($profitGrowths as $pg) {
            $oldBalance = $pg->current_balance;
            
            // Check if this is weekly frequency with 20% growth (2.8% daily growth)
            // This applies to ALL funds regardless of whether they were transferred by user or admin
            $isWeekly20Percent = $pg->growth_frequency === 'weekly' && $pg->growth_percentage == 20.0;
            
            // Check if funds are still locked (for user transfers)
            $isLocked = $pg->lock_until_date && now()->lt($pg->lock_until_date);
            
            if ($isWeekly20Percent) {
                // For 20% weekly growth: calculate 2.8% daily on ALL funds (current_balance)
                // This applies to both user transfers and admin deposits
                $dailyGrowthPercentage = 20.0 / 7; // 2.857% per day
                
                // Calculate growth on full current balance (all funds grow daily)
                $growthAmount = $oldBalance * ($dailyGrowthPercentage / 100);
                $newBalance = $oldBalance + $growthAmount;
                
                // Update profit growth
                $pg->current_balance = $newBalance;
                $pg->last_growth_calculated_at = now();
                $pg->next_growth_calculation_at = now()->addDay()->startOfDay(); // Calculate daily
                
                // If lock period is over, unlock the funds (but continue daily growth)
                if ($isLocked && now()->gte($pg->lock_until_date)) {
                    $pg->locked_amount = 0;
                    $pg->lock_until_date = null;
                }
            } elseif ($isLocked && $pg->locked_amount > 0) {
                // During 7-day lock period for other percentages: calculate incremental daily growth
                // Use the admin-set percentage (could be 50%, etc.)
                // Calculate daily growth: total percentage / 7 days
                $totalGrowthPercentage = $pg->growth_percentage; // Admin-set percentage (e.g., 50%)
                $dailyGrowthPercentage = $totalGrowthPercentage / 7; // Divide by 7 days
                
                // Calculate growth on locked amount
                $growthAmount = $pg->locked_amount * ($dailyGrowthPercentage / 100);
                $newBalance = $oldBalance + $growthAmount;
                
                // Update profit growth
                $pg->current_balance = $newBalance;
                $pg->last_growth_calculated_at = now();
                $pg->next_growth_calculation_at = now()->addDay()->startOfDay(); // Calculate daily during lock
                
                // If lock period is over, unlock the funds
                if (now()->gte($pg->lock_until_date)) {
                    $pg->locked_amount = 0;
                    $pg->lock_until_date = null;
                    // After lock period, switch to normal frequency-based calculation
                    $pg->next_growth_calculation_at = $this->calculateNextGrowthDate($pg->growth_frequency);
                }
            } else {
                // Normal growth calculation (after lock period or for unlocked funds with other frequencies)
                $growthAmount = $oldBalance * ($pg->growth_percentage / 100);
                $newBalance = $oldBalance + $growthAmount;
                
                // Update profit growth
                $pg->current_balance = $newBalance;
                $pg->last_growth_calculated_at = now();
                $pg->next_growth_calculation_at = $this->calculateNextGrowthDate($pg->growth_frequency);
            }
            
            $pg->save();

            // Create activity log
            ActivityLog::create([
                'user_id' => $pg->user_id,
                'action' => 'profit_growth_calculated',
                'fund_type' => 'simulated',
                'entity_type' => ProfitGrowth::class,
                'entity_id' => $pg->id,
                'amount' => $growthAmount,
                'currency' => $pg->currency ?? 'USD',
                'description' => "Auto-calculated growth: {$pg->growth_percentage}% on {$pg->name}. Balance: {$oldBalance} → {$newBalance}",
            ]);

            $calculatedCount++;
            $totalGrowth += $growthAmount;

            $this->line("Calculated growth for: {$pg->name} (User ID: {$pg->user_id}) - {$oldBalance} → {$newBalance}");
        }

        $this->info("Calculation complete! Processed {$calculatedCount} profit growth(s). Total growth: {$totalGrowth}");
        return 0;
    }

    /**
     * Calculate next growth calculation date based on frequency
     */
    private function calculateNextGrowthDate(string $frequency): \Carbon\Carbon
    {
        $now = now();
        return match($frequency) {
            'test' => $now->addMinutes(5), // 5 minutes for testing
            'daily' => $now->addDay()->startOfDay(),
            'weekly' => $now->addWeek()->startOfWeek(),
            'monthly' => $now->addMonth()->startOfMonth(),
            'quarterly' => $now->addQuarter()->startOfQuarter(),
            'yearly' => $now->addYear()->startOfYear(),
            default => $now->addDay()->startOfDay(),
        };
    }
}
