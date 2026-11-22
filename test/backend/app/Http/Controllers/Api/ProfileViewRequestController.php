<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ProfileViewApprovedEmail;
use App\Mail\ProfileViewDeclinedEmail;
use App\Mail\ProfileViewRequestEmail;
use App\Models\ProfileViewRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ProfileViewRequestController extends Controller
{
    /**
     * Create a profile view request
     */
    public function create(Request $request)
    {
        $validated = $request->validate([
            'profile_owner_id' => 'required|exists:users,id',
        ]);

        $requester = $request->user();
        $profileOwnerId = $validated['profile_owner_id'];

        // Can't request to view own profile
        if ($requester->id === $profileOwnerId) {
            return response()->json([
                'message' => 'You cannot request to view your own profile.',
            ], 422);
        }

        // Check if there's already an approved request
        $approvedRequest = ProfileViewRequest::where('requester_id', $requester->id)
            ->where('profile_owner_id', $profileOwnerId)
            ->where('status', 'approved')
            ->first();

        if ($approvedRequest) {
            return response()->json([
                'message' => 'You already have access to view this profile.',
                'request' => $approvedRequest,
            ], 200);
        }

        // If there's a pending request, delete it and create a new one (allows re-requesting)
        $pendingRequest = ProfileViewRequest::where('requester_id', $requester->id)
            ->where('profile_owner_id', $profileOwnerId)
            ->where('status', 'pending')
            ->first();

        if ($pendingRequest) {
            $pendingRequest->delete(); // Delete old pending request to allow new one
        }

        // Create new request
        $profileViewRequest = ProfileViewRequest::create([
            'requester_id' => $requester->id,
            'profile_owner_id' => $profileOwnerId,
            'status' => 'pending',
        ]);

        // Send email notification to profile owner
        $profileOwner = User::findOrFail($profileOwnerId);
        Mail::to($profileOwner->email)->send(new ProfileViewRequestEmail($profileOwner, $requester));

        return response()->json([
            'message' => 'Profile view request sent successfully.',
            'request' => $profileViewRequest->load(['requester', 'profileOwner']),
        ], 201);
    }

    /**
     * Get pending requests for the authenticated user (as profile owner)
     */
    public function getPending(Request $request)
    {
        $user = $request->user();

        $pendingRequests = ProfileViewRequest::where('profile_owner_id', $user->id)
            ->where('status', 'pending')
            ->with(['requester:id,username,name,display_name,avatar_url'])
            ->latest()
            ->get();

        return response()->json([
            'requests' => $pendingRequests,
        ]);
    }

    /**
     * Approve a profile view request
     */
    public function approve(Request $request, $id)
    {
        $user = $request->user();
        $profileViewRequest = ProfileViewRequest::findOrFail($id);

        // Verify the user is the profile owner
        if ($profileViewRequest->profile_owner_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized. You can only approve requests for your own profile.',
            ], 403);
        }

        // Check if already processed
        if ($profileViewRequest->status !== 'pending') {
            return response()->json([
                'message' => 'This request has already been processed.',
            ], 422);
        }

        DB::transaction(function () use ($profileViewRequest) {
            $profileViewRequest->update([
                'status' => 'approved',
                'viewed_at' => now(),
            ]);

            // Send email notification to requester
            Mail::to($profileViewRequest->requester->email)->send(
                new ProfileViewApprovedEmail($profileViewRequest->requester, $profileViewRequest->profileOwner)
            );
        });

        return response()->json([
            'message' => 'Profile view request approved successfully.',
            'request' => $profileViewRequest->fresh()->load(['requester', 'profileOwner']),
        ]);
    }

    /**
     * Decline a profile view request
     */
    public function decline(Request $request, $id)
    {
        $user = $request->user();
        $profileViewRequest = ProfileViewRequest::findOrFail($id);

        // Verify the user is the profile owner
        if ($profileViewRequest->profile_owner_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized. You can only decline requests for your own profile.',
            ], 403);
        }

        // Check if already processed
        if ($profileViewRequest->status !== 'pending') {
            return response()->json([
                'message' => 'This request has already been processed.',
            ], 422);
        }

        DB::transaction(function () use ($profileViewRequest) {
            $profileViewRequest->update([
                'status' => 'declined',
                'viewed_at' => now(),
            ]);

            // Send email notification to requester
            Mail::to($profileViewRequest->requester->email)->send(
                new ProfileViewDeclinedEmail($profileViewRequest->requester, $profileViewRequest->profileOwner)
            );
        });

        return response()->json([
            'message' => 'Profile view request declined successfully.',
            'request' => $profileViewRequest->fresh()->load(['requester', 'profileOwner']),
        ]);
    }

    /**
     * Check if the authenticated user can view a specific profile
     */
    public function checkAccess(Request $request, $profileOwnerId)
    {
        $requester = $request->user();

        // Can always view own profile
        if ($requester->id === $profileOwnerId) {
            return response()->json([
                'can_view' => true,
                'reason' => 'own_profile',
            ]);
        }

        // Check for approved request
        $approvedRequest = ProfileViewRequest::where('requester_id', $requester->id)
            ->where('profile_owner_id', $profileOwnerId)
            ->where('status', 'approved')
            ->first();

        if ($approvedRequest) {
            return response()->json([
                'can_view' => true,
                'reason' => 'approved_request',
                'request' => $approvedRequest,
            ]);
        }

        // Check for pending request
        $pendingRequest = ProfileViewRequest::where('requester_id', $requester->id)
            ->where('profile_owner_id', $profileOwnerId)
            ->where('status', 'pending')
            ->first();

        if ($pendingRequest) {
            return response()->json([
                'can_view' => false,
                'reason' => 'pending_request',
                'request' => $pendingRequest,
            ]);
        }

        // No request exists
        return response()->json([
            'can_view' => false,
            'reason' => 'no_request',
        ]);
    }
}
