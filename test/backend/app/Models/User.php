<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'username',
        'account_number',
        'account_iban',
        'account_issued_at',
        'referral_code',
        'referred_by',
        'referral_bonus_amount',
        'display_name',
        'bio',
        'avatar_url',
        'password',
        'real_balance',
        'simulated_balance',
        'balance_currency',
        'is_admin',
        'is_frozen',
        'frozen_at',
        'freeze_reason',
        'last_active_at',
        'language',
        'timezone',
        'email_notifications_enabled',
        'push_notifications_enabled',
        'marketing_emails_enabled',
        'two_factor_enabled',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'real_balance' => 'decimal:2',
            'simulated_balance' => 'decimal:2',
            'referral_bonus_amount' => 'decimal:2',
            'account_issued_at' => 'datetime',
            'is_admin' => 'boolean',
            'is_frozen' => 'boolean',
            'frozen_at' => 'datetime',
            'last_active_at' => 'datetime',
            'last_login_at' => 'datetime',
            'email_notifications_enabled' => 'boolean',
            'push_notifications_enabled' => 'boolean',
            'marketing_emails_enabled' => 'boolean',
            'two_factor_enabled' => 'boolean',
            'two_factor_recovery_codes' => 'array',
        ];
    }

    /**
     * Auto-generate a unique referral code when a user is created.
     */
    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (empty($user->referral_code)) {
                $length = (int) config('referral.code_length', 8);
                do {
                    $code = strtoupper(\Illuminate\Support\Str::random($length));
                } while (static::where('referral_code', $code)->exists());

                $user->referral_code = $code;
            }

            if (empty($user->account_number)) {
                do {
                    $number = \App\Support\BankAccount::generateNumber();
                } while (static::where('account_number', $number)->exists());

                $user->account_number = $number;
                $user->account_iban = \App\Support\BankAccount::toIban($number);
                $user->account_issued_at = now();
            }
        });
    }

    /**
     * Get the transactions for the user.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * The user who referred this user.
     */
    public function referredBy()
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    /**
     * Users this user has referred.
     */
    public function referrals()
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    /**
     * The referral record where this user was the referee.
     */
    public function referralRecord()
    {
        return $this->hasOne(Referral::class, 'referee_id');
    }

    /**
     * Get the simulations for the user (legacy).
     */
    public function simulations()
    {
        return $this->hasMany(Simulation::class);
    }

    /**
     * Get the profit growths for the user.
     */
    public function profitGrowths()
    {
        return $this->hasMany(ProfitGrowth::class);
    }

    /**
     * Get the simulated transfers sent by the user.
     */
    public function sentSimulatedTransfers()
    {
        return $this->hasMany(SimulatedTransfer::class, 'from_user_id');
    }

    /**
     * Get the simulated transfers received by the user.
     */
    public function receivedSimulatedTransfers()
    {
        return $this->hasMany(SimulatedTransfer::class, 'to_user_id');
    }

    /**
     * Get the activity logs for the user.
     */
    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Get the admin actions performed by this user (if admin).
     */
    public function adminActions()
    {
        return $this->hasMany(AdminAction::class, 'admin_id');
    }

    /**
     * Get profile view requests where this user is the requester.
     */
    public function profileViewRequestsSent()
    {
        return $this->hasMany(ProfileViewRequest::class, 'requester_id');
    }

    /**
     * Get profile view requests where this user is the profile owner.
     */
    public function profileViewRequestsReceived()
    {
        return $this->hasMany(ProfileViewRequest::class, 'profile_owner_id');
    }
}
