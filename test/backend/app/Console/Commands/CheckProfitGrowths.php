<?php

namespace App\Console\Commands;

use App\Models\ProfitGrowth;
use Illuminate\Console\Command;

class CheckProfitGrowths extends Command
{
    protected $signature = 'profit-growth:check';
    protected $description = 'Check all profit growths and their settings';

    public function handle()
    {
        $this->info('=== PROFIT GROWTH STATUS REPORT ===');
        $this->newLine();

        $profitGrowths = ProfitGrowth::all();
        $this->info("Total Profit Growths: " . $profitGrowths->count());
        $this->newLine();

        if ($profitGrowths->count() > 0) {
            $this->table(
                ['ID', 'User ID', 'Name', 'Balance', 'Growth %', 'Frequency', 'Status', 'Lock Until', 'Locked Amount'],
                $profitGrowths->map(function ($pg) {
                    return [
                        $pg->id,
                        $pg->user_id,
                        substr($pg->name ?? 'N/A', 0, 20),
                        number_format($pg->current_balance ?? $pg->balance ?? 0, 2),
                        $pg->growth_percentage . '%',
                        $pg->growth_frequency,
                        $pg->status,
                        $pg->lock_until_date ? $pg->lock_until_date->format('Y-m-d') : 'N/A',
                        number_format($pg->locked_amount ?? 0, 2),
                    ];
                })->toArray()
            );

            $this->newLine();
            $this->info('Summary by Growth Frequency:');
            $byFrequency = $profitGrowths->groupBy('growth_frequency');
            foreach ($byFrequency as $freq => $pgs) {
                $this->line("  {$freq}: " . $pgs->count() . " profit growth(s)");
            }

            $this->newLine();
            $this->info('Summary by Growth Percentage:');
            $byPercentage = $profitGrowths->groupBy('growth_percentage');
            foreach ($byPercentage as $pct => $pgs) {
                $this->line("  {$pct}%: " . $pgs->count() . " profit growth(s)");
            }

            $this->newLine();
            $this->info('Summary by Status:');
            $byStatus = $profitGrowths->groupBy('status');
            foreach ($byStatus as $status => $pgs) {
                $this->line("  {$status}: " . $pgs->count() . " profit growth(s)");
            }

            // Check if all active/paused are 20% weekly
            $this->newLine();
            $this->info('=== VERIFICATION ===');
            $activePaused = $profitGrowths->whereIn('status', ['active', 'paused']);
            $not20Weekly = $activePaused->filter(function($pg) {
                return $pg->growth_percentage != 20.0 || $pg->growth_frequency != 'weekly';
            });

            if ($not20Weekly->count() == 0) {
                $this->info('✅ All active/paused profit growths are set to 20% weekly');
            } else {
                $this->warn('⚠️  Found ' . $not20Weekly->count() . ' active/paused profit growth(s) NOT set to 20% weekly:');
                foreach ($not20Weekly as $pg) {
                    $this->line("   - ID {$pg->id} (User {$pg->user_id}): {$pg->growth_percentage}% {$pg->growth_frequency}");
                }
            }

            // Check calculation logic
            $this->newLine();
            $this->info('=== CALCULATION LOGIC VERIFICATION ===');
            $this->line('For 20% weekly growth during 7-day lock period:');
            $this->line('  - Daily growth percentage: ' . number_format(20.0 / 7, 3) . '% (≈2.857%)');
            $this->line('  - This is calculated incrementally each day during the lock period');
            $this->line('  - After lock period ends, growth switches to weekly frequency');
        } else {
            $this->warn('No profit growths found in the database.');
        }

        $this->newLine();
        $this->info('=== END OF REPORT ===');
        return 0;
    }
}
