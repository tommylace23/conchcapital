<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Referral;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReferralService
{
    /**
     * Find the referring user for a given referral code.
     */
    public function resolveReferrer(?string $code): ?User
    {
        $code = trim((string) $code);
        if ($code === '') {
            return null;
        }

        return User::where('referral_code', strtoupper($code))->first();
    }

    /**
     * The bonus a new joiner receives when using the given referrer's code.
     */
    public function bonusFor(User $referrer): float
    {
        if ($referrer->referral_bonus_amount !== null) {
            return (float) $referrer->referral_bonus_amount;
        }

        return (float) config('referral.default_bonus', 0);
    }

    /**
     * Record a referral and credit the new user's real balance.
     */
    public function register(User $referee, User $referrer): Referral
    {
        $bonus = max(0, $this->bonusFor($referrer));

        return DB::transaction(function () use ($referee, $referrer, $bonus) {
            $referee->forceFill([
                'referred_by' => $referrer->id,
            ])->save();

            $referral = Referral::create([
                'referrer_id' => $referrer->id,
                'referee_id' => $referee->id,
                'code' => $referrer->referral_code,
                'bonus_amount' => $bonus,
                'status' => $bonus > 0 ? 'credited' : 'pending',
                'credited_at' => $bonus > 0 ? now() : null,
            ]);

            if ($bonus > 0) {
                $reference = 'REF-' . strtoupper(Str::random(10));

                $referee->increment('real_balance', $bonus);

                Transaction::create([
                    'user_id' => $referee->id,
                    'type' => 'credit',
                    'fund_type' => 'real',
                    'amount' => $bonus,
                    'currency' => $referee->balance_currency ?? 'USD',
                    'status' => 'completed',
                    'description' => "Referral bonus (code: {$referrer->referral_code})",
                    'reference' => $reference,
                    'metadata' => [
                        'referral_id' => $referral->id,
                        'referrer_id' => $referrer->id,
                        'referral_code' => $referrer->referral_code,
                    ],
                    'processed_at' => now(),
                ]);

                try {
                    \Illuminate\Support\Facades\Mail::to($referee->email)
                        ->send(new \App\Mail\ReferralBonusEmail(
                            $referee,
                            (float) $bonus,
                            $referrer->display_name ?: $referrer->username,
                            $reference
                        ));
                } catch (\Throwable $e) {
                    \Log::warning('Referral bonus email failed: ' . $e->getMessage());
                }

                ActivityLog::create([
                    'user_id' => $referee->id,
                    'action' => 'referral_bonus',
                    'fund_type' => 'real',
                    'entity_type' => Referral::class,
                    'entity_id' => $referral->id,
                    'amount' => $bonus,
                    'currency' => $referee->balance_currency ?? 'USD',
                    'description' => "Referral bonus credited ({$referrer->referral_code})",
                    'metadata' => ['referrer_id' => $referrer->id],
                ]);
            }

            return $referral;
        });
    }

    /**
     * Ensure the user has a referral code (backfill for pre-existing accounts).
     */
    public function ensureCode(User $user): string
    {
        if (! empty($user->referral_code)) {
            return $user->referral_code;
        }

        do {
            $code = strtoupper(Str::random((int) config('referral.code_length', 8)));
        } while (User::where('referral_code', $code)->exists());

        $user->forceFill(['referral_code' => $code])->save();

        return $code;
    }
}
