<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Referral defaults
    |--------------------------------------------------------------------------
    |
    | The bonus credited to a NEW user who joins with a referral code when the
    | referring user has no per-user override set (users.referral_bonus_amount).
    |
    */

    'default_bonus' => (float) env('REFERRAL_DEFAULT_BONUS', 0.00),

    'code_length' => 8,
];
