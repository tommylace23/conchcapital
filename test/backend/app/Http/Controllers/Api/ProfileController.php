<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'display_name' => 'nullable|string|max:50',
            'bio' => 'nullable|string|max:500',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'avatar_url' => 'nullable|url|max:2048',
            'remove_avatar' => 'nullable|boolean',
            'username' => [
                'nullable',
                'regex:/^[A-Za-z0-9._-]+$/',
                'min:3',
                'max:20',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
        ]);

        // Handle avatar removal
        if ($request->has('remove_avatar') && $request->input('remove_avatar') == '1') {
            // Delete old avatar if exists
            if ($user->avatar_url) {
                $oldPath = str_replace('/storage/', '', $user->avatar_url);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
            $validated['avatar_url'] = null;
        }
        // Handle avatar file upload
        elseif ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($user->avatar_url) {
                $oldPath = str_replace('/storage/', '', $user->avatar_url);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            // Store new avatar
            $path = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar_url'] = '/storage/' . $path;
        }

        // Remove avatar_url from validated if it wasn't set (to avoid overwriting with null)
        if (!isset($validated['avatar_url'])) {
            unset($validated['avatar_url']);
        }

        $user->fill($validated);

        if (array_key_exists('display_name', $validated) && $validated['display_name']) {
            $user->name = $validated['display_name'];
        }

        $user->save();

        return response()->json([
            'user' => $user,
        ]);
    }

    /**
     * Get another user's public profile
     */
    public function showUser(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        // Return public profile information
        return response()->json([
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'display_name' => $user->display_name,
                'bio' => $user->bio,
                'avatar_url' => $user->avatar_url,
                'created_at' => $user->created_at,
                // Don't expose sensitive info like email, balances, etc.
            ],
        ]);
    }

    /**
     * Get another user's portfolio statistics (requires access permission)
     */
    public function getUserPortfolio(Request $request, $id)
    {
        $requester = $request->user();
        $profileOwner = User::findOrFail($id);

        // Check if requester has access to view this profile
        // Can always view own profile
        if ($requester->id === $profileOwner->id) {
            $hasAccess = true;
        } else {
            // Check for approved request
            $approvedRequest = \App\Models\ProfileViewRequest::where('requester_id', $requester->id)
                ->where('profile_owner_id', $profileOwner->id)
                ->where('status', 'approved')
                ->first();
            
            $hasAccess = (bool) $approvedRequest;
        }

        if (!$hasAccess) {
            return response()->json([
                'message' => 'You do not have access to view this user\'s portfolio.',
            ], 403);
        }

        // Calculate portfolio statistics
        // Real wallet balance
        $realBalance = $profileOwner->real_balance ?? 0;
        
        // Calculate total profit growth balance (sum of all active/paused profit growths)
        $totalProfitGrowthBalance = $profileOwner->profitGrowths()
            ->whereIn('status', ['active', 'paused'])
            ->sum('current_balance');
        
        // Get best growth performance (highest growth percentage from active profit growths)
        // If no profit growths exist or percentage is 0/null, use default 20%
        $profitGrowths = $profileOwner->profitGrowths()
            ->whereIn('status', ['active', 'paused'])
            ->get();
        
        $bestGrowth = 0;
        if ($profitGrowths->count() > 0) {
            // Get the maximum growth percentage from existing profit growths
            $maxPercentage = $profitGrowths->max('growth_percentage');
            $bestGrowth = $maxPercentage && $maxPercentage > 0 ? $maxPercentage : 20.0; // Default to 20% if null/0
        } else {
            // No profit growths exist, use default 20%
            $bestGrowth = 20.0;
        }

        // Get recent activity (last 10 transactions)
        $recentTransactions = $profileOwner->transactions()
            ->latest()
            ->limit(10)
            ->get(['id', 'type', 'amount', 'description', 'status', 'created_at']);

        return response()->json([
            'statistics' => [
                'real_balance' => $realBalance,
                'total_profit_growth_balance' => $totalProfitGrowthBalance,
                'best_growth_performance' => $bestGrowth,
            ],
            'recent_transactions' => $recentTransactions,
        ]);
    }

    /**
     * Search for users by username, email, or name
     * Also supports listing all users with pagination when no query is provided
     */
    public function searchUsers(Request $request)
    {
        $validated = $request->validate([
            'q' => 'nullable|string|min:1|max:255',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = $validated['q'] ?? null;
        $perPage = $validated['per_page'] ?? 12; // Default 12 users
        $page = $validated['page'] ?? 1;
        
        $usersQuery = User::where('id', '!=', $request->user()->id); // Don't show current user
        
        // If query provided, search
        if ($query) {
            $usersQuery->where(function ($q) use ($query) {
                $q->where('username', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%")
                  ->orWhere('name', 'like', "%{$query}%")
                  ->orWhere('display_name', 'like', "%{$query}%");
            });
            // For search results, order by relevance (most recently active first)
            $usersQuery->orderBy('last_active_at', 'desc')
                       ->orderBy('created_at', 'desc');
        } else {
            // For default listing (no search), randomize results for better UX
            // This ensures different users/families show up on each visit
            $usersQuery->inRandomOrder();
        }
        
        // Paginate results
        $users = $usersQuery->select(['id', 'username', 'email', 'name', 'display_name', 'avatar_url', 'bio', 'created_at', 'last_active_at'])
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'users' => $users->items(),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
            ],
        ]);
    }
}
