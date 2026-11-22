<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\TransferReceivedEmail;
use App\Models\ActivityLog;
use App\Models\ProfitGrowth;
use App\Models\SimulatedTransfer;
use App\Models\Simulation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SimulatedTransferController extends Controller
{
    /**
     * List transfers sent by the authenticated user
     */
    public function sent(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'simulation_id' => 'nullable|exists:simulations,id', // Legacy
            'profit_growth_id' => 'nullable|exists:profit_growths,id',
            'status' => 'nullable|in:pending,completed,failed,cancelled',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = $user->sentSimulatedTransfers()->with(['toUser', 'profitGrowth', 'simulation'])->latest();

        if (isset($validated['profit_growth_id'])) {
            $query->where('simulation_id', $validated['profit_growth_id']);
        } elseif (isset($validated['simulation_id'])) {
            $query->where('simulation_id', $validated['simulation_id']);
        }

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $perPage = $validated['per_page'] ?? 20;
        $transfers = $query->paginate($perPage);

        return response()->json($transfers);
    }

    /**
     * List transfers received by the authenticated user
     */
    public function received(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'simulation_id' => 'nullable|exists:simulations,id',
            'status' => 'nullable|in:pending,completed,failed,cancelled',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = $user->receivedSimulatedTransfers()->with(['fromUser', 'profitGrowth', 'simulation'])->latest();

        if (isset($validated['profit_growth_id'])) {
            $query->where('simulation_id', $validated['profit_growth_id']);
        } elseif (isset($validated['simulation_id'])) {
            $query->where('simulation_id', $validated['simulation_id']);
        }

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $perPage = $validated['per_page'] ?? 20;
        $transfers = $query->paginate($perPage);

        return response()->json($transfers);
    }

    /**
     * List all transfers (sent and received) for the authenticated user
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'type' => 'nullable|in:sent,received',
            'simulation_id' => 'nullable|exists:simulations,id',
            'status' => 'nullable|in:pending,completed,failed,cancelled',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        // Filter by type if specified
        if (isset($validated['type']) && $validated['type'] === 'sent') {
            $query = $user->sentSimulatedTransfers()->with(['toUser', 'simulation']);
        } elseif (isset($validated['type']) && $validated['type'] === 'received') {
            $query = $user->receivedSimulatedTransfers()->with(['fromUser', 'simulation']);
        } else {
            // Get all transfers where user is either sender or receiver
            $query = SimulatedTransfer::where(function ($q) use ($user) {
                $q->where('from_user_id', $user->id)
                  ->orWhere('to_user_id', $user->id);
            })->with(['fromUser', 'toUser', 'profitGrowth', 'simulation']);
        }

        if (isset($validated['profit_growth_id'])) {
            $query->where('simulation_id', $validated['profit_growth_id']);
        } elseif (isset($validated['simulation_id'])) {
            $query->where('simulation_id', $validated['simulation_id']);
        }

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $perPage = $validated['per_page'] ?? 20;
        $transfers = $query->latest()->paginate($perPage);

        return response()->json($transfers);
    }

    /**
     * Get a single transfer
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        
        // Get transfer where user is either sender or receiver
        $transfer = SimulatedTransfer::where(function ($query) use ($user) {
            $query->where('from_user_id', $user->id)
                  ->orWhere('to_user_id', $user->id);
        })->with(['fromUser', 'toUser', 'profitGrowth', 'simulation'])->findOrFail($id);

        return response()->json([
            'transfer' => $transfer,
        ]);
    }

    /**
     * Send simulated funds to another user
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // Check if account is frozen
        if ($user->is_frozen) {
            return response()->json([
                'message' => 'Account is frozen. Cannot send transfers.',
            ], 403);
        }

        $validated = $request->validate([
            'to_user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01|max:999999999.99',
            'currency' => 'nullable|string|size:3',
            'simulation_id' => 'nullable|exists:simulations,id', // Legacy - no longer used
            'profit_growth_id' => 'nullable|exists:profit_growths,id', // Not allowed - users must withdraw first
            'message' => 'nullable|string|max:500',
        ]);

        // Can't send to yourself
        if ($validated['to_user_id'] == $user->id) {
            return response()->json([
                'message' => 'Cannot send funds to yourself.',
            ], 422);
        }

        // Get recipient
        $recipient = User::findOrFail($validated['to_user_id']);

        // Check if recipient account is frozen
        if ($recipient->is_frozen) {
            return response()->json([
                'message' => 'Recipient account is frozen. Cannot send transfers.',
            ], 422);
        }

        // Profit growth and simulation sources are no longer allowed
        // Users must withdraw from profit growth to real balance first, then send

        // Check user's real balance (users can only send real balance funds)
        if ($user->real_balance < $validated['amount']) {
            return response()->json([
                'message' => 'Insufficient real balance.',
            ], 422);
        }

        // Reject if profit_growth_id or simulation_id is provided (users must withdraw first)
        if (isset($validated['profit_growth_id']) || isset($validated['simulation_id'])) {
            return response()->json([
                'message' => 'Cannot send funds directly from profit growth. Please withdraw to your real balance first.',
            ], 422);
        }

        return DB::transaction(function () use ($user, $recipient, $validated) {
            // Create transfer
            $transfer = SimulatedTransfer::create([
                'from_user_id' => $user->id,
                'to_user_id' => $validated['to_user_id'],
                'simulation_id' => null, // No profit growth source - sending real balance
                'amount' => $validated['amount'],
                'currency' => $validated['currency'] ?? 'USD',
                'status' => 'completed', // Auto-complete for now
                'message' => $validated['message'] ?? null,
                'reference' => 'TRF-' . strtoupper(uniqid()),
                'processed_at' => now(),
            ]);

            // Deduct from sender's real balance
            $user->decrement('real_balance', $validated['amount']);
            $user->save();

            // Add to recipient's real balance
            $recipient->increment('real_balance', $validated['amount']);
            $recipient->save();

            // Create activity log for sender
            $currency = $validated['currency'] ?? 'USD';
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'transfer_sent',
                'fund_type' => 'real', // Changed from simulated to real
                'entity_type' => SimulatedTransfer::class,
                'entity_id' => $transfer->id,
                'amount' => $validated['amount'],
                'currency' => $currency,
                'description' => "Sent {$validated['amount']} {$currency} to {$recipient->name}",
            ]);

            // Create activity log for recipient
            ActivityLog::create([
                'user_id' => $recipient->id,
                'action' => 'transfer_received',
                'fund_type' => 'real', // Changed from simulated to real
                'entity_type' => SimulatedTransfer::class,
                'entity_id' => $transfer->id,
                'amount' => $validated['amount'],
                'currency' => $currency,
                'description' => "Received {$validated['amount']} {$currency} from {$user->name}",
            ]);

            // Send email notification to recipient (without showing the amount)
            try {
                Mail::to($recipient->email)->send(new TransferReceivedEmail(
                    $recipient,
                    $user,
                    $currency,
                    $validated['message'] ?? null
                ));
            } catch (\Exception $e) {
                // Log email error but don't fail the transfer transaction
                \Log::error('Failed to send transfer received notification email: ' . $e->getMessage());
            }

            return response()->json([
                'transfer' => $transfer->fresh(['fromUser', 'toUser', 'profitGrowth', 'simulation']),
                'user' => $user->fresh(), // Updated balance
            ], 201);
        });
    }
}
