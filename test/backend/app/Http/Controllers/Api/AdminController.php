<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\CreditNotificationEmail;
use App\Models\AdminAction;
use App\Models\ActivityLog;
use App\Models\ProfitGrowth;
use App\Models\ProfileViewRequest;
use App\Models\SimulatedTransfer;
use App\Models\Simulation;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class AdminController extends Controller
{
    /**
     * Check if the authenticated user is an admin
     */
    private function ensureAdmin(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->is_admin) {
            abort(403, 'Unauthorized. Admin access required.');
        }
        return $user;
    }

    /**
     * Log an admin action
     */
    private function logAdminAction(Request $request, $action, $targetUserId = null, $entityType = null, $entityId = null, $description = null, $metadata = [])
    {
        AdminAction::create([
            'admin_id' => $request->user()->id,
            'target_user_id' => $targetUserId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description ?? $action,
            'metadata' => $metadata,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    /**
     * List all users
     */
    public function getUsers(Request $request)
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'is_frozen' => 'nullable|boolean',
            'is_admin' => 'nullable|boolean',
            'sort' => 'nullable|in:created_at,real_balance,username,last_active_at',
            'direction' => 'nullable|in:asc,desc',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = User::query();

        if (isset($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        if (isset($validated['is_frozen'])) {
            $query->where('is_frozen', $validated['is_frozen']);
        }

        if (isset($validated['is_admin'])) {
            $query->where('is_admin', $validated['is_admin']);
        }

        if (! empty($validated['date_from'])) {
            $query->whereDate('created_at', '>=', $validated['date_from']);
        }

        if (! empty($validated['date_to'])) {
            $query->whereDate('created_at', '<=', $validated['date_to']);
        }

        $perPage = $validated['per_page'] ?? 20;

        if (! empty($validated['sort'])) {
            $query->orderBy($validated['sort'], $validated['direction'] ?? 'desc');
        } else {
            $query->latest();
        }

        $users = $query->paginate($perPage);

        return response()->json($users);
    }

    /**
     * Get a single user with details
     */
    public function getUser(Request $request, $id)
    {
        $this->ensureAdmin($request);

        $user = User::with([
            'transactions' => function ($q) {
                $q->latest()->limit(10);
            },
            'profitGrowths' => function ($q) {
                $q->latest()->limit(20);
            },
            'simulations' => function ($q) {
                $q->latest()->limit(10);
            },
            'activityLogs' => function ($q) {
                $q->latest()->limit(10);
            },
        ])->findOrFail($id);

        // Calculate total profit growth balance (sum of all profit growths' current_balance)
        $totalProfitGrowthBalance = $user->profitGrowths()
            ->whereIn('status', ['active', 'paused'])
            ->sum('current_balance');
        
        // Get statistics
        $stats = [
            'total_transactions' => $user->transactions()->count(),
            'total_simulations' => $user->simulations()->count(),
            'total_profit_growths' => $user->profitGrowths()->count(),
            'active_profit_growths' => $user->profitGrowths()->where('status', 'active')->count(),
            'total_profit_growth_balance' => $totalProfitGrowthBalance,
            'total_sent_transfers' => $user->sentSimulatedTransfers()->count(),
            'total_received_transfers' => $user->receivedSimulatedTransfers()->count(),
            'total_activity_logs' => $user->activityLogs()->count(),
        ];

        $referrer = $user->referred_by ? $user->referredBy : null;

        return response()->json([
            'user' => $user,
            'statistics' => $stats,
            'referral_stats' => [
                'referral_code' => $user->referral_code,
                'referral_bonus_amount' => $user->referral_bonus_amount,
                'referred_by' => $user->referred_by,
                'referred_by_name' => $referrer?->display_name ?? $referrer?->username,
                'total_referrals' => $user->referrals()->count(),
                'total_bonus_awarded' => (float) $user->referrals()->sum('bonus_amount'),
                'default_bonus' => (float) config('referral.default_bonus', 0),
            ],
            'security' => [
                'last_login_at' => $user->last_login_at?->toIso8601String(),
                'last_login_ip' => $user->last_login_ip,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'two_factor_enabled' => (bool) $user->two_factor_enabled,
                'created_at' => $user->created_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Credit funds to a user account
     */
    public function creditFunds(Request $request)
    {
        $admin = $this->ensureAdmin($request);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01|max:999999999.99',
            'fund_type' => 'nullable|in:real,simulated',
            'direction' => 'nullable|in:credit,debit',
            'currency' => 'nullable|string|size:3',
            'reason' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:500',
        ]);

        // Set defaults if not provided
        $validated['fund_type'] = $validated['fund_type'] ?? 'real';
        $validated['currency'] = $validated['currency'] ?? 'USD';
        $validated['direction'] = $validated['direction'] ?? 'credit';
        $isDebit = $validated['direction'] === 'debit';

        $targetUser = User::findOrFail($validated['user_id']);

        if ($targetUser->is_frozen) {
            return response()->json([
                'message' => 'Account is frozen. Unfreeze it before adjusting funds.',
            ], 403);
        }

        // A debit may not exceed the balance held in the selected wallet
        if ($isDebit) {
            $available = $validated['fund_type'] === 'real'
                ? $targetUser->real_balance
                : $targetUser->simulated_balance;

            if ($available < $validated['amount']) {
                return response()->json([
                    'message' => 'Insufficient ' . $validated['fund_type'] . ' balance for this debit.',
                ], 422);
            }
        }

        return DB::transaction(function () use ($request, $admin, $targetUser, $validated, $isDebit) {
            // Create transaction record
            $transaction = Transaction::create([
                'user_id' => $targetUser->id,
                'type' => $isDebit ? 'debit' : 'credit',
                'fund_type' => $validated['fund_type'],
                'amount' => $validated['amount'],
                'currency' => $validated['currency'] ?? 'USD',
                'status' => 'completed',
                'description' => $validated['description'] ?? ($validated['reason'] ?? ($isDebit ? 'Debit' : 'Credit')),
                'reference' => ($isDebit ? 'ADM-DB-' : 'ADM-CR-') . strtoupper(uniqid()),
                'metadata' => [
                    'admin_id' => $admin->id,
                    'admin_name' => $admin->name,
                    'reason' => $validated['reason'] ?? null,
                ],
                'processed_at' => now(),
            ]);

            // Update user balance
            $column = $validated['fund_type'] === 'real' ? 'real_balance' : 'simulated_balance';
            if ($isDebit) {
                $targetUser->decrement($column, $validated['amount']);
            } else {
                $targetUser->increment($column, $validated['amount']);
            }
            $targetUser->save();

            // Create activity log for user
            ActivityLog::create([
                'user_id' => $targetUser->id,
                'action' => $isDebit ? 'admin_debit' : 'admin_credit',
                'fund_type' => $validated['fund_type'],
                'entity_type' => Transaction::class,
                'entity_id' => $transaction->id,
                'amount' => $validated['amount'],
                'currency' => $validated['currency'] ?? 'USD',
                'description' => "{$validated['amount']} " . ($validated['currency'] ?? 'USD') . " ({$validated['fund_type']} funds)",
                'metadata' => [
                    'admin_id' => $admin->id,
                    'reason' => $validated['reason'] ?? null,
                ],
            ]);

            // Log admin action
            $this->logAdminAction(
                $request,
                $isDebit ? 'debit_funds' : 'credit_funds',
                $targetUser->id,
                Transaction::class,
                $transaction->id,
                ($isDebit ? 'Debited ' : 'Credited ') . "{$validated['amount']} " . ($validated['currency'] ?? 'USD') . " ({$validated['fund_type']}) to {$targetUser->name}",
                [
                    'amount' => $validated['amount'],
                    'direction' => $validated['direction'],
                    'fund_type' => $validated['fund_type'],
                    'currency' => $validated['currency'] ?? 'USD',
                    'reason' => $validated['reason'] ?? null,
                ]
            );

            // Send email notification to user (without showing the amount)
            try {
                Mail::to($targetUser->email)->send(new CreditNotificationEmail(
                    $targetUser,
                    $validated['fund_type'],
                    $validated['currency'] ?? 'USD',
                    $validated['reason'] ?? null
                ));
            } catch (\Exception $e) {
                // Log email error but don't fail the credit transaction
                \Log::error('Failed to send credit notification email: ' . $e->getMessage());
            }

            return response()->json([
                'transaction' => $transaction->fresh(),
                'user' => $targetUser->fresh(),
            ], 201);
        });
    }

    /**
     * Freeze a user account
     */
    public function freezeAccount(Request $request, $id)
    {
        $admin = $this->ensureAdmin($request);

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $targetUser = User::findOrFail($id);

        // Can't freeze yourself
        if ($targetUser->id === $admin->id) {
            return response()->json([
                'message' => 'Cannot freeze your own account.',
            ], 422);
        }

        // Can't freeze other admins
        if ($targetUser->is_admin) {
            return response()->json([
                'message' => 'Cannot freeze admin accounts.',
            ], 422);
        }

        if ($targetUser->is_frozen) {
            return response()->json([
                'message' => 'Account is already frozen.',
            ], 422);
        }

        $targetUser->is_frozen = true;
        $targetUser->frozen_at = now();
        $targetUser->freeze_reason = $validated['reason'];
        $targetUser->save();

        // Log admin action
        $this->logAdminAction(
            $request,
            'freeze_account',
            $targetUser->id,
            User::class,
            $targetUser->id,
            "Froze account for {$targetUser->name}",
            [
                'reason' => $validated['reason'],
            ]
        );

        return response()->json([
            'message' => 'Account frozen successfully.',
            'user' => $targetUser->fresh(),
        ]);
    }

    /**
     * Set the referral bonus a new user receives when joining with this user's code.
     */
    public function setReferralBonus(Request $request, $id)
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'referral_bonus_amount' => 'nullable|numeric|min:0|max:1000000',
        ]);

        $targetUser = User::findOrFail($id);
        $oldBonus = $targetUser->referral_bonus_amount;
        $targetUser->referral_bonus_amount = $validated['referral_bonus_amount'] ?? null;
        $targetUser->save();

        $this->logAdminAction(
            $request,
            'set_referral_bonus',
            $targetUser->id,
            User::class,
            $targetUser->id,
            "Set referral bonus for {$targetUser->name} to " . ($targetUser->referral_bonus_amount ?? 'default'),
            [
                'old' => $oldBonus,
                'new' => $targetUser->referral_bonus_amount,
                'referral_code' => $targetUser->referral_code,
            ]
        );

        return response()->json([
            'message' => 'Referral bonus updated.',
            'user' => $targetUser->fresh(),
        ]);
    }

    /**
     * Unfreeze a user account
     */
    public function unfreezeAccount(Request $request, $id)
    {
        $admin = $this->ensureAdmin($request);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $targetUser = User::findOrFail($id);

        if (!$targetUser->is_frozen) {
            return response()->json([
                'message' => 'Account is not frozen.',
            ], 422);
        }

        $targetUser->is_frozen = false;
        $targetUser->frozen_at = null;
        $oldReason = $targetUser->freeze_reason;
        $targetUser->freeze_reason = null;
        $targetUser->save();

        // Log admin action
        $this->logAdminAction(
            $request,
            'unfreeze_account',
            $targetUser->id,
            User::class,
            $targetUser->id,
            "Unfroze account for {$targetUser->name}",
            [
                'previous_freeze_reason' => $oldReason,
                'unfreeze_reason' => $validated['reason'] ?? null,
            ]
        );

        return response()->json([
            'message' => 'Account unfrozen successfully.',
            'user' => $targetUser->fresh(),
        ]);
    }

    /**
     * Get all transactions (admin view)
     */
    public function getTransactions(Request $request)
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'type' => 'nullable|in:deposit,withdrawal,credit,debit',
            'fund_type' => 'nullable|in:real,simulated',
            'status' => 'nullable|in:pending,completed,failed,cancelled',
            'search' => 'nullable|string|max:255',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'min_amount' => 'nullable|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Transaction::with('user')->latest();

        if (isset($validated['user_id'])) {
            $query->where('user_id', $validated['user_id']);
        }

        if (isset($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        if (isset($validated['fund_type'])) {
            $query->where('fund_type', $validated['fund_type']);
        }

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('id', $search)
                  ->orWhereHas('user', function ($u) use ($search) {
                      $u->where('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                  });
            });
        }

        if (! empty($validated['date_from'])) {
            $query->whereDate('created_at', '>=', $validated['date_from']);
        }

        if (! empty($validated['date_to'])) {
            $query->whereDate('created_at', '<=', $validated['date_to']);
        }

        if (isset($validated['min_amount'])) {
            $query->where('amount', '>=', $validated['min_amount']);
        }

        if (isset($validated['max_amount'])) {
            $query->where('amount', '<=', $validated['max_amount']);
        }

        $perPage = $validated['per_page'] ?? 20;
        $transactions = $query->paginate($perPage);

        return response()->json($transactions);
    }

    /**
     * Get admin reports/analytics
     */
    public function getReports(Request $request)
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $validated['start_date'] ?? now()->subDays(30);
        $endDate = $validated['end_date'] ?? now();

        // Total users
        $totalUsers = User::count();
        $activeUsers = User::where('last_active_at', '>=', now()->subDays(30))->count();
        $frozenUsers = User::where('is_frozen', true)->count();
        $adminUsers = User::where('is_admin', true)->count();

        // Transaction statistics
        $totalTransactions = Transaction::whereBetween('created_at', [$startDate, $endDate])->count();
        $totalDeposits = Transaction::where('type', 'deposit')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');
        $totalWithdrawals = Transaction::where('type', 'withdrawal')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');
        $totalCredits = Transaction::where('type', 'credit')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');

        // Simulation statistics
        $totalSimulations = Simulation::whereBetween('created_at', [$startDate, $endDate])->count();
        $activeSimulations = Simulation::where('status', 'active')->count();

        // Transfer statistics
        $totalTransfers = SimulatedTransfer::whereBetween('created_at', [$startDate, $endDate])->count();
        $totalTransferAmount = SimulatedTransfer::whereBetween('created_at', [$startDate, $endDate])->sum('amount');

        // Balance statistics
        $totalRealBalance = User::sum('real_balance');
        $totalSimulatedBalance = User::sum('simulated_balance');

        return response()->json([
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'users' => [
                'total' => $totalUsers,
                'active' => $activeUsers,
                'frozen' => $frozenUsers,
                'admins' => $adminUsers,
            ],
            'transactions' => [
                'total' => $totalTransactions,
                'total_deposits' => $totalDeposits,
                'total_withdrawals' => $totalWithdrawals,
                'total_credits' => $totalCredits,
            ],
            'simulations' => [
                'total' => $totalSimulations,
                'active' => $activeSimulations,
            ],
            'transfers' => [
                'total' => $totalTransfers,
                'total_amount' => $totalTransferAmount,
            ],
            'balances' => [
                'total_real' => $totalRealBalance,
                'total_simulated' => $totalSimulatedBalance,
            ],
        ]);
    }

    /**
     * Create a profit growth for a user (Admin only)
     */
    public function createProfitGrowth(Request $request)
    {
        $admin = $this->ensureAdmin($request);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'initial_balance' => 'required|numeric|min:0|max:999999999.99',
            'currency' => 'nullable|string|size:3',
            'growth_percentage' => 'required|numeric|min:1|max:100',
            'growth_frequency' => 'required|in:daily,weekly,monthly,quarterly,yearly,test',
            'strategy_data' => 'nullable|array',
        ]);

        $targetUser = User::findOrFail($validated['user_id']);

        if ($targetUser->is_frozen) {
            return response()->json([
                'message' => 'Target user account is frozen. Cannot create profit growth.',
            ], 403);
        }

        return DB::transaction(function () use ($request, $admin, $targetUser, $validated) {
            // For the standard weekly rate, grow daily, so set next calculation to tomorrow
            // For other frequencies, use the standard calculation date
            $weeklyRate = (float) config('growth.weekly_percent', 5.0);
            $isWeeklyDefault = $validated['growth_frequency'] === 'weekly' && abs((float) $validated['growth_percentage'] - $weeklyRate) < 0.01;
            $nextCalculation = $isWeeklyDefault 
                ? now()->addDay()->startOfDay() 
                : $this->calculateNextGrowthDate($validated['growth_frequency']);

            $profitGrowth = ProfitGrowth::create([
                'user_id' => $validated['user_id'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'initial_balance' => $validated['initial_balance'],
                'current_balance' => $validated['initial_balance'],
                'currency' => $validated['currency'] ?? 'USD',
                'status' => 'active',
                'growth_percentage' => $validated['growth_percentage'],
                'growth_frequency' => $validated['growth_frequency'],
                'auto_calculate' => true,
                'next_growth_calculation_at' => $nextCalculation,
                'strategy_data' => $validated['strategy_data'] ?? [],
                'started_at' => now(),
            ]);

            // Log admin action
            $this->logAdminAction($request, 'profit_growth_created', $targetUser->id, ProfitGrowth::class, $profitGrowth->id, "Created profit growth: {$validated['name']} for {$targetUser->name}");

            // Create activity log
            ActivityLog::create([
                'user_id' => $targetUser->id,
                'action' => 'profit_growth_created',
                'fund_type' => 'simulated',
                'entity_type' => ProfitGrowth::class,
                'entity_id' => $profitGrowth->id,
                'amount' => $validated['initial_balance'],
                'currency' => $validated['currency'] ?? 'USD',
                'description' => "Admin created profit growth: {$validated['name']}",
            ]);

            return response()->json([
                'profit_growth' => $profitGrowth->fresh(['user']),
            ], 201);
        });
    }

    /**
     * Manually trigger growth calculation for profit growths
     */
    public function triggerGrowthCalculation(Request $request)
    {
        $admin = $this->ensureAdmin($request);

        $validated = $request->validate([
            'profit_growth_id' => 'nullable|exists:profit_growths,id',
            'force' => 'nullable|boolean', // Force calculation even if not due
        ]);

        $result = $this->calculateGrowth($validated['profit_growth_id'] ?? null, $validated['force'] ?? false);

        // Log admin action
        $this->logAdminAction($request, 'growth_calculation_triggered', null, null, null, "Manually triggered growth calculation", [
            'profit_growth_id' => $validated['profit_growth_id'] ?? 'all',
            'force' => $validated['force'] ?? false,
            'calculated_count' => $result['calculated_count'] ?? 0,
        ]);

        return response()->json([
            'message' => 'Growth calculation completed.',
            'result' => $result,
        ]);
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

    /**
     * Calculate growth for profit growths
     */
    private function calculateGrowth(?int $profitGrowthId = null, bool $force = false): array
    {
        $query = ProfitGrowth::where('status', 'active')
            ->where('auto_calculate', true)
            ->where('growth_percentage', '>', 0);

        if ($profitGrowthId) {
            $query->where('id', $profitGrowthId);
        } elseif (!$force) {
            // Only calculate if next_growth_calculation_at is due
            $query->where('next_growth_calculation_at', '<=', now());
        }

        $profitGrowths = $query->get();
        $calculatedCount = 0;
        $totalGrowth = 0;

        foreach ($profitGrowths as $pg) {
            $oldBalance = $pg->current_balance;
            
            // Standard weekly growth is applied daily
            // This applies to ALL funds regardless of whether they were transferred by user or admin
            $weeklyRate = (float) config('growth.weekly_percent', 5.0);
            $isWeeklyDefault = $pg->growth_frequency === 'weekly' && abs((float) $pg->growth_percentage - $weeklyRate) < 0.01;
            
            // Check if funds are still locked (for user transfers)
            $isLocked = $pg->lock_until_date && now()->lt($pg->lock_until_date);
            
            if ($isWeeklyDefault) {
                // Standard weekly growth: apply the daily rate to ALL funds (current_balance)
                // This applies to both user transfers and admin deposits
                $dailyGrowthPercentage = \App\Support\GrowthRate::dailyPercent($weeklyRate);
                
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
                $totalGrowthPercentage = $pg->growth_percentage;
                $dailyGrowthPercentage = \App\Support\GrowthRate::dailyPercent((float) $totalGrowthPercentage);
                
                // Calculate growth on locked amount
                $growthAmount = $pg->locked_amount * ($dailyGrowthPercentage / 100);
                $newBalance = $oldBalance + $growthAmount;
                
                // Update profit growth
                $pg->current_balance = $newBalance;
                $pg->last_growth_calculated_at = now();
                $pg->next_growth_calculation_at = now()->addDay()->startOfDay();
                
                // If lock period is over, unlock the funds
                if (now()->gte($pg->lock_until_date)) {
                    $pg->locked_amount = 0;
                    $pg->lock_until_date = null;
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
                'description' => "Growth calculated: {$pg->growth_percentage}% on {$pg->name}. Balance: {$oldBalance} → {$newBalance}",
            ]);

            $calculatedCount++;
            $totalGrowth += $growthAmount;
        }

        return [
            'calculated_count' => $calculatedCount,
            'total_growth' => $totalGrowth,
        ];
    }

    /**
     * Update a profit growth (Admin only - can update any user's profit growth)
     */
    public function updateProfitGrowth(Request $request, $id)
    {
        $admin = $this->ensureAdmin($request);
        
        $profitGrowth = ProfitGrowth::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'status' => [
                'sometimes',
                \Illuminate\Validation\Rule::in(['active', 'paused', 'completed', 'cancelled']),
            ],
            'growth_percentage' => 'sometimes|numeric|min:1|max:100',
            'growth_frequency' => 'sometimes|in:daily,weekly,monthly,quarterly,yearly,test',
            'strategy_data' => 'nullable|array',
        ]);
        
        return DB::transaction(function () use ($admin, $profitGrowth, $validated, $request) {
            $oldStatus = $profitGrowth->status;
            $oldPercentage = $profitGrowth->growth_percentage;
            $oldFrequency = $profitGrowth->growth_frequency;
            
            $profitGrowth->fill($validated);
            
            // If growth_percentage or growth_frequency changed, update next calculation date
            if (isset($validated['growth_percentage']) || isset($validated['growth_frequency'])) {
                $frequency = $validated['growth_frequency'] ?? $profitGrowth->growth_frequency;
                $profitGrowth->next_growth_calculation_at = $this->calculateNextGrowthDate($frequency);
            }
            
            // If status changed to completed, set completed_at
            if (isset($validated['status']) && $validated['status'] === 'completed' && $oldStatus !== 'completed') {
                $profitGrowth->completed_at = now();
            }
            
            // If status changed from completed, clear completed_at
            if (isset($validated['status']) && $oldStatus === 'completed' && $validated['status'] !== 'completed') {
                $profitGrowth->completed_at = null;
            }
            
            $profitGrowth->save();
            
            // Log admin action
            $changes = [];
            if (isset($validated['growth_percentage']) && $validated['growth_percentage'] != $oldPercentage) {
                $changes['growth_percentage'] = "{$oldPercentage}% → {$validated['growth_percentage']}%";
            }
            if (isset($validated['growth_frequency']) && $validated['growth_frequency'] != $oldFrequency) {
                $changes['growth_frequency'] = "{$oldFrequency} → {$validated['growth_frequency']}";
            }
            
            $this->logAdminAction(
                $request,
                'profit_growth_updated',
                $profitGrowth->user_id,
                ProfitGrowth::class,
                $profitGrowth->id,
                "Updated profit growth: {$profitGrowth->name}" . (!empty($changes) ? ' (' . implode(', ', $changes) . ')' : ''),
                array_merge($validated, ['changes' => $changes])
            );
            
            return response()->json([
                'message' => 'Profit growth updated successfully.',
                'profit_growth' => $profitGrowth->fresh(),
            ]);
        });
    }
    
    /**
     * Delete (cancel) a profit growth (Admin only - can delete any user's profit growth)
     */
    public function deleteProfitGrowth(Request $request, $id)
    {
        $admin = $this->ensureAdmin($request);

        $profitGrowth = ProfitGrowth::findOrFail($id);

        // Can't delete completed profit growths (only cancel active/paused ones)
        if ($profitGrowth->status === 'completed') {
            return response()->json([
                'message' => 'Cannot delete a completed profit growth.',
            ], 422);
        }

        return DB::transaction(function () use ($request, $admin, $profitGrowth) {
            $profitGrowthName = $profitGrowth->name;
            $profitGrowthId = $profitGrowth->id;
            $userId = $profitGrowth->user_id;

            // Update status to cancelled instead of deleting
            $profitGrowth->status = 'cancelled';
            $profitGrowth->completed_at = now();
            $profitGrowth->save();

            // Log admin action
            $this->logAdminAction($request, 'profit_growth_deleted', $userId, ProfitGrowth::class, $profitGrowthId, "Admin deleted profit growth: {$profitGrowthName}");

            // Create activity log
            ActivityLog::create([
                'user_id' => $userId,
                'action' => 'profit_growth_cancelled',
                'fund_type' => 'simulated',
                'entity_type' => ProfitGrowth::class,
                'entity_id' => $profitGrowthId,
                'description' => "Admin cancelled profit growth: {$profitGrowthName}",
            ]);

            return response()->json([
                'message' => 'Profit growth deleted successfully.',
                'profit_growth' => $profitGrowth->fresh(),
            ]);
        });
    }

    /**
     * Delete a user account (Admin only)
     */
    public function deleteUser(Request $request, $id)
    {
        $admin = $this->ensureAdmin($request);

        $targetUser = User::findOrFail($id);

        // Can't delete yourself
        if ($targetUser->id === $admin->id) {
            return response()->json([
                'message' => 'Cannot delete your own account.',
            ], 422);
        }

        // Never leave the platform without an admin
        if ($targetUser->is_admin && User::where('is_admin', true)->count() <= 1) {
            return response()->json([
                'message' => 'Cannot delete the last remaining admin.',
            ], 422);
        }

        return DB::transaction(function () use ($request, $admin, $targetUser) {
            $userName = $targetUser->name;
            $userEmail = $targetUser->email;
            $userId = $targetUser->id;

            // Log admin action before deletion
            $this->logAdminAction(
                $request,
                'delete_user',
                $userId,
                User::class,
                $userId,
                "Deleted user account: {$userName} ({$userEmail})",
                [
                    'deleted_user_name' => $userName,
                    'deleted_user_email' => $userEmail,
                    'deleted_user_id' => $userId,
                ]
            );

            // Delete related data (cascade delete)
            // Note: Laravel will handle foreign key constraints if set up properly
            // We'll delete in order to respect foreign keys
            
            // Delete activity logs
            ActivityLog::where('user_id', $userId)->delete();
            
            // Delete simulated transfers (sent and received)
            SimulatedTransfer::where('from_user_id', $userId)->orWhere('to_user_id', $userId)->delete();
            
            // Cancel/delete profit growths
            ProfitGrowth::where('user_id', $userId)->update(['status' => 'cancelled', 'completed_at' => now()]);
            
            // Delete simulations (legacy)
            Simulation::where('user_id', $userId)->delete();
            
            // Delete transactions
            Transaction::where('user_id', $userId)->delete();
            
            // Delete profile view requests
            ProfileViewRequest::where('requester_id', $userId)
                ->orWhere('profile_owner_id', $userId)
                ->delete();
            
            // Delete admin actions where this user was the target

            // Finally, delete the user
            $targetUser->delete();

            return response()->json([
                'message' => 'User account deleted successfully.',
            ]);
        });
    }

    /**
     * Get all pending deposit transactions
     */
    public function getPendingDeposits(Request $request)
    {
        $this->ensureAdmin($request);

        $pendingDeposits = Transaction::where('type', 'deposit')
            ->where('status', 'pending')
            ->with('user:id,username,email,name,display_name')
            ->latest()
            ->get();

        return response()->json([
            'deposits' => $pendingDeposits,
            'count' => $pendingDeposits->count(),
        ]);
    }

    /**
     * Confirm a deposit transaction (manual confirmation)
     */
    public function confirmDeposit(Request $request, $id)
    {
        $admin = $this->ensureAdmin($request);

        $transaction = Transaction::where('type', 'deposit')
            ->where('status', 'pending')
            ->findOrFail($id);

        $user = $transaction->user;

        return DB::transaction(function () use ($request, $admin, $transaction, $user) {
            // Update transaction status
            $transaction->status = 'completed';
            $transaction->processed_at = now();
            $transaction->save();

            // Update user balance
            if ($transaction->fund_type === 'real') {
                $user->increment('real_balance', $transaction->amount);
            } else {
                $user->increment('simulated_balance', $transaction->amount);
            }
            $user->save();

            // Create activity log (only when deposit is confirmed, not when pending)
            // This prevents pending deposits from showing in recent activity
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'deposit',
                'fund_type' => $transaction->fund_type,
                'entity_type' => Transaction::class,
                'entity_id' => $transaction->id,
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'description' => "Deposit confirmed: {$transaction->amount} {$transaction->currency} ({$transaction->fund_type} funds)",
            ]);

            // Log admin action
            $this->logAdminAction(
                $request,
                'deposit_confirmed',
                $user->id,
                Transaction::class,
                $transaction->id,
                "Confirmed deposit: {$transaction->amount} {$transaction->currency} for user @{$user->username}"
            );

        return response()->json([
            'message' => 'Deposit confirmed successfully.',
            'transaction' => $transaction->fresh(),
            'user' => $user->fresh(),
        ]);
    });
}

    /**
     * Get all pending withdrawal transactions
     */
    public function getPendingWithdrawals(Request $request)
    {
        $this->ensureAdmin($request);

        $pendingWithdrawals = Transaction::where('type', 'withdrawal')
            ->where('status', 'pending')
            ->with('user:id,username,email,name,display_name')
            ->latest()
            ->get();

        return response()->json([
            'withdrawals' => $pendingWithdrawals,
            'count' => $pendingWithdrawals->count(),
        ]);
    }

    /**
     * Confirm a withdrawal transaction (after admin sends crypto to user)
     */
    public function confirmWithdrawal(Request $request, $id)
    {
        $admin = $this->ensureAdmin($request);

        $transaction = Transaction::where('type', 'withdrawal')
            ->where('status', 'pending')
            ->findOrFail($id);

        $user = $transaction->user;

        return DB::transaction(function () use ($request, $admin, $transaction, $user) {
            // Update transaction status
            $transaction->status = 'completed';
            $transaction->processed_at = now();
            $transaction->save();

            // Balance was already deducted when withdrawal was created, so no need to deduct again
            // The funds have been sent to the user's crypto address

            // Update activity log
            ActivityLog::where('entity_type', Transaction::class)
                ->where('entity_id', $transaction->id)
                ->update([
                    'description' => "Withdrawal completed: {$transaction->amount} {$transaction->currency} sent to crypto address",
                ]);

            // Log admin action
            $metadata = $transaction->metadata ?? [];
            $cryptoAddress = $metadata['crypto_address'] ?? 'N/A';
            $cryptoCurrency = $metadata['crypto_currency'] ?? 'N/A';
            
            $this->logAdminAction(
                $request,
                'withdrawal_confirmed',
                $user->id,
                Transaction::class,
                $transaction->id,
                "Confirmed withdrawal: {$transaction->amount} {$transaction->currency} for user @{$user->username} to {$cryptoCurrency} address {$cryptoAddress}",
                [
                    'crypto_address' => $cryptoAddress,
                    'crypto_currency' => $cryptoCurrency,
                    'crypto_amount' => $metadata['crypto_amount'] ?? null,
                ]
            );

            return response()->json([
                'message' => 'Withdrawal confirmed successfully.',
                'transaction' => $transaction->fresh(),
                'user' => $user->fresh(),
            ]);
        });
    }

    /**
     * Cancel a withdrawal transaction and refund user's balance
     */
    public function cancelWithdrawal(Request $request, $id)
    {
        $admin = $this->ensureAdmin($request);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $transaction = Transaction::where('type', 'withdrawal')
            ->where('status', 'pending')
            ->findOrFail($id);

        $user = $transaction->user;

        return DB::transaction(function () use ($request, $admin, $transaction, $user, $validated) {
            // Update transaction status
            $transaction->status = 'cancelled';
            $transaction->processed_at = now();
            $transaction->save();

            // Refund the user's balance (it was deducted when withdrawal was created)
            $user->increment('real_balance', $transaction->amount);
            $user->save();

            // Update activity log
            ActivityLog::where('entity_type', Transaction::class)
                ->where('entity_id', $transaction->id)
                ->update([
                    'description' => "Withdrawal cancelled: {$transaction->amount} {$transaction->currency} refunded. Reason: " . ($validated['reason'] ?? 'No reason provided'),
                ]);

            // Log admin action
            $this->logAdminAction(
                $request,
                'withdrawal_cancelled',
                $user->id,
                Transaction::class,
                $transaction->id,
                "Cancelled withdrawal: {$transaction->amount} {$transaction->currency} for user @{$user->username}",
                [
                    'reason' => $validated['reason'] ?? null,
                ]
            );

            return response()->json([
                'message' => 'Withdrawal cancelled and funds refunded successfully.',
                'transaction' => $transaction->fresh(),
                'user' => $user->fresh(),
            ]);
        });
    }

    /**
     * Create a withdrawal transaction on behalf of a user (Admin only)
     */
    public function createWithdrawal(Request $request)
    {
        $admin = $this->ensureAdmin($request);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01|max:999999999.99',
            'currency' => 'nullable|string|size:3',
            'description' => 'nullable|string|max:500',
            'metadata' => 'nullable|array',
        ]);

        $targetUser = User::findOrFail($validated['user_id']);

        // Check if account is frozen
        if ($targetUser->is_frozen) {
            return response()->json([
                'message' => 'Account is frozen. Cannot process transactions.',
            ], 403);
        }

        // Only real funds can be withdrawn
        if ($targetUser->real_balance < $validated['amount']) {
            return response()->json([
                'message' => 'Insufficient balance for withdrawal.',
            ], 422);
        }

        $feePercent = max(0, (float) config('banking.withdrawal_fee_percent', 0));
        $fee = round(((float) $validated['amount']) * $feePercent / 100, 2);
        $netAmount = round(((float) $validated['amount']) - $fee, 2);

        return DB::transaction(function () use ($request, $admin, $targetUser, $validated, $fee, $netAmount, $feePercent) {
            // Create transaction
            $transaction = Transaction::create([
                'user_id' => $targetUser->id,
                'type' => 'withdrawal',
                'fund_type' => 'real', // Only real funds can be withdrawn
                'amount' => $validated['amount'],
                'currency' => $validated['currency'] ?? 'USD',
                'status' => 'pending', // Withdrawals need approval/processing
                'description' => $validated['description'] ?? 'Withdrawal (Admin initiated)',
                'reference' => 'WD-' . strtoupper(uniqid()),
                'metadata' => array_merge($validated['metadata'] ?? [], [
                    'admin_initiated' => true,
                    'admin_id' => $admin->id,
                    'fee_percent' => $feePercent,
                    'fee_amount' => $fee,
                    'net_amount' => $netAmount,
                ]),
            ]);

            // Deduct from balance (even if pending, reserve the funds)
            $targetUser->decrement('real_balance', $validated['amount']);
            $targetUser->save();

            // Create activity log
            $currency = $validated['currency'] ?? 'USD';
            ActivityLog::create([
                'user_id' => $targetUser->id,
                'action' => 'withdrawal',
                'fund_type' => 'real',
                'entity_type' => Transaction::class,
                'entity_id' => $transaction->id,
                'amount' => $validated['amount'],
                'currency' => $currency,
                'description' => "Withdrawal request for {$validated['amount']} {$currency} (fee {$fee} {$currency}, net {$netAmount} {$currency})",
            ]);

            // Log admin action
            $this->logAdminAction(
                $request,
                'withdrawal_created',
                $targetUser->id,
                Transaction::class,
                $transaction->id,
                "Created withdrawal request: {$validated['amount']} {$currency} for user @{$targetUser->username}",
                [
                    'amount' => $validated['amount'],
                    'currency' => $currency,
                    'fee_amount' => $fee,
                    'net_amount' => $netAmount,
                    'crypto_address' => $validated['metadata']['crypto_address'] ?? null,
                    'crypto_currency' => $validated['metadata']['crypto_currency'] ?? null,
                ]
            );

            return response()->json([
                'transaction' => $transaction->fresh(),
                'user' => $targetUser->fresh(),
            ], 201);
        });
    }

/**
 * Check deposit status via blockchain API (optional - for automatic verification)
 */
public function checkDepositStatus(Request $request, $id)
    {
        $this->ensureAdmin($request);

        $transaction = Transaction::where('type', 'deposit')
            ->where('status', 'pending')
            ->findOrFail($id);

        $metadata = $transaction->metadata ?? [];
        $cryptoAddress = $metadata['crypto_address'] ?? null;
        $cryptoCurrency = $metadata['crypto_currency'] ?? 'BTC';
        $expectedAmount = $metadata['crypto_amount'] ?? null;

        if (!$cryptoAddress) {
            return response()->json([
                'message' => 'No crypto address found for this transaction.',
                'found' => false,
            ], 400);
        }

        // Check blockchain for transactions (using BlockCypher API as example)
        try {
            $found = false;
            $confirmations = 0;
            $txHash = null;

            if ($cryptoCurrency === 'BTC') {
                // Check Bitcoin blockchain
                $url = "https://api.blockcypher.com/v1/btc/main/addrs/{$cryptoAddress}/full";
                $response = file_get_contents($url);
                $data = json_decode($response, true);

                if (isset($data['txs']) && is_array($data['txs'])) {
                    foreach ($data['txs'] as $tx) {
                        // Check if this is a receiving transaction
                        foreach ($tx['outputs'] as $output) {
                            if (in_array($cryptoAddress, $output['addresses'] ?? [])) {
                                $amount = $output['value'] / 100000000; // Convert satoshis to BTC
                                // Check if amount matches (with small tolerance)
                                if (abs($amount - $expectedAmount) < 0.00000001) {
                                    $found = true;
                                    $confirmations = $tx['confirmations'] ?? 0;
                                    $txHash = $tx['hash'] ?? null;
                                    break 2;
                                }
                            }
                        }
                    }
                }
            } elseif ($cryptoCurrency === 'ETH' || $cryptoCurrency === 'USDT') {
                // Check Ethereum blockchain (using Etherscan API - requires API key)
                // For now, return manual check required
                return response()->json([
                    'message' => 'Ethereum/USDT checking requires API key setup. Please verify manually.',
                    'found' => false,
                    'manual_check_required' => true,
                    'address' => $cryptoAddress,
                    'explorer_url' => "https://etherscan.io/address/{$cryptoAddress}",
                ]);
            }

            return response()->json([
                'found' => $found,
                'confirmations' => $confirmations,
                'tx_hash' => $txHash,
                'confirmed' => $confirmations >= 2,
                'message' => $found 
                    ? ($confirmations >= 2 ? 'Transaction found with 2+ confirmations' : "Transaction found with {$confirmations} confirmation(s)")
                    : 'No matching transaction found',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error checking blockchain: ' . $e->getMessage(),
                'found' => false,
                'error' => true,
            ], 500);
        }
    }

    /**
     * Cleanup pending deposit activity logs
     * Removes activity logs for deposits that are still pending
     */
    public function cleanupPendingDepositActivityLogs(Request $request)
    {
        $admin = $this->ensureAdmin($request);

        // Find all activity logs for deposits
        $depositActivityLogs = ActivityLog::where('action', 'deposit')
            ->where('entity_type', Transaction::class)
            ->get();

        $deletedCount = 0;
        $keptCount = 0;
        $deletedDetails = [];

        foreach ($depositActivityLogs as $activityLog) {
            // Get the associated transaction
            $transaction = Transaction::find($activityLog->entity_id);
            
            // If transaction exists and is still pending, delete the activity log
            if ($transaction && $transaction->status === 'pending') {
                $deletedDetails[] = [
                    'transaction_id' => $transaction->id,
                    'user_id' => $activityLog->user_id,
                    'amount' => $activityLog->amount,
                    'currency' => $activityLog->currency,
                ];
                $activityLog->delete();
                $deletedCount++;
            } else {
                // Keep activity logs for completed/cancelled/failed deposits
                $keptCount++;
            }
        }

        // Log admin action
        $this->logAdminAction(
            $request,
            'cleanup_pending_deposit_activity_logs',
            null,
            null,
            null,
            "Cleaned up {$deletedCount} pending deposit activity logs",
            [
                'deleted_count' => $deletedCount,
                'kept_count' => $keptCount,
            ]
        );

        return response()->json([
            'message' => 'Cleanup completed successfully.',
            'deleted_count' => $deletedCount,
            'kept_count' => $keptCount,
            'deleted_details' => $deletedDetails,
        ]);
    }

    /**
     * Check user's profit growth calculation details
     */
    public function checkUserProfitGrowth(Request $request, $userId)
    {
        $admin = $this->ensureAdmin($request);

        $user = User::findOrFail($userId);
        
        // Get profit growth accounts
        $profitGrowths = ProfitGrowth::where('user_id', $user->id)
            ->whereIn('status', ['active', 'paused'])
            ->get();

        $profitGrowthDetails = [];

        foreach ($profitGrowths as $pg) {
            $initialBalance = floatval($pg->initial_balance);
            $currentBalance = floatval($pg->current_balance);
            $totalGrowth = $currentBalance - $initialBalance;
            $growthPercent = $initialBalance > 0 ? (($totalGrowth / $initialBalance) * 100) : 0;

            $details = [
                'id' => $pg->id,
                'name' => $pg->name,
                'status' => $pg->status,
                'initial_balance' => $pg->initial_balance,
                'current_balance' => $pg->current_balance,
                'growth_percentage' => $pg->growth_percentage,
                'growth_frequency' => $pg->growth_frequency,
                'locked_amount' => $pg->locked_amount ?? 0,
                'lock_until_date' => $pg->lock_until_date?->toIso8601String(),
                'last_transfer_date' => $pg->last_transfer_date?->toIso8601String(),
                'last_growth_calculated_at' => $pg->last_growth_calculated_at?->toIso8601String(),
                'next_growth_calculation_at' => $pg->next_growth_calculation_at?->toIso8601String(),
                'created_at' => $pg->created_at->toIso8601String(),
                'total_growth' => $totalGrowth,
                'growth_percent' => round($growthPercent, 2),
            ];

            // Standard weekly growth: calculate expected growth
            if (abs((float) $pg->growth_percentage - (float) config('growth.weekly_percent', 5.0)) < 0.01 && $pg->growth_frequency === 'weekly') {
                $dailyRate = \App\Support\GrowthRate::dailyPercent();
                
                // Calculate days since creation or last transfer
                // Use the most recent date between last_transfer_date and created_at
                // But ensure we don't use a future date
                $startDate = $pg->last_transfer_date ?? $pg->created_at;
                
                // If last_transfer_date is in the future, use created_at instead
                if ($pg->last_transfer_date && $pg->last_transfer_date->isFuture()) {
                    $startDate = $pg->created_at;
                }
                
                // Ensure startDate is not in the future
                if ($startDate->isFuture()) {
                    $startDate = now();
                }
                
                // Calculate days since start (ensure non-negative)
                $daysSinceStart = max(0, now()->diffInDays($startDate, false));
                
                // Calculate expected balance using compound interest
                $expectedBalance = $initialBalance * pow(1 + ($dailyRate / 100), $daysSinceStart);
                $expectedGrowth = $expectedBalance - $initialBalance;
                
                $details['expected_calculation'] = [
                    'days_since_start' => $daysSinceStart,
                    'daily_rate' => round($dailyRate, 3),
                    'expected_balance' => round($expectedBalance, 2),
                    'expected_growth' => round($expectedGrowth, 2),
                    'actual_balance' => round($currentBalance, 2),
                    'difference' => round($currentBalance - $expectedBalance, 2),
                    'is_correct' => abs($currentBalance - $expectedBalance) < 0.01,
                ];

                // Check if growth is due
                if ($pg->next_growth_calculation_at) {
                    $details['growth_due'] = now()->gte($pg->next_growth_calculation_at);
                }
            }

            $profitGrowthDetails[] = $details;
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'created_at' => $user->created_at->toIso8601String(),
                'real_balance' => $user->real_balance,
                'simulated_balance' => $user->simulated_balance,
            ],
            'profit_growths' => $profitGrowthDetails,
            'total_profit_growths' => $profitGrowths->count(),
        ]);
    }
}
