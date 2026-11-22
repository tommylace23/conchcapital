<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimulatedTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_user_id',
        'to_user_id',
        'simulation_id',
        'amount',
        'currency',
        'status',
        'message',
        'reference',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'processed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the user that sent the transfer.
     */
    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    /**
     * Get the user that received the transfer.
     */
    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    /**
     * Get the simulation this transfer belongs to (if any) - legacy.
     */
    public function simulation(): BelongsTo
    {
        return $this->belongsTo(Simulation::class);
    }

    /**
     * Get the profit growth this transfer belongs to (if any).
     * Note: Uses the same simulation_id column but references profit_growths table.
     * This will work once we migrate the foreign key constraint.
     */
    public function profitGrowth(): BelongsTo
    {
        return $this->belongsTo(ProfitGrowth::class, 'simulation_id');
    }
}
