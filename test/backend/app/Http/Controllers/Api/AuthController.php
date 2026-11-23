<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Support\Str;
use OTPHP\TOTP;

class AuthController extends Controller
{
    public function register(Request $request, \App\Services\ReferralService $referrals)
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
            'username' => 'nullable|alpha_dash|min:3|max:20|unique:users,username',
            'display_name' => 'nullable|string|max:50',
            'referral_code' => 'nullable|string|max:16',
        ]);

        $username = $validated['username'] ?? $this->generateUsername($validated['email']);
        $displayName = $validated['display_name'] ?? $username;

        $referrer = $referrals->resolveReferrer($validated['referral_code'] ?? null);

        $user = User::create([
            'name' => $displayName,
            'email' => $validated['email'],
            'username' => $username,
            'display_name' => $displayName,
            'password' => $validated['password'],
        ]);

        $referral = null;
        if ($referrer && $referrer->id !== $user->id) {
            $referral = $referrals->register($user, $referrer);
            $user->refresh();
        }

        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        return response()->json([
            'user' => $user,
            'referral' => $referral ? [
                'applied' => true,
                'code' => $referral->code,
                'bonus_amount' => (float) $referral->bonus_amount,
                'status' => $referral->status,
            ] : null,
        ]);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'identifier' => 'nullable|string',
            'email' => 'nullable|email',
            'password' => ['required'],
            'remember' => 'nullable|boolean',
            'two_factor_code' => 'nullable|string|size:6',
        ]);

        $identifier = $validated['identifier'] ?? $validated['email'] ?? null;
        if (!$identifier) {
            return response()->json([
                'message' => 'Email or username is required.',
            ], 422);
        }

        $credentials = ['password' => $validated['password']];
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $credentials['email'] = $identifier;
        } else {
            $credentials['username'] = $identifier;
        }

        // If 2FA code is provided, verify it
        if (isset($validated['two_factor_code'])) {
            $userId = $request->session()->get('login_user_id');
            if (!$userId) {
                return response()->json([
                    'message' => 'Please complete the initial login first.',
                ], 422);
            }

            $user = User::find($userId);
            if (!$user) {
                return response()->json([
                    'message' => 'Session expired. Please login again.',
                ], 422);
            }

            // Verify 2FA code
            if (!$this->verify2FACode($user, $validated['two_factor_code'])) {
                return response()->json([
                    'message' => 'Invalid 2FA code. Please try again.',
                ], 422);
            }

            // Complete login
            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();
            $request->session()->forget('login_user_id');

            $user->forceFill([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ])->save();

            return response()->json([
                'user' => $user,
            ]);
        }

        // Initial login attempt
        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 422);
        }

        $user = $request->user() ?? Auth::user();

        // Check if 2FA is enabled
        if ($user->two_factor_enabled) {
            // Store user ID in session for 2FA verification
            $request->session()->put('login_user_id', $user->id);
            Auth::logout(); // Don't fully authenticate yet

            return response()->json([
                'requires_2fa' => true,
                'message' => 'Two-factor authentication required.',
            ], 200);
        }

        // No 2FA, proceed with normal login
        $request->session()->regenerate();

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        return response()->json([
            'user' => $user,
        ]);
    }

    /**
     * Verify 2FA code during login
     */
    public function verify2FALogin(Request $request)
    {
        $validated = $request->validate([
            'two_factor_code' => 'required|string|size:6',
        ]);

        $userId = $request->session()->get('login_user_id');
        if (!$userId) {
            return response()->json([
                'message' => 'Session expired. Please login again.',
            ], 422);
        }

        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'message' => 'User not found.',
            ], 422);
        }

        // Verify 2FA code
        if (!$this->verify2FACode($user, $validated['two_factor_code'])) {
            return response()->json([
                'message' => 'Invalid 2FA code. Please try again.',
            ], 422);
        }

        // Complete login
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $request->session()->forget('login_user_id');

        return response()->json([
            'user' => $user,
        ]);
    }

    /**
     * Verify 2FA code using TOTP
     */
    private function verify2FACode(User $user, string $code): bool
    {
        if (!$user->two_factor_enabled || !$user->two_factor_secret) {
            return false;
        }

        // Decrypt the secret
        try {
            $secret = decrypt($user->two_factor_secret);
        } catch (\Exception $e) {
            return false;
        }

        // Verify using TOTP library
        try {
            $totp = TOTP::create($secret);
            // Verify the code (allows for clock skew - checks current, previous, and next time windows)
            return $totp->verify($code, null, 1); // 1 window tolerance (30 seconds)
        } catch (\Exception $e) {
            return false;
        }
    }

    public function logout(Request $request)
    {
        // Session-based Sanctum auth: log the user out of the web guard
        // (the API request guard has no logout()).
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logged out.',
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $status = Password::sendResetLink(['email' => $validated['email']]);

        if ($status !== Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => __($status),
            ], 422);
        }

        return response()->json([
            'message' => __($status),
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            $validated,
            function (User $user) use ($validated) {
                $user->forceFill([
                    'password' => Hash::make($validated['password']),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'message' => __($status),
            ], 422);
        }

        return response()->json([
            'message' => __($status),
        ]);
    }

    public function sendEmailVerification(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified.',
            ]);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Verification email sent.',
        ]);
    }

    public function verifyEmail(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified.',
            ]);
        }

        $user->markEmailAsVerified();

        return response()->json([
            'message' => 'Email verified.',
        ]);
    }

    private function generateUsername(string $email): string
    {
        $base = Str::slug(Str::before($email, '@'), '_');
        $base = $base ?: 'user';

        $username = $base;
        $suffix = 1;

        while (User::where('username', $username)->exists()) {
            $username = $base.$suffix;
            $suffix++;
        }

        return $username;
    }
}
