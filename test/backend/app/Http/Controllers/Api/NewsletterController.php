<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NewsletterController extends Controller
{
    /**
     * Subscribe to newsletter
     */
    public function subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid email address.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $email = $request->input('email');

        // Check if email already exists
        $subscription = NewsletterSubscription::where('email', $email)->first();

        if ($subscription) {
            if ($subscription->is_active) {
                return response()->json([
                    'message' => 'This email is already subscribed to our newsletter.',
                ], 422);
            } else {
                // Reactivate subscription
                $subscription->is_active = true;
                $subscription->subscribed_at = now();
                $subscription->unsubscribed_at = null;
                $subscription->save();

                return response()->json([
                    'message' => 'Successfully resubscribed to our newsletter!',
                ]);
            }
        }

        // Create new subscription
        NewsletterSubscription::create([
            'email' => $email,
            'is_active' => true,
            'subscribed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Successfully subscribed to our newsletter!',
        ]);
    }

    /**
     * Unsubscribe from newsletter
     */
    public function unsubscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid email address.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $email = $request->input('email');
        $subscription = NewsletterSubscription::where('email', $email)->first();

        if (!$subscription) {
            return response()->json([
                'message' => 'Email not found in our newsletter subscriptions.',
            ], 404);
        }

        if (!$subscription->is_active) {
            return response()->json([
                'message' => 'This email is already unsubscribed.',
            ], 422);
        }

        $subscription->is_active = false;
        $subscription->unsubscribed_at = now();
        $subscription->save();

        return response()->json([
            'message' => 'Successfully unsubscribed from our newsletter.',
        ]);
    }
}
