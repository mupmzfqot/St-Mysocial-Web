<?php

namespace App\Traits;

use App\Models\UserBlock;

trait HasBlockedUsers
{
    /**
     * Get blocked user IDs for current user (bidirectional)
     * This method can be used in any controller that needs to filter blocked users
     */
    protected function getBlockedUserIds($userId): array
    {
        $blockedIds = UserBlock::where('blocker_id', $userId)->pluck('blocked_id');
        $blockedByIds = UserBlock::where('blocked_id', $userId)->pluck('blocker_id');
        
        return $blockedIds->merge($blockedByIds)
            ->unique()
            ->reject(fn($id) => $id == $userId)
            ->toArray();
    }
}

