<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBlock extends Model
{
    protected $guarded = [];

    public function blocker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocker_id');
    }

    public function blocked(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_id');
    }

    /**
     * Scope untuk mendapatkan user yang di-block oleh user tertentu
     */
    public function scopeBlockedBy($query, $userId)
    {
        return $query->where('blocker_id', $userId);
    }

    /**
     * Scope untuk mendapatkan user yang mem-block user tertentu
     */
    public function scopeBlocking($query, $userId)
    {
        return $query->where('blocked_id', $userId);
    }
}
