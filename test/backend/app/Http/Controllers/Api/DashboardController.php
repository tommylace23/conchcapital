<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get dashboard data (balances, recent transactions, etc.)
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get recent transactions (last 10)
        $recentTransactions = $user->transactions()
            ->latest()
            ->limit(10)
            ->get();

        // Get recent activity (last 10)
        $recentActivity = $user->activityLogs()
            ->latest()
            ->limit(10)
            ->get();

        // Get total profit growths count
        $profitGrowthsCount = $user->profitGrowths()->count();
        $activeProfitGrowthsCount = $user->profitGrowths()->where('status', 'active')->count();
        
        // Calculate total profit growth balance (sum of all profit growths' current_balance)
        // Include all profit growths (active, paused, etc.) but not cancelled or completed
        $totalProfitGrowthBalance = $user->profitGrowths()
            ->whereIn('status', ['active', 'paused'])
            ->sum('current_balance');
        
        // Growth earnings summary (drives the performance strip on the dashboard)
        $earnedTotal = (float) $user->transactions()->where('type', 'growth')->sum('amount');
        $earned7d = (float) $user->transactions()
            ->where('type', 'growth')
            ->where('created_at', '>=', now()->subDays(7))
            ->sum('amount');
        $lockedTotal = (float) $user->profitGrowths()
            ->whereIn('status', ['active', 'paused'])
            ->sum('locked_amount');
        $nextCreditAt = $user->profitGrowths()
            ->where('status', 'active')
            ->whereNotNull('next_growth_calculation_at')
            ->min('next_growth_calculation_at');

        $growthStats = [
            'earned_total' => round($earnedTotal, 2),
            'earned_last_7_days' => round($earned7d, 2),
            'available_to_withdraw' => round(max(0, (float) $totalProfitGrowthBalance - $lockedTotal), 2),
            'next_credit_at' => $nextCreditAt ? \Carbon\Carbon::parse($nextCreditAt)->toIso8601String() : null,
        ];

        // Client standing: tier, progress to the next tier and earning streak
        $portfolio = (float) $user->real_balance + (float) $totalProfitGrowthBalance;

        $tiers = [
            ['key' => 'bronze', 'label' => 'Bronze', 'min' => 0,      'perk' => 'Standard settlement'],
            ['key' => 'silver', 'label' => 'Silver', 'min' => 50000,  'perk' => 'Priority settlement'],
            ['key' => 'gold',   'label' => 'Gold',   'min' => 250000, 'perk' => 'Dedicated adviser · priority settlement'],
        ];

        $currentTier = $tiers[0];
        $nextTier = null;
        foreach ($tiers as $i => $tier) {
            if ($portfolio >= $tier['min']) {
                $currentTier = $tier;
                $nextTier = $tiers[$i + 1] ?? null;
            }
        }

        // Consecutive days with growth credited
        $growthDays = $user->transactions()
            ->where('type', 'growth')
            ->selectRaw('DATE(created_at) as d')
            ->distinct()
            ->orderByDesc('d')
            ->limit(400)
            ->pluck('d')
            ->all();

        $streak = 0;
        $cursor = now()->startOfDay();
        $daySet = array_flip($growthDays);
        for ($i = 0; $i < 400; $i++) {
            $key = $cursor->toDateString();
            if (isset($daySet[$key])) {
                $streak++;
                $cursor->subDay();
                continue;
            }
            // allow today to be missing if the credit has not run yet
            if ($i === 0) { $cursor->subDay(); continue; }
            break;
        }

        $status = [
            'tier' => $currentTier['key'],
            'tier_label' => $currentTier['label'],
            'perk' => $currentTier['perk'],
            'next_tier' => $nextTier['label'] ?? null,
            'amount_to_next' => $nextTier ? round(max(0, $nextTier['min'] - $portfolio), 2) : null,
            'progress_pct' => $nextTier
                ? round(min(100, max(0, (($portfolio - $currentTier['min']) / max(1, $nextTier['min'] - $currentTier['min'])) * 100)), 1)
                : 100,
            'streak_days' => $streak,
        ];

        // Legacy: also get simulations count for backward compatibility
        $simulationsCount = $user->simulations()->count();
        $activeSimulationsCount = $user->simulations()->where('status', 'active')->count();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'display_name' => $user->display_name,
                'avatar_url' => $user->avatar_url,
                'real_balance' => $user->real_balance,
                'simulated_balance' => $user->simulated_balance,
                'balance_currency' => $user->balance_currency,
            ],
            'recent_transactions' => $recentTransactions,
            'recent_activity' => $recentActivity,
            'growth_stats' => $growthStats,
            'status' => $status,
            'statistics' => [
                'total_profit_growths' => $profitGrowthsCount,
                'active_profit_growths' => $activeProfitGrowthsCount,
                'total_profit_growth_balance' => $totalProfitGrowthBalance,
                // Legacy: keep for backward compatibility
                'total_simulations' => $simulationsCount,
                'active_simulations' => $activeSimulationsCount,
            ],
        ]);
    }
}
