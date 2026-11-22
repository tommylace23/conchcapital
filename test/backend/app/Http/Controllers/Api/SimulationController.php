<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Simulation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SimulationController extends Controller
{
    /**
     * List all simulations for the authenticated user
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'status' => 'nullable|in:active,paused,completed,cancelled',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = $user->simulations()->latest();

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $perPage = $validated['per_page'] ?? 20;
        $simulations = $query->paginate($perPage);

        return response()->json($simulations);
    }

    /**
     * Get a single simulation
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $simulation = $user->simulations()->with(['simulatedTransfers'])->findOrFail($id);

        return response()->json([
            'simulation' => $simulation,
        ]);
    }

    /**
     * Create a new simulation
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // Check if account is frozen
        if ($user->is_frozen) {
            return response()->json([
                'message' => 'Account is frozen. Cannot create simulations.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'initial_balance' => 'required|numeric|min:0|max:999999999.99',
            'currency' => 'nullable|string|size:3',
            'strategy_data' => 'nullable|array',
        ]);

        return DB::transaction(function () use ($user, $validated) {
            // Create simulation
            $simulation = Simulation::create([
                'user_id' => $user->id,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'initial_balance' => $validated['initial_balance'],
                'current_balance' => $validated['initial_balance'], // Start with initial balance
                'currency' => $validated['currency'] ?? 'USD',
                'status' => 'active',
                'strategy_data' => $validated['strategy_data'] ?? [],
                'started_at' => now(),
            ]);

            // Create activity log
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'simulation_created',
                'fund_type' => 'simulated',
                'entity_type' => Simulation::class,
                'entity_id' => $simulation->id,
                'amount' => $validated['initial_balance'],
                'currency' => $validated['currency'] ?? 'USD',
                'description' => "Created simulation: {$validated['name']}",
            ]);

            return response()->json([
                'simulation' => $simulation->fresh(),
            ], 201);
        });
    }

    /**
     * Update a simulation
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $simulation = $user->simulations()->findOrFail($id);

        // Can't update completed or cancelled simulations
        if (in_array($simulation->status, ['completed', 'cancelled'])) {
            return response()->json([
                'message' => 'Cannot update a completed or cancelled simulation.',
            ], 422);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'status' => [
                'sometimes',
                Rule::in(['active', 'paused', 'completed', 'cancelled']),
            ],
            'strategy_data' => 'nullable|array',
        ]);

        return DB::transaction(function () use ($simulation, $validated) {
            $oldStatus = $simulation->status;
            
            $simulation->fill($validated);
            
            // If status changed to completed, set completed_at
            if (isset($validated['status']) && $validated['status'] === 'completed' && $oldStatus !== 'completed') {
                $simulation->completed_at = now();
            }
            
            // If status changed from completed, clear completed_at
            if (isset($validated['status']) && $oldStatus === 'completed' && $validated['status'] !== 'completed') {
                $simulation->completed_at = null;
            }

            $simulation->save();

            // Create activity log
            ActivityLog::create([
                'user_id' => $simulation->user_id,
                'action' => 'simulation_updated',
                'fund_type' => 'simulated',
                'entity_type' => Simulation::class,
                'entity_id' => $simulation->id,
                'description' => "Updated simulation: {$simulation->name}",
                'metadata' => [
                    'changes' => $validated,
                    'old_status' => $oldStatus,
                    'new_status' => $simulation->status,
                ],
            ]);

            return response()->json([
                'simulation' => $simulation->fresh(),
            ]);
        });
    }

    /**
     * Delete (cancel) a simulation
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $simulation = $user->simulations()->findOrFail($id);

        // Can't delete completed simulations (only cancel active/paused ones)
        if ($simulation->status === 'completed') {
            return response()->json([
                'message' => 'Cannot delete a completed simulation.',
            ], 422);
        }

        return DB::transaction(function () use ($simulation) {
            $simulationName = $simulation->name;
            $simulationId = $simulation->id;
            $userId = $simulation->user_id;

            // Update status to cancelled instead of deleting
            $simulation->status = 'cancelled';
            $simulation->completed_at = now();
            $simulation->save();

            // Create activity log
            ActivityLog::create([
                'user_id' => $userId,
                'action' => 'simulation_cancelled',
                'fund_type' => 'simulated',
                'entity_type' => Simulation::class,
                'entity_id' => $simulationId,
                'description' => "Cancelled simulation: {$simulationName}",
            ]);

            return response()->json([
                'message' => 'Simulation cancelled successfully.',
                'simulation' => $simulation->fresh(),
            ]);
        });
    }
}
