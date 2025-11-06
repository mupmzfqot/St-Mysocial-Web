<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\UserBlock;
use App\Services\BlockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BlockController extends Controller
{
    protected $blockService;

    public function __construct(BlockService $blockService)
    {
        $this->blockService = $blockService;
    }

    /**
     * Block a user
     */
    public function blockUser(Request $request, $userId)
    {
        try {
            $blocker = $request->user();
            $blocked = User::findOrFail($userId);

            // Prevent self-blocking
            if ($blocker->id === $blocked->id) {
                return response()->json([
                    'error' => 1,
                    'message' => 'You cannot block yourself'
                ], 400);
            }

            // Check if already blocked
            if ($blocker->hasBlocked($blocked->id)) {
                return response()->json([
                    'error' => 0,
                    'message' => 'User already blocked'
                ]);
            }

            $success = $this->blockService->blockUser($blocker, $blocked);

            if ($success) {
                // Clear cache
                Cache::forget("blocked_users_{$blocker->id}");

                return response()->json([
                    'error' => 0,
                    'message' => 'User blocked successfully'
                ]);
            }

            return response()->json([
                'error' => 1,
                'message' => 'Failed to block user'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 1,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Unblock a user
     */
    public function unblockUser(Request $request, $userId)
    {
        try {
            $blocker = $request->user();
            $blocked = User::findOrFail($userId);

            if (!$blocker->hasBlocked($blocked->id)) {
                return response()->json([
                    'error' => 1,
                    'message' => 'User is not blocked'
                ], 400);
            }

            $success = $this->blockService->unblockUser($blocker, $blocked);

            if ($success) {
                // Clear cache
                Cache::forget("blocked_users_{$blocker->id}");

                return response()->json([
                    'error' => 0,
                    'message' => 'User unblocked successfully'
                ]);
            }

            return response()->json([
                'error' => 1,
                'message' => 'Failed to unblock user'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 1,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get list of blocked users
     */
    public function getBlockedUsers(Request $request)
    {
        try {
            $user = $request->user();
            
            $blockedUsers = UserBlock::where('blocker_id', $user->id)
                ->with('blocked')
                ->get()
                ->pluck('blocked');

            return response()->json([
                'error' => 0,
                'data' => UserResource::collection($blockedUsers)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 1,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
