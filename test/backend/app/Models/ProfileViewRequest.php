<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfileViewRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'requester_id',
        'profile_owner_id',
        'status',
        'viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    /**
     * Get the user who requested to view the profile
     */
    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /**
     * Get the user whose profile is being requested
     */
    public function profileOwner()
    {
        return $this->belongsTo(User::class, 'profile_owner_id');
    }
}
