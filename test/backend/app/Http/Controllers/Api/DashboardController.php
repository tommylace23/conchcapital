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
