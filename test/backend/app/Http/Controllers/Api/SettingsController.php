<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Transaction;
use App\Models\ActivityLog;
use App\Models\ProfitGrowth;
use App\Models\SimulatedTransfer;
use App\Models\Simulation;
use App\Models\ProfileViewRequest;
use App\Models\AdminAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\Rule;
use OTPHP\TOTP;

class SettingsController extends Controller
{
    /**
     * Get user settings
     */
    public function getSettings(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'settings' => [
                'language' => $user->language ?? 'English (US)',
                'timezone' => $user->timezone ?? 'UTC-5 (Eastern Time)',
                'email_notifications_enabled' => $user->email_notifications_enabled ?? true,
                'push_notifications_enabled' => $user->push_notifications_enabled ?? true,
                'marketing_emails_enabled' => $user->marketing_emails_enabled ?? false,
                'two_factor_enabled' => $user->two_factor_enabled ?? false,
            ],
            'user' => [
                'email' => $user->email,
            ],
        ]);
    }

    /**
     * Update account settings (language, timezone)
     */
    public function updateAccountSettings(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'language' => 'nullable|string|max:50',
            'timezone' => 'nullable|string|max:50',
        ]);

        $user->fill($validated);
        $user->save();

        return response()->json([
            'message' => 'Account settings updated successfully.',
            'settings' => [
                'language' => $user->language,
                'timezone' => $user->timezone,
            ],
        ]);
    }

    /**
     * Update notification preferences
     */
    public function updateNotificationPreferences(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'email_notifications_enabled' => 'nullable|boolean',
            'push_notifications_enabled' => 'nullable|boolean',
            'marketing_emails_enabled' => 'nullable|boolean',
        ]);

        $user->fill($validated);
        $user->save();

        return response()->json([
            'message' => 'Notification preferences updated successfully.',
            'preferences' => [
                'email_notifications_enabled' => $user->email_notifications_enabled,
                'push_notifications_enabled' => $user->push_notifications_enabled,
                'marketing_emails_enabled' => $user->marketing_emails_enabled,
            ],
        ]);
    }

    /**
     * Change user password
     */
    public function changePassword(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        // Verify current password
        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect.',
            ], 422);
        }

        // Update password
        $user->password = Hash::make($validated['password']);
        $user->save();

        // Create activity log
        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'password_changed',
            'fund_type' => 'real',
            'description' => 'User changed their password',
        ]);

        return response()->json([
            'message' => 'Password changed successfully.',
        ]);
    }

    /**
     * Change user email
     */
    public function changeEmail(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password' => 'required|string', // Require password confirmation
        ]);

        // Verify password
        if (!Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Password is incorrect.',
            ], 422);
        }

        $oldEmail = $user->email;
        $user->email = $validated['email'];
        $user->email_verified_at = null; // Require email verification for new email
        $user->save();

        // Send email verification notification
        $user->sendEmailVerificationNotification();

        // Create activity log
        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'email_changed',
            'fund_type' => 'real',
            'description' => "Email changed from {$oldEmail} to {$validated['email']}",
        ]);

        return response()->json([
            'message' => 'Email changed successfully. Please verify your new email address.',
            'user' => $user->fresh(),
        ]);
    }

    /**
     * Get active sessions for the user
     */
    public function getActiveSessions(Request $request)
    {
        $user = $request->user();
        
        // Get sessions from Laravel's session table
        $sessions = DB::table('sessions')
            ->where('user_id', $user->id)
            ->orderBy('last_activity', 'desc')
            ->get()
            ->map(function ($session) {
                return [
                    'id' => $session->id,
                    'ip_address' => $session->ip_address,
                    'user_agent' => $session->user_agent,
                    'last_activity' => date('Y-m-d H:i:s', $session->last_activity),
                    'is_current' => $session->id === session()->getId(),
                ];
            });

        return response()->json([
            'sessions' => $sessions,
            'count' => $sessions->count(),
        ]);
    }

    /**
     * Revoke a session (logout from specific device)
     */
    public function revokeSession(Request $request, $sessionId)
    {
        $user = $request->user();
        $currentSessionId = session()->getId();

        // Prevent revoking current session
        if ($sessionId === $currentSessionId) {
            return response()->json([
                'message' => 'Cannot revoke your current session. Please logout instead.',
            ], 422);
        }

        // Delete the session
        DB::table('sessions')
            ->where('id', $sessionId)
            ->where('user_id', $user->id)
            ->delete();

        return response()->json([
            'message' => 'Session revoked successfully.',
        ]);
    }

    /**
     * Revoke all other sessions (logout from all other devices)
     */
    public function revokeAllOtherSessions(Request $request)
    {
        $user = $request->user();
        $currentSessionId = session()->getId();

        // Delete all sessions except current
        DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentSessionId)
            ->delete();

        return response()->json([
            'message' => 'All other sessions revoked successfully.',
        ]);
    }

    /**
     * Initialize 2FA setup (generate secret)
     */
    public function initialize2FA(Request $request)
    {
        $user = $request->user();

        if ($user->two_factor_enabled) {
            return response()->json([
                'message' => 'Two-factor authentication is already enabled.',
            ], 422);
        }

        // Generate a proper TOTP secret
        // 20 random bytes → 32-char Base32 secret (160-bit)
        $totp = TOTP::create(null, 30, 'sha1', 6, TOTP::DEFAULT_EPOCH, null, 20);
        $totp->setLabel($user->email);
        $totp->setIssuer('ConchCapital');
        
        $secret = $totp->getSecret();
        $provisioningUri = $totp->getProvisioningUri();
        
        // Generate QR code URL using the provisioning URI
        $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($provisioningUri);
        
        return response()->json([
            'message' => '2FA setup initialized. Scan the QR code with your authenticator app.',
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
        ]);
    }

    /**
     * Enable 2FA (after verification)
     */
    public function enable2FA(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'secret' => 'required|string',
            'verification_code' => 'required|string|size:6',
        ]);

        // Verify the code using TOTP before enabling
        try {
            $totp = TOTP::create($validated['secret']);
            if (!$totp->verify($validated['verification_code'], null, 1)) {
                return response()->json([
                    'message' => 'Invalid verification code. Please try again.',
                ], 422);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to verify code. Please try again.',
            ], 422);
        }
        
        // Code is valid, enable 2FA
        $user->two_factor_enabled = true;
        $user->two_factor_secret = encrypt($validated['secret']);
        $user->two_factor_recovery_codes = encrypt(json_encode($this->generateRecoveryCodes()));
        $user->save();

        // Create activity log
        ActivityLog::create([
            'user_id' => $user->id,
            'action' => '2fa_enabled',
            'fund_type' => 'real',
            'description' => 'Two-factor authentication enabled',
        ]);

        return response()->json([
            'message' => 'Two-factor authentication enabled successfully.',
            'recovery_codes' => json_decode(decrypt($user->two_factor_recovery_codes)),
        ]);
    }

    /**
     * Disable 2FA
     */
    public function disable2FA(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'password' => 'required|string',
        ]);

        // Verify password
        if (!Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Password is incorrect.',
            ], 422);
        }

        $user->two_factor_enabled = false;
        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->save();

        // Create activity log
        ActivityLog::create([
            'user_id' => $user->id,
            'action' => '2fa_disabled',
            'fund_type' => 'real',
            'description' => 'Two-factor authentication disabled',
        ]);

        return response()->json([
            'message' => 'Two-factor authentication disabled successfully.',
        ]);
    }

    /**
     * Delete user account
     */
    public function deleteAccount(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'password' => 'required|string',
            'confirmation' => 'required|string|in:DELETE',
        ]);

        // Check if account is frozen
        if ($user->is_frozen) {
            return response()->json([
                'message' => 'Account is frozen. Cannot delete account.',
            ], 403);
        }

        // Verify password
        if (!Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Password is incorrect.',
            ], 422);
        }

        return DB::transaction(function () use ($user) {
            $userId = $user->id;
            $userEmail = $user->email;
            $userName = $user->name;

            // Delete related data (cascade delete)
            ActivityLog::where('user_id', $userId)->delete();
            SimulatedTransfer::where('from_user_id', $userId)->orWhere('to_user_id', $userId)->delete();
            ProfitGrowth::where('user_id', $userId)->update(['status' => 'cancelled', 'completed_at' => now()]);
            Simulation::where('user_id', $userId)->delete();
            Transaction::where('user_id', $userId)->delete();
            ProfileViewRequest::where('requester_id', $userId)
                ->orWhere('profile_owner_id', $userId)
                ->delete();
            AdminAction::where('target_user_id', $userId)->delete();
            
            // Delete sessions
            DB::table('sessions')->where('user_id', $userId)->delete();

            // Finally, delete the user
            $user->delete();

            // Logout the user
            auth()->logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return response()->json([
                'message' => 'Account deleted successfully.',
            ]);
        });
    }

    /**
     * Generate recovery codes for 2FA
     */
    private function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4)));
        }
        return $codes;
    }
}
