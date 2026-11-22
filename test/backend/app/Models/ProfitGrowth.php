<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProfitGrowth extends Model
{
    use HasFactory;

    protected $table = 'profit_growths';

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'initial_balance',
        'current_balance',
        'currency',
        'status',
        'strategy_data',
        'growth_percentage',
        'growth_frequency',
        'last_growth_calculated_at',
        'next_growth_calculation_at',
        'auto_calculate',
        'lock_until_date',
        'last_transfer_date',
        'locked_amount',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'initial_balance' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'growth_percentage' => 'decimal:2',
            'locked_amount' => 'decimal:2',
            'strategy_data' => 'array',
            'auto_calculate' => 'boolean',
            'last_growth_calculated_at' => 'datetime',
            'next_growth_calculation_at' => 'datetime',
            'lock_until_date' => 'datetime',
            'last_transfer_date' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the profit growth.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the simulated transfers for this profit growth.
     */
    public function simulatedTransfers(): HasMany
    {
        return $this->hasMany(SimulatedTransfer::class, 'simulation_id');
    }
}
