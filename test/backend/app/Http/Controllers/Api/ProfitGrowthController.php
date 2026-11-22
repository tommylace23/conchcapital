<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ProfitGrowth;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProfitGrowthController extends Controller
{
    /**
     * List all profit growths for the authenticated user
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'status' => 'nullable|in:active,paused,completed,cancelled',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = $user->profitGrowths()->latest();

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $perPage = $validated['per_page'] ?? 20;
        $profitGrowths = $query->paginate($perPage);

        return response()->json($profitGrowths);
    }

    /**
     * Get a single profit growth
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $profitGrowth = $user->profitGrowths()->with(['simulatedTransfers'])->findOrFail($id);

        return response()->json([
            'profit_growth' => $profitGrowth,
        ]);
    }

    /**
     * Create a new profit growth (RESTRICTED - Admin only)
     * Users can no longer create profit growths directly
     */
    public function store(Request $request)
    {
        // Only admins can create profit growths
        if (!$request->user()->is_admin) {
            return response()->json([
                'message' => 'Only administrators can create profit growths. Please contact an admin.',
            ], 403);
        }

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

        // Check if target user account is frozen
        if ($targetUser->is_frozen) {
            return response()->json([
                'message' => 'Target user account is frozen. Cannot create profit growth.',
            ], 403);
        }

        return DB::transaction(function () use ($request, $targetUser, $validated) {
            // Calculate next growth calculation date based on frequency
            $nextCalculation = $this->calculateNextGrowthDate($validated['growth_frequency']);

            // Create profit growth
            $profitGrowth = ProfitGrowth::create([
                'user_id' => $validated['user_id'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'initial_balance' => $validated['initial_balance'],
                'current_balance' => $validated['initial_balance'], // Start with initial balance
                'currency' => $validated['currency'] ?? 'USD',
                'status' => 'active',
                'growth_percentage' => $validated['growth_percentage'],
                'growth_frequency' => $validated['growth_frequency'],
                'auto_calculate' => true,
                'next_growth_calculation_at' => $nextCalculation,
                'strategy_data' => $validated['strategy_data'] ?? [],
                'started_at' => now(),
            ]);

            // Create activity log
            ActivityLog::create([
                'user_id' => $request->user()->id,
                'action' => 'profit_growth_created',
                'fund_type' => 'simulated',
                'entity_type' => ProfitGrowth::class,
                'entity_id' => $profitGrowth->id,
                'amount' => $validated['initial_balance'],
                'currency' => $validated['currency'] ?? 'USD',
                'description' => "Admin created profit growth: {$validated['name']} for user {$targetUser->name}",
            ]);

            return response()->json([
                'profit_growth' => $profitGrowth->fresh(),
            ], 201);
        });
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
     * Update a profit growth
     * Admins can update any user's profit growth, regular users can only update their own
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        
        // Admins can update any profit growth, regular users can only update their own
        if ($user->is_admin) {
            $profitGrowth = ProfitGrowth::findOrFail($id);
        } else {
            $profitGrowth = $user->profitGrowths()->findOrFail($id);
        }

        // Can't update completed or cancelled profit growths
        if (in_array($profitGrowth->status, ['completed', 'cancelled'])) {
            return response()->json([
                'message' => 'Cannot update a completed or cancelled profit growth.',
            ], 422);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'status' => [
                'sometimes',
                Rule::in(['active', 'paused', 'completed', 'cancelled']),
            ],
            'growth_percentage' => 'sometimes|numeric|min:1|max:100',
            'growth_frequency' => 'sometimes|in:daily,weekly,monthly,quarterly,yearly,test',
            'strategy_data' => 'nullable|array',
        ]);

        return DB::transaction(function () use ($profitGrowth, $validated) {
            $oldStatus = $profitGrowth->status;
            
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

            // Create activity log
            ActivityLog::create([
                'user_id' => $profitGrowth->user_id,
                'action' => 'profit_growth_updated',
                'fund_type' => 'simulated',
                'entity_type' => ProfitGrowth::class,
                'entity_id' => $profitGrowth->id,
                'description' => "Updated profit growth: {$profitGrowth->name}",
                'metadata' => [
                    'changes' => $validated,
                    'old_status' => $oldStatus,
                    'new_status' => $profitGrowth->status,
                ],
            ]);

            return response()->json([
                'profit_growth' => $profitGrowth->fresh(),
            ]);
        });
    }

    /**
     * Delete (cancel) a profit growth
     * RESTRICTED: Only admins can delete profit growths
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        
        // Only admins can delete profit growths
        if (!$user->is_admin) {
            return response()->json([
                'message' => 'Only administrators can delete profit growths. Please contact an admin.',
            ], 403);
        }
        
        // Admins can delete any profit growth
        $profitGrowth = ProfitGrowth::findOrFail($id);

        // Can't delete completed profit growths (only cancel active/paused ones)
        if ($profitGrowth->status === 'completed') {
            return response()->json([
                'message' => 'Cannot delete a completed profit growth.',
            ], 422);
        }

        return DB::transaction(function () use ($profitGrowth) {
            $profitGrowthName = $profitGrowth->name;
            $profitGrowthId = $profitGrowth->id;
            $userId = $profitGrowth->user_id;

            // Update status to cancelled instead of deleting
            $profitGrowth->status = 'cancelled';
            $profitGrowth->completed_at = now();
            $profitGrowth->save();

            // Create activity log
            ActivityLog::create([
                'user_id' => $userId,
                'action' => 'profit_growth_cancelled',
                'fund_type' => 'simulated',
                'entity_type' => ProfitGrowth::class,
                'entity_id' => $profitGrowthId,
                'description' => "Cancelled profit growth: {$profitGrowthName}",
            ]);

            return response()->json([
                'message' => 'Profit growth cancelled successfully.',
                'profit_growth' => $profitGrowth->fresh(),
            ]);
        });
    }

    /**
     * Transfer funds from user's real wallet to a profit growth (or create one if none exists)
     */
    public function transferFundsWithCreate(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:999999999.99',
            'name' => 'nullable|string|max:255',
            // growth_percentage and growth_frequency removed - users cannot set these
            // Defaults to 20% weekly, admin can change via admin panel
        ]);

        return DB::transaction(function () use ($user, $validated) {
            // Check if user has sufficient real balance
            if ($user->real_balance < $validated['amount']) {
                return response()->json([
                    'message' => 'Insufficient real wallet balance.',
                ], 422);
            }

            // Check if user has any active profit growths
            $existingProfitGrowth = $user->profitGrowths()
                ->whereIn('status', ['active', 'paused'])
                ->first();

            $profitGrowth = null;

            if ($existingProfitGrowth) {
                // Use existing profit growth
                $profitGrowth = $existingProfitGrowth;
            } else {
                // Create a new profit growth with default settings
                // Users cannot set percentage/frequency - defaults to 20% weekly
                $name = $validated['name'] ?? 'My Profit Growth Account';
                $growthPercentage = 20.0; // Default 20% (admin can change this)
                $growthFrequency = 'weekly'; // Default weekly (admin can change this)

                $nextCalculation = $this->calculateNextGrowthDate($growthFrequency);

                $profitGrowth = ProfitGrowth::create([
                    'user_id' => $user->id,
                    'name' => $name,
                    'description' => 'Auto-created profit growth account',
                    'initial_balance' => 0,
                    'current_balance' => 0,
                    'currency' => 'USD',
                    'status' => 'active',
                    'growth_percentage' => $growthPercentage,
                    'growth_frequency' => $growthFrequency,
                    'next_growth_calculation_at' => $nextCalculation,
                    'auto_calculate' => true,
                ]);
            }
            
            // Check 7-day transfer cooldown
            $lastTransferDate = $profitGrowth->last_transfer_date;
            if ($lastTransferDate) {
                $daysSinceLastTransfer = now()->diffInDays($lastTransferDate);
                if ($daysSinceLastTransfer < 7) {
                    $daysRemaining = 7 - $daysSinceLastTransfer;
                    return response()->json([
                        'message' => "You can only transfer funds once per week. Please wait {$daysRemaining} more day(s) before transferring again.",
                    ], 422);
                }
            }
            
            // Set 7-day lock period
            $lockUntilDate = now()->addDays(7);
            
            // Deduct from real balance
            $user->decrement('real_balance', $validated['amount']);
            $user->save();

            // Add to profit growth current balance and locked amount
            $profitGrowth->increment('current_balance', $validated['amount']);
            $profitGrowth->increment('locked_amount', $validated['amount']);
            
            // Set lock period and update last transfer date
            $profitGrowth->lock_until_date = $lockUntilDate;
            $profitGrowth->last_transfer_date = now();
            
            // Update next growth calculation to start daily increments
            // Growth will be calculated daily: 20% / 7 days = ~2.857% per day
            $profitGrowth->next_growth_calculation_at = now()->addDay()->startOfDay();
            $profitGrowth->save();

            // Create activity log
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'funds_transferred_to_profit_growth',
                'fund_type' => 'real',
                'entity_type' => ProfitGrowth::class,
                'entity_id' => $profitGrowth->id,
                'amount' => $validated['amount'],
                'currency' => $profitGrowth->currency ?? 'USD',
                'description' => "Transferred {$validated['amount']} to profit growth: {$profitGrowth->name}. Funds locked for 7 days.",
            ]);

            return response()->json([
                'message' => 'Funds transferred successfully. Funds will be locked for 7 days while they grow.',
                'profit_growth' => $profitGrowth->fresh(),
                'user' => $user->fresh(),
                'created' => !$existingProfitGrowth, // Indicate if profit growth was just created
                'lock_until_date' => $lockUntilDate->toIso8601String(),
            ]);
        });
    }

    /**
     * Transfer funds from user's real wallet to a profit growth
     */
    public function transferFunds(Request $request, $id)
    {
        $user = $request->user();
        $profitGrowth = $user->profitGrowths()->findOrFail($id);

        // Can't transfer to completed or cancelled profit growths
        if (in_array($profitGrowth->status, ['completed', 'cancelled'])) {
            return response()->json([
                'message' => 'Cannot transfer funds to a completed or cancelled profit growth.',
            ], 422);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:999999999.99',
        ]);

        return DB::transaction(function () use ($user, $profitGrowth, $validated) {
            // Check if user has sufficient real balance
            if ($user->real_balance < $validated['amount']) {
                return response()->json([
                    'message' => 'Insufficient real wallet balance.',
                ], 422);
            }

            // Check 7-day transfer cooldown
            $lastTransferDate = $profitGrowth->last_transfer_date;
            if ($lastTransferDate) {
                $daysSinceLastTransfer = now()->diffInDays($lastTransferDate);
                if ($daysSinceLastTransfer < 7) {
                    $daysRemaining = 7 - $daysSinceLastTransfer;
                    return response()->json([
                        'message' => "You can only transfer funds once per week. Please wait {$daysRemaining} more day(s) before transferring again.",
                    ], 422);
                }
            }

            // Set 7-day lock period
            $lockUntilDate = now()->addDays(7);

            // Deduct from real balance
            $user->decrement('real_balance', $validated['amount']);
            $user->save();

            // Add to profit growth current balance and locked amount
            $profitGrowth->increment('current_balance', $validated['amount']);
            $profitGrowth->increment('locked_amount', $validated['amount']);
            
            // Set lock period and update last transfer date
            $profitGrowth->lock_until_date = $lockUntilDate;
            $profitGrowth->last_transfer_date = now();
            
            // Update next growth calculation to start daily increments
            $profitGrowth->next_growth_calculation_at = now()->addDay()->startOfDay();
            $profitGrowth->save();

            // Create activity log
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'funds_transferred_to_profit_growth',
                'fund_type' => 'real',
                'entity_type' => ProfitGrowth::class,
                'entity_id' => $profitGrowth->id,
                'amount' => $validated['amount'],
                'currency' => $profitGrowth->currency ?? 'USD',
                'description' => "Transferred {$validated['amount']} to profit growth: {$profitGrowth->name}",
            ]);

            return response()->json([
                'message' => 'Funds transferred successfully.',
                'profit_growth' => $profitGrowth->fresh(),
                'user' => $user->fresh(),
            ]);
        });
    }

    /**
     * Withdraw funds from a profit growth to user's real wallet balance
     */
    public function withdrawFunds(Request $request, $id)
    {
        $user = $request->user();
        $profitGrowth = $user->profitGrowths()->findOrFail($id);

        // Can't withdraw from completed or cancelled profit growths
        if (in_array($profitGrowth->status, ['completed', 'cancelled'])) {
            return response()->json([
                'message' => 'Cannot withdraw funds from a completed or cancelled profit growth.',
            ], 422);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:999999999.99',
        ]);

        return DB::transaction(function () use ($user, $profitGrowth, $validated) {
            // Check if profit growth has sufficient balance
            if ($profitGrowth->current_balance < $validated['amount']) {
                return response()->json([
                    'message' => 'Insufficient profit growth balance.',
                ], 422);
            }

            // Check if funds are locked (7-day lock period)
            if ($profitGrowth->lock_until_date && now()->lt($profitGrowth->lock_until_date)) {
                $daysRemaining = now()->diffInDays($profitGrowth->lock_until_date);
                return response()->json([
                    'message' => "Funds are locked for {$daysRemaining} more day(s). Withdrawals are not allowed during the 7-day growth period.",
                ], 422);
            }

            // Check if trying to withdraw locked amount
            $availableBalance = $profitGrowth->current_balance - $profitGrowth->locked_amount;
            if ($validated['amount'] > $availableBalance) {
                $lockedAmount = $profitGrowth->locked_amount;
                return response()->json([
                    'message' => "You can only withdraw unlocked funds. {$lockedAmount} is currently locked and growing.",
                ], 422);
            }

            // Deduct from profit growth current balance
            $profitGrowth->decrement('current_balance', $validated['amount']);
            
            // Reduce locked amount if withdrawing from locked funds (after lock period)
            if ($profitGrowth->locked_amount > 0) {
                $amountToUnlock = min($validated['amount'], $profitGrowth->locked_amount);
                $profitGrowth->decrement('locked_amount', $amountToUnlock);
            }
            
            $profitGrowth->save();

            // Add to user's real balance
            $user->increment('real_balance', $validated['amount']);
            $user->save();

            // Create transaction record
            Transaction::create([
                'user_id' => $user->id,
                'type' => 'withdrawal',
                'fund_type' => 'real',
                'amount' => $validated['amount'],
                'currency' => $profitGrowth->currency ?? 'USD',
                'status' => 'completed',
                'description' => "Withdrawn from profit growth: {$profitGrowth->name}",
                'reference' => 'PG-WD-' . strtoupper(uniqid()),
                'metadata' => [
                    'profit_growth_id' => $profitGrowth->id,
                    'profit_growth_name' => $profitGrowth->name,
                ],
                'processed_at' => now(),
            ]);

            // Create activity log
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'funds_withdrawn_from_profit_growth',
                'fund_type' => 'real',
                'entity_type' => ProfitGrowth::class,
                'entity_id' => $profitGrowth->id,
                'amount' => $validated['amount'],
                'currency' => $profitGrowth->currency ?? 'USD',
                'description' => "Withdrew {$validated['amount']} from profit growth: {$profitGrowth->name} to real balance",
            ]);

            return response()->json([
                'message' => 'Funds withdrawn successfully.',
                'profit_growth' => $profitGrowth->fresh(),
                'user' => $user->fresh(),
            ]);
        });
    }
}
