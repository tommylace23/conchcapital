<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReferralService;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    public function index(Request $request, ReferralService $referrals)
    {
        $user = $request->user();
        $code = $referrals->ensureCode($user);

        $records = $user->referrals()
            ->with('referee:id,display_name,username,created_at')
            ->latest()
            ->limit(50)
            ->get();

        $base = rtrim(config('app.url'), '/');

        return response()->json([
            'referral_code' => $code,
            'referral_link' => "{$base}/registration.html?ref={$code}",
            'bonus_per_signup' => $referrals->bonusFor($user),
            'total_referrals' => $records->count(),
            'total_bonus_awarded' => (float) $records->sum('bonus_amount'),
            'referrals' => $records->map(fn ($r) => [
                'name' => $r->referee?->display_name ?? $r->referee?->username,
                'bonus_amount' => (float) $r->bonus_amount,
                'status' => $r->status,
                'joined_at' => $r->created_at?->toIso8601String(),
            ]),
        ]);
    }
}
